<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
// app/Config/Routes.php

// ... (kode routing bawaan CI4) ...

/**
 * --------------------------------------------------------------------
 * API Routes
 * --------------------------------------------------------------------
 */

// Rute untuk registrasi
// Memetakan: POST http://.../public/registrasi
// Ke:         RegistrasiController, fungsi create()
$routes->post('registrasi', 'RegistrasiController::create');

// Rute untuk login
// Memetakan: POST http://.../public/login
// Ke:         LoginController, fungsi login()
$routes->post('login', 'LoginController::login');
$routes->post('logout', 'LogoutController::logout');

// Grup routing untuk '/produk'
// Semua rute di dalam grup ini akan memiliki awalan '/produk'
//$routes->group('produk', function($routes) {

    // GET /produk -> ProdukController::list()
  //  $routes->get('/', 'ProdukController::list');

    // (:num) adalah placeholder CI4 untuk angka (ID)
    // $1 akan memasukkan angka tersebut sebagai parameter

    // GET /produk/123 -> ProdukController::detail(123)
   // $routes->get('(:num)', 'ProdukController::detail/$1');

    // POST /produk -> ProdukController::create()
   // $routes->post('/', 'ProdukController::create');

    // PUT /produk/123 -> ProdukController::update(123)
   // $routes->put('(:num)', 'ProdukController::update/$1');

    // DELETE /produk/123 -> ProdukController::delete(123)
//     $routes->delete('(:num)', 'ProdukController::delete/$1');
// });

// $routes->group('api', function($routes){
//     $routes->resource('resep', ['controller'=>'Api\Resep']);
// });

$routes->post('/auth/login', 'AuthController::login');
$routes->post('/auth/register', 'AuthController::register');

//$routes->post('/recipes/create', 'ResepController::create');
 
$routes->post('resep/create', 'ResepController::create');
// 'resep/simpan' should bookmark (save) a recipe, not create one
$routes->post('resep/simpan', 'ResepController::bookmark');
// Endpoint untuk menyimpan/bookmark resep (tidak membuat resep baru)
$routes->post('resep/bookmark', 'ResepController::bookmark');
$routes->resource('resep', ['controller' => 'ResepController']);
// Ambil resep yang disimpan user
$routes->get('resep/saved/(:num)', 'ResepController::saved/$1');

$routes->get('/resep/user/(:num)', 'ResepController::user/$1');

$routes->get('/profile/(:num)', 'ProfileController::get/$1');
$routes->post('/profile/update', 'ProfileController::update');


$routes->get('test-model', function() {
    return var_export(class_exists(\App\Models\RecipeModel::class), true);




});

$routes->group('resep', function($routes) {
    // GET /resep/populer -> ResepController::popular()
    $routes->get('populer', 'ResepController::popular');

    // GET /resep/terbaru -> ResepController::latest');
    $routes->get('terbaru', 'ResepController::latest');
   
    // Rute resource utama (index, create, delete, dll)
   // $routes->resource('/', ['controller' => 'ResepController']);

    $routes->get('filter/(:any)', 'ResepController::filterByCategory/$1');

    $routes->get('search/(:any)', 'ResepController::search/$1');
});


