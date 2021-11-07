<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class CommentController extends Controller
{
    public function create(Request $request) {

        $validator = Validator::make($request->all(), [
            'thread_id' => ['required'],
            'text' => ['required'],
        ]);
        if($validator->fails()){
            return response()->json($validator->messages(), 400);
        }

        if (DB::select('select * from threads where id = ?', [request('thread_id')]) == [])
            return response()->json([
                'message' => 'No thread with id = '.strval(request('thread_id')),
                'status' => 400,
            ],400);

        $comment = new Comment;
        $comment->user_id = Auth::id();
        $comment->thread_id = request('thread_id');
        if ($request->has('pre_comment_id')){
            if (DB::select('select * from comments where id = ?', [request('pre_comment_id')]) == [])
                return response()->json([
                    'message' => 'No comment with id = '.strval(request('pre_comment_id')),
                    'status' => 400,
                ],400);
            $comment->pre_comment_id = request('pre_comment_id');
        }
        $comment->text = request('text');
        $comment->save();

        return response()->json(['status' => 201]);
    }

    public function read($thread_id) {

        $commentsArray = Comment::where('thread_id', $thread_id)->get();

        foreach ($commentsArray as $comment) {
            $user = User::find($comment->user_id);
            unset($user->id);
            $comment['user'] = $user;
            $comment['users_avatar'] = null;
            if (Storage::disk('public')->exists($user->avatar_path)) {
                $comment['users_avatar'] = base64_encode(Storage::get($user->avatar_path));
            }
        }

        $thread = Thread::find($thread_id);
        if ($thread == null)
            return response()->json([
                'message' => 'No thread with id = '.strval($thread_id),
                'status' => 400,
            ],400);
        $user = User::find($thread->user_id);
        unset($user->id);
        $thread['user'] = $user;
        $thread['users_avatar'] = base64_encode(Storage::get($user->avatar_path));

        return response()->json([
            'thread' => $thread,
            'commentsArray' => $commentsArray,
            'status' => 200,
        ]);
    }

    public function update(Request $request) {

        $validator = Validator::make($request->all(), [
            'comment_id' => ['required'],
            'text' => ['required'],
        ]);
        if($validator->fails()){
            return response()->json($validator->messages(), 400);
        }

        $comment = Comment::find(request('comment_id'));
        if ($comment == null)
            return response()->json([
                'message' => 'No comment with id = '.strval($comment_id),
                'status' => 400,
            ],400);
        if ($comment->user_id != Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => 401
            ], 401);
        }
        $comment->text = $comment->text.'\n Added at '.strval(date("Y-m-d H:i:s")).':\n'.request('text');
        $comment->save();

        return response()->json(['status' => 200]);

    }

    public function delete($comment_id) {

        $comment = Comment::find($comment_id);
        if ($comment == null)
            return response()->json([
                'message' => 'No comment with id = '.strval($comment_id),
                'status' => 400,
            ],400);
        if ($comment->user_id != Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => 401
            ], 401);
        }
        $comment->delete();

        return response()->json(['status' => 200]);
    }
}
