<?php
// controllers/AuthController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController {

    public static function handleLogin(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrf($token)) {
            setFlash('error', 'Invalid request. Please try again.');
            header('Location: ' . APP_URL . '/index.php');
            exit;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            setFlash('error', 'Email and password are required.');
            header('Location: ' . APP_URL . '/index.php');
            exit;
        }

        $user = UserModel::findByEmail($email);

        if (!$user || !UserModel::verifyPassword($password, $user['password_hash'])) {
            setFlash('error', 'Invalid email or password.');
            header('Location: ' . APP_URL . '/index.php');
            exit;
        }

        if ($user['is_banned']) {
            setFlash('error', 'Your account has been suspended. Reason: ' . ($user['ban_reason'] ?? 'Policy violation'));
            header('Location: ' . APP_URL . '/index.php');
            exit;
        }

        loginUser($user);

        // Redirect based on role
        switch ($user['role']) {
            case 'seller':
                header('Location: ' . APP_URL . '/views/seller/dashboard.php');
                break;
            case 'buyer':
                header('Location: ' . APP_URL . '/views/buyer/browse.php');
                break;
            default:
                header('Location: ' . APP_URL . '/index.php');
        }
        exit;
    }

    public static function handleRegister(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrf($token)) {
            setFlash('error', 'Invalid request.');
            header('Location: ' . APP_URL . '/index.php?action=register');
            exit;
        }

        $data = [
            'username'  => trim($_POST['username'] ?? ''),
            'email'     => trim($_POST['email'] ?? ''),
            'password'  => $_POST['password'] ?? '',
            'confirm'   => $_POST['confirm_password'] ?? '',
            'full_name' => trim($_POST['full_name'] ?? ''),
            'phone'     => trim($_POST['phone'] ?? ''),
            'role'      => in_array($_POST['role'] ?? '', ['buyer', 'seller']) ? $_POST['role'] : 'buyer'
        ];

        // Validation
        $errors = [];
        if (strlen($data['username']) < 3 || strlen($data['username']) > 50) $errors[] = 'Username must be 3-50 characters.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))              $errors[] = 'Invalid email address.';
        if (strlen($data['password']) < 8)                                   $errors[] = 'Password must be at least 8 characters.';
        if ($data['password'] !== $data['confirm'])                          $errors[] = 'Passwords do not match.';
        if (empty($data['full_name']))                                        $errors[] = 'Full name is required.';

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) $errors[] = 'Username can only contain letters, numbers, underscores.';

        if (UserModel::emailExists($data['email']))       $errors[] = 'Email already registered.';
        if (UserModel::usernameExists($data['username'])) $errors[] = 'Username already taken.';

        if (!empty($errors)) {
            setFlash('error', implode(' ', $errors));
            header('Location: ' . APP_URL . '/index.php?action=register');
            exit;
        }

        $userId = UserModel::create($data);
        $user   = UserModel::findById($userId);
        loginUser($user);

        setFlash('success', 'Account created! Welcome to CrownAuction.');

        if ($user['role'] === 'seller') {
            header('Location: ' . APP_URL . '/views/seller/dashboard.php');
        } else {
            header('Location: ' . APP_URL . '/views/buyer/browse.php');
        }
        exit;
    }

    public static function handleLogout(): void {
        logoutUser();
        setFlash('success', 'You have been logged out.');
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}
