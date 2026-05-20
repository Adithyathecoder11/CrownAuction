<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/controllers/AuthController.php';

AuthController::handleLogout();
