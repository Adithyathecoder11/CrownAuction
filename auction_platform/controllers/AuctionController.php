<?php
// controllers/AuctionController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/AuctionModel.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuctionController {

    public static function handleCreate(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        requireRole('seller');
        $user = getCurrentUser();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrf($token)) {
            setFlash('error', 'Invalid request.');
            header('Location: ' . APP_URL . '/views/seller/create_auction.php');
            exit;
        }

        $data = [
            'seller_id'        => $user['id'],
            'title'            => trim($_POST['title'] ?? ''),
            'description'      => trim($_POST['description'] ?? ''),
            'category'         => trim($_POST['category'] ?? ''),
            'starting_price'   => (float)($_POST['starting_price'] ?? 0),
            'min_bid_increment'=> (float)($_POST['min_bid_increment'] ?? 1),
            'duration_minutes' => (int)($_POST['duration_minutes'] ?? 60),
        ];

        // Validation
        $errors = [];
        if (empty($data['title']))                                             $errors[] = 'Title is required.';
        if ($data['starting_price'] <= 0)                                     $errors[] = 'Starting price must be greater than 0.';
        if ($data['min_bid_increment'] <= 0)                                  $errors[] = 'Minimum bid increment must be greater than 0.';
        if ($data['duration_minutes'] < MIN_AUCTION_DURATION || $data['duration_minutes'] > MAX_AUCTION_DURATION)
            $errors[] = 'Auction duration must be between 30 minutes and 6 hours (360 minutes).';

        // Image upload
        $imagePath = 'default_item.jpg';
        if (!empty($_FILES['image']['name'])) {
            $uploadResult = self::handleImageUpload($_FILES['image']);
            if (isset($uploadResult['error'])) {
                $errors[] = $uploadResult['error'];
            } else {
                $imagePath = $uploadResult['filename'];
            }
        }

        if (!empty($errors)) {
            setFlash('error', implode(' ', $errors));
            header('Location: ' . APP_URL . '/views/seller/create_auction.php');
            exit;
        }

        $data['image_path'] = $imagePath;
        $auctionId = AuctionModel::create($data);

        setFlash('success', 'Auction created successfully! It is now live.');
        header('Location: ' . APP_URL . '/views/seller/dashboard.php');
        exit;
    }

    private static function handleImageUpload(array $file): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'File upload failed.'];
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            return ['error' => 'File size must be under 5MB.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS)) {
            return ['error' => 'Only JPG, PNG, GIF, WEBP files are allowed.'];
        }

        // Verify it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            return ['error' => 'Invalid image file.'];
        }

        $filename = uniqid('auction_', true) . '.' . $ext;
        $destination = UPLOAD_PATH . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['error' => 'Failed to save uploaded file.'];
        }

        return ['filename' => $filename];
    }
}
