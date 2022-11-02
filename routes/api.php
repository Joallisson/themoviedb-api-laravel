<?php

use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Auth\v1\UserAuthController;
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


Route::prefix('user')->group(function()
{
    Route::post('/', [UserController::class, 'store']);
    Route::post('/login', [UserAuthController::class, 'login']);
    Route::get('reset_password', [UserAuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function()
    {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::post('/logout', [UserAuthController::class, 'logout']);
    });
});
