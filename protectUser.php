<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php'; // Load Firebase JWT
require_once 'AuthHelper.php';

use Firebase\JWT\ExpiredException;

function protectUser() {
    // Try to get Authorization header from different sources
    $authHeader = null;

    // Method 1: Try apache_request_headers if available
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        }
    }

    // Method 2: Fallback to $_SERVER
    if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }

    // Method 3: Sometimes it's redirected
    if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    // Final check
    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];

        if (empty($token) || $token === 'undefined') {
            http_response_code(401);
            echo json_encode(["message" => "Token is empty or undefined"]);
            exit();
        }

        try {
            $decoded = AuthHelper::decodeToken($token);
            $GLOBALS['userPayload'] = (array)$decoded;

        } catch (ExpiredException $e) {
            http_response_code(401);
            echo json_encode(["message" => "Token expired"]);
            exit();
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid token", "error" => $e->getMessage()]);
            exit();
        }
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Authorization header missing or malformed"]);
        exit();
    }
}
?>
