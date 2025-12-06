<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// --------------------------------------------------------------------
// 🔓 AUTH PUBLIK (Tidak perlu Token)
// --------------------------------------------------------------------

// Registrasi dan Login - tanpa /api/ agar URL lebih pendek
$routes->post('register', 'AuthController::register'); 
$routes->post('login', 'AuthController::login'); 

// --------------------------------------------------------------------
// 🔓 GRUP PUBLIK API
// --------------------------------------------------------------------
$routes->group('api', function($routes) {
    
    // RESEP PUBLIK
    //$routes->resource('resep', ['controller' => 'ResepController', 'only' => ['index', 'show']]);
    $routes->get('resep', 'ResepController::index');
   $routes->get('resep/(:num)', 'ResepController::show/$1');
    $routes->get('resep/populer', 'ResepController::popular');
    $routes->get('resep/terbaru', 'ResepController::latest');
    $routes->get('resep/filter/(:any)', 'ResepController::filterByCategory/$1');
    $routes->get('resep/search/(:any)', 'ResepController::search/$1');
    $routes->get('resep/user/(:num)', 'ResepController::user/$1');
    
    // PROFIL PUBLIK
    $routes->get('profile/(:num)', 'ProfileController::get/$1');
});

// --------------------------------------------------------------------
// 🔒 GRUP TERLINDUNGI API (PERLU TOKEN)
// --------------------------------------------------------------------
$routes->group('api', ['filter' => 'auth'], function($routes) {
    
    // AUTH TERPROTEKSI
    $routes->post('logout', 'AuthController::logout');
    
    // PROFIL TERPROTEKSI
    $routes->put('profile', 'ProfileController::update');
    $routes->post('profile/update', 'ProfileController::update'); // Alternatif POST
    
    // RESEP TERPROTEKSI
    $routes->post('resep', 'ResepController::create');
    $routes->put('resep/(:num)', 'ResepController::update/$1');
    $routes->delete('resep/(:num)', 'ResepController::delete/$1');
    $routes->get('myrecipes', 'ResepController::myRecipes');
    $routes->get('resep/saved', 'ResepController::saved');
    $routes->post('resep/bookmark', 'ResepController::bookmark');
    $routes->delete('resep/bookmark', 'ResepController::unbookmark');
    
    // VALIDASI TOKEN
    $routes->get('validate-token', 'AuthController::validateTokenEndpoint');
});