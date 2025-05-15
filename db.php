<?php
$host = 'localhost'; // Your host
$dbname = 'mvsdeqdz_mvsdeals';
$username = 'mvsdeqdz_dcorehost';
$password = 'A#A%j;~VSvdP';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>
