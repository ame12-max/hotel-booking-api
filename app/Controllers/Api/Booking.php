<?php

namespace App\Controllers\Api;

use App\Models\BookingModel;
use App\Models\RoomModel;
use App\Models\HotelModel;
use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class Booking extends ResourceController
{
    protected $modelName = BookingModel::class;
    protected $format = 'json';

    /**
     * GET /api/bookings
     *
     * Customer -> own bookings only
     * Admin -> all bookings
     */
    public function index()
    {
        $user = service('request')->user;

        if ($user->role === 'admin') {
            $bookings = $this->model
                ->orderBy('created_at', 'DESC')
                ->findAll();
        } else {
            $bookings = $this->model
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'DESC')
                ->findAll();
        }

        return $this->respond([
            'status' => true,
            'data' => $bookings
        ]);
    }

    /**
     * GET /api/bookings/{id}
     */
    public function show($id = null)
    {
        $user = service('request')->user;

        $booking = $this->model->find($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        // Customers can only view their own bookings
        if (
            $user->role !== 'admin' &&
            $booking['user_id'] != $user->id
        ) {
            return $this->failForbidden(
                'You do not have permission to view this booking'
            );
        }

        return $this->respond([
            'status' => true,
            'data' => $booking
        ]);
    }

    /**
     * POST /api/bookings
     * Public route - no authentication required
     */
    public function create()
    {
        // Get the JSON data from request
        $data = $this->request->getJSON(true);

        // Validation rules
        $rules = [
            'hotel_id'      => 'required|integer',
            'room_id'       => 'required|integer',
            'check_in'      => 'required|valid_date',
            'check_out'     => 'required|valid_date',
            'guests'        => 'required|integer|greater_than[0]',
            'guest_name'    => 'required|min_length[2]|max_length[100]',
            'guest_email'   => 'required|valid_email|max_length[100]',
            'guest_phone'   => 'required|min_length[10]|max_length[20]',
            'special_requests' => 'permit_empty|max_length[500]'
        ];

        // Validate the data
        if (!$this->validateData($data, $rules)) {
            return $this->respond([
                'status' => false,
                'errors' => $this->validator->getErrors()
            ], 422);
        }

        // Load models
        $hotelModel = new HotelModel();
        $roomModel = new RoomModel();
        $userModel = new UserModel();

        // Check if hotel exists
        $hotel = $hotelModel->find($data['hotel_id']);
        if (!$hotel) {
            return $this->failNotFound('Hotel not found');
        }

        // Check if room exists
        $room = $roomModel->find($data['room_id']);
        if (!$room) {
            return $this->failNotFound('Room not found');
        }

        // Verify room belongs to hotel
        if ($room['hotel_id'] != $data['hotel_id']) {
            return $this->respond([
                'status' => false,
                'message' => 'Room does not belong to selected hotel'
            ], 422);
        }

        // Check room capacity
        if ($data['guests'] > $room['capacity']) {
            return $this->respond([
                'status' => false,
                'message' => 'Guest count exceeds room capacity (Maximum: ' . $room['capacity'] . ' guests)'
            ], 422);
        }

        // Validate dates
        $checkIn = new \DateTime($data['check_in']);
        $checkOut = new \DateTime($data['check_out']);
        $today = new \DateTime();

        // Check if check-in is in the past
        if ($checkIn < $today->setTime(0, 0, 0)) {
            return $this->respond([
                'status' => false,
                'message' => 'Check-in date cannot be in the past'
            ], 422);
        }

        // Calculate nights
        $nights = $checkIn->diff($checkOut)->days;
        if ($nights <= 0) {
            return $this->respond([
                'status' => false,
                'message' => 'Check-out date must be after check-in date'
            ], 422);
        }

        // Check for overlapping bookings
        $conflict = $this->model
            ->where('room_id', $data['room_id'])
            ->where('status !=', 'cancelled')
            ->groupStart()
                ->where('check_in <', $data['check_out'])
                ->where('check_out >', $data['check_in'])
            ->groupEnd()
            ->first();

        if ($conflict) {
            return $this->respond([
                'status' => false,
                'message' => 'Room is already booked for the selected dates. Please choose different dates.'
            ], 409);
        }

        // Calculate total price
        $totalPrice = $nights * $room['price'];

        // Generate unique booking number
        $bookingNumber = 'HB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        // Find or create user based on email
        $userId = $this->findOrCreateUser(
            $data['guest_email'],
            $data['guest_name'],
            $data['guest_phone'] ?? null
        );

        // Prepare booking data
        $bookingData = [
            'booking_number' => $bookingNumber,
            'user_id'        => $userId,
            'hotel_id'       => $data['hotel_id'],
            'room_id'        => $data['room_id'],
            'check_in'       => $data['check_in'],
            'check_out'      => $data['check_out'],
            'guests'         => $data['guests'],
            'total_price'    => $totalPrice,
            'status'         => 'pending',
            'special_requests' => $data['special_requests'] ?? null,
            'guest_name'     => $data['guest_name'],
            'guest_email'    => $data['guest_email'],
            'guest_phone'    => $data['guest_phone'] ?? null
        ];

        // Insert booking
        $bookingId = $this->model->insert($bookingData);

        if (!$bookingId) {
            return $this->respond([
                'status' => false,
                'message' => 'Failed to create booking. Please try again.'
            ], 500);
        }

        // Return success response
        return $this->respondCreated([
            'status' => true,
            'message' => 'Booking created successfully!',
            'data' => [
                'id' => $bookingId,
                'booking_number' => $bookingNumber,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'nights' => $nights,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email']
            ]
        ]);
    }

    /**
     * PATCH /api/bookings/{id}/cancel
     */
    public function cancel($id = null)
    {
        $user = service('request')->user;

        $booking = $this->model->find($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        if (
            $user->role !== 'admin' &&
            $booking['user_id'] != $user->id
        ) {
            return $this->failForbidden(
                'You do not have permission to cancel this booking'
            );
        }

        if ($booking['status'] === 'cancelled') {
            return $this->respond([
                'status' => false,
                'message' => 'Booking already cancelled'
            ], 400);
        }

        // Check if check-in date has passed
        $checkIn = new \DateTime($booking['check_in']);
        $today = new \DateTime();
        if ($checkIn < $today) {
            return $this->respond([
                'status' => false,
                'message' => 'Cannot cancel a booking that has already started or passed'
            ], 400);
        }

        $this->model->update($id, [
            'status' => 'cancelled'
        ]);

        return $this->respond([
            'status' => true,
            'message' => 'Booking cancelled successfully'
        ]);
    }

    /**
     * DELETE /api/bookings/{id}
     *
     * Admin only permanent delete
     */
    public function delete($id = null)
    {
        $user = service('request')->user;

        if ($user->role !== 'admin') {
            return $this->failForbidden(
                'Only admins can delete bookings'
            );
        }

        $booking = $this->model->find($id);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        $this->model->delete($id);

        return $this->respond([
            'status' => true,
            'message' => 'Booking deleted successfully'
        ]);
    }

    /**
     * Helper method to find or create a user
     * 
     * @param string $email
     * @param string $name
     * @param string|null $phone
     * @return int User ID
     */
    private function findOrCreateUser($email, $name, $phone = null)
    {
        $userModel = new UserModel();
        
        // Check if user already exists
        $existingUser = $userModel->where('email', $email)->first();
        
        if ($existingUser) {
            // Update user info if needed
            $updateData = [];
            if ($existingUser['full_name'] !== $name) {
                $updateData['full_name'] = $name;
            }
            if ($phone && $existingUser['phone'] !== $phone) {
                $updateData['phone'] = $phone;
            }
            
            if (!empty($updateData)) {
                $userModel->update($existingUser['id'], $updateData);
            }
            
            return $existingUser['id'];
        }
        
        // Create new user (guest)
        $password = bin2hex(random_bytes(8)); // Generate random password
        
        $userData = [
            'full_name'  => $name,        // Changed from 'name' to 'full_name'
            'email'      => $email,
            'phone'      => $phone,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'role'       => 'customer',
            'is_active'  => true
        ];
        
        $userId = $userModel->insert($userData);
        
        // You might want to send an email with the password here
        // or trigger a welcome email event
        
        return $userId;
    }
}