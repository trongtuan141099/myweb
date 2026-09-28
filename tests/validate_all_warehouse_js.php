<?php
$files = [
    'modules/warehouse/approval.php',
    'modules/warehouse/dashboard.php',
    'modules/warehouse/issue_request.php',
    'modules/warehouse/materials.php',
    'modules/warehouse/reorder_tracking.php',
    'modules/warehouse/settings.php'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    $content = file_get_contents($fullPath);
    preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $content, $matches);
    $allJs = implode("\n;\n", $matches[1]);
    
    // Replace PHP inline echoes
    $cleanJs = preg_replace('/<\?=\s*json_encode\(.*?\)\s*\?>/', 'null', $allJs);
    $cleanJs = preg_replace('/<\?.*?\?>/s', '""', $cleanJs);
    
    $testHtml = "<!DOCTYPE html><html><head><meta charset='utf-8'></head><body>
    <script>
      window.errors = [];
      window.onerror = function(msg, url, line, col, err) {
        window.errors.push(msg + ' at line ' + line + ':' + col);
      };
    </script>
    <script>
    try {
      // Stub common DOM & Globals
      var bootstrap = { Modal: { getInstance: function(){return {hide:function(){},show:function(){}}}, getOrCreateInstance: function(){return {hide:function(){},show:function(){}}} } };
      {$cleanJs}
    } catch(e) {
      window.errors.push('PARSE_EXEC_ERROR: ' + e.message + ' at line ' + (e.lineNumber || ''));
    }
    </script>
    <div id='res'></div>
    <script>
      document.getElementById('res').textContent = JSON.stringify(window.errors);
    </script>
    </body></html>";
    
    $tmpName = __DIR__ . '/tmp_test_' . basename($file, '.php') . '.html';
    file_put_contents($tmpName, $testHtml);
    
    $chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
    $cmd = "\"{$chromePath}\" --headless --disable-gpu --dump-dom \"file:///" . str_replace('\\', '/', $tmpName) . "\"";
    $output = shell_exec($cmd);
    
    preg_match('/<div id="res">(.*?)<\/div>/s', $output, $m);
    $errs = json_decode($m[1] ?? '[]', true);
    
    echo "FILE: {$file}\n";
    if (empty($errs)) {
        echo "  [PASS] No JS syntax/parse errors detected!\n";
    } else {
        echo "  [FAIL] Errors found:\n";
        foreach ($errs as $err) {
            echo "    - {$err}\n";
        }
    }
    @unlink($tmpName);
}
