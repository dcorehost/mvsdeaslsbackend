<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php'; // This gives us $pdo (a PDO object)

// Get JSON data from the request
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

// Validate JSON
if (!is_array($data)) {
    echo json_encode(["error" => "Invalid JSON or empty body"]);
    exit;
}

foreach ($data as $p) {
    $name = $p['name'] ?? null;
    $displayName = $p['displayName'] ?? null;
    $price = isset($p['price']) ? floatval($p['price']) : 0;
    $cutoffPrice = isset($p['cutoffPrice']) ? floatval($p['cutoffPrice']) : null;
    $image = $p['image'] ?? null;
    $section = $p['section'] ?? 'home';

    $stmt = $pdo->prepare("INSERT INTO products (name, display_name, price, cutoff_price, image, section) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $displayName, $price, $cutoffPrice, $image, $section]);
}

echo json_encode(["message" => "All products inserted"]);
?>
