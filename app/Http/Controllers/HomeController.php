<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\Company;
use App\Models\Application;
use App\Models\Category;
use App\Models\JobClick;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
  public function index(Request $request)
  {
    try {
      $userId = null;

      // 🔥 Tự check token ngầm để lấy User ẩn danh hoặc đã đăng nhập
      $user = auth('sanctum')->user();
      if ($user) {
        $userId = $user->id;
      }

      $savedJobIds = [];
      if ($userId) {
        $savedJobIds = \DB::table('wishlists')
          ->where('user_id', $userId)
          ->pluck('job_id')
          ->toArray();
      }

      // 1. Khởi tạo Query ban đầu
      $query = Job::with(['company', 'category', 'skills'])
        ->where('status', 'active');

      // 2. 🔥 BỘ LỌC TÌM KIẾM THEO ĐIỀU KIỆN TỪ FRONTEND GỬI LÊN

      // Lọc theo từ khóa (Keyword): Tìm theo tiêu đề job hoặc tên công ty
      if ($request->has('keyword') && !empty($request->keyword)) {
        $keyword = $request->keyword;
        $query->where(function ($q) use ($keyword) {
          $q->where('title', 'like', '%' . $keyword . '%')
            ->orWhereHas('company', function ($companyQuery) use ($keyword) {
              $companyQuery->where('company_name', 'like', '%' . $keyword . '%');
            });
        });
      }

      // Lọc theo Địa điểm (Location)
      if ($request->has('location') && !empty($request->location)) {
        $query->where('location', 'like', '%' . $request->location . '%');
      }

      // Lọc theo Ngành nghề (Category ID)
      if ($request->has('category_id') && !empty($request->category_id)) {
        // Nếu cột category_id trong bảng jobs là ID (số), hãy đảm bảo Frontend truyền lên ID.
        // Còn nếu đang lưu thẳng chuỗi tên ngành nghề, điều kiện dưới đây vẫn đúng:
        $query->where('category_id', $request->category_id);
      }

      // Lọc theo Cấp bậc (Level) - Frontend mặc định gửi "Nổi bật" nếu không chọn
      if ($request->has('level') && !empty($request->level) && $request->level !== 'Nổi bật') {
        $query->where('level', $request->level);
      }

      // Lọc theo khoảng Lương (Salary)
      if ($request->has('salary_from') && !empty($request->salary_from)) {
        $query->where('salary_min', '>=', $request->salary_from); // Thay 'salary' bằng tên cột tương ứng
      }
      if ($request->has('salary_to') && !empty($request->salary_to)) {
        $query->where('salary_max', '<=', $request->salary_to);
      }

      // --- ĐOẠN DƯỚI GIỮ NGUYÊN HOÀN TOÀN ---
      // Sắp xếp theo tin đăng mới nhất và thực hiện phân trang
      $jobs = $query->latest('id')->paginate(9);

      $jobs->getCollection()->transform(function ($job) use ($savedJobIds) {
        $job->is_saved = in_array($job->id, $savedJobIds);
        return $job;
      });

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
    // Kiểm tra xem User đăng nhập đã ứng tuyển chưa
    $applicationStatus = false;
    if (Auth::guard('sanctum')->check()) {
      $applicationStatus = Application::where('job_id', $id)
        ->where('user_id', Auth::guard('sanctum')->id())
        ->value('status');
    }

    // 3. Trả về dữ liệu công việc thành công
    return response()->json([
      'success' => true,
      'message' => 'Lấy thông tin chi tiết tin tuyển dụng thành công.',
      'data' => $job, // Trả về object công việc (không dùng ->items())
      'application_status' => $applicationStatus
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

  public function getCategories()
  {
    try {
      // Lấy ra id và name của tất cả ngành nghề
      $categories = Category::select('id', 'name')->get();

      return response()->json([
        'success' => true,
        'data' => $categories
      ], 200);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Không thể lấy danh sách ngành nghề.',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function trackClick($id)
  {
    // 1. Kiểm tra tin tuyển dụng
    $jobExists = Job::where('id', $id)->exists();
    if (!$jobExists) {
      return response()->json(['success' => false, 'message' => 'Tin không tồn tại.'], 404);
    }

    $today = Carbon::today()->toDateString();

    // 2. Tìm xem ngày hôm nay tin này đã được click chưa
    $query = JobClick::where('job_id', $id)->where('click_date', $today);

    if ($query->exists()) {
      // NẾU ĐÃ CÓ: Bắn thẳng lệnh UPDATE xuống DB +1, không thông qua Object save() nữa
      $query->increment('click_count');
    } else {
      // NẾU CHƯA CÓ: Tạo mới bản ghi, DB tự nạp giá trị mặc định là 1
      JobClick::create([
        'job_id' => $id,
        'click_date' => $today
      ]);
    }

    return response()->json([
      'success' => true,
      'message' => 'Ghi nhận lượt click thành công.'
    ], 200);
  }

}