<?php 
namespace App\Controllers;
use App\Models\UserModel;
use App\Models\RecipeModel;
use App\Models\SavedRecipeModel;
use CodeIgniter\RESTful\ResourceController;

class ProfileController extends ResourceController
{
        /**
     * @var \CodeIgniter\HTTP\IncomingRequest $request
     */
    protected $request;

    public function get($id){
        $user = new UserModel();
        $recipeModel = new RecipeModel();
        $saved = new SavedRecipeModel();
        
        $userData = $user->find($id);
        
        // Ambil semua resep milik user ini
        $userRecipes = $recipeModel->where('user_id', $id)->findAll();
        
        // Ambil semua resep yang user ini simpan/bookmark
        $savedRecipesRows = $saved
            ->where('saved_recipes.user_id', $id)
            ->orderBy('saved_recipes.created_at', 'DESC')
            ->findAll();

        $base = rtrim(base_url(), '/');
        $savedMapped = [];
        foreach ($savedRecipesRows as $row) {
            $recipeId = isset($row['recipe_id']) ? $row['recipe_id'] : null;
            if (!$recipeId) continue;

            $recipe = $recipeModel->find($recipeId);
            if (!$recipe) continue;

            $imagePath = isset($recipe['image']) && $recipe['image'] ? $recipe['image'] : null;
            $recipe['image_url'] = $imagePath ? $base . '/' . ltrim($imagePath, '/') : null;
            // camelCase alias
            $recipe['imageUrl'] = $recipe['image_url'];
            if (!isset($recipe['name'])) {
                $recipe['name'] = isset($recipe['title']) ? $recipe['title'] : null;
            }
            // include saved timestamp
            $recipe['saved_at'] = isset($row['created_at']) ? $row['created_at'] : null;
            $recipe['savedAt'] = $recipe['saved_at'];

            $savedMapped[] = $recipe;
        }

        // Gabungkan user data dengan recipes
        $userData['recipes'] = $userRecipes;
        $userData['saved_recipes'] = $savedMapped;
        
        return $this->respond($userData);
    }

    public function update($id = null)
    {
        $user = new UserModel();

        $id = $id ?? $this->request->getPost("id");

        $data = [
            "name" => $this->request->getPost("name"),
            "email" => $this->request->getPost("email"),
            "about" => $this->request->getPost("about")
        ];

        $user->update($id, $data);

        return $this->respond([
            "status" => true,
            "message" => "Profil berhasil diperbarui"
        ]);
    }
}