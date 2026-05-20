<?php
// api/messages.php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/TransactionModel.php';

requireLogin();
$user = getCurrentUser();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'send':
        $txId      = (int)($_POST['transaction_id'] ?? 0);
        $message   = trim($_POST['message'] ?? '');
        $token     = $_POST['csrf_token'] ?? '';

        if (!verifyCsrf($token) || !$txId || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        // Verify user is part of this transaction
        $tx = TransactionModel::getById($txId);
        if (!$tx || ($tx['buyer_id'] != $user['id'] && $tx['seller_id'] != $user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }

        $receiverId = ($user['id'] == $tx['buyer_id']) ? $tx['seller_id'] : $tx['buyer_id'];
        $msgId = TransactionModel::sendMessage($txId, $user['id'], $receiverId, htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

        echo json_encode([
            'success'    => true,
            'message_id' => $msgId,
            'sent_at'    => date('d M, h:i A')
        ]);
        break;

    case 'fetch':
        $txId = (int)($_GET['transaction_id'] ?? 0);
        $tx   = TransactionModel::getById($txId);

        if (!$tx || ($tx['buyer_id'] != $user['id'] && $tx['seller_id'] != $user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }

        TransactionModel::markMessagesRead($txId, $user['id']);
        $messages = TransactionModel::getMessages($txId);

        echo json_encode([
            'success'  => true,
            'messages' => array_map(fn($m) => [
                'id'       => $m['id'],
                'sender'   => $m['username'],
                'text'     => $m['message'],
                'time'     => date('d M, h:i A', strtotime($m['sent_at'])),
                'is_mine'  => ($m['sender_id'] == $user['id'])
            ], $messages)
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
