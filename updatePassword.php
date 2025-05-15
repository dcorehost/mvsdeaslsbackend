<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';
session_start();

header('Content-Type: application/json');

// Handle GET request - display password reset form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['email'], $_GET['token'])) {
        echo json_encode(['error' => 'Invalid reset link.']);
        exit;
    }

    $email = $_GET['email'];
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->execute([$email, $token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['error' => 'Invalid or expired reset link.']);
        exit;
    }

    header('Content-Type: text/html');
    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Reset Password</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; }
            input { width: 100%; padding: 8px; box-sizing: border-box; }
            button { background: #007bff; color: white; border: none; padding: 10px 15px; cursor: pointer; }
            button:hover { background: #0056b3; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <h2>Reset Password</h2>
        <form id="resetForm">
            <input type="hidden" name="email" value="' . htmlspecialchars($email) . '">
            <input type="hidden" name="token" value="' . htmlspecialchars($token) . '">
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
            </div>
            <button type="submit">Reset Password</button>
            <div id="error" class="error"></div>
            <div id="success"></div>
        </form>
        
        <script>
            document.getElementById("resetForm").addEventListener("submit", async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const response = await fetch("updatePassword.php", {
                    method: "POST",
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.error) {
                    document.getElementById("error").textContent = result.error;
                    document.getElementById("success").textContent = "";
                } else {
                    document.getElementById("error").textContent = "";
                    document.getElementById("success").textContent = result.success;
                    this.reset();
                }
            });
        </script>
    </body>
    </html>';
    exit;
}

// Handle POST request - process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = '';
    $token = '';
    $newPassword = '';
    $confirmPassword = '';

    $contentType = $_SERVER["CONTENT_TYPE"] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $rawData = file_get_contents("php://input");
        $json = json_decode($rawData, true);

        $email = $json['email'] ?? ($_GET['email'] ?? '');
        $token = $json['token'] ?? ($_GET['token'] ?? '');
        $newPassword = trim($json['new_password'] ?? '');
        $confirmPassword = trim($json['confirm_password'] ?? '');
    } else {
        $email = $_POST['email'] ?? ($_GET['email'] ?? '');
        $token = $_POST['token'] ?? ($_GET['token'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
    }

    if (!$email || !$token) {
        echo json_encode(['error' => 'Email or token missing.']);
        exit;
    }

    if (strlen($newPassword) < 6) {
        echo json_encode(['error' => 'Password must be at least 6 characters long.']);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        echo json_encode(['error' => 'Passwords do not match.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_token_expiry > NOW()");
        $stmt->execute([$email, $token]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['error' => 'Invalid or expired reset link.']);
            exit;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL, updated_at = NOW() WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);

        $pdo->commit();
        echo json_encode(['success' => 'Password has been updated successfully.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => 'An error occurred while updating your password: ' . $e->getMessage()]);
    }

    exit;
}
?>
