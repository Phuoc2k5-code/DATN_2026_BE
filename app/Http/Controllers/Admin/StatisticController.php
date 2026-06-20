<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Job;
use App\Models\Application;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticController extends Controller
{
    public function getStatistics(Request $request)
    {
        try {
            $range = $request->query('range', 'month');
            $startDate = Carbon::now();

            // 1. Xác định mốc thời gian bắt đầu dựa vào bộ lọc range
            if ($range === 'week') {
                $startDate = Carbon::now()->subDays(7);
            } elseif ($range === 'year') {
                $startDate = Carbon::now()->startOfYear();
            } else {
                $startDate = Carbon::now()->subDays(30); // Mặc định là 'month' (30 ngày qua)
            }

            // Mốc so sánh của chu kỳ trước đó để tính toán phần trăm tăng/giảm (%)
            $daysDiff = Carbon::now()->diffInDays($startDate);
            $previousPeriodStart = Carbon::parse($startDate)->subDays($daysDiff);

            // 2. Lấy dữ liệu cho Summary Cards (Chu kỳ hiện tại)
            $newUsersCount = User::where('created_at', '>=', $startDate)->count();
            $newJobsCount = Job::where('created_at', '>=', $startDate)->count();
            $newApplicationsCount = Application::where('applied_at', '>=', $startDate)->count();

            // Dữ liệu chu kỳ trước để tính % tăng trưởng
            $prevUsersCount = User::whereBetween('created_at', [$previousPeriodStart, $startDate])->count();
            $prevJobsCount = Job::whereBetween('created_at', [$previousPeriodStart, $startDate])->count();
            $prevAppsCount = Application::whereBetween('applied_at', [$previousPeriodStart, $startDate])->count();

            // 3. Xử lý dữ liệu biểu đồ 1: Tăng trưởng người dùng (Gom nhóm theo ngày/tháng)
            $userGrowthData = User::where('created_at', '>=', $startDate)
                ->select(
                    $range === 'year' ? DB::raw("DATE_FORMAT(created_at, '%m/%Y') as label") : DB::raw("DATE_FORMAT(created_at, '%d/%m') as label"),
                    DB::raw('count(*) as total')
                )
                ->groupBy('label')
                ->orderBy('created_at', 'asc')
                ->get();

            // 4. Xử lý dữ liệu biểu đồ 2: Tỷ lệ ngành nghề tuyển dụng (Top ngành nhiều bài đăng nhất)
            $categoryChartData = Category::withCount('jobs')
                ->orderBy('jobs_count', 'desc')
                ->take(5)
                ->get()
                ->map(function ($cat) {
                    return [
                        'name' => $cat->name,
                        'value' => $cat->jobs_count
                    ];
                });

            // 5. Dữ liệu bảng: Top 5 doanh nghiệp đăng tuyển nhiều nhất kèm lượt ứng tuyển
            // Giả định quan hệ: Company hasMany Job; Job hasMany Application
            $topCompanies = Company::select('id', 'company_name', 'industry')
                ->withCount([
                    'jobs' => function ($query) use ($startDate) {
                        $query->where('created_at', '>=', $startDate);
                    }
                ])
                ->withCount([
                    'applications' => function ($query) use ($startDate) {
                        // LƯU Ý: Vì dùng HasManyThrough, bảng ứng tuyển đích nên chỉ định rõ 'applications.applied_at'
                        $query->where('applications.applied_at', '>=', $startDate);
                    }
                ])
                ->orderBy('jobs_count', 'desc')
                ->take(5)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy dữ liệu thống kê thành công!',
                'data' => [
                    'cards' => [
                        'users' => [
                            'current' => $newUsersCount,
                            'growth' => $this->calculateGrowth($newUsersCount, $prevUsersCount)
                        ],
                        'jobs' => [
                            'current' => $newJobsCount,
                            'growth' => $this->calculateGrowth($newJobsCount, $prevJobsCount)
                        ],
                        'applications' => [
                            'current' => $newApplicationsCount,
                            'growth' => $this->calculateGrowth($newApplicationsCount, $prevAppsCount)
                        ]
                    ],
                    'charts' => [
                        'user_growth' => $userGrowthData,
                        'category_distribution' => $categoryChartData
                    ],
                    'top_companies' => $topCompanies
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xử lý dữ liệu thống kê hệ thống.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Hàm tính toán phần trăm tăng trưởng phụ trợ
    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
