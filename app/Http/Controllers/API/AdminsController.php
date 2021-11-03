<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BannedEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\AuthController;

class AdminsController extends Controller
{
    public function getUsersList() {
        $usersArray = DB::select('select * from users', []);

        return response()->json([
            'usersList' => $usersArray,
            'status' => 200,
        ]);
    }

    public function logoutUser(int $id) {
        DB::update('UPDATE oauth_access_tokens SET revoked = 1 WHERE user_id = ?', [$id]);
    }

    public function switchRole() {

        $userId = request('user_id');
        $usersArray = DB::select('select * from users where id = ?', [$userId]);

        if ($usersArray == []) {
            return response()->json([
                'message' => 'There is no user with id='.strval($userId),
                'status' => 422,
            ], 422);
        }

        $user = $usersArray[0];
        if ($user->scope == 'admin') {
            DB::update('UPDATE users SET scope = "" WHERE id = ?', [$userId]);
            return response()->json([
                'status' => 200,
            ], 200);
        }
        DB::update('UPDATE users SET scope = "admin" WHERE id = ?', [$userId]);

        $this->logoutUser($userId);

        return response()->json([
            'status' => 200,
        ], 200);

    }

    public function changeSupervisor() {

        $userId = request('user_id');
        $usersArray = DB::select('select * from users where id = ?', [$userId]);

        if ($usersArray == []) {
            return response()->json([
                'message' => 'There is no user with id='.strval($userId),
                'status' => 422,
            ], 422);
        }

        DB::update('UPDATE users SET scope = "supervisor" WHERE id = ?', [$userId]);

        $fromUserId = auth()->id();
        DB::update('UPDATE users SET scope = "" WHERE id = ?', [$fromUserId]);

        $this->logoutUser($userId);
        $this->logoutUser($fromUserId);

        return response()->json([
            'status' => 200,
        ], 200);

    }

    public function banUser() {

        $user = User::find(request('user_id'));
        $email = new BannedEmail();
        $email->email = $user->email;
        $email->save();
        app(AuthController::class)->delete($user->id);

        return response()->json([
            'status' => 200,
        ]);

    }

}
