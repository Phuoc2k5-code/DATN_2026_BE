<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    # Đăng nhập người dùng thông thường
    public function LoginUser(Request $request)
    {
        // Validate dữ liệu đầu vào cơ bản trước để phòng thủ
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {            
            $user = Auth::user();

            if ($user->role !== 'admin' && $user->status === 'active') {
                // Tạo token Sanctum (Đảm bảo trong Model User.php đã có: use HasApiTokens;)
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Đăng nhập thành công.',
                    'user' => $user,
                    'token' => $token,
                ], 200); 
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản bạn chưa được kích hoạt hoặc không có quyền truy cập.',
                ], 403);
            }
        }

        // Trả về lỗi 401 khi sai tài khoản/mật khẩu, axios sẽ tự nhảy vào .catch
        return response()->json([
            'success' => false,
            'message' => 'Email hoặc mật khẩu không chính xác.',
        ], 401);
    }  

    # Đăng nhập dành cho Admin
    public function LoginAdmin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {            
            $user = Auth::user();

            if ($user->role === 'admin' && $user->status === 'active') {
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Đăng nhập Admin thành công.',
                    'user' => $user,
                    'token' => $token,
                ], 200); 
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập vào trang quản trị.',
                ], 403);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Thông tin đăng nhập Admin không chính xác.',
        ], 401);
    }
}