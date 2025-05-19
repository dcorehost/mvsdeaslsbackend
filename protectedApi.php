<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Require the protectUser.php file to handle token validation
require_once 'protectUser.php';

// Call the protectUser function to validate the token and extract the user data
protectUser();

// After successfully decoding the token, we get the user data from the global variable
$user = $GLOBALS['userPayload'];

// Return a response with user data if authentication is successful
$response = [
    "success" => true,  // Indicating the request was successful
    "message" => "Access granted",  // Success message
    "user" => $user  // User data decoded from the JWT token
];

echo json_encode($response);  // Send the response back as JSON
?>
