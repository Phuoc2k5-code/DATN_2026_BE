<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\SendOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * API Đăng ký tài khoản & Gửi mã OTP
     */
    public function register(Request $request)
    {
        // 1. Kiểm tra dữ liệu đầu vào (Validation)
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)          // Tối thiểu 8 ký tự
                    ->letters()           // Phải có cả chữ cái
                    ->mixedCase()         // Phải có cả chữ HOA và chữ thường
                    ->numbers()           // Phải có chữ số (0-9)
                    ->symbols(),          // Phải có ký tự đặc biệt (!, @, #, $,...)
            ], // Đã sửa: Chỉ giữ lại 1 dấu phẩy hợp lệ ở đây
            'role' => 'required|in:employer,candidate',
        ], [
            'name.required' => 'Tên không được để trống.',
            'name.max' => 'Tên không được vượt quá 255 ký tự.',
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được đăng ký trước đó.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'role.required' => 'Vui lòng chọn vai trò (Nhà tuyển dụng hoặc Ứng viên).',
            'role.in' => 'Vai trò không hợp lệ.',
        ]);

        // Nếu dữ liệu không hợp lệ, trả về lỗi 422
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 2. Tạo User ở trạng thái chờ kích hoạt
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status'=> 'pending'                
            ]);

            // 3. Sinh mã OTP ngẫu nhiên gồm 6 chữ số
            $otpCode = rand(100000, 999999);

            // 4. Lưu mã OTP vào Cache (Hiệu lực 15 phút)
            $cacheKey = "auth:otp:register:" . $request->email;
            Cache::put($cacheKey, $otpCode, now()->addMinutes(15));

            // 5. Gửi email chứa OTP cho người dùng
            Mail::to($request->email)->send(new SendOtpMail($otpCode));

            // 6. Trả về phản hồi thành công cho Frontend
            return response()->json([
                'success' => true,
                'message' => 'Đăng ký bước đầu thành công! Mã OTP đã được gửi đến email của bạn.',
                'data' => [
                    'email' => $request->email
                ]
            ], 200);

        } catch (\Exception $e) {
            // Xử lý nếu có lỗi hệ thống (ví dụ lỗi gửi mail)
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi trong quá trình hệ thống xử lý.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API Xác thực mã OTP để kích hoạt tài khoản
     */
    public function verifyOtp(Request $request)
    {
        // 1. Kiểm tra dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp_code' => 'required|numeric',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'otp_code.required' => 'Vui lòng nhập mã OTP.',
            'otp_code.numeric' => 'Mã OTP phải là chuỗi số.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Lấy OTP từ Cache
        $cacheKey = "auth:otp:register:" . $request->email;
        $cachedOtp = Cache::get($cacheKey);

        // 3. Kiểm tra mã OTP có tồn tại không
        if (!$cachedOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP đã hết hạn hoặc không tồn tại. Vui lòng yêu cầu gửi lại mã mới.'
            ], 400);
        }

        // 4. So sánh mã OTP
        if ($cachedOtp != $request->otp_code) {
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP không chính xác. Vui lòng kiểm tra lại.'
            ], 400);
        }

        // 5. Nếu khớp -> Tìm User và kích hoạt tài khoản
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->email_verified_at = now();
            $user->status = 'active';
            $user->save();

            // 6. Xóa Key OTP khỏi Cache
            Cache::forget($cacheKey);

            return response()->json([
                'success' => true,
                'message' => 'Xác thực tài khoản thành công! Bạn hiện tại đã có thể đăng nhập vào hệ thống.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy tài khoản tương ứng với email này.'
        ], 404);
    }
}