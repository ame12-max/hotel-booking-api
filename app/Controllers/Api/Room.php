<?php

namespace App\Controllers\Api;

use App\Models\RoomModel;
use App\Models\HotelModel;
use CodeIgniter\RESTful\ResourceController;

class Room extends ResourceController
{
    protected $modelName = RoomModel::class;
    protected $format    = 'json';

    /**
     * GET /api/rooms
     */
    public function index()
    {
        $hotelId = $this->request->getGet('hotel_id');
        $roomType = $this->request->getGet('room_type');
        $status = $this->request->getGet('status');
        $minPrice = $this->request->getGet('min_price');
        $maxPrice = $this->request->getGet('max_price');

        $builder = $this->model;

        if ($hotelId) {
            $builder->where('hotel_id', $hotelId);
        }

        if ($roomType) {
            $builder->where('room_type', $roomType);
        }

        if ($status) {
            $builder->where('status', $status);
        }

        if ($minPrice) {
            $builder->where('price >=', $minPrice);
        }

        if ($maxPrice) {
            $builder->where('price <=', $maxPrice);
        }

        $rooms = $builder->findAll();

        return $this->respond([
            'status' => true,
            'data' => $rooms
        ]);
    }

    /**
     * GET /api/rooms/{id}
     */
    public function show($id = null)
    {
        $room = $this->model->find($id);

        if (!$room) {
            return $this->failNotFound('Room not found');
        }

        return $this->respond([
            'status' => true,
            'data' => $room
        ]);
    }

    /**
     * POST /api/admin/rooms
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        $hotelModel = new HotelModel();

        if (!$hotelModel->find($data['hotel_id'])) {
            return $this->failNotFound('Hotel not found');
        }

        $rules = [
            'hotel_id' => 'required',
            'room_number' => 'required',
            'room_type' => 'required',
            'capacity' => 'required|integer',
            'price' => 'required|decimal'
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->respond([
                'status' => false,
                'errors' => $this->validator->getErrors()
            ], 422);
        }

        $roomData = [
            'hotel_id' => $data['hotel_id'],
            'room_number' => $data['room_number'],
            'room_type' => $data['room_type'],
            'capacity' => $data['capacity'],
            'price' => $data['price'],
            'status' => $data['status'] ?? 'available',
            'description' => $data['description'] ?? null,
        ];

        $this->model->insert($roomData);

        return $this->respondCreated([
            'status' => true,
            'message' => 'Room created successfully'
        ]);
    }

    /**
     * PUT /api/admin/rooms/{id}
     */
    public function update($id = null)
    {
        $room = $this->model->find($id);

        if (!$room) {
            return $this->failNotFound('Room not found');
        }

        $data = $this->request->getJSON(true);

        $this->model->update($id, $data);

        return $this->respond([
            'status' => true,
            'message' => 'Room updated successfully'
        ]);
    }

    /**
     * DELETE /api/admin/rooms/{id}
     */
    public function delete($id = null)
    {
        $room = $this->model->find($id);

        if (!$room) {
            return $this->failNotFound('Room not found');
        }

        $this->model->delete($id);

        return $this->respond([
            'status' => true,
            'message' => 'Room deleted successfully'
        ]);
    }
}