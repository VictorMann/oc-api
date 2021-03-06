<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::group([
    'middleware' => 'apiJWT',
    'prefix'     => 'oc',
    'namespace'  => 'App\Http\Controllers\Api'], function () {

    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');

});



Route::group([
    'prefix' => 'oc',
    'namespace' => 'App\Http\Controllers\Api'], function() {

    Route::post('login', 'AuthController@login');

    Route::get('users', 'UserController@index');
    Route::get('marcas', 'MarcaController@index');

    Route::get('categoria/{cat}', 'CategoriaController@show');
    Route::get('categoria/{cat}/sidefilter', 'CategoriaController@sideFilter');

    Route::get('produto/cat/{cat}', 'ProdutoController@getCategory');
    Route::get('produto/bestseller', 'ProdutoController@bestseller');
    Route::get('busca', 'ProdutoController@busca');

    Route::get('produto/{slug}', 'ProdutoController@index');

});




