<?php

namespace App\Controllers\Api;

use App\Models\PaymentModel;
use App\Models\BookingModel;
use CodeIgniter\RESTful\ResourceController;

class Payment extends ResourceController
{
    protected $modelName = PaymentModel::class;
    protected $format = 'json';

    public function index()
    {
        $user = service('request')->user;

        $builder = $this->model
            ->select('payments.*')
            ->join('bookings', 'bookings.id = payments.booking_id');

        if ($user->role !== 'admin') {
            $builder->where('bookings.user_id', $user->id);
        }

        return $this->respond([
            'status' => true,
            'data' => $builder->findAll()
        ]);
    }

    public function show($id = null)
    {
        $user = service('request')->user;

        $payment = $this->model
            ->select('payments.*, bookings.user_id')
            ->join('bookings', 'bookings.id = payments.booking_id')
            ->where('payments.id', $id)
            ->first();

        if (!$payment) {
            return $this->failNotFound('Payment not found');
        }

        if (
            $user->role !== 'admin' &&
            $payment['user_id'] != $user->id
        ) {
            return $this->failForbidden(
                'You cannot access this payment'
            );
        }

        return $this->respond([
            'status' => true,
            'data' => $payment
        ]);
    }

    public function create()
    {
        $user = service('request')->user;

        $data = $this->request->getJSON(true);

        $rules = [
            'booking_id' => 'required|integer',
            'payment_method' => 'required|in_list[cash,telebirr,cbe_birr]'
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->respond([
                'status' => false,
                'errors' => $this->validator->getErrors()
            ], 422);
        }

        $bookingModel = new BookingModel();

        $booking = $bookingModel->find($data['booking_id']);

        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        if (
            $user->role !== 'admin' &&
            $booking['user_id'] != $user->id
        ) {
            return $this->failForbidden(
                'This booking does not belong to you'
            );
        }

        $paymentId = $this->model->insert([
            'booking_id' => $booking['id'],
            'payment_method' => $data['payment_method'],
            'amount' => $booking['total_price'],
            'payment_status' => 'pending',
            'payment_date' => date('Y-m-d H:i:s')
        ]);

        return $this->respondCreated([
            'status' => true,
            'message' => 'Payment created successfully',
            'data' => [
                'payment_id' => $paymentId,
                'amount' => $booking['total_price'],
                'payment_status' => 'pending'
            ]
        ]);
    }

    public function confirm($id = null)
    {
        $payment = $this->model->find($id);

        if (!$payment) {
            return $this->failNotFound('Payment not found');
        }

        $this->model->update($id, [
            'payment_status' => 'completed'
        ]);

        $bookingModel = new BookingModel();

        $bookingModel->update(
            $payment['booking_id'],
            [
                'status' => 'confirmed'
            ]
        );

        return $this->respond([
            'status' => true,
            'message' => 'Payment confirmed successfully'
        ]);
    }

    public function reject($id = null)
    {
        $payment = $this->model->find($id);

        if (!$payment) {
            return $this->failNotFound('Payment not found');
        }

        $this->model->update($id, [
            'payment_status' => 'failed'
        ]);

        $bookingModel = new BookingModel();

        $bookingModel->update(
            $payment['booking_id'],
            [
                'status' => 'cancelled'
            ]
        );

        return $this->respond([
            'status' => true,
            'message' => 'Payment rejected successfully'
        ]);
    }
}