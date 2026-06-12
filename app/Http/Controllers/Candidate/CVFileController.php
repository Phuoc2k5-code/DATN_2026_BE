<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\CvFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use App\Models\Candidate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

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
			'cv_file.mimes' => 'Hệ thống chỉ chấp nhận định dạng file PDF hoặc Word (.docx, .doc).',
			'cv_file.max' => 'Dung lượng file vượt quá giới hạn cho phép (Tối đa 5MB).',
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
				'user_id' => $userId,
				'file_name' => $file->getClientOriginalName(),
				'file_path' => $fileUrl,
				'file_size' => $rawFileSize, // 👈 Truyền biến này vào là êm ru, không bị lỗi nữa!
				'type' => 'uploaded',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Tải lên CV thành công!',
				'data' => [
					'file_name' => $file->getClientOriginalName(),
					'file_path' => $fileUrl,
					'file_size' => $fileSizeMB,
					'type' => 'uploaded'
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

	public function downloadAndSaveCV(Request $request, $id = null)
	{
		// 1. LẤY THÔNG TIN NGƯỜI DÙNG ĐANG ĐĂNG NHẬP
		$user = Auth::user();

		$relations = [
			'category',
			'cvTemplate',
			'skills' => function ($query) {
				$query->withPivot('level');
			}
		];

		if ($id) {
			$candidate = Candidate::with($relations)->find($id);
		} else {
			$candidate = Candidate::with($relations)->where('user_id', $user->id)->first();
		}

		if (!$candidate) {
			return response()->json([
				'success' => false,
				'message' => 'Hồ sơ ứng viên không tồn tại hoặc bạn không có quyền truy cập.'
			], 404);
		}

		// 2. CƠ CHẾ ĐỊNH TUYẾN TEMPLATE BLADE
		$viewPath = 'cv_templates.default';

		if ($candidate->cvTemplate && !empty($candidate->cvTemplate->file_path)) {
			$templateName = str_replace('.blade.php', '', $candidate->cvTemplate->file_path);
			$templateName = trim($templateName);
			$targetView = 'cv_templates.' . $templateName;

			if (\View::exists($targetView)) {
				$viewPath = $targetView;
			}
		}

		try {
			// --- 🚀 TÍNH TOÁN ĐƯỜNG DẪN ẢNH VẬT LÝ TUYỆT ĐỐI ---
			$avatarPdfPath = null;

			if (!empty($candidate->avatar_url)) {
				// Lấy ra tên file chính xác (ví dụ: avatar_2_1781165257.png)
				$pureFileName = basename($candidate->avatar_url);
				// Đường dẫn vật lý trên ổ cứng server
				$imagePath = public_path('avatars/' . $pureFileName);
				
				if (file_exists($imagePath) && is_file($imagePath)) {
					$avatarPdfPath = $imagePath; // Truyền đường dẫn vật lý này sang Blade
				}
			}
			$candidate->avatar_pdf_path = $avatarPdfPath;			
			// --- 🚀 XỬ LÝ BIẾN LASTNAME ĐỂ TRÁNH LỖI TRONG BLADE ---
			$nameParts = explode(' ', trim($candidate->full_name));
			$lastName = end($nameParts);

			// 3. ĐỔ DỮ LIỆU VÀO VIEW VÀ KẾT XUẤT PDF
			$pdf = Pdf::loadView($viewPath, compact('candidate', 'lastName'))
				->setPaper('a4', 'portrait')
				->setWarnings(false);

			$pdfContent = $pdf->output();

			// 4. CHUẨN HÓA TÊN FILE VÀ ĐƯỜNG DẪN LƯU TRỮ VẬT LÝ
			$safeName = \Str::slug($candidate->full_name, '_');
			$fileName = 'CV_' . strtoupper($safeName) . '_' . time() . '.pdf';

			$publicFolder = public_path('cv_files');

			if (!file_exists($publicFolder)) {
				mkdir($publicFolder, 0755, true);
			}

			$fullPath = $publicFolder . '/' . $fileName;
			file_put_contents($fullPath, $pdfContent);

			$fileSize = filesize($fullPath);
			$dbFilePath = 'cv_files/' . $fileName;

			// 5. ĐỒNG BỘ DỮ LIỆU VÀO BẢNG cv_files
			\DB::table('cv_files')->insert([
				'user_id' => $id ? $candidate->user_id : $user->id,
				'file_name' => $fileName,
				'file_path' => $dbFilePath,
				'file_size' => $fileSize,
				'type' => 'online',
				'created_at' => now(),
				'updated_at' => now()
			]);

			// 6. TRẢ LUỒNG FILE PDF VỀ PHÍA FRONTEND REACTJS
			return response($pdfContent, 200, [
				'Content-Type' => 'application/pdf',
				'Content-Disposition' => 'inline; filename="' . $fileName . '"',
				'Access-Control-Expose-Headers' => 'Content-Disposition'
			]);

		} catch (\Exception $e) {
			\Log::error('Lỗi tự động lưu và xuất PDF CV: ' . $e->getMessage());
			\Log::info($imagePath);
			\Log::info(file_exists($imagePath));
			return response()->json([
				'success' => false,
				'message' => 'Có lỗi xảy ra trong quá trình xử lý, lưu trữ và xuất file PDF CV.',
				'error' => $e->getMessage()
			], 500);
		}
	}

}
