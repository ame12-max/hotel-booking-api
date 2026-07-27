<?php

namespace App\Controllers\Api;

use App\Models\ReviewModel;
use App\Models\BookingModel;
use CodeIgniter\RESTful\ResourceController;

class Review extends ResourceController
{
    protected $modelName = ReviewModel::class;
    protected $format = 'json';

    public function index()
    {
        $hotelId = $this->request->getGet('hotel_id');

        $builder = $this->model;

        if ($hotelId) {
            $builder->where('hotel_id', $hotelId);
        }

        return $this->respond([
            'status' => true,
            'data' => $builder->findAll()
        ]);
    }

    public function show($id = null)
    {
        $review = $this->model->find($id);

        if (!$review) {
            return $this->failNotFound('Review not found');
        }

        return $this->respond([
            'status' => true,
            'data' => $review
        ]);
    }

    public function create()
    {
        $user = service('request')->user;

        $data = $this->request->getJSON(true);

        $rules = [
            'hotel_id' => 'required|integer',
            'rating' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
            'review' => 'required|min_length[5]'
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->respond([
                'status' => false,
                'errors' => $this->validator->getErrors()
            ], 422);
        }

        $bookingModel = new BookingModel();

        $booking = $bookingModel
            ->where('user_id', $user->id)
            ->where('hotel_id', $data['hotel_id'])
            ->first();

        if (!$booking) {
            return $this->failForbidden(
                'You can only review hotels you have booked.'
            );
        }

        $existingReview = $this->model
            ->where('user_id', $user->id)
            ->where('hotel_id', $data['hotel_id'])
            ->first();

        if ($existingReview) {
            return $this->respond([
                'status' => false,
                'message' => 'You already reviewed this hotel.'
            ], 409);
        }

        $reviewId = $this->model->insert([
            'user_id' => $user->id,
            'hotel_id' => $data['hotel_id'],
            'rating' => $data['rating'],
            'review' => $data['review']
        ]);

        return $this->respondCreated([
            'status' => true,
            'message' => 'Review created successfully',
            'review_id' => $reviewId
        ]);
    }

    public function update($id = null)
    {
        $user = service('request')->user;

        $review = $this->model->find($id);

        if (!$review) {
            return $this->failNotFound('Review not found');
        }

        if ($review['user_id'] != $user->id) {
            return $this->failForbidden(
                'You can only update your own reviews.'
            );
        }

        $data = $this->request->getJSON(true);

        $rules = [
            'rating' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
            'review' => 'permit_empty|min_length[5]'
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->respond([
                'status' => false,
                'errors' => $this->validator->getErrors()
            ], 422);
        }

        $this->model->update($id, $data);

        return $this->respond([
            'status' => true,
            'message' => 'Review updated successfully'
        ]);
    }

    public function delete($id = null)
    {
        $user = service('request')->user;

        $review = $this->model->find($id);

        if (!$review) {
            return $this->failNotFound('Review not found');
        }

        if (
            $user->role !== 'admin' &&
            $review['user_id'] != $user->id
        ) {
            return $this->failForbidden(
                'You can only delete your own reviews.'
            );
        }

        $this->model->delete($id);

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Review deleted successfully'
        ]);
    }
}