<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReviewsTable extends Migration
{
public function up()
{
    $this->forge->addField([
        'id' => [
            'type' => 'BIGSERIAL',
        ],

        'user_id' => [
            'type' => 'BIGINT',
        ],

        'hotel_id' => [
            'type' => 'BIGINT',
        ],

        'rating' => [
            'type' => 'INT',
            'constraint' => 1,
        ],

        'review' => [
            'type' => 'TEXT',
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
    $this->forge->addKey('user_id');
    $this->forge->addKey('hotel_id');

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

    $this->forge->createTable('reviews');
}

public function down()
{
    $this->forge->dropTable('reviews', true);
}
}
