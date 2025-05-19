<?php

include 'db.php';


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $json = file_get_contents('php://input');
    file_put_contents('log.txt', $json); // Log the incoming JSON

    $data = json_decode($json, true);
    
    

    // Check if data was decoded successfully
    if (json_last_error() === JSON_ERROR_NONE) {
        // Sanitize and assign the data
        $name = htmlspecialchars(trim($data['name']));
        $email = htmlspecialchars(trim($data['email']));
        $subject = htmlspecialchars(trim($data['subject']));
        $message = htmlspecialchars(trim($data['message']));

        // Prepare and execute the SQL statement
        $sql = "INSERT INTO contact_form (name, email, subject, message) VALUES (?, ?, ?, ?)";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $email, $subject, $message]);
            
            // Return a success response
            echo json_encode(["message" => "Thank you for your message!"]);
        } catch (PDOException $e) {
            // Return an error response
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Invalid JSON data"]);
    }
} else {
    echo json_encode(["message" => "Invalid request"]);
}
?>
