<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BannedEmail;
use App\Models\Comment;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Validator;


class AdminsController extends Controller
{
    public function getUsersList() {
        $usersArray = User::all();
        return response()->json([
            'usersList' => $usersArray,
            'status' => 200,
        ]);
    }

    public function logoutUser(int $id) {
        $accessTokenArray = DB::select('select * from oauth_access_tokens where user_id = ?', [$id]);
        foreach ($accessTokenArray as $accessToken) {
            DB::delete('delete from oauth_refresh_tokens where access_token_id = ?', [$accessToken->id]);
        }
        DB::delete('delete from oauth_access_tokens where user_id = ?', [$id]);
    }

    public function switchRole() {

        $userId = request('user_id');
        $usersArray = DB::select('select * from users where id = ?', [$userId]);

        if ($usersArray == []) {
            return response()->json([
                'message' => 'There is no user with id='.strval($userId),
                'status' => 400,
            ], 400);
        }

        $user = $usersArray[0];
        if ($user->scope == 'admin') {
            DB::update('UPDATE users SET scope = "" WHERE id = ?', [$userId]);
            $this->logoutUser($userId);
            return response()->json([
                'status' => 200,
            ]);
        }
        DB::update('UPDATE users SET scope = "admin" WHERE id = ?', [$userId]);
        $this->logoutUser($userId);
        return response()->json([
            'status' => 200,
        ]);
    }

    public function changeSupervisor() {

        $userId = request('user_id');
        $usersArray = DB::select('select * from users where id = ?', [$userId]);

        if ($usersArray == []) {
            return response()->json([
                'message' => 'There is no user with id='.strval($userId),
                'status' => 400,
            ], 400);
        }

        DB::update('UPDATE users SET scope = "supervisor" WHERE id = ?', [$userId]);

        $fromUserId = auth()->id();
        DB::update('UPDATE users SET scope = "" WHERE id = ?', [$fromUserId]);

        $this->logoutUser($userId);
        $this->logoutUser($fromUserId);

        return response()->json([
            'status' => 200,
        ]);

    }

    public function banUser(Request $request) {

        $user = User::find(request('user_id'));
        if ($user == null)
            return response()->json([
                'message' => 'There is no user with id='.strval(request('user_id')),
                'status' => 400,
            ], 400);
        if (!$request->user()->tokenCan('supervisor') && ($user->scope == 'admin' || $user->scope == 'supervisor')) {
            return response()->json([
                'message' => 'Access denied! Insufficient rights!',
                'status' => 403,
            ], 403);
        }
        $email = new BannedEmail();
        $email->email = $user->email;
        $email->save();
        app(AuthController::class)->delete($user->id);

        return response()->json([
            'status' => 200,
        ]);
    }

    public function  deleteComment($comment_id)
    {
        $comment = Comment::find($comment_id);
        if ($comment == null)
            return response()->json([
                'message' => 'No comment with id = '.strval($comment_id),
                'status' => 400,
            ],400);
        $comment->delete();
        return response()->json(['status' => 200]);
    }

    public function  deleteThread($thread_id)
    {
        $thread = Thread::find($thread_id);
        if ($thread == null)
            return response()->json([
                'message' => 'No thread with id = ' . strval($thread_id),
                'status' => 400,
            ], 400);
        $thread->delete();
        DB::delete('delete from comments where thread_id = ?', [$thread_id]);
        return response()->json(['status' => 200]);
    }
}
