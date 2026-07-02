<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use App\Models\User;
use App\Models\Candidate;
class UserController extends Controller
{
  public function userProfile()
  {
    try {
      $userId = Auth::id();

      if (!$userId) {
        return response()->json([
          'success' => false,
          'message' => 'Chưa đăng nhập hoặc Token không hợp lệ.'
        ], 401);
      }

      // 1. Kiểm tra tài khoản user có tồn tại không
      $user = User::find($userId);
      if (!$user) {
        return response()->json([
          'success' => false,
          'message' => 'Không tìm thấy tài khoản.'
        ], 404);
      }

      // 🚀 SỬA TỪ ĐÂY: KHÔNG TỰ ĐỘNG CREATE NỮA!
      // Chỉ lấy dữ liệu ra thôi, có thì dùng, không có thì trả về null
      $data = User::select('id', 'name', 'email', 'role', 'status')
        ->with([
          'candidate' => function ($query) {
            $query->select('id', 'user_id', 'cv_template_id', 'category_id', 'title', 'full_name', 'gender', 'birthday', 'phone', 'email', 'address', 'avatar_url');
          }
        ])
        ->find($userId);

      return response()->json([
        'success' => true,
        'message' => 'Tải thông tin hồ sơ thành công!',
        'data' => $data
      ], 200);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi hệ thống (500): ' . $e->getMessage(),
      ], 500);
    }
  }

  // Hàm sửa thông tin tài khoản & upload Avatar
  public function updateProfile(Request $request)
{
    // 1. Kiểm tra Validate dữ liệu đầu vào
    $validator = Validator::make($request->all(), [
        'full_name' => 'required|string|max:255', // Dùng để cập nhật cho cả user và candidate
        'phone' => 'nullable|string|max:15',
        'title' => 'nullable|string|max:255',
        'address' => 'nullable|string|max:255',
        'birthday' => 'nullable|date|before:today',
        'gender' => 'nullable|string|max:10',
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', 
    ], [
        'full_name.required' => 'Họ và tên không được để trống.',
        'birthday.date' => 'Ngày sinh không đúng định dạng.',
        'birthday.before' => 'Ngày sinh phải là một ngày trong quá khứ.',
        'avatar.image' => 'Tệp tải lên phải là hình ảnh.',
        'avatar.mimes' => 'Ảnh đại diện chỉ chấp nhận định dạng jpeg, png, jpg, gif.',
        'avatar.max' => 'Dung lượng ảnh đại diện không được vượt quá 5MB.',
    ]);

    // Nếu Validate thất bại, trả về lỗi 422
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors()
        ], 422);
    }

    // 2. Lấy thông tin User và Candidate đang đăng nhập
    $user = Auth::user();
    $candidate = $user->candidate;

    if (!$candidate) {
        return response()->json([
            'success' => false,
            'message' => 'Hồ sơ ứng viên không tồn tại trong hệ thống.'
        ], 404);
    }

    // Mảng dữ liệu cập nhật cho bảng 'candidates'
    $updateCandidateData = [
        'full_name' => $request->input('full_name'),
        'phone' => $request->input('phone'),
        'title' => $request->input('title'),
        'address' => $request->input('address'),
        'birthday' => $request->input('birthday') ? $request->input('birthday') : null,
        'gender' => $request->input('gender'),
    ];

    // 3. XỬ LÝ UPLOAD AVATAR & TỐI ƯU BỘ NHỚ
    if ($request->hasFile('avatar')) {
        if (!empty($candidate->avatar_url)) {
            $oldImagePath = public_path($candidate->avatar_url);
            if (File::exists($oldImagePath) && !Str::contains($candidate->avatar_url, 'default-avatar.png')) {
                File::delete($oldImagePath); 
            }
        }

        $file = $request->file('avatar');
        $fileName = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $destinationPath = public_path('avatars');

        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true, true);
        }

        $file->move($destinationPath, $fileName);
        $updateCandidateData['avatar_url'] = 'avatars/' . $fileName;
    }

    // 4. SỬ DỤNG TRANSACTION ĐỂ CẬP NHẬT ĐỒNG THỜI CẢ 2 BẢNG (USERS & CANDIDATES)
    \DB::beginTransaction();
    try {
        // Cập nhật trường name ở bảng users
        $user->update([
            'name' => $request->input('full_name')
        ]);

        // Cập nhật thông tin ở bảng candidates
        $candidate->update($updateCandidateData);

        DB::commit(); // Xác nhận lưu thay đổi thành công vào DB

        // 5. Trả về thông báo thành công và dữ liệu mới nhất
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin tài khoản và ảnh đại diện thành công!',
            'data' => [
                'name' => $user->name,
                'avatar_url' => $candidate->avatar_url
            ]
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack(); // Hoàn tác nếu có bất kỳ lỗi nào xảy ra trong quá trình update DB

        // Nếu có upload avatar mới mà DB lỗi thì nên xóa file vừa upload để tránh file rác
        if (isset($fileName) && File::exists(public_path('avatars/' . $fileName))) {
            File::delete(public_path('avatars/' . $fileName));
        }

        return response()->json([
            'success' => false,
            'message' => 'Lỗi cập nhật hệ thống: ' . $e->getMessage()
        ], 500);
    }
  }

  //Hàm đổi mật khẩu 
  public function updatePassword(Request $request)
  {
    // 1. Kiểm tra cấu trúc form mật khẩu từ Front-end gửi lên
    $validator = Validator::make($request->all(), [
      'current_password' => 'required|string',
      'new_password' => [
        'required',
        'confirmed',
        Password::min(8)          // Tối thiểu 8 ký tự
          ->letters()           // Phải có cả chữ cái
          ->mixedCase()         // Phải có cả chữ HOA và chữ thường
          ->numbers()           // Phải có chữ số (0-9)
          ->symbols(),          // Phải có ký tự đặc biệt (!, @, #, $,...)
      ], // Mật khẩu mới tối thiểu 6 ký tự bảo mật
    ], [
      'current_password.required' => 'Mật khẩu hiện tại không được để trống.',
      'new_password.required' => 'Mật khẩu mới không được để trống.',
      'password' => 'Mật khẩu phải tối thiểu 8 ký tự, bao gồm cả chữ hoa, chữ thường, số và ký tự đặc biệt.',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => $validator->errors()->first()
      ], 422);
    }

    $user = Auth::user();

    // 2. KIỂM TRA BẢO MẬT: Mật khẩu cũ người dùng nhập có khớp với mật khẩu Bcrypt trong DB không?
    if (!Hash::check($request->input('current_password'), $user->password)) {
      return response()->json([
        'success' => false,
        'message' => 'Mật khẩu hiện tại không chính xác.'
      ], 400); // Mã lỗi 400 Bad Request
    }

    // 3. Tiến hành mã hóa mật khẩu mới bằng Bcrypt và cập nhật vào bảng 'users'
    $user->update([
      'password' => Hash::make($request->input('new_password'))
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Đổi mật khẩu tài khoản thành công!'
    ], 200);
  }
}
