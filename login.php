<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'vendor/autoload.php';

use Firebase\JWT\JWT; // ✅ ADDED
use Firebase\JWT\Key;  // ✅ ADDED
session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // ✅ ADDED
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST method is allowed.']);
    exit;
}

// Get login data from the request
$inputData = json_decode(file_get_contents('php://input'), true);

// Validate email and password
if (!isset($inputData['email']) || empty(trim($inputData['email']))) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Email is required.']);
    exit;
}

if (!isset($inputData['password']) || empty(trim($inputData['password']))) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Password is required.']);
    exit;
}

$email = trim($inputData['email']);
$password = trim($inputData['password']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['error' => 'Invalid email format.']);
    exit;
}

try {
    // Check if user exists with the provided email
    $stmt = $pdo->prepare("SELECT id, email , password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Verify password with the stored hashed password
        if (password_verify($password, $user['password'])) {
            
            $payload = [
                '_id' => $user['id'],
                'email' => $user['email'],
                // 'phone' => $user['phone'],
                // 'typeOfUser' => $user['user_type'],
                // 'employeeId' => $user['employee_id'],
                'iat' => time(),
                'exp' => time() + (10 * 60 * 60) // 10 hours
            ];

            $secretKey = 'mvsdeals.online'; // 🔄 Use a secure env key in production
            $token = JWT::encode($payload, $secretKey, 'HS256');
            
            http_response_code(200); // OK
            echo json_encode([
                'success' => true,
                'message' => 'Login successful.',
                                'token' => $token, // ✅ Return the token
                'user_id' => $user['id'],
                'email' => $email,
                'note' => 'Login successful with the temporary password.'
            ]);
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Invalid email or password.']);
        }
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'User not found.']);
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'error' => 'Something went wrong while processing your request.',
        'details' => $e->getMessage()
    ]);
}
exit;
