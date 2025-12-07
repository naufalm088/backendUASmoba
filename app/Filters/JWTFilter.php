<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // ambil header Authorization
        $header = $request->getHeaderLine('Authorization');
        $token = null;

        if (!empty($header) && preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            $token = $matches[1];
        }

        if (is_null($token)) {
            return Services::response()
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'message' => 'Akses ditolak: Token tidak tersedia.'
                ]);
        }

        try {
            // Ambil secret dari env (pastikan sudah di .env: JWT_SECRET=your_key)
            $key = getenv('JWT_SECRET') ?: env('JWT_SECRET');

            if (empty($key)) {
                // Kalau belum di-set, beri pesan jelas
                return Services::response()
                    ->setStatusCode(500)
                    ->setJSON([
                        'status' => false,
                        'message' => 'Server error: JWT secret belum dikonfigurasi.'
                    ]);
            }

            // decode token (Firebase JWT v6+ memakai Key)
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            // jika mau akses data user di controller, simpan ke request property
            // Note: beberapa versi CI tidak set property arbitrary di Request, jadi
            // kamu bisa gunakan Services::request() atau simpan di global helper.
            // Namun untuk kebanyakan kasus menambahkan property langsung cukup:
            $request->user = $decoded;

        } catch (\Throwable $e) {
            return Services::response()
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'message' => 'Token tidak valid atau kadaluarsa: ' . $e->getMessage()
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // tidak dipakai
    }
}
