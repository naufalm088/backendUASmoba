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
            log_message('debug', 'CurrentUser ID in bookmark: ' . ($currentUserId ?? 'NULL'));
              log_message('debug', 'User ID from JWT token: ' . ($currentUserId ?? 'NULL'));
    
                 // Get all request data
    $rawInput = $this->request->getRawInput();
    log_message('debug', 'Raw input (getRawInput): ' . json_encode($rawInput));
     $contentType = $this->request->getHeaderLine('Content-Type');
    log_message('debug', 'Content-Type: ' . $contentType);
    
      if (strpos($contentType, 'application/json') !== false) {
        $input = $this->request->getJSON(true) ?: [];
        log_message('debug', 'JSON input: ' . json_encode($input));
        $recipeId = isset($input['recipe_id']) ? (int) $input['recipe_id'] : null;
    } else {
        $input = $this->request->getPost() ?: [];
        log_message('debug', 'POST input: ' . json_encode($input));
        $recipeId = isset($input['recipe_id']) ? (int) $input['recipe_id'] : null;
    }
    
    log_message('debug', 'Recipe ID from request: ' . ($recipeId ?? 'NULL'));
    log_message('debug', '====== BOOKMARK REQUEST END ======');
    

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
// Di ResepController.php - Tambahkan fungsi unbookmark
public function unbookmark()
{
    $currentUserId = $this->request->user->id ?? null;
    log_message('debug', 'CurrentUser ID in unbookmark: ' . ($currentUserId ?? 'NULL'));
    
    if (!$currentUserId) {
        return $this->failUnauthorized('Anda harus login untuk menghapus bookmark.');
    }
    
    // Terima baik JSON ataupun form-data
    $contentType = $this->request->getHeaderLine('Content-Type');
    if (strpos($contentType, 'application/json') !== false) {
        $input = $this->request->getJSON(true) ?: [];
    } else {
        $input = $this->request->getPost() ?: [];
    }

    log_message('debug', 'Unbookmark input: ' . json_encode($input));

    $recipeId = isset($input['recipe_id']) ? (int) $input['recipe_id'] : null;
    
    if (empty($recipeId)) {
        return $this->fail('Parameter `recipe_id` diperlukan.', 400);
    }

    // Pastikan resep ada
    $recipe = $this->model->find($recipeId);
    if (!$recipe) {
        return $this->failNotFound('Resep dengan ID ' . $recipeId . ' tidak ditemukan.');
    }

    // Hapus dari saved_recipes
    $savedModel = new \App\Models\SavedRecipeModel();
    $deleted = $savedModel->where('user_id', $currentUserId)
                          ->where('recipe_id', $recipeId)
                          ->delete();

    if ($deleted) {
        return $this->respondDeleted([
            'status' => true,
            'message' => 'Resep berhasil dihapus dari bookmark.'
        ]);
    } else {
        return $this->fail('Gagal menghapus bookmark.', 500);
    }
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

      // Contoh sederhana di CI4 controller
    // Contoh sederhana di CI4 controller
    // Tambahkan fungsi ini di ResepController.php setelah fungsi unbookmark()

/**
 * Menyimpan resep dari sumber eksternal (API pihak ketiga) ke database lokal
 * POST /api/resep/save-external
 */
public function saveExternal()
{
    $currentUserId = $this->request->user->id ?? null;
    
    if (!$currentUserId) {
        return $this->failUnauthorized('Anda harus login untuk menyimpan resep.');
    }
    
    log_message('debug', '===== SAVE EXTERNAL REQUEST =====');
    log_message('debug', 'User ID: ' . $currentUserId);
    
    // Ambil data JSON dari request Flutter
    $contentType = $this->request->getHeaderLine('Content-Type');
    if (strpos($contentType, 'application/json') !== false) {
        $input = $this->request->getJSON(true) ?: [];
    } else {
        $input = $this->request->getPost() ?: [];
    }
    
    log_message('debug', 'Input data: ' . json_encode($input));
    
    // Validasi data yang diperlukan
    if (empty($input['title'])) {
        log_message('error', 'Validation failed: title is empty');
        return $this->fail('Judul resep tidak boleh kosong.', 400);
    }
    
    if (empty($input['ingredients'])) {
        return $this->fail('Bahan-bahan tidak boleh kosong.', 400);
    }
    
    if (empty($input['steps'])) {
        return $this->fail('Langkah-langkah tidak boleh kosong.', 400);
    }
    
    // 1. SIMPAN KE TABEL RECIPES DULU
    $recipeData = [
        'user_id'      => $currentUserId,
        'title'        => $input['title'] ?? '',
        'kategori'     => $input['kategori'] ?? 'Umum',
        'description'  => $input['description'] ?? '',
        'ingredients'  => $input['ingredients'] ?? '',
        'steps'        => $input['steps'] ?? '',
        'time'         => $input['time'] ?? '0',
        'difficulty'   => $input['difficulty'] ?? 'Mudah',
        'image'        => $input['image'] ?? null, // URL gambar dari API eksternal
        'created_at'   => date('Y-m-d H:i:s'),
        'updated_at'   => date('Y-m-d H:i:s')
    ];
    
    log_message('debug', 'Recipe data to save: ' . json_encode($recipeData));
    
    try {
        // Simpan ke tabel recipes
        $recipeId = $this->model->insert($recipeData, true); // true untuk mendapatkan ID
        
        if (!$recipeId) {
            log_message('error', 'Failed to save recipe to database');
            return $this->fail('Gagal menyimpan resep ke database.', 500);
        }
        
        log_message('debug', 'Recipe saved with ID: ' . $recipeId);
        
        // 2. OTOMATIS SIMPAN KE SAVED_RECIPES (BOOKMARK)
        $savedRecipeModel = new \App\Models\SavedRecipeModel();
        
        // Cek apakah sudah ada di saved_recipes
        $existing = $savedRecipeModel->where([
            'user_id' => $currentUserId,
            'recipe_id' => $recipeId
        ])->first();
        
        if (!$existing) {
            $savedData = [
                'user_id'   => $currentUserId,
                'recipe_id' => $recipeId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $saved = $savedRecipeModel->insert($savedData);
            
            if (!$saved) {
                log_message('warning', 'Recipe saved to recipes but failed to bookmark');
                // Tidak return error, karena resep sudah tersimpan
            } else {
                log_message('debug', 'Recipe also bookmarked successfully');
            }
        } else {
            log_message('debug', 'Recipe already bookmarked');
        }
        
        // Ambil resep yang baru disimpan
        $recipe = $this->model->find($recipeId);
        
        // Tambahkan URL gambar jika ada
        $base = rtrim(base_url(), '/');
        if (!empty($recipe['image'])) {
            $recipe['image_url'] = $recipe['image'];
        }
        
        log_message('debug', 'Recipe saved successfully');
        log_message('debug', '===== SAVE EXTERNAL COMPLETE =====');
        
        return $this->respondCreated([
            'status'  => true,
            'message' => 'Resep berhasil disimpan ke koleksi Anda',
            'data'    => $recipe
        ]);
        
    } catch (\Exception $e) {
        log_message('error', 'Error saving external recipe: ' . $e->getMessage());
        log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        
        return $this->fail('Terjadi kesalahan saat menyimpan resep: ' . $e->getMessage(), 500);
    }
}
// public function saveExternal() {
//        $currentUserId = $this->request->user->id ?? null;
//      if (!$currentUserId) {
//         return $this->failUnauthorized('Anda harus login untuk menyimpan resep.');
//     }
//     // Ambil data JSON dari request Flutter
//     $input = $this->request->getJSON(true);
    
//     // Validasi data yang diperlukan
//     if (empty($input['title'])) {
//         return $this->fail('Judul resep tidak boleh kosong.', 400);
//     }
    
//     if (empty($input['ingredients'])) {
//         return $this->fail('Bahan-bahan tidak boleh kosong.', 400);
//     }
    
//     if (empty($input['steps'])) {
//         return $this->fail('Langkah-langkah tidak boleh kosong.', 400);
//     }
    
//     // Siapkan data untuk disimpan
//     $data = [
//         'user_id' => $currentUserId,
//         'title' => $input['title'] ?? '',
//         'kategori' => $input['kategori'] ?? 'Umum',
//         'description' => $input['description'] ?? '',
//         'ingredients' => $input['ingredients'] ?? '',
//         'steps' => $input['steps'] ?? '',
//         'time' => $input['time'] ?? '0',
//         'difficulty' => $input['difficulty'] ?? 'Mudah',
//         'image' => $input['image'] ?? null, // Handle jika ada gambar dari API eksternal
//         'created_at' => date('Y-m-d H:i:s'),
//         'updated_at' => date('Y-m-d H:i:s')
//     ];
    
//     try {
//         // Simpan ke database
//         $saved = $this->model->insert($data);
        
//         if ($saved) {
//             $recipe = $this->model->find($saved);
            
//             return $this->respondCreated([
//                 'status' => true,
//                 'message' => 'Resep berhasil disimpan ke koleksi Anda',
//                 'data' => $recipe
//             ]);
//         } else {
//             return $this->fail('Gagal menyimpan resep ke database.', 500);
//         }
//     } catch (\Exception $e) {
//         log_message('error', 'Error saving external recipe: ' . $e->getMessage());
//         return $this->fail('Terjadi kesalahan saat menyimpan resep: ' . $e->getMessage(), 500);
//     }

//     // // 1. Verifikasi token
//     // $this->validateToken();
    
//     // // 2. Ambil data dari request
//     // $data = $this->request->getJSON(true);
    
//     // // 3. Simpan ke database
//     // $recipeModel = new RecipeModel();
//     // $saved = $recipeModel->insert($data);
    
//     // // 4. Kembalikan response
//     // if ($saved) {
//     //     $recipe = $recipeModel->find($saved);
//     //     return $this->response->setJSON([
//     //         'status' => 201,
//     //         'message' => 'Resep berhasil disimpan',
//     //         'data' => $recipe
//     //     ]);
//     // } else {
//     //     return $this->response->setJSON([
//     //         'status' => 400,
//     //         'message' => 'Gagal menyimpan resep'
//     //     ], 400);
//     // }
// }

// private function validateToken() {
//     $header = $this->request->getHeader('Authorization');
//     if (!$header) {
//         return $this->response->setJSON([
//             'status' => 401,
//             'message' => 'Token tidak ditemukan'
//         ], 401);
//     }
    
//     $token = str_replace('Bearer ', '', $header->getValue());
//     // Validasi token dengan library JWT atau sesuai implementasi Anda
//     // ...
// }

private function validateToken() {
    $header = $this->request->getHeader('Authorization');
    if (!$header) {
        return $this->response->setJSON([
            'status' => 401,
            'message' => 'Token tidak ditemukan'
        ], 401);
    }
    
    $token = str_replace('Bearer ', '', $header->getValue());
    // Validasi token dengan library JWT atau sesuai implementasi Anda
    // ...
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