<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

include 'db.php';

// Get the Authorization header
$authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

if (empty($authorization)) {
    echo json_encode(["status" => "error", "message" => "Authorization header is missing"]);
    exit;
}

// Extract the token from the header
list($bearer, $token) = explode(" ", $authorization);
if (empty($token)) {
    echo json_encode(["status" => "error", "message" => "Bearer token is missing"]);
    exit;
}

// Read the JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id']) || !isset($input['product_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

$user_id = $input['user_id'];
$product_id = $input['product_id'];

// Delete the product from the user's cart
$stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
$success = $stmt->execute([$user_id, $product_id]);

if ($success) {
    echo json_encode(["status" => "success", "message" => "Product removed from cart"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to remove product from cart"]);
}
?>
