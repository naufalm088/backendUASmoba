<?php namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\MemberTokenModel;
use App\Models\MemberModel; // Tambah ini

class AuthFilter implements FilterInterface 
{
    public function before(RequestInterface $request, $arguments = null)
    {
         // Debug: cek URI yang diakses
       // log_message('debug', 'AuthFilter for URI: ' . $request->getUri()->getPath());
        
        $authHeader = $request->getHeaderLine('Authorization');
        
        // 1. Cek format header
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorizedResponse();
        }

        $token = substr($authHeader, 7); 
        
        // 2. Cek token di database
        $tokenModel = new MemberTokenModel();
        $tokenData = $tokenModel->where('auth_key', $token)->first();

        if (!$tokenData) {
            return $this->unauthorizedResponse();
        }

        // 3. Ambil data user berdasarkan member_id dari token
        $memberModel = new MemberModel();
        $user = $memberModel->find($tokenData['member_id']);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        // 4. TAMBAHKAN DATA USER KE REQUEST
        // Ini bagian yang krusial!
        $request->user = (object)[
            'id' => $user['id'],
            'nama' => $user['nama'],
            'email' => $user['email']
        ];

        // Token valid, lanjutkan request
        return;
    }

    private function unauthorizedResponse()
    {
        return service('response')
            ->setStatusCode(401)
            ->setHeader('Content-Type', 'application/json')
            ->setJSON([
                'status' => 401,
                'message' => 'Token kadaluarsa atau tidak valid. Silakan login kembali.',
            ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak perlu melakukan apa-apa di sini
    }
}