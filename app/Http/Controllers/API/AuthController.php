<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // make validatation
        $validate = Validator::make($request->all(), [
            "name" => "required",
            "email" => "required|unique:users,email",
            "password" => "required"
        ]);

        // checking data
        if ($validate->fails()) {
            return response()->json([
                "message" => "Register gagal",
                "error_messages" => $validate->messages(),
            ]);
        }

        // register user to wordpress as author
        $createUserWp = Http::withBasicAuth(env("API_WP_USER"), env("API_WP_PW"))->post(env("API_WP_URL") . "users", [
            "username" => $request->name,
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "roles" => "author"
        ]);

        // register user to local db
        $wpUserId = json_decode($createUserWp, true);
        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "wp_user_id" => $wpUserId["id"],
        ]);

        // create token
        $token = $user->createToken("authToken")->plainTextToken;

        // show message
        return response()->json([
            "message" => "Register Berhasil",
            "token_access"  => $token,
            "token_type" => "bearer",
            "wp_response" => json_decode($createUserWp),
        ]);
    }

    public function login(Request $request)
    {
        // make validatation
        $validate = Validator::make($request->all(), [
            "email" => "required|same:email",
            "password" => "required"
        ]);

        // checking data
        if ($validate->fails()) {
            return response()->json([
                "message" => "Login gagal",
                "error_messages" => $validate->messages(),
            ]);
        }

        // login method
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = User::where("email", $request->email)->first();
            $token = $user->createToken("authToken")->plainTextToken;
            return response()->json([
                "message" => "Login Berhasil",
                "token_access"  => $token,
                "token_type" => "bearer",
            ]);
        } else {
            return response()->json([
                "message" => "Login gagal",
                "error_messages" => "Email atau password salah",
            ]);
        }
    }
}
