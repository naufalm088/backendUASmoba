<?php
namespace App\Models;

use CodeIgniter\Model;

class SavedRecipeModel extends Model
{
    protected $table = 'saved_recipes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'recipe_id'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
