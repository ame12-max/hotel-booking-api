<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificationsTable extends Migration
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

        'title' => [
            'type' => 'VARCHAR',
            'constraint' => 255,
        ],

        'message' => [
            'type' => 'TEXT',
        ],

        'status' => [
            'type' => 'VARCHAR',
            'constraint' => 20,
            'default' => 'unread',
        ],

        'created_at' => [
            'type' => 'TIMESTAMP',
            'null' => true,
        ],
    ]);

    $this->forge->addKey('id', true);
    $this->forge->addKey('user_id');

    $this->forge->addForeignKey(
        'user_id',
        'users',
        'id',
        'CASCADE',
        'CASCADE'
    );

    $this->forge->createTable('notifications');
}

public function down()
{
    $this->forge->dropTable('notifications', true);
}
}
