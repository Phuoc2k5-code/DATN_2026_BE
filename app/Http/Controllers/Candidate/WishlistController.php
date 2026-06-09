<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wishlist; // Hoặc model tương ứng của bạn
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
  public function index(){
    try{
    $userId = auth('sanctum')->id();
    if (!$userId) {
        return response()->json([
            'success' => false,
            'message' => 'Chưa đăng nhập.',
        ], 401);
    }
    $wishlists = Wishlist::with(['job.category', 'job.skills', 'job.company'])->where('user_id', $userId)->latest('id')->paginate(9);
    return response()->json([
          'success' => true,
          'message' => 'Tải trang thành công!',
          'data' => $wishlists->items()
      ], 200);
    } catch (\Exception $e) {
      // Xử lý lỗi hệ thống nếu có phát sinh ngoại lệ
      return response()->json([
        'success' => false,
        'message' => 'Đã xảy ra lỗi khi tải!',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function saveJob($jobId)
  {
    // 1. Kiểm tra xem user đã đăng nhập chưa
    if (!Auth::check()) {
      return response()->json([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để thực hiện chức năng này.'
      ], 401);
    }
    // 3. Lấy ID của user đang đăng nhập
    $userId = Auth::id(); // hoặc auth()->id();

    // 4. (Tùy chọn nâng cao) Kiểm tra xem user đã lưu tin này trước đó chưa để tránh trùng lặp
    $exists = Wishlist::where('user_id', $userId)
      ->where('job_id', $jobId)
      ->first();

    if ($exists) {
      $exists->delete();
      return response()->json([
          'success' => true,
          'action' => 'removed', // Gửi trạng thái này về để Frontend biết đường đổi màu nút
          'message' => 'Đã bỏ lưu tin tuyển dụng thành công.'
      ], 200);
    } else {
        // NẾU CHƯA CÓ -> TIẾN HÀNH THÊM MỚI (SAVE)
        $saveJob = Wishlist::create([
            'user_id' => $userId,
            'job_id' => $jobId
        ]);

        return response()->json([
            'success' => true,
            'action' => 'added', // Gửi trạng thái này về để Frontend bật màu nút lên
            'message' => 'Đã lưu tin tuyển dụng thành công!',
            'data' => $saveJob
        ], 201);
      }
  }

}
