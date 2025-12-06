<?php

namespace App\Controllers;

use App\Models\MemberModel;
use App\Models\MemberTokenModel;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    /**
     * @var \CodeIgniter\HTTP\IncomingRequest $request
     */
    protected $request;

    /**
     * Registrasi member baru
     * POST /auth/register
     */
    public function register()
    {
        $model = new MemberModel();
        
        // Ambil data JSON dari body request
        $data = $this->request->getJSON();

        // Validasi input
        if (!isset($data->nama) || !isset($data->email) || !isset($data->password)) {
            return $this->failResponse("Nama, email, dan password diperlukan", 400);
        }

        if (strlen($data->password) < 8) {
            return $this->failResponse("Password minimal 8 karakter", 400);
        }

        // Pengecekan duplikasi email
        if ($model->where('email', $data->email)->first()) {
            return $this->failResponse("Email sudah terdaftar", 400);
        }

        try {
            // Memasukkan data ke database
            $model->insert([
                'nama' => $data->nama,
                'email' => $data->email,
                'password' => $data->password
                // Password akan otomatis di-hash oleh callback beforeInsert di MemberModel
            ]);

            // Kirim respons sukses dengan kode 201 Created
            return $this->successResponse("Registrasi berhasil", [], 201);

        } catch (\Exception $e) {
            return $this->failResponse("Gagal mendaftar: " . $e->getMessage(), 500);
        }
    }

    /**
     * Login member
     * POST /auth/login
     */
    public function login()
    {
        $modelMember = new MemberModel();
        $modelToken = new MemberTokenModel();

        // Ambil data JSON dari body request
        $data = $this->request->getJSON();

        // Validasi input
        if (!isset($data->email) || !isset($data->password)) {
            return $this->failResponse("Email dan password diperlukan", 400);
        }

        // 1. Cari user berdasarkan email
        $user = $modelMember->where('email', $data->email)->first();

        // 2. Verifikasi user dan password
        if (!$user || !password_verify($data->password, $user['password'])) {
            return $this->failResponse("Email atau password salah", 401);
        }

        // 3. Buat token
        $token = bin2hex(random_bytes(32));

        // 4. Simpan token ke database
        try {
            // Hapus token lama jika ada
            $modelToken->where('member_id', $user['id'])->delete();
            
            // Simpan token baru
            $modelToken->insert([
                'member_id' => $user['id'],
                'auth_key' => $token
            ]);
        } catch (\Exception $e) {
            return $this->failResponse("Gagal membuat token: " . $e->getMessage(), 500);
        }

        // 5. Kirim respons sukses
        return $this->successResponse("Login berhasil", [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'nama' => $user['nama'],
                'email' => $user['email']
            ]
        ], 200);
    }

    /**
     * Logout member
     * POST /auth/logout
     */
    public function logout()
    {
        // Ambil token dari Header Authorization
        $authHeader = $this->request->getHeaderLine('Authorization');

        // Format: "Bearer token123..."
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->failResponse("Token tidak ditemukan di header Authorization", 401);
        }

        // Ambil nilai token-nya saja
        $token = str_replace('Bearer ', '', $authHeader);

        // Inisialisasi model token
        $modelToken = new MemberTokenModel();

        // Cari token di tabel member_token
        $found = $modelToken->where('auth_key', $token)->first();

        if (!$found) {
            // Token tidak valid / sudah dihapus
            return $this->failResponse("Token tidak valid atau sudah logout", 401);
        }

        try {
            // Hapus token
            $modelToken->delete($found['id']);
            return $this->successResponse("Logout berhasil");
        } catch (\Exception $e) {
            return $this->failResponse("Gagal logout: " . $e->getMessage(), 500);
        }
    }

    /**
     * Helper function untuk format respons gagal
     */
    protected function failResponse($message, $code = 400)
    {
        return $this->response
                    ->setStatusCode($code)
                    ->setJSON(['status' => $code, 'message' => $message]);
    }

    /**
     * Helper function untuk format respons sukses
     */
    protected function successResponse($message, $data = [], $code = 200)
    {
        $response = [
            'status' => $code,
            'message' => $message
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        return $this->response
                    ->setStatusCode($code)
                    ->setJSON($response);
    }

    /**
     * Fungsi untuk validasi token (bisa digunakan di method lain)
     */
    public function validateToken($token = null)
    {
        if (!$token) {
            // Ambil token dari header jika tidak disediakan
            $authHeader = $this->request->getHeaderLine('Authorization');
            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return ['status' => false, 'message' => 'Token tidak ditemukan'];
            }
            $token = str_replace('Bearer ', '', $authHeader);
        }

        $modelToken = new MemberTokenModel();
        $modelMember = new MemberModel();

        $tokenData = $modelToken->where('auth_key', $token)->first();

        if (!$tokenData) {
            return ['status' => false, 'message' => 'Token tidak valid'];
        }

        $user = $modelMember->find($tokenData['member_id']);

        if (!$user) {
            return ['status' => false, 'message' => 'User tidak ditemukan'];
        }

        return [
            'status' => true,
            'user' => $user
        ];
    }
}