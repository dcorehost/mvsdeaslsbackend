<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// AuthHelper.php
require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthHelper {

    public static function generateToken($id, $email, $phone, $userType, $employeeId) {
        $secretKey = getenv('JWT_SECRET') ?: 'mvsdeals.online'; // or from config
        $issuedAt = time();
        $expirationTime = $issuedAt + (10 * 60 * 60); // 10 hours

        $payload = [
            "_id" => $id,
            "email" => $email,
            "phone" => $phone,
            "typeOfUser" => $userType,
            "employeeId" => $employeeId,
            "iat" => $issuedAt,
            "exp" => $expirationTime
        ];

        return JWT::encode($payload, $secretKey, 'HS256');
    }

    public static function decodeToken($token) {
        $secretKey = getenv('JWT_SECRET') ?: 'mvsdeals.online';

        return JWT::decode($token, new Key($secretKey, 'HS256'));
    }
}
