<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

include 'db.php'; // Your PDO connection

$data = json_decode(file_get_contents('php://input'), true);

// Validate required structure
if (
    !$data || 
    !isset($data['user_id']) || 
    !isset($data['billing']) || 
    !isset($data['cartItems']) || 
    !isset($data['total'])
) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    exit;
}

$user_id = $data['user_id'];
$billing = $data['billing'];
$cartItems = $data['cartItems'];
$total = $data['total'];

// Validation function
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isValidPhone($phone) {
    return preg_match('/^\d{10}$/', $phone); // 10 digit numeric phone
}

$requiredFields = ['first_name', 'last_name', 'country', 'address', 'city', 'state', 'zip', 'phone', 'email'];

foreach ($requiredFields as $field) {
    if (empty($billing[$field])) {
        echo json_encode(['status' => 'error', 'message' => "Missing billing field: $field"]);
        exit;
    }
}

if (!isValidEmail($billing['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}

if (!isValidPhone($billing['phone'])) {
    echo json_encode(['status' => 'error', 'message' => 'Phone number must be 10 digits']);
    exit;
}

// Insert into orders table
$orderStmt = $pdo->prepare("
    INSERT INTO orders (user_id, first_name, last_name, company, country, address, apartment, city, state, zip_code, phone, email, notes, total, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$orderSuccess = $orderStmt->execute([
    $user_id,
    $billing['first_name'],
    $billing['last_name'],
    $billing['company'] ?? '',
    $billing['country'],
    $billing['address'],
    $billing['apartment'] ?? '',
    $billing['city'],
    $billing['state'],
    $billing['zip'],
    $billing['phone'],
    $billing['email'],
    $billing['notes'] ?? '',
    $total
]);

if (!$orderSuccess) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to create order']);
    exit;
}

$orderId = $pdo->lastInsertId();

// Insert order items
$itemStmt = $pdo->prepare("
    INSERT INTO order_items (order_id, product_id, name, quantity, price, subtotal)
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($cartItems as $item) {
    if (!isset($item['product_id'], $item['name'], $item['quantity'], $item['price'], $item['subtotal'])) {
        echo json_encode(['status' => 'error', 'message' => 'Incomplete product item data']);
        exit;
    }

    $itemStmt->execute([
        $orderId,
        $item['product_id'],
        $item['name'],
        $item['quantity'],
        $item['price'],
        $item['subtotal']
    ]);
}

echo json_encode([
    'status' => 'success',
    'message' => 'Order placed successfully',
    'order_id' => $orderId
]);
?>
