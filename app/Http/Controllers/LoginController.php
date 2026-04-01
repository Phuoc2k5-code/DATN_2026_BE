<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function Login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            Auth::user();

            // tạo token
            $token = Auth::user()->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Đăng nhập thành công.',
                'user' => Auth::user(),
                'token' => $token,
            ]); 
        }

        return response()->json([
            'message' => 'Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin đăng nhập.',
        ], 401);
    }  
}
