    <!-- === ADMIN JS === -->
    <!-- Core admin JavaScript (sidebar toggle, table sorting, confirmation dialogs, etc.) -->
    <script src="../js/admin.js"></script>
    <?php if (!empty($extraScripts)): ?>
        <!-- === PAGE-SPECIFIC SCRIPTS === -->
        <!-- Some admin pages inject additional JS via $extraScripts before including this footer. -->
        <?= $extraScripts ?>
    <?php endif; ?>
</body>
</html>
