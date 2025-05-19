<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'AuthHelper.php';

header('Content-Type: application/json');

// Replace with dynamic values during actual login
$token = AuthHelper::generateToken(1, "demo@example.com", "9876543210", "admin", "EMP123");

echo json_encode([
    "token" => $token
]);
