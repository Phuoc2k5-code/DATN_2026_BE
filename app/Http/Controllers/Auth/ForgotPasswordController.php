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

class ForgotPasswordController extends Controller
{
    /**
     * BƯỚC 1: API Yêu cầu gửi mã OTP Quên mật khẩu
     */
    public function sendResetOtp(Request $request)
    {
        // Validate dữ liệu email đầu vào
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'Vui lòng cung cấp địa chỉ Email.',
            'email.email' => 'Định dạng Email không đúng.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Kiểm tra xem Email có tồn tại trong hệ thống không
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email này chưa được đăng ký trong hệ thống.'
            ], 442);
        }

        try {
            // Tạo mã OTP ngẫu nhiên gồm 6 chữ số
            $otpCode = rand(100000, 999999);

            // Lưu mã OTP vào Cache dành riêng cho luồng Quên mật khẩu (Hiệu lực 15 phút)
            $cacheKey = "auth:otp:forgot:" . $request->email;
            Cache::put($cacheKey, $otpCode, now()->addMinutes(15));

            // Gửi mail bằng class SendOtpMail đã nâng cấp (truyền tham số 'forgot_password')
            Mail::to($request->email)->send(new SendOtpMail($otpCode, 'forgot_password'));

            return response()->json([
                'success' => true,
                'message' => 'Mã OTP khôi phục mật khẩu đã được gửi đến hòm thư của bạn.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng kiểm tra lại cấu hình mail server.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * BƯỚC 2: API Xác thực mã OTP người dùng nhập vào
     */
    public function verifyResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp_code' => 'required|numeric',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'otp_code.required' => 'Vui lòng nhập mã OTP.',
            'otp_code.numeric' => 'Mã OTP phải là một chuỗi số.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Lấy OTP từ Cache ra so khớp
        $cacheKey = "auth:otp:forgot:" . $request->email;
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP đã hết hạn hoặc không tồn tại. Vui lòng yêu cầu gửi lại mã.'
            ], 400);
        }

        if ($cachedOtp != $request->otp_code) {
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP bạn nhập không chính xác.'
            ], 400);
        }

        // Lưu tạm trạng thái xác thực thành công vào cache thêm 5 phút để cho phép đổi pass ở bước 3
        Cache::put("auth:otp:verified:" . $request->email, true, now()->addMinutes(5));

        return response()->json([
            'success' => true,
            'message' => 'Xác thực OTP thành công! Vui lòng thiết lập lại mật khẩu mới.'
        ], 200);
    }

    /**
     * BƯỚC 3: API Tiến hành Đổi/Cập nhật mật khẩu mới
     */
    public function resetPassword(Request $request)
    {
        // Kiểm tra dữ liệu đầu vào & kiểm tra mật khẩu xác nhận (confirmed)
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => [
                'required',
                'confirmed', // Bắt buộc trường password_confirmation từ FE gửi lên phải giống mật khẩu này
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
            'password.required' => 'Mật khẩu mới không được để trống.',
            'password.confirmed' => 'Mật khẩu xác nhận nhập lại không trùng khớp.',
            'password' => 'Mật khẩu mới phải từ 8 ký tự trở lên, bao gồm cả chữ hoa, chữ thường, số và ký tự đặc biệt.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Bảo mật bổ sung: Kiểm tra xem user này đã thực sự qua Bước 2 chưa
        if (!Cache::has("auth:otp:verified:" . $request->email)) {
            return response()->json([
                'success' => false,
                'message' => 'Hành động không hợp lệ. Bạn cần phải xác thực OTP trước khi đổi mật khẩu.'
            ], 403);
        }

        // Tiến hành cập nhật mật khẩu mới vào CSDL
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();

            // Dọn sạch rác trong bộ nhớ Cache liên quan đến tài khoản này
            Cache::forget("auth:otp:forgot:" . $request->email);
            Cache::forget("auth:otp:verified:" . $request->email);

            return response()->json([
                'success' => true,
                'message' => 'Chúc mừng bạn! Mật khẩu tài khoản đã được thay đổi thành công.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy thông tin tài khoản để cập nhật.'
        ], 404);
    }
}