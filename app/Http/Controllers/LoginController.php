<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    # Đăng nhập người dùng
    public function LoginUser(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {            
            Auth::user();
            if (Auth::user()->role !== 'admin') {
            // tạo token
                $token = Auth::user()->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'Đăng nhập thành công.',
                    'user' => Auth::user(),
                    'token' => $token,
                    'role' => Auth::user()->role,
                ]); 
            } else {
                return response()->json([
                    'message' => 'Bạn không có quyền truy cập.',
                ], 403);
            }
        }

        return response()->json([
            'message' => 'Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin đăng nhập.',
        ], 401);
    }  

    // đăng nhâp admin
    public function LoginAdmin(Request $request){
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {            
            $user = Auth::user();
            if ($user->role === 'admin') {
                // tạo token
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'Đăng nhập thành công.',
                    'user' => $user,
                    'token' => $token,
                ]); 
            } else {
                return response()->json([
                    'message' => 'Bạn không có quyền truy cập.',
                ], 403);
            }
        }

        return response()->json([
            'message' => 'Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin đăng nhập.',
        ], 401);
    }

}
