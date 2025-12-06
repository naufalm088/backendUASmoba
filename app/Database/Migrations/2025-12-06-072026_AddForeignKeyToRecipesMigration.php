<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddForeignKeyToRecipesMigration extends Migration
{
    public function up()
    {
        $this -> forge ->addForeignKey('user_id', 'member', 'id', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('recipes');
    }

    public function down()
    {
        $this->forge->dropForeignKey('recipes', 'recipes_user_id_foreign');
    }
}
