  </main> <!-- END CONTENT-AREA -->

  <!-- FOOTER -->
  <footer class="main-footer">
    <span data-i18n="footer.copyright">&copy; <?= date('Y') ?> <?= (function_exists('__') ? __('footer.copyright', 'DX Plastic Group — Factory Management System') : 'DX Plastic Group — Factory Management System') ?></span>
    <span data-i18n="footer.version"><?= (function_exists('__') ? __('footer.version', 'Version 2.0 • Industrial Standard') : 'Version 2.0 • Industrial Standard') ?></span>
  </footer>
</div> <!-- END MAIN-WRAPPER -->
</div> <!-- END APP-CONTAINER -->

<!-- System Scripts -->
<script src="js/main.js?v=<?= filemtime(__DIR__ . '/../js/main.js') ?>"></script>
</body>
</html>