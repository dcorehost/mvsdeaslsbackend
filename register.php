<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
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

// Get JSON input
$inputData = json_decode(file_get_contents('php://input'), true);

// Validate email
if (!isset($inputData['email']) || empty(trim($inputData['email']))) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Email is required.']);
    exit;
}

$email = trim($inputData['email']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['error' => 'Invalid email format.']);
    exit;
}

// Generate credentials
$randomPassword = bin2hex(random_bytes(4)); // 8-character temp password
$hashedPassword = password_hash($randomPassword, PASSWORD_BCRYPT);
$resetToken = bin2hex(random_bytes(32));
$tokenExpiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

try {
    $pdo->beginTransaction();

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = ?, reset_token_expiry = ?, updated_at = NOW() WHERE email = ?");
        $stmt->execute([$hashedPassword, $resetToken, $tokenExpiry, $email]);
        $message = 'User updated. Password reset link generated.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, reset_token, reset_token_expiry, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$email, $hashedPassword, $resetToken, $tokenExpiry]);
        $message = 'User registered. Password reset link generated.';
    }

    $pdo->commit();


 error_log("Email: $email");
    error_log("Temporary Password (hashed): $hashedPassword");
    // ✅ Email functionality is disabled for now.
    
    $resetLink = "https://mvsdeals.online/updatePassword.php?email=" . urlencode($email) . "&token=$resetToken";

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'mail.mvsdeals.online';
    $mail->SMTPAuth = true;
    $mail->Username = 'support@mvsdeals.online';
    $mail->Password = 'raj@3245M';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('support@mvsdeals.online', 'MSV Deals');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Your Account Details';
    $mail->Body = "
    <h2>Welcome to MSV Deals!</h2>
    <p>Your account has been created successfully.</p>
    <p>Please click the button below to set your password:</p>
    <p><a href='$resetLink' style='display:inline-block;padding:10px 15px;background:#007bff;color:#fff;text-decoration:none;'>Set Your Password</a></p>
    <p>This link will expire in 1 hour for security purposes.</p>
";

    $mail->send();
    

    // ✅ Respond with success
    http_response_code(200); // OK
    echo json_encode([
        'success' => true,
        'message' => $message,
        'email' => $email,
        'temporary_password' => $randomPassword,
        'note' => 'Password reset link was generated but email sending is currently disabled.'
    ]);
}  catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500); // Internal Server Error
    echo json_encode([
        'error' => 'Something went wrong while processing your request.',
        'details' => $e->getMessage()
    ]);
}
exit;
