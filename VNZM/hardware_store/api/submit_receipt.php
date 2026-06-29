<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$customerName = trim($data['customer_name'] ?? 'Walk-in');
$items = $data['items'] ?? [];

if (empty($items)) {
    echo json_encode(['error' => 'No items provided']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Calculate total
    $total = array_sum(array_column($items, 'subtotal'));

    // Insert transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (receipt_number, customer_name, notes, total_amount) VALUES (?, ?, ?, ?)");
    $lastId = $pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM transactions")->fetchColumn();
    $receiptNumber = 'VNZM-' . str_pad($lastId, 4, '0', STR_PAD_LEFT);
    $stmt->execute([$receiptNumber, $customerName, $data['notes'] ?? null, $total]);
    $transactionId = $pdo->lastInsertId();

    // Insert each item
    $itemStmt = $pdo->prepare("
        INSERT INTO transaction_items (transaction_id, product_id, quantity, unit_price, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $itemStmt->execute([
            $transactionId,
            $item['id'],
            $item['qty'],
            $item['price'],
            $item['subtotal']
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'transaction_id' => $transactionId, 'receipt_number' => $receiptNumber]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Failed to save receipt: ' . $e->getMessage()]);
}
