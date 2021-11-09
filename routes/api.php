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
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\ThreadController;

Route::post('auth/register', [AuthController::class, 'register'])->middleware('invert.passport');
Route::post('auth/login', [AuthController::class, 'login'])->middleware('invert.passport');

Route::middleware('auth:api')->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/delete', [AuthController::class, 'delete']);
    Route::post('auth/upload-avatar', [AuthController::class, 'uploadAvatar']);
    Route::delete('auth/delete-avatar', [AuthController::class, 'deleteAvatar']);

    Route::post('content/create-comment', [CommentController::class, 'create']);
    Route::put('content/update-comment', [CommentController::class, 'update']);
    Route::delete('content/{comment_id}/delete-comment', [CommentController::class, 'delete']);

    Route::post('content/create-thread', [ThreadController::class, 'create']);
    Route::put('content/update-thread', [ThreadController::class, 'update']);
    Route::delete('content/{thread_id}/delete-thread', [ThreadController::class, 'delete']);

    Route::middleware(['auth:api', 'scope:admin,supervisor'])->group(function () {

        Route::get('admin-affairs/get-users-list', [AdminsController::class, 'getUsersList']);
        Route::post('admin-affairs/ban-user', [AdminsController::class, 'banUser']);
        Route::delete('content/{comment_id}/delete-user-comment', [AdminsController::class, 'deleteComment']);
        Route::delete('content/{thread_id}/delete-user-thread', [AdminsController::class, 'deleteThread']);

    });

    Route::middleware(['auth:api', 'scope:supervisor'])->group(function () {
        Route::post('admin-affairs/switch-role', [AdminsController::class, 'switchRole']);
        Route::post('admin-affairs/change-supervisor', [AdminsController::class, 'changeSuperVisor']);
    });

});

Route::get('content/{thread_id}/get-comments', [CommentController::class, 'read']);
Route::get('content/get-threads', [ThreadController::class, 'read']);
