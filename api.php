<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'mark_printed' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $ids = array_map('intval', $data['ids'] ?? []);
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE serial_numbers SET is_printed = 1, printed_at = NOW() WHERE id IN ($placeholders)");
        $stmt->execute($ids);
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

if ($action === 'global_search') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode([]);
        exit;
    }
    $like = "%$q%";
    $stmt = $pdo->prepare("
        SELECT s.id, s.serial_no, s.batch_code, s.is_printed, s.printed_at, s.is_sold, s.created_at,
               p.product_name, p.product_type, p.model_name,
               sa.customer_name, sa.customer_mobile, sa.customer_address, sa.dealer_name, sa.sale_date, sa.warranty_months,
               DATE_ADD(sa.sale_date, INTERVAL sa.warranty_months MONTH) AS warranty_expiry
        FROM serial_numbers s
        JOIN products p ON s.product_id = p.id
        LEFT JOIN sales sa ON s.id = sa.serial_id
        WHERE s.serial_no LIKE ?
           OR p.model_name LIKE ?
           OR sa.customer_name LIKE ?
           OR sa.customer_mobile LIKE ?
           OR sa.customer_address LIKE ?
           OR sa.dealer_name LIKE ?
        ORDER BY s.id DESC
        LIMIT 25
    ");
    $stmt->execute([$like, $like, $like, $like, $like, $like]);
    echo json_encode($stmt->fetchAll());
    exit;
}

echo json_encode(['error' => 'Invalid action']);