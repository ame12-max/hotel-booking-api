<?php

namespace App\Controllers\Api\Admin;

use CodeIgniter\RESTful\ResourceController;

class Dashboard extends ResourceController
{
    protected $format = 'json';


    public function index()
    {

        $db = \Config\Database::connect();


        /*
        |--------------------------------------------------------------------------
        | Counts
        |--------------------------------------------------------------------------
        */


        $totalHotels = $db
            ->table('hotels')
            ->countAllResults();


        $totalRooms = $db
            ->table('rooms')
            ->countAllResults();


        $totalCustomers = $db
            ->table('users')
            ->where('role', 'customer')
            ->countAllResults();


        $totalBookings = $db
            ->table('bookings')
            ->countAllResults();



        /*
        |--------------------------------------------------------------------------
        | Booking Statistics
        |--------------------------------------------------------------------------
        */


        $bookingStats = [];


        $statuses = [
            'pending',
            'confirmed',
            'cancelled',
            'completed'
        ];


        foreach ($statuses as $status) {

            $bookingStats[$status] =
                $db
                ->table('bookings')
                ->where('status', $status)
                ->countAllResults();

        }



        /*
        |--------------------------------------------------------------------------
        | Recent Bookings
        |--------------------------------------------------------------------------
        */


        $recentBookings = $db
            ->table('bookings')
            ->select(
                '
                bookings.booking_number,
                bookings.status,
                bookings.total_price,
                bookings.check_in,
                bookings.check_out,

                users.full_name AS customer,

                hotels.name AS hotel,

                rooms.room_number

                '
            )

            ->join(
                'users',
                'users.id = bookings.user_id'
            )

            ->join(
                'hotels',
                'hotels.id = bookings.hotel_id'
            )

            ->join(
                'rooms',
                'rooms.id = bookings.room_id'
            )

            ->orderBy(
                'bookings.created_at',
                'DESC'
            )

            ->limit(10)

            ->get()

            ->getResultArray();



        return $this->respond([

            'status'=>true,

            'data'=>[


                'statistics'=>[

                    'total_hotels'=>$totalHotels,

                    'total_rooms'=>$totalRooms,

                    'total_customers'=>$totalCustomers,

                    'total_bookings'=>$totalBookings

                ],


                'booking_statistics'=>$bookingStats,


                'recent_bookings'=>$recentBookings


            ]

        ]);

    }

}