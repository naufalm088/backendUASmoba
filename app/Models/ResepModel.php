<?php
namespace App\Models;
use CodeIgniter\Model;

class ResepModel extends Model
{ // <-- Pembuka class
    protected $table            = 'resep'; 
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array'; 
    
    // Pastikan array ditutup di sini 
    protected $allowedFields    = ['title', 'kategori', 'description', 'image_url', 'rating', 'steps', 'time', 'difficulty', 'user_id']; 

} // <-- 💡 Tanda kurung kurawal penutup class harus ada di sini