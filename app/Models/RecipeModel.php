<?php
namespace App\Models;
use CodeIgniter\Model;

class RecipeModel extends Model {
    protected $table = 'recipes';
    protected $allowedFields = [
        'user_id','title','kategori','description','ingredients',
        'steps','time','difficulty','image'
    ];
}
