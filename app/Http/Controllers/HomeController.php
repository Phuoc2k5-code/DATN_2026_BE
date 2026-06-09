<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
  public function index(Request $request)
  {
    try {
      $userId = null;

      // 🔥 GIẢI PHÁP ĐỘC QUYỀN: Gọi thẳng Guard Sanctum để tự check token ngầm
      // Nếu Header có token hợp lệ, hàm này tự bóc tách tìm ra User luôn.
      // Nếu không có token (khách vãng lai), nó trả về null chứ KHÔNG báo lỗi 401 chặn trang.
      $user = auth('sanctum')->user();

      if ($user) {
        $userId = $user->id;
      }

      // --- ĐOẠN DƯỚI GIỮ NGUYÊN HOÀN TOÀN ---
      $savedJobIds = [];
      if ($userId) {
        $savedJobIds = \DB::table('wishlists')
          ->where('user_id', $userId)
          ->pluck('job_id')
          ->toArray();
      }

      $query = Job::with(['company', 'category', 'skills'])
        ->where('status', 'active');
      // Sắp xếp theo tin đăng mới nhất và thực hiện phân trang (Ví dụ: 10 tin trên 1 trang)
      $jobs = $query->latest('id')->paginate(9);

      $jobs->getCollection()->transform(function ($job) use ($savedJobIds) {
        $job->is_saved = in_array($job->id, $savedJobIds);
        return $job;
      });

      // 6. Trả về dữ liệu JSON chuẩn API cho Frontend (ReactJS/Next.js) nhận diện
      return response()->json([
        'success' => true,
        'message' => 'Lấy danh sách tin tuyển dụng thành công.',
        'data' => $jobs->items(),
        'pagination' => [
          'current_page' => $jobs->currentPage(),
          'last_page' => $jobs->lastPage(),
          'per_page' => $jobs->perPage(),
          'total' => $jobs->total(),
        ],
      ], 200);

    } catch (\Exception $e) {
      // Xử lý lỗi hệ thống nếu có phát sinh ngoại lệ
      return response()->json([
        'success' => false,
        'message' => 'Đã xảy ra lỗi khi tải danh sách tin tuyển dụng.',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function JobDetail($id)
  {
    // 1. Thực thi câu lệnh tìm kiếm theo ID kèm nạp sẵn các quan hệ (Eager Loading)
    // Dùng find($id) để lấy ra đúng 1 Object Job duy nhất
    $job = Job::with(['company', 'category', 'skills'])->find($id);

    // 2. Tinh tế check xem id này có tồn tại trong Database không
    // Nếu nhà tuyển dụng xóa bài hoặc người dùng gõ bậy ID trên URL -> Trả về lỗi 404 sạch sẽ
    if (!$job) {
      return response()->json([
        'success' => false,
        'message' => 'Không tìm thấy tin tuyển dụng này hoặc tin đã bị xóa.'
      ], 404);
    }

    // 3. Trả về dữ liệu công việc thành công
    return response()->json([
      'success' => true,
      'message' => 'Lấy thông tin chi tiết tin tuyển dụng thành công.',
      'data' => $job // Trả về object công việc (không dùng ->items())
    ], 200);
  }

  public function getCompanyDetail($id)
  {
    try {
      // Eager loading: Lấy công ty kèm theo danh sách jobs của công ty đó
      // Sắp xếp các job mới nhất lên đầu (latest)
      $company = Company::with([
        'jobs' => function ($query) {
          $query->where('expired_at', '>=', now()) // Chỉ lấy những job còn hạn (tùy bro chọn)
            ->latest();
        }
      ])->find($id);

      // Kiểm tra nếu không tìm thấy công ty (Lỗi 404)
      if (!$company) {
        return response()->json([
          'success' => false,
          'message' => 'Không tìm thấy thông tin công ty này.'
        ], 404);
      }

      // Trả về dữ liệu thành công chuẩn cấu trúc JSON
      return response()->json([
        'success' => true,
        'message' => 'Tải chi tiết công ty thành công.',
        'data' => $company
      ], 200);

    } catch (\Exception $e) {
      // Xử lý lỗi hệ thống/kết nối DB
      return response()->json([
        'success' => false,
        'message' => 'Đã xảy ra lỗi trên hệ thống máy chủ.',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}