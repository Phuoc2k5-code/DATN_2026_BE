<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Job;
use App\Models\Report;
use App\Models\Application;
use App\Models\JobClick;
use Carbon\Carbon;
use DB;

class AdminController extends Controller
{
    public function getDashboardStats()
    {
        // 1. LẤY SỐ LIỆU CHO 4 TOP CARDS
        $totalUsers = User::count();

        // Tin tuyển dụng mới trong tháng hiện tại
        $newJobsThisMonth = Job::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // Tin tuyển dụng đang trong trạng thái chờ duyệt
        $pendingJobsCount = Job::where('status', 'pending')->count();

        // Báo cáo vi phạm đang ở trạng thái chờ xử lý (pending)
        $pendingReportsCount = Report::where('status', 'pending')->count();


        // 2. LẤY 7 TIN TUYỂN DỤNG CHỜ DUYỆT MỚI NHẤT (CHO DATA TABLE)
        // Nạp kèm quan hệ 'company' để lấy tên doanh nghiệp
        $latestPendingJobs = Job::with(['company:id,company_name'])
            ->where('status', 'pending')
            ->latest('id')
            ->take(7)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'company_name' => $job->company->company_name ?? 'N/A',
                    'title' => $job->title,
                    'created_at' => Carbon::parse($job->created_at)->format('d/m/Y'),
                    'status' => $job->status
                ];
            });

        // 3. XỬ LÝ DỮ LIỆU BIỂU ĐỒ CỘT/ĐƯỜNG (ỨNG TUYỂN TRONG 7 NGÀY QUA)
        $chartLineBar = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $labelString = $date->format('d/m'); // Định dạng hiển thị ví dụ: "16/06"

            // Đếm số lượt nộp đơn ứng tuyển (Applications) trong ngày này
            $applyCount = Application::whereDate('applied_at', $dateString)->count();

            // Giả lập thêm số lượt truy cập (Views) nếu bạn chưa có bảng click_tracks, 
            // Hoặc bạn có thể đếm từ một bảng log khác. Ở đây mình tính tỉ lệ tạm thời:
            $viewCount = JobClick::where('click_date', $dateString)
                ->sum('click_count');

            $chartLineBar[] = [
                'name' => $labelString,
                'Lượt ứng tuyển' => $applyCount,
                'Lượt truy cập' => $viewCount
            ];
        }

        // 4. XỬ LÝ DỮ LIỆU BIỂU ĐỒ TRÒN (TỶ LỆ TRẠNG THÁI TIN ĐĂNG)
        // Lấy danh sách số lượng phân nhóm theo cột status trong DB
        $jobStatusStats = Job::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        // Định nghĩa màu sắc và tên hiển thị tương ứng để Frontend chỉ việc vẽ
        $statusLabels = [
            'active' => ['label' => 'Đang hiển thị', 'color' => '#10B981'],  // Xanh lá
            'pending' => ['label' => 'Đang chờ duyệt', 'color' => '#F59E0B'], // Cam
            'closed' => ['label' => 'Đã hết hạn', 'color' => '#EF4444'],   // Đỏ
            'rejected' => ['label' => 'Bị từ chối', 'color' => '#64748B']   // Xám
        ];

        $chartDoughnut = [];
        foreach ($statusLabels as $statusKey => $config) {
            $count = $jobStatusStats[$statusKey] ?? 0;
            // Chỉ đẩy vào biểu đồ tròn nếu trạng thái đó có tin bài (tránh bị trống map)
            if ($count >= 0) {
                $chartDoughnut[] = [
                    'name' => $config['label'],
                    'value' => $count,
                    'color' => $config['color']
                ];
            }
        }

        // 5. TRẢ VỀ JSON TỔNG HỢP
        return response()->json([
            'success' => true,
            'cards' => [
                'total_users' => $totalUsers,
                'new_jobs_month' => $newJobsThisMonth,
                'pending_jobs' => $pendingJobsCount,
                'pending_reports' => $pendingReportsCount
            ],
            'charts' => [
                'line_bar' => $chartLineBar,
                'doughnut' => $chartDoughnut
            ],
            'latest_pending_jobs' => $latestPendingJobs
        ], 200);
    }
}
