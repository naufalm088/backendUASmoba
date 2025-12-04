<?php
namespace App\Models;
use CodeIgniter\Model;

class RecipeModel extends Model {
    protected $table = 'recipes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id','title','kategori','description','ingredients',
        'steps','time','difficulty','image'
    ];
}
