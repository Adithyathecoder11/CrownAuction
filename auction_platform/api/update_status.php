<?php
// api/update_status.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/TransactionModel.php';

requireLogin();
$user = getCurrentUser();

$txId  = (int)($_POST['transaction_id'] ?? 0);
$type  = $_POST['type'] ?? '';
$value = $_POST['value'] ?? '';
$token = $_POST['csrf_token'] ?? '';

if (!verifyCsrf($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid token.']);
    exit;
}

$tx = TransactionModel::getById($txId);
if (!$tx || ($tx['buyer_id'] != $user['id'] && $tx['seller_id'] != $user['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($type === 'payment' && in_array($value, ['pending','paid','failed','refunded'])) {
    TransactionModel::updatePaymentStatus($txId, $value);
    echo json_encode(['success' => true]);
} elseif ($type === 'delivery' && in_array($value, ['pending','shipped','delivered','completed'])) {
    TransactionModel::updateDeliveryStatus($txId, $value);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid status type or value.']);
}
