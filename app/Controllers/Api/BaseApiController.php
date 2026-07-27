<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class BaseApiController extends ResourceController
{
    protected $format = 'json';

    protected function successResponse(
        $data = [],
        string $message = 'Success',
        int $code = 200
    ) {
        return $this->respond([
            'status'  => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    protected function errorResponse(
        string $message = 'Error',
        int $code = 400
    ) {
        return $this->respond([
            'status'  => false,
            'message' => $message,
        ], $code);
    }
}