<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'switchnex_db';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}

// Auto-seed default admin (Username: admin | Password: admin123) if table is empty
$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
if ($adminCount === 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO admins (username, password_hash, full_name) VALUES ('admin', ?, 'SwitchNex Admin')")->execute([$hash]);
}

function generateSerialFromPattern($pattern, $prefix, $sizeCode, $modelName) {
    $replacements = [
        '{PREFIX}' => strtoupper(trim($prefix)),
        '{SIZE}'   => strtoupper(trim($sizeCode)),
        '{MODEL}'  => strtoupper(trim($modelName)),
        '{MM}'     => date('m'),
        '{YY}'     => date('y'),
        '{YYYY}'   => date('Y'),
    ];
    $res = strtr($pattern, $replacements);
    return preg_replace_callback('/\{RAND(\d+)\}/', function($m) {
        $len = max(1, min(10, (int)$m[1]));
        return (string)random_int((int)pow(10, $len - 1), (int)pow(10, $len) - 1);
    }, $res);
}

function setFlash($msg, $type = 'success') {
    $_SESSION['flash_msg'] = $msg;
    $_SESSION['flash_type'] = $type;
}