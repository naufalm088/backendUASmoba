<?php
namespace App\Controllers\Api;
use CodeIgniter\RESTful\ResourceController;
use PhpParser\Node\Stmt\ElseIf_;
use CodeIgniter\Files\File;

class Resep extends ResourceController
{
    protected $modelName = 'App\Models\ResepModel';
    protected $format    = 'json';

    /**
     * @var \CodeIgniter\HTTP\IncomingRequest
     */
    protected $request;

    /**
 * @return \CodeIgniter\HTTP\Response
 * @return void
 */
    public function index()
    {
        $data = $this->model->findAll();

        if ($data){
            return $this->respond($data);
        }else{
        return $this->failNotFound('Data tidak ditemukan');
        }
}
/**
 * @return \CodeIgniter\HTTP\Response
 * @property \CodeIgniter\HTTP\IncomingRequest $request
 */
public function create()
{
    // Ambil data JSON yang dikirim dari Flutter
    $data = $this->request->getPost(); 

    $uploadedFile = $this->request->getFile('image_url'); 
    $fileName = null;

    if ($uploadedFile && $uploadedFile->isValid() && !$uploadedFile->hasMoved()) {
        // Pindahkan file ke direktori yang diinginkan (misalnya 'uploads/')
        $path = FCPATH . 'uploads/resep/';
        $fileName = $uploadedFile->getRandomName();
        $uploadedFile->move(WRITEPATH . 'uploads', $fileName);
        
        // Simpan nama file di data yang akan disimpan ke database
        $data['image_url'] = 'uploads/resep/' . $fileName;
    }

    // Coba simpan ke database
    if ($this->model->insert($data)) {
        // Jika berhasil, ambil data yang baru saja disimpan (dengan ID)
        $newRecipe = $this->model->find($this->model->getInsertID());
        
        // Kembalikan respons sukses (Status 201 Created)
        return $this->respondCreated($newRecipe, 'Resep berhasil disimpan');
    } else {
        // Jika gagal (misalnya validasi CI4 gagal)
        return $this->failValidationErrors($this->model->errors());
    }
}

} 