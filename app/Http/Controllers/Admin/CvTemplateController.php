<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CvTemplate;
use Illuminate\Support\Facades\Validator;
class CvTemplateController extends Controller
{
    public function index()
    {
        try {
            // Lấy tất cả mẫu CV, sắp xếp theo ID tăng dần (hoặc mẫu mới lên trước tùy bạn)
            $templates = CvTemplate::withCount('candidates')
                ->orderBy('id', 'asc')
                ->get();

            // Trả về dữ liệu JSON chuẩn cho React nhận
            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách mẫu CV thành công.',
                'data' => $templates
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống khi lấy danh sách mẫu CV: ' . $e->getMessage()
            ], 500);
        }
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'file_path' => 'required|string|max:255|unique:cv_templates,file_path',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $template = CvTemplate::create([
            'name' => $request->name,
            'file_path' => $request->file_path,
            'description' => $request->description
        ]);

        return response()->json([
          'success' => true,
          'message' => 'Thêm mới mẫu CV thành công!',
          'data' => $template
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $template = CvTemplate::find($id);
        
        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy mẫu CV yêu cầu.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // Bỏ qua kiểm tra unique cho chính ID đang sửa
            'file_path' => 'required|string|max:255|unique:cv_templates,file_path,' . $id,
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $template->update([
            'name' => $request->name,
            'file_path' => $request->file_path,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật mẫu CV thành công!',
            'data' => $template
        ], 200);
    }

    public function destroy($id)
    {
        $template = CvTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy mẫu CV hoặc mẫu này đã bị xóa trước đó.'
            ], 404);
        }

        // Vì Model đã khai báo SoftDeletes, lệnh ->delete() dưới đây sẽ tự động
        // nạp thời gian vào cột deleted_at chứ KHÔNG xóa bản ghi ra khỏi ổ đĩa.
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa mềm mẫu CV thành công vào thùng rác!'
        ], 200);
    }
}
