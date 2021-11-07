<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class ThreadController extends Controller
{
    public function create(Request $request) {

        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'text' => ['required'],
        ]);
        if($validator->fails()){
            return response()->json($validator->messages(), 400);
        }

        $comment = new Thread;
        $comment->user_id = Auth::id();
        $comment->name = request('name');
        $comment->text = request('text');
        $comment->save();

        return response()->json(['status' => 201]);
    }

    public function read() {
        $threadsArray = Thread::all();

        return response()->json([
            'threadsArray' => $threadsArray,
            'status' => 200,
        ]);

    }

    public function update(Request $request) {

        $validator = Validator::make($request->all(), [
            'thread_id' => ['required'],
            'text' => ['required'],
        ]);
        if($validator->fails()){
            return response()->json($validator->messages(), 400);
        }

        $thread = Thread::find(request('thread_id'));
        if ($thread == null)
            return response()->json([
                'message' => 'No thread with id = '.strval($thread_id),
                'status' => 400,
            ],400);

        if ($thread->user_id != Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => 401
            ], 401);
        }
        $thread->text = $thread->text.'\n Added at '.strval(date("Y-m-d H:i:s")).':\n'.request('text');
        $thread->save();

        return response()->json(['status' => 200]);

    }

    public function delete($thread_id) {

        $thread = Thread::find($thread_id);
        if ($thread == null)
            return response()->json([
                'message' => 'No thread with id = '.strval($thread_id),
                'status' => 400,
            ],400);
        if ($thread->user_id != Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => 401
            ], 401);
        }
        $thread->delete();
        DB::delete('delete from comments where thread_id = ?', [$thread_id]);

        return response()->json(['status' => 200]);
    }
}
