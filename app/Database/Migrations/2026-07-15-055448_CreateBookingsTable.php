<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingsTable extends Migration
{
public function up()
{
    $this->forge->addField([
        'id' => [
            'type' => 'BIGSERIAL',
        ],

        'booking_number' => [
            'type' => 'VARCHAR',
            'constraint' => 50,
        ],

        'user_id' => [
            'type' => 'BIGINT',
        ],

        'hotel_id' => [
            'type' => 'BIGINT',
        ],

        'room_id' => [
            'type' => 'BIGINT',
        ],

        'check_in' => [
            'type' => 'DATE',
        ],

        'check_out' => [
            'type' => 'DATE',
        ],

        'guests' => [
            'type' => 'INT',
        ],

        'total_price' => [
            'type' => 'DECIMAL',
            'constraint' => '10,2',
        ],

        'status' => [
            'type' => 'VARCHAR',
            'constraint' => 20,
            'default' => 'pending',
        ],

        'created_at' => [
            'type' => 'TIMESTAMP',
            'null' => true,
        ],

        'updated_at' => [
            'type' => 'TIMESTAMP',
            'null' => true,
        ],

        'deleted_at' => [
            'type' => 'TIMESTAMP',
            'null' => true,
        ],
    ]);

    $this->forge->addKey('id', true);

    $this->forge->addUniqueKey('booking_number');

    $this->forge->addKey('user_id');
    $this->forge->addKey('hotel_id');
    $this->forge->addKey('room_id');

    $this->forge->addForeignKey(
        'user_id',
        'users',
        'id',
        'CASCADE',
        'CASCADE'
    );

    $this->forge->addForeignKey(
        'hotel_id',
        'hotels',
        'id',
        'CASCADE',
        'CASCADE'
    );

    $this->forge->addForeignKey(
        'room_id',
        'rooms',
        'id',
        'CASCADE',
        'CASCADE'
    );

    $this->forge->createTable('bookings');
}

public function down()
{
    $this->forge->dropTable('bookings', true);
}
}
