<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Job;
use App\Models\Company;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;


class ReportController extends Controller
{
  public function store(Request $request)
  {
    $userId = auth()->id();

    // 1. Validate dữ liệu đầu vào
    $validator = Validator::make($request->all(), [
      'id' => 'required|integer',
      'type' => 'required|string|in:job,company',
      'reason_type' => 'required|string',
      'description' => 'nullable|string|max:1000',
    ], [
      'id.required' => 'Thiếu ID đối tượng cần báo cáo.',
      'type.required' => 'Vui lòng xác định loại báo cáo (job hoặc company).',
      'reason_type.required' => 'Vui lòng chọn lý do báo cáo.',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }

    $jobId = null;
    $companyId = null;

    // 2. Kiểm tra sự tồn tại của dữ liệu & Gán ID chuẩn
    if ($request->input('type') === 'job') {
      if (!Job::where('id', $request->input('id'))->exists()) {
        return response()->json(['success' => false, 'message' => 'Bài tuyển dụng không tồn tại.'], 404);
      }
      $jobId = $request->input('id');
    } else {
      if (!Company::where('id', $request->input('id'))->exists()) {
        return response()->json(['success' => false, 'message' => 'Công ty không tồn tại.'], 404);
      }
      $companyId = $request->input('id');
    }

    // ================= CHỐT CHẶN MỚI: KIỂM TRA USER ĐÃ BÁO CÁO HAY CHƯA =================
    $alreadyReported = Report::where('user_id', $userId)
      ->where('status', 'pending') // Chỉ chặn khi báo cáo cũ vẫn đang "Chờ duyệt/Chờ xử lý"
      ->where(function ($query) use ($jobId, $companyId) {
        if ($jobId) {
          $query->where('job_id', $jobId);
        } else {
          $query->where('company_id', $companyId);
        }
      })
      ->exists();

    if ($alreadyReported) {
      return response()->json([
        'success' => false,
        'message' => 'Bạn đã gửi báo cáo vi phạm cho đối tượng này rồi. Vui lòng chờ Ban quản trị xử lý.'
      ], 400); // Trả về mã lỗi 400 Bad Request
    }
    // ===================================================================================

    try {
      // 3. Tạo mới dữ liệu bằng Eloquent ORM
      $report = Report::create([
        'user_id' => $userId,
        'job_id' => $jobId,
        'company_id' => $companyId,
        'reason_type' => $request->input('reason_type'),
        'description' => $request->input('description'),
        'status' => 'pending',
      ]);

      // 4. Logic Auto-Moderation (Tự động ẩn nếu bị report nhiều)
      // Lưu ý: Đoạn code cũ của bạn lấy lộn biến từ request ($request->job_id), mình sửa lại thành biến $jobId chuẩn đã gán ở trên nhé.
      if ($jobId) {
        // Đếm xem bài Job này đã nhận bao nhiêu report ở trạng thái 'pending'
        $reportCount = Report::where('job_id', $jobId)
          ->where('status', 'pending') // Sửa từ 'active' thành 'pending' cho đồng bộ luồng
          ->count();

        // Nếu vượt ngưỡng 15 lượt report -> Tự động ẩn bài
        if ($reportCount >= 15) {
          $job = Job::find($jobId);
          if ($job && $job->status !== 'blocked_by_report') {
            $job->update([
              'status' => 'blocked_by_report',
              'admin_note' => 'Bài viết bị hệ thống tạm ẩn tự động do nhận quá nhiều phản hồi tiêu cực từ cộng đồng.'
            ]);

            // Cập nhật tất cả report đang chờ xử lý của Job này sang 'resolved'
            Report::where('job_id', $jobId)
              ->where('status', 'pending')
              ->update([
                'status' => 'resolved',
                'admin_note' => 'Hệ thống tự động xử lý ẩn bài do vượt ngưỡng 15 lượt báo cáo vi phạm.'
              ]);
          }
        }
      }

      return response()->json([
        'success' => true,
        'message' => 'Gửi báo cáo thành công! Ban quản trị sẽ rà soát lại.',
        'report_id' => $report->id
      ], 201);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi hệ thống khi lưu báo cáo: ' . $e->getMessage()
      ], 500);
    }
  }

  public function getViolationReports(Request $request)
{
    // Kéo kèm thông tin User gửi, Job bị báo cáo, Company bị báo cáo
    $query = Report::with([
        'user' => function ($q) {
            $q->withTrashed();
        },
        // Nạp Job kèm theo đếm tổng số report của chính Job đó
        'job' => function ($q) {
            $q->select('id', 'title')->withCount('reports');
        },
        // Nạp Company kèm theo đếm tổng số report của chính Company đó
        'company' => function ($q) {
            $q->select('id', 'company_name', 'logo_url')->withCount('reports');
        }
    ])->latest();

    // Thêm bộ lọc trạng thái nếu phía React cần lọc (pending, resolved...)
    if ($request->has('status') && $request->status != 'all') {
        $query->where('status', $request->status);
    }

    // Thêm bộ lọc loại báo cáo (Lọc xem báo cáo Job hay báo cáo Company)
    if ($request->has('type') && $request->type != 'all') {
        if ($request->type === 'job') {
            $query->whereNotNull('job_id');
        } elseif ($request->type === 'company') {
            $query->whereNotNull('company_id');
        }
    }

    // Phân trang tự động bắt tham số ?page từ React gửi lên (Mỗi trang 10 dòng)
    $reports = $query->paginate(10);

    return response()->json([
        'success'   => true,
        'message'   => 'Lấy danh sách báo cáo thành công.',
        'data'      => $reports->items(), // Trả về danh sách của trang hiện tại
        'pagination' => [
            'current_page' => $reports->currentPage(),
            'last_page'    => $reports->lastPage(),
            'total'        => $reports->total(),
            'per_page'     => $reports->perPage(),
        ]
    ], 200);
}

  /**
   * API: Xem chi tiết một bản ghi báo cáo vi phạm
   */
  public function showReportDetail($id)
  {
    // Tìm báo cáo kèm theo tất cả thông tin liên quan (kể cả người dùng đã bị xóa)
    $report = Report::with([
      'user' => function ($q) {
        $q->withTrashed(); // Lấy cả thông tin người báo cáo nếu lỡ bị khóa tài khoản
      },
      'job' => function ($q) {
        // Lấy thêm các thông tin chi tiết của Job để Admin dễ đối chiếu
        $q->select('id', 'title', 'company_id', 'status', 'created_at');
      },
      'company' => function ($q) {
        // Lấy thêm thông tin chi tiết của Công ty
        $q->select('id', 'company_name', 'logo_url', 'status');
      }
    ])->find($id);

    // Kiểm tra xem bản ghi có tồn tại không
    if (!$report) {
      return response()->json([
        'success' => false,
        'message' => 'Không tìm thấy thông tin chi tiết của báo cáo này.'
      ], 404);
    }

    return response()->json([
      'success' => true,
      'message' => 'Lấy chi tiết báo cáo thành công.',
      'data' => $report
    ], 200);
  }

  public function resolveReport(Request $request, $id){
    $report = Report::find($id);
    if (!$report) {
      return response()->json([
        'success' => false,
        'message' => 'Không tìm thấy dữ liệu báo cáo này.'
      ], 404);
    }
    // Nếu báo cáo đã xử lý từ trước rồi thì chặn lại không cho bấm nữa
    if ($report->status !== 'pending') {
      return response()->json([
        'success' => false,
        'message' => 'Báo cáo này đã được xử lý hoặc bác bỏ từ trước.'
      ], 400);
    }
    // Nhận lý do kỷ luật từ phía React gửi lên (nếu không nhập thì lấy lý do mặc định)
    $adminNote = $request->input('admin_note', 'Vi phạm tiêu chuẩn đăng tin tuyển dụng.');
    // NẾU LÀ BÁO CÁO TIN TUYỂN DỤNG (JOB)
    if ($report->job_id) {
      $job = Job::find($report->job_id);
      if ($job) {
        // Chuyển sang trạng thái ẩn khóa chuyên biệt để phân biệt với bài chờ duyệt mới
        $job->update([
          'status' => 'blocked_by_report'
        ]);
      }
    }
    // NẾU LÀ BÁO CÁO DOANH NGHIỆP (COMPANY)
    if ($report->company_id) {
      $company = Company::find($report->company_id);
      if ($company) {
        // Bước A: Chuyển trạng thái công ty sang bị đình chỉ
        $company->update([
          'status' => 'suspended'
        ]);
        // Bước B: Tìm tài khoản User (Nhà tuyển dụng) sở hữu công ty này và KHÓA LUÔN
        // (Giả sử bảng companies của bạn có cột user_id liên kết sang bảng users)
        $employer = User::find($company->user_id);
        if ($employer) {
          $employer->delete(); // Soft Delete: Khóa tài khoản, không cho đăng nhập hệ thống nữa
        }
      }
    }
    // 4. Cập nhật trạng thái bản ghi Report sang ĐÃ XỬ LÝ
    $report->update([
      'status' => 'resolved',
      'admin_note' => $adminNote,
      'resolved_at' => Carbon::now() // Lưu mốc thời gian xử lý thực tế
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Đã thực thi lệnh kỷ luật vi phạm và cập nhật trạng thái hệ thống thành công.',
      'data' => $report
    ], 200);
  }

  /**
   * API 2: Bác bỏ báo cáo (Không xử phạt, giữ nguyên hiện trạng bài viết/công ty)
   */
  public function dismissReport(Request $request, $id)
  {
    $report = Report::find($id);

    if (!$report) {
      return response()->json([
        'success' => false,
        'message' => 'Không tìm thấy dữ liệu báo cáo.'
      ], 404);
    }

    if ($report->status !== 'pending') {
      return response()->json([
        'success' => false,
        'message' => 'Báo cáo này đã được xử lý hoặc bác bỏ từ trước.'
      ], 400);
    }

    // 2. Cập nhật thẳng trạng thái Report thành ĐÃ BÁC BỎ
    $report->update([
      'status' => 'dismissed',
      'admin_note' => $request->input('admin_note', 'Báo cáo bị bác bỏ bởi Admin do thiếu căn cứ xác minh.'),
      'resolved_at' => Carbon::now()
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Đã bác bỏ báo cáo vi phạm thành công.',
      'data' => $report
    ], 200);
  }
}

