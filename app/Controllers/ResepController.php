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

    public function myRecipes(){
        $currentUserId = $this->request->user->id ?? null;

        if(empty($currentUserId)){
            return $this->failUnauthorized('Silahkan login untuk melihat resep kamu.');

        }

        $myRecipes = $this -> model->where('user_id', $currentUserId)->findAll();

        if (empty($myRecipes)) {
            return $this->respond(['status' => true, 'data' => [], 'message' => 'Anda belum memiliki resep.']);
        }

        return $this->respond(['status' => true, 'data' => $myRecipes]);
    }


    public function create()
    {
        $currentUserId = $this->request->user->id ?? null;
        if (empty($currentUserId)){
            return $this->failUnauthorized('Autentikasi gagal atau ID pengguna tidak ditemukan');
        }

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
            "user_id" => $currentUserId,
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
    public function delete($id = null)
{
    $currentUserId = $this->request->user->id ?? null; 
    
    if (!$currentUserId) {
        return $this->failUnauthorized('Anda harus login untuk menghapus resep.');
    }

    $resep = $this->model->find($id);

    if (!$resep) {
        return $this->failNotFound('Resep dengan ID ' . $id . ' tidak ditemukan.');
    }

    if ($resep['user_id'] != $currentUserId) {
        return $this->failForbidden('Anda tidak memiliki izin untuk menghapus resep ini.');
    }

    if ($this->model->delete($id)) {
        return $this->respondDeleted([
            'status' => true,
            'message' => 'Resep berhasil dihapus',
        ]);
    }
    return $this->failNotFound('Resep dengan ID ' . $id . ' tidak ditemukan atau gagal dihapus');
}

public function update($id = null)
{
    $currentUserId = $this->request->user->id ?? null; 
    
    if (!$currentUserId) {
        return $this->failUnauthorized('Anda harus login untuk memperbarui resep.');
    }
    
    // 2. Cari resep yang akan diupdate
    // 2. Cari resep (dan cek ID)
    if (!$id) {
        return $this->failNotFound('ID resep tidak diberikan.');
    }

    $resep = $this->model->find($id);
    if (!$resep) {
        return $this->failNotFound('Resep dengan ID ' . $id . ' tidak ditemukan.');
    }

    // if (!$id || !$resep) {
    //     return $this->failNotFound('Resep dengan ID ' . $id . ' tidak ditemukan.');
    // }
    if ($resep['user_id'] != $currentUserId) {
        return $this->failForbidden('Anda tidak memiliki izin untuk memperbarui resep ini.');
    }
   
    // 5. Filter hanya field yang allowed di model
    $filteredData = [];
    $allowedFields = $this->model->allowedFields;

    unset($filteredData['user_id']);
    if (empty($filteredData)) {
        return $this->fail('Tidak ada data yang diubah.', 400);
    }
    // Validasi ID
    // if (!$id) {
    //     return $this->failNotFound('ID resep tidak diberikan.');
    // }

    // if (!$this->model->find($id)) {
    //     return $this->failNotFound('Resep dengan ID ' . $id . ' tidak ditemukan.');
    // }

    //start blok pengambilan data
    $data = [];
    
    // Cek apakah request adalah JSON atau form-data
    $contentType = $this->request->getHeaderLine('Content-Type');
    
    if (strpos($contentType, 'application/json') !== false) {
        // JSON request
        $rawInput = $this->request->getJSON();
        if ($rawInput && is_object($rawInput)) {
            $data = (array) $rawInput;
        }
    } else {
        // Form-data request
        $data = $this->request->getPost();
    }

    // Debugging - log data yang diterima
    log_message('debug', 'Update ResepController - ID: ' . $id . ', Data: ' . json_encode($data));

   if (empty($data)) {
        log_message('error', 'Request Body Kosong atau Gagal Parsing');
    }
    // Hapus ID dari data update
    unset($data['id']);

    // Filter hanya field yang allowed di model
    $filteredData = [];
    $allowedFields = $this->model->allowedFields;
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowedFields)) {
            // Abaikan field kosong
            if ($value !== null && $value !== '') {
                $filteredData[$key] = $value;
            }
        }
    }

    // Validasi minimal ada data yang diubah
    if (empty($filteredData)) {
        return $this->fail('Tidak ada data yang diubah.', 400);
    }
    unset($filteredData['user_id']);

    // Handle upload gambar jika ada
    $image = $this->request->getFile('image');
    if ($image && $image->isValid()) {
        $imageName = $image->getRandomName();
        $image->move('uploads/recipes/', $imageName);
        $imagePath = 'uploads/recipes/' . $imageName;

        // Compress gambar jika terlalu besar
        if (filesize($imagePath) > 500000) {
            \Config\Services::image()
                ->withFile($imagePath)
                ->resize(800, 600, true, 'auto')
                ->save($imagePath);
        }
        $filteredData['image'] = $imageName;
    }

    // Lakukan update dengan data yang sudah difilter
    if ($this->model->update($id, $filteredData)) {
        return $this->respond([
            'status' => true,
            'message' => 'Resep berhasil diperbarui',
            'data' => $filteredData
        ]);
    }

    // Tampilkan error jika ada
    if ($this->model->errors()) {
        return $this->fail([
            'message' => 'Gagal memperbarui resep.',
            'errors' => $this->model->errors()
        ], 400);
    }

    return $this->fail('Gagal memperbarui resep.', 500);
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
    
        // Bookmark / save a recipe for a user (saved recipes)
        public function bookmark()
        {
            $currentUserId = $this->request->user->id ?? null;
            if (!$currentUserId) {
        return $this->failUnauthorized('Anda harus login untuk menyimpan resep.');
    }
            // Terima baik JSON ataupun form-data
            $contentType = $this->request->getHeaderLine('Content-Type');
            if (strpos($contentType, 'application/json') !== false) {
                $input = $this->request->getJSON(true) ?: [];
            } else {
                $input = $this->request->getPost() ?: [];
            }

            // logging untuk debugging
            log_message('debug', 'Bookmark input: ' . json_encode($input));

           // $userId = isset($input['user_id']) ? (int) $input['user_id'] : null;
            $recipeId = isset($input['recipe_id']) ? (int) $input['recipe_id'] : null;
            
if (empty($recipeId)) {
        return $this->fail('Parameter `recipe_id` diperlukan.', 400); // Hapus pengecekan userId dari sini
    }
            if (empty($currentUserId) || empty($recipeId)) {
                return $this->fail('Parameter `user_id` dan `recipe_id` diperlukan dan harus berupa angka.', 400);
            }

            // Pastikan resep ada
            $recipe = $this->model->find($recipeId);
            if (!$recipe) {
                return $this->failNotFound('Resep dengan ID ' . $recipeId . ' tidak ditemukan.');
            }

            // Gunakan model SavedRecipeModel
            $savedModel = new \App\Models\SavedRecipeModel();

            // Cek apakah sudah disimpan sebelumnya
            $exists = $savedModel->where('user_id', $currentUserId)->where('recipe_id', $recipeId)->first();
            if ($exists) {
                return $this->respond([
                    'status' => true,
                    'message' => 'Resep sudah tersimpan sebelumnya.'
                ]);
            }

            $inserted = $savedModel->insert([
                'user_id' => $currentUserId,
                'recipe_id' => $recipeId,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if ($inserted === false) {
                // Ambil error dari model jika ada
                $errors = $savedModel->errors();
                log_message('error', 'Failed to insert saved_recipes: ' . json_encode($errors));
                return $this->fail('Gagal menyimpan resep.', 500);
            }

            return $this->respondCreated([
                'status' => true,
                'message' => 'Resep berhasil disimpan.'
            ]);
        }

        // Ambil daftar resep yang disimpan oleh user
        public function saved()
        {
            //debug
            
            $currentUserId = $this->request->user->id ?? null; 

    if (!$currentUserId) {
        return $this->failUnauthorized('User ID diperlukan.');
    }

            // if (!$userId) {
            //     return $this->fail('User ID diperlukan.', 400);
            // }

            $savedModel = new \App\Models\SavedRecipeModel();
            $savedRows = $savedModel
                ->where('saved_recipes.user_id', $currentUserId)
                ->orderBy('saved_recipes.created_at', 'DESC')
                ->findAll();

            $base = rtrim(base_url(), '/');
            $mapped = [];

            foreach ($savedRows as $row) {
                $recipeId = isset($row['recipe_id']) ? $row['recipe_id'] : null;
                if (!$recipeId) continue;

                // Ambil seluruh detail resep agar sama persis seperti detail view
                $recipe = $this->model->find($recipeId);
                if (!$recipe) continue;

                $imagePath = isset($recipe['image']) && $recipe['image'] ? $recipe['image'] : null;
                $recipe['image_url'] = $imagePath ? $base . '/' . ltrim($imagePath, '/') : null;
                // camelCase alias untuk frontend
                $recipe['imageUrl'] = $recipe['image_url'];
                // alias name jika frontend mengharapkan
                if (!isset($recipe['name'])) {
                    $recipe['name'] = isset($recipe['title']) ? $recipe['title'] : null;
                }
                // include saved timestamp from saved_recipes row if available
                $recipe['saved_at'] = isset($row['created_at']) ? $row['created_at'] : null;
                $recipe['savedAt'] = $recipe['saved_at'];

                $mapped[] = $recipe;
            }

            return $this->respond([
                'status' => true,
                'data' => $mapped
            ]);
        }

public function show($id = null)
{
    // if ($id === 'saved'){
    //     return $this->saved();
    // }

    if (!is_numeric($id)){
        return $this->fail('Id resep harus berupa angka', 400);
    }
    // 1. Cari resep berdasarkan ID
    $resep = $this->model->find($id);

    if ($resep) {
        // 2. Jika ditemukan, kirim respons 200 (OK)
        return $this->respond([
            'status' => true,
            'data' => $resep
        ]);
    }

    // 3. Jika tidak ditemukan, kirim respons 404 (Not Found)
    return $this->failNotFound('Resep dengan ID ' . $id . ' tidak ditemukan');
}
}