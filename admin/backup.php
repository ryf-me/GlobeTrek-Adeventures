<?php
/**
 * Database Backup Page (Admin Only)
 *
 * Allows admin to generate manual database backups using mysqldump.
 * Backups are saved as timestamped .sql files with optional .gz compression.
 */

$pageTitle = 'Database Backup';
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$message = '';
$success = false;

// Handle backup generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_backup') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid security token.';
    } else {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "globetrek_backup_{$timestamp}.sql";
        $filepath = $backupDir . '/' . $filename;

        // Detect mysqldump path
        $mysqldumpPaths = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',  // Windows XAMPP
            '/usr/bin/mysqldump',                     // Linux
            '/usr/local/bin/mysqldump',               // macOS Homebrew
            'mysqldump',                              // In PATH
        ];

        $mysqldump = 'mysqldump';
        foreach ($mysqldumpPaths as $path) {
            if (file_exists($path)) {
                $mysqldump = $path;
                break;
            }
        }

        $cmd = "\"{$mysqldump}\" -u " . DB_USER . " " . (DB_PASS !== '' ? '-p"' . DB_PASS . '" ' : '') . DB_NAME . " > \"{$filepath}\" 2>&1";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
            // Compress to .gz
            $gzFile = $filepath . '.gz';
            $fp = fopen($filepath, 'rb');
            $gz = gzopen($gzFile, 'wb9');
            if ($fp && $gz) {
                while (!feof($fp)) {
                    gzwrite($gz, fread($fp, 8192));
                }
                gzclose($gz);
                fclose($fp);
            }

            $success = true;
            $sizeKB = round(filesize($gzFile) / 1024, 1);
            $message = "Backup created successfully! File: {$filename}.gz ({$sizeKB} KB)";
            logActivity('database_backup_created', 'system', null, "Backup: {$filename}.gz");
        } else {
            $message = 'Backup failed. Output: ' . implode("\n", $output);
        }
    }
}

// Handle backup download
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $filepath = $backupDir . '/' . $file;
    if (file_exists($filepath) && strpos($file, 'globetrek_backup_') === 0) {
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

// Handle backup deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_backup') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid security token.';
    } else {
        $file = basename($_POST['file'] ?? '');
        $filepath = $backupDir . '/' . $file;
        if (file_exists($filepath) && strpos($file, 'globetrek_backup_') === 0) {
            unlink($filepath);
            $message = "Deleted backup: {$file}";
        }
    }
}

// List existing backups
$backups = [];
$files = glob($backupDir . '/globetrek_backup_*');
if ($files) {
    usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
    foreach ($files as $f) {
        $name = basename($f);
        $backups[] = [
            'name' => $name,
            'size' => round(filesize($f) / 1024, 1),
            'date' => date('Y-m-d H:i:s', filemtime($f)),
        ];
    }
}
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
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
        <?php if ($message !== ''): ?>
            <div class="adm-alert <?= $success ? 'adm-alert-success' : 'adm-alert-error' ?>">
                <span class="material-symbols-outlined"><?= $success ? 'check_circle' : 'error' ?></span>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Create Backup -->
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

        <!-- Existing Backups -->
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
                                            <a href="?download=<?= urlencode($b['name']) ?>" class="adm-btn-icon" title="Download">
                                                <span class="material-symbols-outlined">download</span>
                                            </a>
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
