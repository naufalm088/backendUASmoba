<?php 
namespace App\Controllers;
use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class ProfileController extends ResourceController
{
        /**
     * @var \CodeIgniter\HTTP\IncomingRequest $request
     */
    protected $request;

    public function get($id){
        $user = new UserModel();
        return $this->respond(
            $user->find($id)
        );
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