<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Job;
use App\Models\Company;
use App\Models\Report;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $userId = auth()->id();

        // 1. Validate trực tiếp ngay trong hàm bằng Validator
        $validator = Validator::make($request->all(), [
            'id'          => 'required|integer',
            'type'        => 'required|string|in:job,company',
            'reason_type' => 'required|string|in:fraud,wrong_info,expired,fake_company,bad_behavior,other',
            'description' => 'nullable|string|max:1000',
        ], [
            'id.required'          => 'Thiếu ID đối tượng cần báo cáo.',
            'type.required'        => 'Vui lòng xác định loại báo cáo (job hoặc company).',
            'reason_type.required' => 'Vui lòng chọn lý do báo cáo.',
        ]);

        // Nếu validate thất bại, trả về lỗi ngay lập tức
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // Khởi tạo sẵn các biến null cho Eloquent
        $jobId = null;
        $companyId = null;

        // 2. Sử dụng Eloquent để kiểm tra sự tồn tại của dữ liệu
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

        try {
            // 3. Sử dụng Eloquent ORM để tạo mới dữ liệu thay vì DB::table
            $report = Report::create([
                'user_id' => $userId, // Thay bằng user_id nếu cấu trúc migration của bạn đổi tên nhé
                'job_id'       => $jobId,
                'company_id'   => $companyId,
                'reason_type'  => $request->input('reason_type'),
                'description'  => $request->input('description'),
                'status'       => 'pending', // Mặc định luôn là chờ xử lý
            ]);

            return response()->json([
                'success'   => true,
                'message'   => 'Gửi báo cáo thành công! Ban quản trị sẽ rà soát lại.',
                'report_id' => $report->id
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống khi lưu báo cáo: ' . $e->getMessage()
            ], 500);
        }
    }
}
