<?php
/**
 * File: admin/backup.php
 * Purpose: Admin-only database backup page - create, download, and delete MySQL backups.
 * Dependencies: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php, config/database.php, config/logger.php
 * Used By: Admin users navigating via admin sidebar
 * Parent Files: None (entry point for backup management)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Database Backup';
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../config/logger.php';

// === ADMIN-ONLY ACCESS CONTROL ===
// Verify user has admin role; redirect non-admins immediately
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// === BACKUP DIRECTORY SETUP ===
// Create backups directory if it doesn't exist
$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    // 0755 = owner rwx, group rx, others rx (secure default)
    mkdir($backupDir, 0755, true);
}

// === STATUS MESSAGES ===
$message = '';
$success = false;

// === CREATE BACKUP ===
// Handle POST request for creating a new database backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_backup') {
    // CSRF token validation before processing
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid security token.';
    } else {
        // Generate timestamped filename for uniqueness
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "globetrek_backup_{$timestamp}.sql";
        $filepath = $backupDir . '/' . $filename;

        // === MYSQLDUMP PATH DETECTION ===
        // Check multiple common locations for mysqldump binary
        $mysqldumpPaths = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',  // Windows XAMPP (primary dev environment)
            '/usr/bin/mysqldump',                     // Linux standard
            '/usr/local/bin/mysqldump',               // macOS Homebrew
            'mysqldump',                              // Fallback: assume in PATH
        ];

        // Select the first existing mysqldump path
        $mysqldump = 'mysqldump';
        foreach ($mysqldumpPaths as $path) {
            if (file_exists($path)) {
                $mysqldump = $path;
                break;
            }
        }

        // === EXECUTE MYSQLDUMP ===
        // Build command with database credentials from config
        // Password is quoted to handle special characters
        $cmd = "\"{$mysqldump}\" -u " . DB_USER . " " . (DB_PASS !== '' ? '-p"' . DB_PASS . '" ' : '') . DB_NAME . " > \"{$filepath}\" 2>&1";
        exec($cmd, $output, $returnCode);

        // === COMPRESS BACKUP ===
        // Only compress if dump was successful and file is non-empty
        if ($returnCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
            // Create gzipped version of the SQL file
            $gzFile = $filepath . '.gz';
            $fp = fopen($filepath, 'rb');
            // wb9 = write binary, compression level 9 (maximum)
            $gz = gzopen($gzFile, 'wb9');
            if ($fp && $gz) {
                // Stream read/write in 8KB chunks to handle large files efficiently
                while (!feof($fp)) {
                    gzwrite($gz, fread($fp, 8192));
                }
                gzclose($gz);
                fclose($fp);
            }

            $success = true;
            $sizeKB = round(filesize($gzFile) / 1024, 1);
            $message = "Backup created successfully! File: {$filename}.gz ({$sizeKB} KB)";
            // Log the backup creation for audit trail
            logActivity('database_backup_created', 'system', null, "Backup: {$filename}.gz");
        } else {
            // Include mysqldump output in error message for debugging
            $message = 'Backup failed. Output: ' . implode("\n", $output);
        }
    }
}

// === DOWNLOAD BACKUP ===
// Handle GET request for downloading a backup file
if (isset($_GET['download'])) {
    // basename() prevents directory traversal attacks
    $file = basename($_GET['download']);
    $filepath = $backupDir . '/' . $file;
    // Verify file exists and matches expected naming pattern (prefix check)
    if (file_exists($filepath) && strpos($file, 'globetrek_backup_') === 0) {
        // Set appropriate MIME type based on file extension
        $mime = 'application/octet-stream';
        if (substr($file, -3) === '.gz') {
            $mime = 'application/gzip';
        } elseif (substr($file, -4) === '.sql') {
            $mime = 'text/plain';
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}

// === DELETE BACKUP ===
// Handle POST request for deleting a backup file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_backup') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid security token.';
    } else {
        // basename() prevents directory traversal; prefix check ensures only backup files
        $file = basename($_POST['file'] ?? '');
        $filepath = $backupDir . '/' . $file;
        if (file_exists($filepath) && strpos($file, 'globetrek_backup_') === 0) {
            unlink($filepath);
            $message = "Deleted backup: {$file}";
        }
    }
}

// === LIST EXISTING BACKUPS ===
// Scan backup directory and build sorted list with metadata
$backups = [];
$files = glob($backupDir . '/globetrek_backup_*');
if ($files) {
    // Sort by modification time descending (newest first)
    usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
    foreach ($files as $f) {
        $name = basename($f);
        $backups[] = [
            'name' => $name,
            'size' => round(filesize($f) / 1024, 1), // Size in KB
            'date' => date('Y-m-d H:i:s', filemtime($f)),
        ];
    }
}
?>

<!-- === ADMIN LAYOUT === -->
<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Database Backup</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === FLASH MESSAGE === -->
        <?php if ($message !== ''): ?>
            <div class="adm-alert <?= $success ? 'adm-alert-success' : 'adm-alert-error' ?>">
                <span class="material-symbols-outlined"><?= $success ? 'check_circle' : 'error' ?></span>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- === CREATE BACKUP FORM === -->
        <div class="adm-form-card">
            <h2>Create New Backup</h2>
            <p style="color:#64748b; margin-bottom:1rem;">Generate a full database backup. The backup will be saved as a compressed .sql.gz file.</p>
            <form method="post" style="display:flex; gap:0.5rem; align-items:center;">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="create_backup">
                <button type="submit" class="adm-btn adm-btn-primary">
                    <span class="material-symbols-outlined">backup</span>
                    Generate Backup Now
                </button>
            </form>
        </div>

        <!-- === EXISTING BACKUPS TABLE === -->
        <div class="adm-form-card">
            <h2>Existing Backups (<?= count($backups) ?>)</h2>
            <?php if (empty($backups)): ?>
                <div class="adm-empty">
                    <span class="material-symbols-outlined adm-empty-icon">folder_off</span>
                    <h2>No backups yet</h2>
                    <p>Generate your first backup using the button above.</p>
                </div>
            <?php else: ?>
                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Created</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- === BACKUP ROW LOOP === -->
                            <?php foreach ($backups as $b): ?>
                                <tr>
                                    <td class="cell-main">
                                        <span class="material-symbols-outlined" style="vertical-align:middle; margin-right:4px; color:#264653;">description</span>
                                        <?= htmlspecialchars($b['name']) ?>
                                    </td>
                                    <td class="cell-mono"><?= $b['size'] ?> KB</td>
                                    <td class="cell-muted"><?= $b['date'] ?></td>
                                    <td>
                                        <div class="cell-actions">
                                            <!-- Download link - uses GET parameter for direct file download -->
                                            <a href="?download=<?= urlencode($b['name']) ?>" class="adm-btn-icon" title="Download">
                                                <span class="material-symbols-outlined">download</span>
                                            </a>
                                            <!-- Delete form with JavaScript confirmation -->
                                            <form method="post" style="display:inline;" data-confirm="Delete this backup permanently?">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete_backup">
                                                <input type="hidden" name="file" value="<?= htmlspecialchars($b['name']) ?>">
                                                <button type="submit" class="adm-btn-icon adm-btn-icon-danger" title="Delete">
                                                    <span class="material-symbols-outlined">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
