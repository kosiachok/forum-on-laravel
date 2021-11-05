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

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AdminsController;

Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/delete', [AuthController::class, 'delete']);
    Route::post('auth/upload-avatar', [AuthController::class, 'uploadAvatar']);
    Route::delete('auth/delete-avatar', [AuthController::class, 'deleteAvatar']);

    Route::get('auth/check', function () {
        return response()->json([
            'status' => 200,
        ], 200);
    });

    Route::middleware(['auth:api', 'scope:admin,supervisor'])->group(function () {

        Route::get('admin-affairs/get-users-list', [AdminsController::class, 'getUsersList']);
        Route::post('admin-affairs/ban-user', [AdminsController::class, 'banUser']);

    });

    Route::middleware(['auth:api', 'scope:supervisor'])->group(function () {

        Route::post('admin-affairs/switch-role', [AdminsController::class, 'switchRole']);
        Route::post('admin-affairs/change-supervisor', [AdminsController::class, 'changeSuperVisor']);

    });

});
