<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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

      // 2. Kiểm tra xem đã có bản ghi bên bảng candidates chưa
      $candidate = Candidate::where('user_id', $userId)->first();

      if (!$candidate) {
        // 💡 GIẢI PHÁP CHO LỖI 500: Điền đầy đủ các cột NOT NULL theo file Csdl_19.docx
        // Bạn hãy kiểm tra trong DB xem bảng 'categories' và 'cv_templates' đã có dữ liệu chưa.
        // Nếu chưa có, hãy chạy Seeder hoặc thêm thủ công 1 bản ghi có id = 1 vào 2 bảng đó trước nhé!
        $candidate = Candidate::create([
          'user_id' => $userId,
          'cv_template_id' => 1, // Điền ID mặc định để tránh lỗi database ràng buộc
          'category_id' => 1, // Điền ID ngành nghề mặc định
          'title' => '',
          'full_name' => 'Ứng viên mới',
          'gender' => 'Nam',
          'birthday' => now()->format('Y-m-d'),
          'phone' => '',
          'email' => $user->email,
          'address' => '',
          'summary' => '',
          'objective' => '',
          'avatar_url' => 'uploads/avatars/default-avatar.png'
        ]);
      }

      // 3. Lấy dữ liệu trọn vẹn (Bắt buộc phải có cột 'id' trong select của User)
      $data = User::select('id', 'email', 'role', 'status')
        ->with([
          'candidate' => function ($query) {
            // Đảm bảo lấy đủ các trường cốt lõi, bắt buộc phải có 'user_id' và 'id'
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
      // 💡 BẮT LỖI NGẦM: Nếu vẫn bị 500, đoạn này sẽ bắt lại và trả về câu thông báo lỗi chính xác của MySQL để bạn biết đường sửa!
      return response()->json([
        'success' => false,
        'message' => 'Lỗi hệ thống (500): ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
      ], 500);
    }
  }

  // Hàm sửa thông tin tài khoản & upload Avatar
  public function updateProfile(Request $request)
  {
    // 1. Kiểm tra Validate dữ liệu đầu vào (Bổ sung validate cho trường avatar)
    $validator = Validator::make($request->all(), [
      'full_name' => 'required|string|max:255',
      'phone' => 'nullable|string|max:15',
      'title' => 'nullable|string|max:255',
      'address' => 'nullable|string|max:255',
      'birthday' => 'nullable|date|before:today',
      'gender' => 'nullable|string|max:10',
      'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 💡 ĐÃ THÊM: Cho phép tối đa 5MB
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

    // 2. Lấy thông tin Candidate thông qua tài khoản đang đăng nhập
    $user = Auth::user();
    $candidate = $user->candidate;

    if (!$candidate) {
      return response()->json([
        'success' => false,
        'message' => 'Hồ sơ ứng viên không tồn tại trong hệ thống.'
      ], 404);
    }

    // Mảng dữ liệu cập nhật ban đầu
    $updateData = [
      'full_name' => $request->input('full_name'),
      'phone' => $request->input('phone'),
      'title' => $request->input('title'),
      'address' => $request->input('address'),
      'birthday' => $request->input('birthday') ? $request->input('birthday') : null,
      'gender' => $request->input('gender'),
    ];

    // 3. 💡 XỬ LÝ UPLOAD AVATAR & TỐI ƯU BỘ NHỚ (XÓA FILE CŨ)
    if ($request->hasFile('avatar')) {
      // Bước A: Kiểm tra và xóa file ảnh cũ để tránh rác bộ nhớ server
      if (!empty($candidate->avatar_url)) {
        // Xác định đường dẫn tuyệt đối của file cũ trong thư mục public
        $oldImagePath = public_path($candidate->avatar_url);

        // Ràng buộc bảo vệ: Chỉ xóa nếu file cũ tồn tại VÀ đó không phải là file ảnh mặc định hệ thống
        if (File::exists($oldImagePath) && !Str::contains($candidate->avatar_url, 'default-avatar.png')) {
          File::delete($oldImagePath); // Tiến hành xóa file cũ khỏi server
        }
      }

      // Bước B: Tiến hành lưu file ảnh mới
      $file = $request->file('avatar');

      // Tạo tên file độc nhất bằng chuỗi ngẫu nhiên + thời gian tránh bị trùng lặp đè file
      $fileName = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();

      // Định nghĩa thư mục đích: public/uploads/avatars
      $destinationPath = public_path('uploads/avatars');

      // Nếu thư mục chưa tồn tại thì tự động tạo mới thư mục với quyền ghi (0755)
      if (!File::exists($destinationPath)) {
        File::makeDirectory($destinationPath, 0755, true, true);
      }

      // Di chuyển file từ thư mục tạm của PHP sang thư mục public của Laravel
      $file->move($destinationPath, $fileName);

      // Gán đường dẫn tương đối vào mảng để chuẩn bị lưu vào Database
      $updateData['avatar_url'] = 'uploads/avatars/' . $fileName;
    }

    // 4. Tiến hành cập nhật dữ liệu vào bảng 'candidates' trong DB
    $candidate->update($updateData);

    // 5. Trả về thông báo thành công và dữ liệu mới nhất (bao gồm avatar_url mới để ReactJS đồng bộ)
    return response()->json([
      'success' => true,
      'message' => 'Cập nhật thông tin hồ sơ và ảnh đại diện thành công!',
      'data' => [
        'avatar_url' => $candidate->avatar_url
      ]
    ], 200);
  }

  //Hàm đổi mật khẩu 
  public function updatePassword(Request $request)
  {
    // 1. Kiểm tra cấu trúc form mật khẩu từ Front-end gửi lên
    $validator = Validator::make($request->all(), [
      'current_password' => 'required|string',
      'new_password' => 'required|string|min:6', // Mật khẩu mới tối thiểu 6 ký tự bảo mật
    ], [
      'current_password.required' => 'Mật khẩu hiện tại không được để trống.',
      'new_password.required' => 'Mật khẩu mới không được để trống.',
      'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
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
