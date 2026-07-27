<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoomsTable extends Migration
{
 public function up()
{
    $this->forge->addField([
        'id' => [
            'type' => 'BIGSERIAL',
        ],

        'hotel_id' => [
            'type' => 'BIGINT',
        ],

        'room_number' => [
            'type' => 'VARCHAR',
            'constraint' => 50,
        ],

        'room_type' => [
            'type' => 'VARCHAR',
            'constraint' => 50,
        ],

        'capacity' => [
            'type' => 'INT',
        ],

        'price' => [
            'type' => 'DECIMAL',
            'constraint' => '10,2',
        ],

        'status' => [
            'type' => 'VARCHAR',
            'constraint' => 20,
            'default' => 'available',
        ],

        'description' => [
            'type' => 'TEXT',
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

        'deleted_at' => [
            'type' => 'TIMESTAMP',
            'null' => true,
        ],
    ]);

    $this->forge->addKey('id', true);
    $this->forge->addKey('hotel_id');

    $this->forge->addForeignKey(
        'hotel_id',
        'hotels',
        'id',
        'CASCADE',
        'CASCADE'
    );

    $this->forge->createTable('rooms');
}

    public function down()
{
    $this->forge->dropTable('rooms', true);
}
}
