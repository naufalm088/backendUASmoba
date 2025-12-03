<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class Auth extends ResourceController
{
        /**
     * @var \CodeIgniter\HTTP\IncomingRequest $request
     */
    protected $request;

    public function register()
    {
        $model = new UserModel();
        $data = $this->request->getPost();

        if ($model->where('email', $data['email'])->first()) {
            return $this->respond(['status' => false, 'message' => 'Email sudah digunakan'], 400);
        }

        $save = $model->save([
            'nama' => $data['nama'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'auth_token' => bin2hex(random_bytes(40)),
        ]);

        return $this->respond(['status' => true, 'message' => 'Registrasi berhasil']);
    }

    public function login()
    {
        $model = new UserModel();
        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');

        $user = $model->where('email', $email)->first();
        if (!$user) {
            return $this->respond(['status' => false, 'message' => 'Email tidak ditemukan'], 404);
        }

        if (!password_verify($password, $user['password'])) {
            return $this->respond(['status' => false, 'message' => 'Password salah'], 401);
        }

        return $this->respond([
            'status' => true,
            'message' => 'Login berhasil',
            'token' => $user['auth_token'],
            'nama' => $user['nama']
        ]);
    }
}
