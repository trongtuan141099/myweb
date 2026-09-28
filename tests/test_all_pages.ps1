# PowerShell test runner for warehouse pages
$edgePath = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
$phpPath = "d:\myweb\php\php.exe"

$pages = @("approval.php", "dashboard.php", "issue_request.php", "materials.php", "reorder_tracking.php", "settings.php")

foreach ($page in $pages) {
    Write-Host "=== TESTING $page ===" -ForegroundColor Cyan
    
    # 1. PHP Lint
    $lint = & $phpPath -l "modules/warehouse/$page"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "PHP LINT ERROR: $lint" -ForegroundColor Red
        continue
    } else {
        Write-Host "PHP Lint: OK" -ForegroundColor Green
    }
    
    # 2. Render Page to temp HTML with test hooks
    $renderScript = @"
session_start();
`$_SESSION['user_id'] = 1;
`$_SESSION['username'] = 'admin';
`$_SESSION['role'] = 'admin';
`$_SESSION['full_name'] = 'Administrator';
require_once 'config/db.php';
require_once 'core/check_permission.php';

ob_start();
require 'modules/warehouse/$page';
`$html = ob_get_clean();

preg_match_all('/onclick=["\']([a-zA-Z0-9_]+)\(/i', `$html, `$clicks);
preg_match_all('/onsubmit=["\'](?:return\s+)?([a-zA-Z0-9_]+)\(/i', `$html, `$submits);
`$allFns = array_values(array_unique(array_merge(`$clicks[1], `$submits[1])));

`$head = '<script>
window.alert = function(){};
window.confirm = function(){ return true; };
window.prompt = function(){ return null; };
window.__CAUGHT_ERRORS = [];
window.onerror = function(m, u, l, c) { window.__CAUGHT_ERRORS.push({msg: m, line: l, col: c}); };
</script>';

`$tail = '<script>
var targetFns = ' . json_encode(`$allFns) . ';
var missing = [];
targetFns.forEach(function(f) {
  if (typeof window[f] !== "function") missing.push(f);
});
var el = document.createElement("div");
el.id = "ps_report";
el.innerText = "REPORT:" + JSON.stringify({errors: window.__CAUGHT_ERRORS, missing: missing, count: targetFns.length});
document.body.appendChild(el);
</script>';

file_put_contents('tests/temp_test_$page.html', `$head . `$html . `$tail);
"@
    
    $tempPhp = "tests/temp_render.php"
    Set-Content -Path $tempPhp -Value "<?php`n$renderScript" -Encoding UTF8
    & $phpPath $tempPhp
    Remove-Item -Path $tempPhp -Force -ErrorAction SilentlyContinue

    $tempHtml = "d:/myweb/htdocs/myweb/tests/temp_test_$page.html"
    $outTxt = "d:\myweb\htdocs\myweb\tests\temp_edge_out.txt"
    $errTxt = "d:\myweb\htdocs\myweb\tests\temp_edge_err.txt"
    
    if (Test-Path $tempHtml) {
        $p = Start-Process -FilePath $edgePath -ArgumentList "--headless --disable-gpu --dump-dom file:///$tempHtml" -NoNewWindow -PassThru -RedirectStandardOutput $outTxt -RedirectStandardError $errTxt
        $p.WaitForExit(6000)
        
        $content = Get-Content $outTxt -Raw -ErrorAction SilentlyContinue
        if ($content -match 'REPORT:({.*?})') {
            $report = $matches[1] | ConvertFrom-Json
            Write-Host "Total Functions Checked: $($report.count)" -ForegroundColor White
            if ($report.missing.Count -eq 0 -and $report.errors.Count -eq 0) {
                Write-Host "Result: PASS (0 errors, 0 missing functions)" -ForegroundColor Green
            } else {
                if ($report.errors.Count -gt 0) {
                    Write-Host "JS Errors: $($report.errors | ConvertTo-Json -Compress)" -ForegroundColor Red
                }
                if ($report.missing.Count -gt 0) {
                    Write-Host "Missing Functions: $($report.missing -join ', ')" -ForegroundColor Red
                }
            }
        } else {
            Write-Host "Could not parse Edge report" -ForegroundColor Yellow
        }
        
        Remove-Item -Path $tempHtml -Force -ErrorAction SilentlyContinue
        Remove-Item -Path $outTxt -Force -ErrorAction SilentlyContinue
        Remove-Item -Path $errTxt -Force -ErrorAction SilentlyContinue
    }
}
