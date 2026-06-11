<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\CvFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CVFileController extends Controller
{

    public function uploadCV(Request $request)
    {
        // 1. Kiểm tra đăng nhập
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để thực hiện chức năng này.'
            ], 401);
        }

        // 2. Validate file nghiêm ngặt (Chỉ nhận pdf, docx, doc và tối đa 5MB)
        // 5120 KB tương đương chính xác với 5MB
        $validator = Validator::make($request->all(), [
            'file|mimes:pdf|mimetypes:application/pdf|max:5120'
        ], [
            'cv_file.required' => 'Vui lòng chọn file CV để tải lên.',
            'cv_file.mimes'    => 'Hệ thống chỉ chấp nhận định dạng file PDF hoặc Word (.docx, .doc).',
            'cv_file.max'      => 'Dung lượng file vượt quá giới hạn cho phép (Tối đa 5MB).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $file = $request->file('cv_file');
            $extension = $file->getClientOriginalExtension();
            
            // 🚀 1. LẤY DUNG LƯỢNG FILE GỐC (BYTES) TRƯỚC KHI DI CHUYỂN
            $rawFileSize = $file->getSize(); 
            
            // Tính dung lượng định dạng MB để trả về nếu cần hiển thị
            $fileSizeMB = number_format($rawFileSize / 1024 / 1024, 2) . ' MB';
            
            // Định dạng tên file an toàn
            $fileName = 'cv_user_' . $userId . '_' . time() . '.' . $extension;

            // 🚀 2. SAU ĐÓ MỚI DI CHUYỂN FILE VÀO THƯ MƯC PUBLIC
            $file->move(public_path('cv_files'), $fileName);

            // Đường dẫn URL để lưu DB và trả về Frontend
            $fileUrl = '/cv_files/' . $fileName;

            // 🚀 3. LƯU VÀO DATABASE (Dùng biến $rawFileSize đã lưu từ trước)
            CvFile::create([
                'user_id'   => $userId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $fileUrl,
                'file_size' => $rawFileSize, // 👈 Truyền biến này vào là êm ru, không bị lỗi nữa!
                'type'      => 'uploaded',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tải lên CV thành công!',
                'data' => [
                    'file_name' => $file->getClientOriginalName(),
                    'file_path'  => $fileUrl,
                    'file_size' => $fileSizeMB,
                    'type'      => 'uploaded'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ khi lưu file: ' . $e->getMessage()
            ], 500);
        }
    }

public function destroyFile(Request $request, $id)
{
    try {
        // 1. Xác thực người dùng qua Token
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên đăng nhập đã hết hạn.'
            ], 401);
        }

        // 2. Tìm file thuộc quyền sở hữu của chính User đó (Tránh xóa nhầm file người khác)
        $file = CvFile::where('id', $id)->where('user_id', $user->id)->first();

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tệp tin CV hoặc bạn không có quyền xóa tệp này!'
            ], 404);
        }

        // 3. CHẶN KHÔNG CHO XÓA FILE HỆ THỐNG (type !== 'uploaded')
        if ($file->type !== 'uploaded') {
            return response()->json([
                'success' => false,
                'message' => 'Tệp tin lịch sử ứng tuyển hệ thống đã bị đóng băng, không thể xóa mềm!'
            ], 403);
        }

        // 4. THỰC HIỆN XÓA MỀM (Chỉ cập nhật deleted_at, file vật lý vẫn nguyên vẹn an toàn)
        $file->delete(); 

        return response()->json([
            'success' => true,
            'message' => 'Đã chuyển tệp tin CV vào trạng thái lưu trữ ẩn (Xóa mềm thành công)!'
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Lỗi khi xóa mềm file CV: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Hệ thống Server phát sinh ngoại lệ: ' . $e->getMessage()
        ], 500);
    }
}
}
