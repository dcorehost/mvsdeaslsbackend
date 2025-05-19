<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Allow Authorization header
header('Content-Type: application/json');

include 'db.php'; // Assuming this gives you $pdo, not $conn

// Get the Authorization header
$authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

if (empty($authorization)) {
    echo json_encode(["status" => "error", "message" => "Authorization header is missing"]);
    exit;
}

// Extract the token from the header
list($bearer, $token) = explode(" ", $authorization);

// You can add your token validation here (e.g., using JWT or your custom logic)
if (empty($token)) {
    echo json_encode(["status" => "error", "message" => "Bearer token is missing"]);
    exit;
}

// Read the JSON body of the request
$input = json_decode(file_get_contents('php://input'), true);

// Check if the required fields exist in the request body
if (!isset($input['user_id']) || !isset($input['product_id']) || !isset($input['quantity'])) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

$user_id = $input['user_id'];
$product_id = $input['product_id'];
$quantity = $input['quantity'];

// Fetch product details from products table
$stmt = $pdo->prepare("SELECT name, price, image FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(["status" => "error", "message" => "Product not found"]);
    exit;
}

// Insert or update cart
$stmt = $pdo->prepare("
    INSERT INTO cart (user_id, product_id, name, price, image, quantity)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE quantity = quantity + ?
");

$success = $stmt->execute([
    $user_id,
    $product_id,
    $product['name'],
    $product['price'],
    $product['image'],
    $quantity,
    $quantity
]);

if ($success) {
    echo json_encode(["status" => "success", "message" => "Product added to cart"]);
} else {
    echo json_encode(["status" => "error", "message" => "Could not add product to cart"]);
}
?>
