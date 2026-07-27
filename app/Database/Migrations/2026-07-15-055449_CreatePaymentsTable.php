<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentsTable extends Migration
{
public function up()
{
    $this->forge->addField([
        'id' => [
            'type' => 'BIGSERIAL',
        ],

        'booking_id' => [
            'type' => 'BIGINT',
        ],

        'payment_method' => [
            'type' => 'VARCHAR',
            'constraint' => 50,
        ],

        'amount' => [
            'type' => 'DECIMAL',
            'constraint' => '10,2',
        ],

        'payment_status' => [
            'type' => 'VARCHAR',
            'constraint' => 20,
            'default' => 'pending',
        ],

        'payment_date' => [
            'type' => 'TIMESTAMP',
            'null' => true,
        ],

        'created_at' => [
            'type' => 'TIMESTAMP',
            'null' => true,
        ],

        'updated_at' => [
            'type' => 'TIMESTAMP',
            'null' => true,
        ],
    ]);

    $this->forge->addKey('id', true);
    $this->forge->addKey('booking_id');

    $this->forge->addForeignKey(
        'booking_id',
        'bookings',
        'id',
        'CASCADE',
        'CASCADE'
    );

    $this->forge->createTable('payments');
}

public function down()
{
    $this->forge->dropTable('payments', true);
}
}
