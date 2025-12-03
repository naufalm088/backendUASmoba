<?php
namespace App\Controllers;
use App\Models\RecipeModel;
use CodeIgniter\RESTful\ResourceController;

class ResepController extends ResourceController
{
   protected $modelName = 'App\Models\RecipeModel';
    /**
     * @var \CodeIgniter\HTTP\IncomingRequest $request
     */
    protected $request;

     public function index()
    {
        return $this->respond([
            'status' => true,
            'data' => $this->model->findAll()
        ]);
    }


    public function create()
    {
        $resep = new RecipeModel();
           $image = $this->request->getFile('image');
        $imageName = null;

        $image = $this->request->getFile('image');
        if($image && $image->isValid()) {
            $imageName = $image->getRandomName();
            $image->move('uploads/recipes/', $imageName);
        $imagePath = 'uploads/recipes/' . $imageName;

        if (filesize($imagePath) > 500000) { // Contoh: Jika lebih besar dari 500KB
            \Config\Services::image()
                ->withFile($imagePath)
                ->resize(800, 600, true, 'auto') // Ubah ukuran maksimal menjadi 800x600
                ->save($imagePath); // Simpan kembali, menimpa file asli
        }
        }

        $data = [
            "user_id" => $this->request->getPost("user_id"),
            "title" => $this->request->getPost("title"),
            "kategori" => $this->request->getPost("kategori"),
            "description" => $this->request->getPost("description"),
            "ingredients" => $this->request->getPost("ingredients"),
            "steps" => $this->request->getPost("steps"),
            "time" => $this->request->getPost("time"),
            "difficulty" => $this->request->getPost("difficulty"),
            // "rating" => $this->request->getPost("rating"),
             "image" => $imageName
        ];

        $resep->save($data);

        return $this->respond([
            "status" => true,
            "message" => "Resep berhasil ditambahkan"
        ]);
    }

     public function user($id)
    {
       // $recipe = new RecipeModel();
        return $this->respond(
            $this->model->where("user_id", $id)->findAll()
        );
    }

    public function latest(){
        $latesrRecipes = $this ->model
        ->orderBy('created_at', 'DESC')
        ->limit(5)
        ->findAll();

        return $this->respond([
            'status' => true,
            'data' => $latesrRecipes
        ]);
    }

    public function popular()
    {
        // Mengambil 5 resep yang paling 'populer' (diurutkan berdasarkan ID terlama/terkecil untuk contoh)
        $popularRecipes = $this->model
            ->orderBy('id', 'ASC')
            ->limit(5)
            ->findAll();
            
        // Jika Anda sudah menambahkan kolom 'rating' ke database:
        // $popularRecipes = $this->model->orderBy('rating', 'DESC')->limit(5)->findAll();

        return $this->respond([
            'status' => true,
            'data' => $popularRecipes
        ]);
    }

    public function filterByCategory($kategori){
        $filteredRecipes = $this->model
        ->where('kategori', $kategori)
        ->findAll();

        return $this->respond([
            'status' =>true,
            'data' => $filteredRecipes
        ]);
    }

    public function search($keyword){
        $searchResults = $this->model 
        ->like('title', $keyword, 'both')
        ->orLike('ingredients', $keyword, 'both')
        ->findAll();

        return $this->respond([
        'status' => true,
        'data' => $searchResults
    ]);

    }

}