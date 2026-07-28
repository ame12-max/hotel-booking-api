<?php

namespace App\Controllers\Api;

use App\Models\HotelModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\Files\UploadedFile;

class Hotel extends ResourceController
{
    protected $modelName = HotelModel::class;
    protected $format    = 'json';

    // Upload directory path relative to public folder
    protected $uploadPath = 'uploads/hotels/';
    protected $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    protected $maxSize = 2048; // KB

    /**
     * GET /api/hotels
     */
    public function index()
    {
        $builder = $this->model;
        $name = $this->request->getGet('name');
        if ($name) {
            $builder->like('hotels.name', $name);
        }

        $city = $this->request->getGet('city');
        if ($city) {
            $builder->like('hotels.city', $city);
        }

        $rating = $this->request->getGet('rating');
        if ($rating) {
            $builder->where('hotels.rating >=', $rating);
        }

        $roomType = $this->request->getGet('room_type');
        $minPrice = $this->request->getGet('min_price');
        $maxPrice = $this->request->getGet('max_price');

        if ($roomType || $minPrice || $maxPrice) {
            $db = \Config\Database::connect();
            $subQuery = $db->table('rooms')
                ->select('rooms.hotel_id')
                ->where('rooms.hotel_id = hotels.id');
            if ($roomType) {
                $subQuery->where('rooms.room_type', $roomType);
            }
            if ($minPrice) {
                $subQuery->where('rooms.price >=', $minPrice);
            }
            if ($maxPrice) {
                $subQuery->where('rooms.price <=', $maxPrice);
            }
            $builder->where("EXISTS (" . $subQuery->getCompiledSelect() . ")", null, false);
        }

        $hotels = $builder->findAll();

        // Append full image URL
        foreach ($hotels as &$hotel) {
            if (!empty($hotel['image'])) {
                $hotel['image_url'] = base_url($this->uploadPath . $hotel['image']);
            } else {
                $hotel['image_url'] = null;
            }
        }

        return $this->respond([
            'status'  => true,
            'count'   => count($hotels),
            'data'    => $hotels
        ]);
    }

    /**
     * GET /api/hotels/{id}
     */
    public function show($id = null)
    {
        $hotel = $this->model->find($id);
        if (!$hotel) {
            return $this->failNotFound('Hotel not found');
        }

        // Append image URL
        if (!empty($hotel['image'])) {
            $hotel['image_url'] = base_url($this->uploadPath . $hotel['image']);
        } else {
            $hotel['image_url'] = null;
        }

        // Fetch rooms for this hotel
        $roomModel = model('App\Models\RoomModel');
        $rooms = $roomModel->where('hotel_id', $id)->findAll();

        // Append room image URLs if needed
        foreach ($rooms as &$room) {
            if (!empty($room['image'])) {
                $room['image_url'] = base_url('uploads/rooms/' . $room['image']);
            }
        }

        return $this->respond([
            'status'  => true,
            'message' => 'Hotel retrieved successfully',
            'data'    => [
                'hotel' => $hotel,
                'rooms' => $rooms,
            ]
        ]);
    }

    /**
     * POST /api/admin/hotels
     * Create hotel with optional image upload
     */
    public function create()
{
    $data = $this->request->getJSON(true);
    if (empty($data)) {
        // fallback for form-data
        $data = $this->request->getPost();
        if (empty($data)) {
            $data = json_decode($this->request->getRawInput(), true) ?? [];
        }
    }

    $rules = [
        'name'    => 'required|min_length[3]',
        'address' => 'required',
        'city'    => 'required',
        'description' => 'permit_empty',
        'phone'   => 'permit_empty',
        'email'   => 'permit_empty|valid_email',
        'rating'  => 'permit_empty|decimal'
    ];

    if (!$this->validateData($data, $rules)) {
        return $this->respond([
            'status' => false,
            'errors' => $this->validator->getErrors()
        ], 422);
    }

    $imageName = null;
    $file = $this->request->getFile('image');

    if ($file && $file->isValid() && !$file->hasMoved()) {
        try {
            $uploadPath = FCPATH . 'uploads/hotels/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $newName = $file->getRandomName();
            if ($file->move($uploadPath, $newName)) {
                $imageName = $newName;
            } else {
                // Log the error
                log_message('error', 'Failed to move uploaded file');
            }
        } catch (\Exception $e) {
            log_message('error', 'File upload error: ' . $e->getMessage());
        }
    }

    $hotelData = [
        'name'        => $data['name'],
        'address'     => $data['address'],
        'city'        => $data['city'],
        'description' => $data['description'] ?? null,
        'phone'       => $data['phone'] ?? null,
        'email'       => $data['email'] ?? null,
        'rating'      => (float) ($data['rating'] ?? 0),
        'image'       => $imageName,
    ];

    $this->model->insert($hotelData);
    $id = $this->model->getInsertID();
    $newHotel = $this->model->find($id);
    if ($newHotel && !empty($newHotel['image'])) {
        $newHotel['image_url'] = base_url('uploads/hotels/' . $newHotel['image']);
    }

    return $this->respondCreated([
        'status' => true,
        'message' => 'Hotel created successfully',
        'data'    => $newHotel
    ]);
}

    /**
     * PUT /api/admin/hotels/{id}
     * Update hotel with optional image upload or removal
     */
    public function update($id = null)
    {
        $hotel = $this->model->find($id);
        if (!$hotel) {
            return $this->failNotFound('Hotel not found');
        }

        // Detect content type
        $contentType = $this->request->getHeaderLine('Content-Type');

        // For JSON requests
        if (strpos($contentType, 'application/json') !== false) {
            $data = $this->request->getJSON(true);
            if (empty($data)) {
                return $this->respond([
                    'status' => false,
                    'message' => 'Invalid JSON payload'
                ], 400);
            }
            // No file in JSON, keep existing image unless remove_image flag
            $imageName = $hotel['image'];
            if (isset($data['remove_image']) && $data['remove_image'] === true) {
                if (!empty($hotel['image']) && file_exists($this->uploadPath . $hotel['image'])) {
                    unlink($this->uploadPath . $hotel['image']);
                }
                $imageName = null;
            }
        } else {
            // For multipart/form-data
            $data = $this->request->getPost();
            if (empty($data)) {
                $raw = $this->request->getRawInput();
                if (!empty($raw)) {
                    $data = json_decode($raw, true) ?? [];
                }
            }

            // Handle file upload
            $imageName = $hotel['image']; // keep existing by default
            $file = $this->request->getFile('image');
            if ($file && $file->isValid() && !$file->hasMoved()) {
                // Delete old image
                if (!empty($hotel['image']) && file_exists($this->uploadPath . $hotel['image'])) {
                    unlink($this->uploadPath . $hotel['image']);
                }

                if (!in_array($file->getMimeType(), $this->allowedTypes)) {
                    return $this->respond([
                        'status' => false,
                        'message' => 'Invalid image type. Allowed: JPEG, PNG, WEBP, GIF'
                    ], 400);
                }
                if ($file->getSize() > $this->maxSize * 1024) {
                    return $this->respond([
                        'status' => false,
                        'message' => 'Image size exceeds ' . $this->maxSize . 'KB'
                    ], 400);
                }

                $newName = $file->getRandomName();
                if ($file->move($this->uploadPath, $newName)) {
                    $imageName = $newName;
                } else {
                    return $this->respond([
                        'status' => false,
                        'message' => 'Failed to move uploaded file'
                    ], 500);
                }
            } elseif (isset($data['remove_image']) && $data['remove_image'] === true) {
                if (!empty($hotel['image']) && file_exists($this->uploadPath . $hotel['image'])) {
                    unlink($this->uploadPath . $hotel['image']);
                }
                $imageName = null;
            }
        }

        // Validate data if provided
        $updateData = [
            'name'        => $data['name'] ?? $hotel['name'],
            'address'     => $data['address'] ?? $hotel['address'],
            'city'        => $data['city'] ?? $hotel['city'],
            'description' => $data['description'] ?? $hotel['description'],
            'phone'       => $data['phone'] ?? $hotel['phone'],
            'email'       => $data['email'] ?? $hotel['email'],
            'rating'      => $data['rating'] ?? $hotel['rating'],
            'image'       => $imageName,
        ];

        try {
            $this->model->update($id, $updateData);
            $updatedHotel = $this->model->find($id);
            if ($updatedHotel && !empty($updatedHotel['image'])) {
                $updatedHotel['image_url'] = base_url($this->uploadPath . $updatedHotel['image']);
            }
            return $this->respond([
                'status'  => true,
                'message' => 'Hotel updated successfully',
                'data'    => $updatedHotel
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/admin/hotels/{id}
     */
    public function delete($id = null)
    {
        $hotel = $this->model->find($id);
        if (!$hotel) {
            return $this->failNotFound('Hotel not found');
        }

        // Delete associated image if exists
        if (!empty($hotel['image']) && file_exists($this->uploadPath . $hotel['image'])) {
            unlink($this->uploadPath . $hotel['image']);
        }

        $this->model->delete($id);

        return $this->respond([
            'status' => true,
            'message' => 'Hotel deleted successfully'
        ]);
    }
}
