<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\ProfileController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware('auth:sanctum')->group(function () {

   Route::prefix('/user')->group(function () {
      Route::put('/update-banner', [ProfileController::class, 'updateBanner']);
      Route::put('/update-profile', [ProfileController::class, 'updateProfile']);
   });

   Route::apiResource('links', LinkController::class)
      ->except(['index', 'show'])
      ->scoped(['link' => 'slug']);

   Route::post('/logout', [AuthController::class, 'logout']);
});

Route::get('/user/{user:username}', [ProfileController::class, 'getUserProfile']);

Route::prefix('/links')->group(function () {
   Route::get('/', [LinkController::class, 'index']);
   Route::get('/{link:slug}', [LinkController::class, 'show']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
