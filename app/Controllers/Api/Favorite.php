<?php

namespace App\Controllers\Api;

use App\Models\FavoriteModel;
use App\Models\HotelModel;
use CodeIgniter\RESTful\ResourceController;

class Favorite extends ResourceController
{
    protected $modelName = FavoriteModel::class;
    protected $format = 'json';

    public function index()
    {
        $user = service('request')->user;

        $favorites = $this->model
            ->select('favorites.*, hotels.name, hotels.city, hotels.image, hotels.rating')
            ->join('hotels', 'hotels.id = favorites.hotel_id')
            ->where('favorites.user_id', $user->id)
            ->findAll();

        return $this->respond([
            'status' => true,
            'data' => $favorites
        ]);
    }

    public function create()
    {
        $user = service('request')->user;

        $data = $this->request->getJSON(true);

        $rules = [
            'hotel_id' => 'required|integer'
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->respond([
                'status' => false,
                'errors' => $this->validator->getErrors()
            ], 422);
        }

        $hotelModel = new HotelModel();

        $hotel = $hotelModel->find($data['hotel_id']);

        if (!$hotel) {
            return $this->failNotFound('Hotel not found');
        }

        $existing = $this->model
            ->where('user_id', $user->id)
            ->where('hotel_id', $data['hotel_id'])
            ->first();

        if ($existing) {
            return $this->respond([
                'status' => false,
                'message' => 'Hotel already added to favorites.'
            ], 409);
        }

        $favoriteId = $this->model->insert([
            'user_id' => $user->id,
            'hotel_id' => $data['hotel_id']
        ]);

        return $this->respondCreated([
            'status' => true,
            'message' => 'Hotel added to favorites successfully.',
            'favorite_id' => $favoriteId
        ]);
    }

    public function delete($id = null)
    {
        $user = service('request')->user;

        $favorite = $this->model->find($id);

        if (!$favorite) {
            return $this->failNotFound('Favorite not found');
        }

        if ($favorite['user_id'] != $user->id) {
            return $this->failForbidden(
                'You can only remove your own favorites.'
            );
        }

        $this->model->delete($id);

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Favorite removed successfully.'
        ]);
    }
}