<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

use App\Models\CvFile;
use App\Models\Candidate;
use App\Models\Application;

class AppliedJobController extends Controller
{
  // Hàm xuất danh sách lịch sử ứng tuyển kèm thông tin CV
  public function history()
  {
    // 1. Lấy thông tin ứng viên đang đăng nhập thông qua tài khoản User
    $user = Auth::user();

    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => 'Bạn không phải là ứng viên hoặc chưa tạo hồ sơ!'
      ], 404);
    }

    // 2. Lấy lịch sử ứng tuyển
    $applications = $user->applications()
      ->with([
        'job.company', 
        'cvFile'       
      ])
      ->orderBy('applied_at', 'desc')
      ->get();

    // 3. Trả về mảng dữ liệu JSON cấu trúc chuẩn cho Frontend
    return response()->json([
      'success' => true,
      'data' => $applications
    ]);
  }

  /* api này chạy khi người dùng bấm nút ứng tuyển
    1. xuất danh sách cv của người dùng, 
    2. xuất  cv online nếu người dùng chưa tải hoặc hệ thống chưa có file cv này */
  public function quickApplyInit(Request $request)
  {
    $user = Auth::user();
    $relations = [
      'category',
      'cvTemplate',
      'skills' => function ($query) {
        $query->withPivot('level');
      }
    ];
    $candidate = Candidate::with($relations)->where('user_id', $user->id)->first();

    if ($candidate) {
      // Lấy file online mới nhất của RIÊNG user này
      $latestCvFile = CvFile::where('user_id', $user->id)
        ->where('type', 'online')
        ->latest('id')
        ->first();

      if (!$latestCvFile || $candidate->updated_at != $latestCvFile->updated_at) {

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
          // --- TÍNH TOÁN ĐƯỜNG DẪN ẢNH VẬT LÝ TUYỆT ĐỐI ---
          $avatarPdfPath = null;
          $imagePath = '';

          if (!empty($candidate->avatar_url)) {
            $pureFileName = basename($candidate->avatar_url);
            $imagePath = public_path('avatars/' . $pureFileName);

            if (file_exists($imagePath) && is_file($imagePath)) {
              $avatarPdfPath = $imagePath;
            }
          }
          $candidate->avatar_pdf_path = $avatarPdfPath;

          // --- XỬ LÝ BIẾN LASTNAME ĐỂ TRÁNH LỖI TRONG BLADE ---
          $nameParts = explode(' ', trim($candidate->full_name));
          $lastName = end($nameParts);

          // 3. ĐỔ DỮ LIỆU VÀO VIEW VÀ KẾT XUẤT PDF
          $pdf = Pdf::loadView($viewPath, compact('candidate', 'lastName'))
            ->setPaper('a4', 'portrait')
            ->setWarnings(false);

          $pdfContent = $pdf->output();

          // 4. CHUẨN HÓA TÊN FILE VÀ ĐƯỜNG DẪN LƯU TRỮ VẬT LÝ
          $safeName = \Str::slug($candidate->full_name, '_');
          $safeTitle = \Str::slug($candidate->title, '_');
          $fileName = 'CV_' . strtoupper($safeName) . '_' . strtoupper($safeTitle) . '_' . time() . '.pdf';

          $publicFolder = public_path('cv_files');

          if (!file_exists($publicFolder)) {
            mkdir($publicFolder, 0755, true); //  xuất cv bản pdf
          }

          $fullPath = $publicFolder . '/' . $fileName;
          file_put_contents($fullPath, $pdfContent);

          $fileSize = filesize($fullPath);
          $dbFilePath = '/cv_files/' . $fileName;

          // 5. ĐỒNG BỘ DỮ LIỆU BẰNG ELOQUENT (Tạo dòng mới để lưu vết)
          CvFile::create([
            'user_id' => $user->id,
            'file_name' => $fileName,
            'file_path' => $dbFilePath,
            'file_size' => $fileSize,
            'type' => 'online',
            'updated_at' => $candidate->updated_at
          ]);

          // 🎯 FIX LỖI 2: Chỉ lấy data của đúng USER này
          $data1 = CvFile::where('user_id', $user->id)->where('type', 'online')->latest('id')->first();
          $data2 = CvFile::where('user_id', $user->id)->where('type', 'uploaded')->latest('id')->get();

          return response()->json([
            'success' => true,
            'cache' => 'MISS - Rendered New CV',
            'data' => [
              'online' => $data1,
              'uploaded' => $data2
            ]
          ]);

        } catch (\Exception $e) {
          \Log::error('Lỗi tự động lưu và xuất PDF CV: ' . $e->getMessage());
          return response()->json([
            'success' => false,
            'message' => 'Có lỗi xảy ra trong quá trình xử lý, lưu trữ và xuất file PDF CV.',
            'error' => $e->getMessage()
          ], 500);
        }

      } else { // Cv online ko có cập nhật j mới  
        $data1 = CvFile::where('user_id', $user->id)->where('type', 'online')->latest('id')->first();
        $data2 = CvFile::where('user_id', $user->id)->where('type', 'uploaded')->latest('id')->get();

        return response()->json([
          'success' => true,
          'cache' => 'HIT - Used Cache CV',
          'data' => [
            'online' => $data1,
            'uploaded' => $data2
          ]
        ]);
      }
    } else { // ko có  cv online 
      $data1 = CvFile::where('user_id', $user->id)->where('type', 'online')->latest('id')->first();
      $data2 = CvFile::where('user_id', $user->id)->where('type', 'uploaded')->latest('id')->get();

      return response()->json([
        'success' => true,
        'cache' => 'HIT - Used Cache CV',
        'data' => [
          'online' => $data1,
          'uploaded' => $data2
        ]
      ]);
    }
  }

  //hàm xử lý thao tác ứng tuyển nhanh
  public function quickApply(Request $request) 
{
  $userId = Auth::id() ?? 1;
    // 1. Lưu đơn ứng tuyển vào bảng trước với điểm mặc định là 0
    $application = Application::updateOrCreate(
        [
            'job_id'  => $request->job_id,
            'user_id' => $userId,
        ],
        [
            'cv_file_id'     => $request->cv_file_id,
            'description'    => $request->description ?? '',
            'matching_score' => 0, // Reset điểm về 0 để chờ Queue AI tính toán lại dựa trên file CV mới
            'status'         => 'pending',
            'applied_at'     => now(),
        ]
    );

    // 2. NÉM CÔNG VIỆC CHẤM ĐIỂM VÀO HÀNG ĐỢI (QUEUE) ĐỂ CHẠY NGẦM
    \App\Jobs\AnalyzeCVJob::dispatch($application->id);

    // 3. Trả kết quả ngay lập tức cho người dùng, không bắt họ chờ AI
    return response()->json([
        'success' => true,
        'message' => 'Ứng tuyển thành công! Hệ thống đang phân tích CV của bạn ngầm.'
    ], 201);
}
}