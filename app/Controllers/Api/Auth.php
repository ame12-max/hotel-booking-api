<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth extends BaseApiController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function register()
    {
        try {
            $data = $this->request->getJSON(true);

            if (empty($data)) {
                return $this->respond([
                    'status' => false,
                    'message' => 'No data provided'
                ], 400);
            }

            $rules = [
                'full_name' => 'required|min_length[3]',
                'email'     => 'required|valid_email|is_unique[users.email]',
                'phone'     => 'required|is_unique[users.phone]',
                'password'  => 'required|min_length[6]',
            ];

            if (!$this->validateData($data, $rules)) {
                return $this->respond([
                    'status' => false,
                    'errors' => $this->validator->getErrors(),
                ], 422);
            }

            $userData = [
                'full_name' => $data['full_name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'],
                'password'  => password_hash($data['password'], PASSWORD_DEFAULT),
                'role'      => 'customer',
            ];

            $this->userModel->insert($userData);

            return $this->respondCreated([
                'status'  => true,
                'message' => 'User registered successfully'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Register error: ' . $e->getMessage());
            return $this->respond([
                'status' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function login()
    {
        try {
            $data = $this->request->getJSON(true);

            if (empty($data)) {
                return $this->respond([
                    'status' => false,
                    'message' => 'No data provided'
                ], 400);
            }

            $rules = [
                'email'    => 'required|valid_email',
                'password' => 'required'
            ];

            if (!$this->validateData($data, $rules)) {
                return $this->respond([
                    'status' => false,
                    'errors' => $this->validator->getErrors()
                ], 422);
            }

            $email = $data['email'];
            $password = $data['password'];

            $user = $this->userModel->where('email', $email)->first();

            if (!$user) {
                return $this->failUnauthorized('Invalid credentials');
            }

            if (!password_verify($password, $user['password'])) {
                return $this->failUnauthorized('Invalid credentials');
            }

            // ✅ JWT_SECRET must be defined in environment
            $jwtSecret = env('JWT_SECRET');
            if (empty($jwtSecret)) {
                log_message('error', 'JWT_SECRET is not set');
                return $this->respond([
                    'status' => false,
                    'message' => 'Server configuration error'
                ], 500);
            }

            $payload = [
                'iss' => base_url(),
                'aud' => base_url(),
                'iat' => time(),
                'exp' => time() + (60 * 60 * 24), // 1 day
                'data' => [
                    'id'        => $user['id'],
                    'full_name' => $user['full_name'],
                    'email'     => $user['email'],
                    'role'      => $user['role']
                ]
            ];

            $token = JWT::encode($payload, $jwtSecret, 'HS256');

            return $this->respond([
                'status' => true,
                'token'  => $token,
                'user'   => [
                    'id'        => $user['id'],
                    'full_name' => $user['full_name'],
                    'email'     => $user['email'],
                    'role'      => $user['role']
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Login error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->respond([
                'status' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        return $this->respond([
            'status' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
