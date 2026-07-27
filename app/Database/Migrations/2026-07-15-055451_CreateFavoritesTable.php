<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFavoritesTable extends Migration
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

        'created_at' => [
            'type' => 'TIMESTAMP',
            'null' => true,
        ],
    ]);

    $this->forge->addKey('id', true);

    $this->forge->addUniqueKey(['user_id', 'hotel_id']);

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

    $this->forge->createTable('favorites');
}

public function down()
{
    $this->forge->dropTable('favorites', true);
}
}
