<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        if (DB::select('select * from banned_emails where email = ?', [request('email')]) != []) {
            return response()->json([
                'message' => 'You are banned!',
                'status' => 403
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'unique:users', 'max:255'],
            'email' => ['required', 'unique:users'],
            'password' => ['required'],
        ]);

        if($validator->fails()){
            return response()->json($validator->messages(), 400);
        }

        $user = new User;
        $user->name = request('name');
        $user->email = request('email');
        $user->password = Hash::make(request('password'));
        $user->avatar_path = 'public/avatars/default.jpg';
        $user->save();

        return response()->json(['status' => 201]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required'],
            'password' => ['required'],
        ]);
        if($validator->fails()){
            return response()->json($validator->messages(), 400);
        }

        $user = User::whereEmail($request->input('username'))->first();

        if (!$user) {
            return response()->json([
                'message' => 'No user with email '.$request->input('username'),
                'status' => 422
            ], 422);
        }

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'message' => 'Wrong email or password',
                'status' => 422
            ], 422);
        }

        $client = DB::table('oauth_clients')
            ->where('password_client', true)
            ->first();

        if (!$client) {
            return response()->json([
                'message' => 'Laravel Passport is not setup properly.',
                'status' => 500
            ], 500);
        }

        $data = [
            'grant_type' => 'password',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'username' => request('username'),
            'password' => request('password'),
            'scope' => $user->scope,
        ];

        $request = Request::create('/oauth/token', 'POST', $data);

        $response = app()->handle($request);

        if ($response->getStatusCode() != 200) {
            return response()->json([
                'message' => 'Wrong email or password',
                'status' => 422
            ], 422);
        }

        $data = json_decode($response->getContent());
        unset($user->id);

        return response()->json([
            'token' => $data->access_token,
            'user' => $user,
            'avatar' => base64_encode(Storage::get($user->avatar_path)),
            'status' => 200
        ]);
    }

    public function logout()
    {
        $accessToken = auth()->user()->token();

        $refreshToken = DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $accessToken->id);

        $accessToken->delete();
        $refreshToken->delete();

        return response()->json(['status' => 200]);
    }

    public function delete(int $id = null)
    {
        if ($id == null) {
            $id = auth()->id();
        }
        DB::delete('delete from users where id = ?', [$id]);
        $accessTokenArray = DB::select('select * from oauth_access_tokens where user_id = ?', [$id]);
        foreach ($accessTokenArray as $accessToken) {
            DB::delete('delete from oauth_refresh_tokens where access_token_id = ?', [$accessToken->id]);
        }
        DB::delete('delete from oauth_access_tokens where user_id = ?', [$id]);
        return response()->json(['status' => 200]);
    }

    public function uploadAvatar(Request $request)
    {
        if (!$request->hasFile('avatar')) {
            return response()->json([
                'message' => 'No image in request!',
                'status' => 400,
            ], 400);
        }
        if (!$request->file('avatar')->isValid()) {
            return response()->json([
                'message' => 'File is not valid!',
                'status' => 400,
            ], 400);
        }

        $user = User::find($request->user()->id);
        $request->file('avatar')->storeAs(
            'public/avatars', strval($user->id).'.jpg'
        );
        $user->avatar_path = 'public/avatars/'.strval($user->id).'.jpg';
        $user->save();

        return response()->json([
            'avatar' => base64_encode(Storage::get($request->user()->avatar_path)),
            'status' => 201
        ], 201);
    }

    public function deleteAvatar()
    {
        if (Storage::disk('public')->missing('avatars/'.strval(auth()->id()).'.jpg')) {
            return response()->json([
                'message' => 'Nothing to delete!',
                'status' => 400,
            ], 400);
        }
        Storage::delete('public/avatars/'.strval(auth()->id()).'.jpg');
        $user = User::find(auth()->id());
        $user->avatar_path = 'public/avatars/default.jpg';
        $user->save();
        return response()->json([
            'status' => 200,
        ]);
    }
}
