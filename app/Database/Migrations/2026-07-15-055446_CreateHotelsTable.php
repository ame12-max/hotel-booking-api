<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHotelsTable extends Migration
{
public function up()
{
    $this->forge->addField([
        'id' => [
            'type' => 'BIGSERIAL',
        ],

        'name' => [
            'type' => 'VARCHAR',
            'constraint' => 200,
        ],

        'address' => [
            'type' => 'TEXT',
        ],

        'city' => [
            'type' => 'VARCHAR',
            'constraint' => 100,
        ],

        'description' => [
            'type' => 'TEXT',
            'null' => true,
        ],

        'phone' => [
            'type' => 'VARCHAR',
            'constraint' => 20,
            'null' => true,
        ],

        'email' => [
            'type' => 'VARCHAR',
            'constraint' => 150,
            'null' => true,
        ],

        'rating' => [
            'type' => 'NUMERIC',
            'constraint' => '2,1',
            'default' => 0,
        ],

        'image' => [
            'type' => 'VARCHAR',
            'constraint' => 255,
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

    $this->forge->createTable('hotels');
}

public function down()
{
    $this->forge->dropTable('hotels');
}
}
