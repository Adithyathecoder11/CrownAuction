<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

unset($_SESSION['admin_authenticated']);
unset($_SESSION['admin_login_time']);

header('Location: ' . APP_URL . '/' . ADMIN_SECRET_PATH . '.php');
exit;
