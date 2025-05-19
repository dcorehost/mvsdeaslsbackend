<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);



header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Allow Authorization header
header('Content-Type: application/json');

include 'db.php'; // This gives you the $pdo variable

$user_id = $_GET['user_id'];

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Missing user_id"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.product_id, c.name, c.price, c.image, c.quantity, 
               (c.price * c.quantity) AS subtotal
        FROM cart c
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "cart" => $cart]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
