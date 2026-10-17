<?php
require_once __DIR__ . '/auth.php';

// Ensure local backups folder exists
$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    file_put_contents($backupDir . '/.htaccess', "Deny from all\n");
}

// ================= HELPER: GENERATE FULL SQL DUMP =================
function generateDatabaseDump(PDO $pdo,$dbName) {
    $tables = array('admins', 'products', 'sn_formats', 'serial_numbers', 'sales');$sql  = "-- =========================================================\n";
    $sql .= "-- SwitchNex Database Backup\n";
    $sql .= "-- Database: `{$dbName}`\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- =========================================================\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n\n";

    foreach ($tables as$table) {
        $check =$pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn();
        if (!$check) continue;

        $createRow =$pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $sql .= "-- ---------------------------------------------------------\n";
        $sql .= "-- Table structure for `$table`\n";
        $sql .= "-- ---------------------------------------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .=$createRow[1] . ";\n\n";

        $rows =$pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {$sql .= "-- Dumping data for `$table` (" . count($rows) . " rows)\n";
            $cols = array();
            foreach (array_keys($rows[0]) as $c) {$cols[] = "`$c`";
            }
            $colList = implode(', ',$cols);

            foreach (array_chunk($rows, 200) as $chunk) {$valuesBatch = array();
                foreach ($chunk as $row) {$vals = array();
                    foreach ($row as$val) {
                        $vals[] = ($val === null) ? "NULL" : $pdo->quote((string)$val);
                    }
                    $valuesBatch[] = "(" . implode(', ', $vals) . ")";
                }
                $sql .= "INSERT INTO `$table` ($colList) VALUES \n" . implode(",\n", $valuesBatch) . ";\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

// ================= HELPER: RESTORE SQL CONTENT =================
function executeSqlRestore(PDO $pdo, $sqlContent) {$pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
    
    // Strip comment lines safely without using || or str_starts_with
    $lines = explode("\n", str_replace("\r", "", $sqlContent));$cleanLines = array();
    foreach ($lines as$line) {
        $trimmed = trim($line);
        if ($trimmed === '') {
            continue;
        }
        if (strpos($trimmed, '--') === 0) {
            continue;
        }
        if (strpos($trimmed, '#') === 0) {
            continue;
        }
        $cleanLines[] =$line;
    }
    $cleanSql = implode("\n", $cleanLines);

    // Split SQL statements safely outside of single-quoted strings
    $statements = array();
    $current = '';$inString = false;
    $len = strlen($cleanSql);

    for ($i = 0; $i <$len; $i++) {$char = $cleanSql[$i];
        $prev = ($i > 0) ? $cleanSql[$i - 1] : '';

        if ($char === "'" && $prev !== '\\') {
            $inString = !$inString;
        }
        if ($char === ';' && !$inString) {
            if (trim($current) !== '') {
                $statements[] = trim($current);
            }
            $current = '';
        } else {
            $current .=$char;
        }
    }
    if (trim($current) !== '') {
        $statements[] = trim($current);
    }

    foreach ($statements as$stmt) {
        $pdo->exec($stmt);
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
}

// ================= ACTION HANDLERS =================

// 1. Download Instant SQL Backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'download_backup') {
    $dump = generateDatabaseDump($pdo, $db_name);$filename = 'SwitchNex_Backup_' . date('Y-m-d_His') . '.sql';

    file_put_contents($backupDir . '/' . $filename,$dump);

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($dump));
    echo $dump;
    exit;
}

// 2. Save Snapshot on Server Only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_server_snapshot') {
    $dump = generateDatabaseDump($pdo, $db_name);$filename = 'SwitchNex_Snapshot_' . date('Y-m-d_His') . '.sql';
    file_put_contents($backupDir . '/' . $filename,$dump);
    setFlash("Snapshot saved on server ($filename).");
    header("Location: backup.php");
    exit;
}

// 3. Download Existing Server Snapshot
if (isset($_GET['download_file'])) {$file = basename($_GET['download_file']);$fullPath = $backupDir . '/' .$file;
    if (is_file($fullPath) && pathinfo($fullPath, PATHINFO_EXTENSION) === 'sql') {
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }
}

// 4. Restore from Uploaded .sql File
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'restore_upload') {
    if (!empty($_FILES['sql_file']['tmp_name']) &&$_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['sql_file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            setFlash("Invalid file format. Please upload a .sql backup file.", "error");
        } else {
            try {
                // Save an automatic safety snapshot before restoring
                $autoSafety = 'AutoSafety_BeforeRestore_' . date('Y-m-d_His') . '.sql';
                file_put_contents($backupDir . '/' .$autoSafety, generateDatabaseDump($pdo,$db_name));

                $sqlContent = file_get_contents($_FILES['sql_file']['tmp_name']);
                executeSqlRestore($pdo,$sqlContent);
                setFlash("Database restored successfully from uploaded file.");
            } catch (Exception $e) {
                setFlash("Restore failed: " . $e->getMessage(), "error");
            }
        }
    } else {
        setFlash("Please select a valid .sql file to upload.", "error");
    }
    header("Location: backup.php");
    exit;
}

// 5. Restore from Saved Server Snapshot
if (isset($_GET['restore_file'])) {$file = basename($_GET['restore_file']);$fullPath = $backupDir . '/' .$file;
    if (is_file($fullPath) && pathinfo($fullPath, PATHINFO_EXTENSION) === 'sql') {
        try {
            $sqlContent = file_get_contents($fullPath);
            executeSqlRestore($pdo,$sqlContent);
            setFlash("Database restored to snapshot: $file");
        } catch (Exception $e) {
            setFlash("Restore failed: " . $e->getMessage(), "error");
        }
    }
    header("Location: backup.php");
    exit;
}

// 6. Delete Server Snapshot
if (isset($_GET['delete_file'])) {$file = basename($_GET['delete_file']);$fullPath = $backupDir . '/' .$file;
    if (is_file($fullPath) && pathinfo($fullPath, PATHINFO_EXTENSION) === 'sql') {
        unlink($fullPath);
        setFlash("Backup file deleted.");
    }
    header("Location: backup.php");
    exit;
}

// Fetch saved backup files
$savedFiles = glob($backupDir . '/*.sql');
if (!is_array($savedFiles)) {$savedFiles = array();
}
usort($savedFiles, function($a,$b) {
    return filemtime($b) - filemtime($a);
});

// Table row counts for overview
$counts = array(
    'Products'       => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'Serial Numbers' => (int)$pdo->query("SELECT COUNT(*) FROM serial_numbers")->fetchColumn(),
    'Sales Records'  => (int)$pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn(),
);

$pageTitle = 'Backup & Restore';
require_once __DIR__ . '/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Left Column: Export Backup & Upload Restore -->
    <div class="lg:col-span-5 space-y-6">
        
        <!-- Export Backup Card -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="download-cloud" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Create Database Backup</h3>
                    <p class="text-xs text-slate-500">Products: <?= number_format($counts['Products']) ?> • Serials: <?= number_format($counts['Serial Numbers']) ?> • Sales: <?= number_format($counts['Sales Records']) ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                <form method="POST">
                    <input type="hidden" name="action" value="download_backup">
                    <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center justify-center gap-2">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Download .SQL Backup</span>
                    </button>
                </form>

                <form method="POST">
                    <input type="hidden" name="action" value="save_server_snapshot">
                    <button type="submit" class="w-full py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow transition flex items-center justify-center gap-2">
                        <i data-lucide="hard-drive" class="w-4 h-4"></i>
                        <span>Save Server Snapshot</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Restore from File Upload Card -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Restore from .SQL File</h3>
                    <p class="text-xs text-slate-500">Upload a previously downloaded SwitchNex `.sql` file</p>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('Restore database from this file? Current records will be replaced.');" class="space-y-4">
                <input type="hidden" name="action" value="restore_upload">
                <div>
                    <input type="file" name="sql_file" accept=".sql" required class="w-full text-xs text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-xl p-1.5">
                </div>
                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center justify-center gap-2">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Upload & Restore Database</span>
                </button>
            </form>
        </div>

    </div>

    <!-- Right Column: Server Backup History -->
    <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden h-fit">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Saved Server Snapshots</h3>
            <span class="text-xs font-bold text-slate-500"><?= count($savedFiles) ?> Files</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase">
                        <th class="py-3 px-5 font-semibold">Backup Filename</th>
                        <th class="py-3 px-5 font-semibold">Date & Time</th>
                        <th class="py-3 px-5 font-semibold">Size</th>
                        <th class="py-3 px-5 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($savedFiles)): ?>
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">No snapshots saved on server yet.</td>
                        </tr>
                    <?php else: foreach ($savedFiles as$filePath):
                        $fName = basename($filePath);
                        $fDate = date('d M Y, h:i A', filemtime($filePath));
                        $fSize = round(filesize($filePath) / 1024, 1) . ' KB';
                    ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3.5 px-5 mono font-bold text-xs text-slate-800"><?= htmlspecialchars($fName) ?></td>
                            <td class="py-3.5 px-5 text-xs text-slate-500"><?= htmlspecialchars($fDate) ?></td>
                            <td class="py-3.5 px-5 mono text-xs text-slate-600"><?= htmlspecialchars($fSize) ?></td>
                            <td class="py-3.5 px-5 text-right space-x-2">
                                <a href="?download_file=<?= urlencode($fName) ?>" class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold">Download</a>
                                <a href="?restore_file=<?= urlencode($fName) ?>" onclick="return confirm('Restore database to <?= htmlspecialchars($fName) ?>?')" class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold">Restore</a>
                                <a href="?delete_file=<?= urlencode($fName) ?>" onclick="return confirm('Delete this backup file?')" class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>