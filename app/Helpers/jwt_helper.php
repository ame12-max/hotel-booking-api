<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('generateJWT')) {

    function generateJWT(array $user): string
    {
        $key = env('JWT_SECRET');

        $payload = [
            'iss' => base_url(),
            'aud' => base_url(),
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24), // 24 hours

            'data' => [
                'id'        => $user['id'],
                'full_name' => $user['full_name'],
                'email'     => $user['email'],
                'role'      => $user['role'],
            ]
        ];

        return JWT::encode($payload, $key, 'HS256');
    }
}

if (!function_exists('validateJWT')) {

    function validateJWT(string $token)
    {
        try {
            $key = env('JWT_SECRET');

            return JWT::decode(
                $token,
                new Key($key, 'HS256')
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}