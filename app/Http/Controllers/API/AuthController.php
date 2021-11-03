<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            return response()->json($validator->messages(), 200);
        }

        $user = new User;
        $user->name = request('name');
        $user->email = request('email');
        $user->password = Hash::make(request('password'));
        $user->save();

        return response()->json(['status' => 201]);
    }

    public function login()
    {
        $user = User::whereEmail(request('username'))->first();

        if (!$user) {
            return response()->json([
                'message' => 'Wrong email or password',
                'status' => 422
            ], 422);
        }

        if (!Hash::check(request('password'), $user->password)) {
            return response()->json([
                'message' => 'Wrong email or password',
                'status' => 422
            ], 422);
        }

        $accessTokenArray = DB::select('select * from oauth_access_tokens where user_id = ?', [$user->id]);
        foreach ($accessTokenArray as $accessToken) {
            DB::delete('delete from oauth_refresh_tokens where access_token_id = ?', [$accessToken->id]);
        }
        DB::delete('delete from oauth_access_tokens where user_id = ?', [$user->id]);

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

        return response()->json([
            'token' => $data->access_token,
            'user' => $user,
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
            $user = auth()->user();
            $accessToken = auth()->user()->token();
            $refreshToken = DB::table('oauth_refresh_tokens')
                ->where('access_token_id', $accessToken->id);
            $accessToken->delete();
            $refreshToken->delete();
            $user->delete();
            return response()->json(['status' => 200]);
        }

        DB::delete('delete from users where id = ?', [$id]);
        $accessTokenArray = DB::select('select * from oauth_access_tokens where user_id = ?', [$id]);
        foreach ($accessTokenArray as $accessToken) {
            DB::delete('delete from oauth_refresh_tokens where access_token_id = ?', [$accessToken->id]);
        }
        DB::delete('delete from oauth_access_tokens where user_id = ?', [$id]);

        return response()->json(['status' => 200]);
    }
}
