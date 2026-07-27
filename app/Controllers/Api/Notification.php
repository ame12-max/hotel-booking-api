<?php

namespace App\Controllers\Api;

use App\Models\NotificationModel;
use CodeIgniter\RESTful\ResourceController;

class Notification extends ResourceController
{
    protected $modelName = NotificationModel::class;
    protected $format = 'json';

    public function index()
    {
        $user = service('request')->user;

        $notifications = $this->model
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => true,
            'data' => $notifications
        ]);
    }

    public function markAsRead($id = null)
    {
        $user = service('request')->user;

        $notification = $this->model->find($id);

        if (!$notification) {
            return $this->failNotFound('Notification not found');
        }

        if ($notification['user_id'] != $user->id) {
            return $this->failForbidden(
                'You cannot access this notification'
            );
        }

        $this->model->update($id, [
            'status' => 'read'
        ]);

        return $this->respond([
            'status' => true,
            'message' => 'Notification marked as read'
        ]);
    }
}