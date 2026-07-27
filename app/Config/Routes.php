<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

$routes->group('api', function ($routes) {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    $routes->post('register', 'Api\Auth::register');
    $routes->post('login', 'Api\Auth::login');

    /*
    |--------------------------------------------------------------------------
    | Hotels (Public)
    |--------------------------------------------------------------------------
    */

    $routes->get('hotels', 'Api\Hotel::index');
    $routes->get('hotels/(:num)', 'Api\Hotel::show/$1');

    /*
    |--------------------------------------------------------------------------
    | Rooms (Public)
    |--------------------------------------------------------------------------
    */

    $routes->get('rooms', 'Api\Room::index');
    $routes->get('rooms/(:num)', 'Api\Room::show/$1');
    $routes->post('bookings', 'Api\Booking::create');


    /*
    |--------------------------------------------------------------------------
    | Reviews (Public)
    |--------------------------------------------------------------------------
    */

    $routes->get('reviews', 'Api\Review::index');
    $routes->get('reviews/(:num)', 'Api\Review::show/$1');
});


/*
|--------------------------------------------------------------------------
| CUSTOMER + ADMIN ROUTES
|--------------------------------------------------------------------------
*/

$routes->group('api', ['filter' => 'jwt'], function ($routes) {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    $routes->post('logout', 'Api\Auth::logout');

    /*
    |--------------------------------------------------------------------------
    | Bookings
    |--------------------------------------------------------------------------
    */

    $routes->get('bookings', 'Api\Booking::index');
    $routes->get('bookings/(:num)', 'Api\Booking::show/$1');

    // Customer cancel booking
    $routes->patch('bookings/(:num)/cancel', 'Api\Booking::cancel/$1');

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */

    $routes->post('payments', 'Api\Payment::create');
    $routes->get('payments', 'Api\Payment::index');
    $routes->get('payments/(:num)', 'Api\Payment::show/$1');

    /*
    |--------------------------------------------------------------------------
    | Reviews
    |--------------------------------------------------------------------------
    */

    $routes->post('reviews', 'Api\Review::create');
    $routes->put('reviews/(:num)', 'Api\Review::update/$1');
    $routes->delete('reviews/(:num)', 'Api\Review::delete/$1');

    /*
    |--------------------------------------------------------------------------
    | Favorites
    |--------------------------------------------------------------------------
    */

    $routes->post('favorites', 'Api\Favorite::create');
    $routes->get('favorites', 'Api\Favorite::index');
    $routes->delete('favorites/(:num)', 'Api\Favorite::delete/$1');

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    $routes->get('notifications', 'Api\Notification::index');
    $routes->patch(
        'notifications/(:num)/read',
        'Api\Notification::markAsRead/$1'
    );
});


/*
|--------------------------------------------------------------------------
| ADMIN ONLY ROUTES
|--------------------------------------------------------------------------
*/

$routes->group('api/admin', [
    'filter' => ['jwt', 'admin']

    
], function ($routes) {

    /*
    |--------------------------------------------------------------------------
    | Hotels
    |--------------------------------------------------------------------------
    */
    $routes->get(
        'dashboard',
        'Api\Admin\Dashboard::index'
    );
    $routes->post('hotels', 'Api\Hotel::create');
    $routes->put('hotels/(:num)', 'Api\Hotel::update/$1');
    $routes->delete('hotels/(:num)', 'Api\Hotel::delete/$1');

    /*
    |--------------------------------------------------------------------------
    | Rooms
    |--------------------------------------------------------------------------
    */

    $routes->post('rooms', 'Api\Room::create');
    $routes->put('rooms/(:num)', 'Api\Room::update/$1');
    $routes->delete('rooms/(:num)', 'Api\Room::delete/$1');

    /*
    |--------------------------------------------------------------------------
    | Booking Administration
    |--------------------------------------------------------------------------
    */

    $routes->delete('bookings/(:num)', 'Api\Booking::delete/$1');

    /*
    |--------------------------------------------------------------------------
    | Payment Administration
    |--------------------------------------------------------------------------
    */

    $routes->patch(
        'payments/(:num)/confirm',
        'Api\Payment::confirm/$1'
    );

    $routes->patch(
        'payments/(:num)/reject',
        'Api\Payment::reject/$1'
    );
});