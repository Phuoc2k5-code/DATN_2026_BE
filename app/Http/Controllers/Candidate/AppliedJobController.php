<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppliedJobController extends Controller
{
    // Hàm xuất danh sách lịch sử ứng tuyển kèm thông tin CV
    public function history()
    {
        // 1. Lấy thông tin ứng viên đang đăng nhập thông qua tài khoản User
        $candidate = Auth::user()->candidate; 

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không phải là ứng viên hoặc chưa tạo hồ sơ!'
            ], 404);
        }

        // 2. Lấy lịch sử ứng tuyển: lôi luôn Job, Company và file CV đã nộp ra cùng lúc
        $applications = $candidate->applications()
            ->with([
                'job.company', // Lấy dây chuyền: Đơn -> Thuộc về Job -> Thuộc về Company
                'cvFile'       // 🚀 LẤY THÊM: Thông tin file CV từ bảng cv_files (cv_file_id)
            ]) 
            ->orderBy('applied_at', 'desc')
            ->get(); 

        // 3. Trả về mảng dữ liệu JSON cấu trúc chuẩn cho Frontend
        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }
}