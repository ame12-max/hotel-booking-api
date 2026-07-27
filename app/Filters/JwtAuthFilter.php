<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if (!$header) {
            return service('response')->setJSON([
                'status' => false,
                'message' => 'Authorization token required'
            ])->setStatusCode(401);
        }

        $token = str_replace('Bearer ', '', $header);

        try {
            $decoded = JWT::decode(
                $token,
                new Key(env('JWT_SECRET'), 'HS256')
            );

            $request->user = $decoded->data;

        } catch (\Exception $e) {
            return service('response')->setJSON([
                'status' => false,
                'message' => 'Invalid or expired token'
            ])->setStatusCode(401);
        }
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
    }
}