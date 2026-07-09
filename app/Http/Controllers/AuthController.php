<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
public function login(LoginRequest $request)
{
    $request->validated();
        if(!Auth::attempt($request->only('email','password')))
        {
            return response()->json(['message'=>'Invalid email or password'], 401);
        }
        $user=User::where('email',$request->email)->firstOrFail();
        $token=$user->createToken('auth_Token')->plainTextToken;
        return response()->json(
            ['massage'=>'User log in Succssfully ',
                    'Token'=>$token
                    ], 201);
}
public function logout(Request $request)
{
    $user = $request->user();
    $user->currentAccessToken()->delete();
    return response()->json([
    'message' => 'The log out successfully',
    'is_verified' => false
    ], 200);
}}
