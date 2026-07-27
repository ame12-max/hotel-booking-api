<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
  public function up()
{
    $this->forge->addField([
        'id' => [
            'type' => 'BIGSERIAL',
        ],

        'full_name' => [
            'type' => 'VARCHAR',
            'constraint' => 150,
        ],

        'email' => [
            'type' => 'VARCHAR',
            'constraint' => 150,
        ],

        'phone' => [
            'type' => 'VARCHAR',
            'constraint' => 20,
        ],

        'password' => [
            'type' => 'VARCHAR',
            'constraint' => 255,
        ],

        'role' => [
            'type' => 'VARCHAR',
            'constraint' => 20,
            'default' => 'customer',
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
    $this->forge->addUniqueKey('email');
    $this->forge->addUniqueKey('phone');

    $this->forge->createTable('users');
}

public function down()
{
    $this->forge->dropTable('users');
}

}
