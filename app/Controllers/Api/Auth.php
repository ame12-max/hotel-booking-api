<?php

namespace App\Controllers\Api;

use App\Models\UserModel;

class Auth extends BaseApiController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

public function register()
{
    $data = $this->request->getJSON(true);

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
        'password'  => password_hash(
            $data['password'],
            PASSWORD_DEFAULT
        ),
        'role'      => 'customer',
    ];

    $this->userModel->insert($userData);

    return $this->respondCreated([
        'status' => true,
        'message' => 'User registered successfully'
    ]);
}

public function login()
{
    $data = $this->request->getJSON(true);

    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    $user = $this->userModel
        ->where('email', $email)
        ->first();

    if (!$user) {
        return $this->failUnauthorized('Invalid credentials');
    }

    if (!password_verify($password, $user['password'])) {
        return $this->failUnauthorized('Invalid credentials');
    }

    $token = generateJWT($user);

    return $this->respond([
        'status' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
}

    public function logout()
    {
        return $this->successResponse(
            [],
            'Logged out successfully'
        );
    }
}