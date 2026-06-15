<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Candidate;
use App\Models\CvFile;
use App\Models\Job;
use Smalot\PdfParser\Parser;

class AIController extends Controller
{
  public function getRecommendations(Request $request): JsonResponse
  {
    $userId = auth()->id(); // Hoặc $request->input('user_id') tùy cách bạn làm Auth
    $chosenType = $request->input('type'); // 'online' hoặc 'upload'
    $chosenId = $request->input('id');     // ID của Candidate hoặc CvFile

    Log::info("====================================================");
    Log::info("🤖 [AI RECOMMENDATION] Khởi động bộ lọc thông minh cho User ID: " . $userId);

    try {
      $cvContentText = "";

      // ----------------------------------------------------
      // BƯỚC 1: XÁC ĐỊNH NỘI DUNG CV (TỰ ĐỘNG HOẶC USER CHỌN)
      // ----------------------------------------------------

      // TRƯỜNG HỢP A: Chọn Hồ sơ Online HOẶC Chạy Tự động ban đầu (Ưu tiên Online)
      if ($chosenType === 'online' || (empty($chosenType) && empty($chosenId))) {
        $query = Candidate::with(['skills', 'category'])->where('user_id', $userId);
        $candidate = ($chosenId && $chosenType === 'online') ? $query->find($chosenId) : $query->first();

        if ($candidate) {
          Log::info("🔹 [AI] Đang bóc tách dữ liệu từ hồ sơ Online...");
          $skillsList = $candidate->skills ? $candidate->skills->pluck('name')->implode(', ') : '';
          $categoryName = $candidate->category->name ?? 'Chưa phân loại';

          // Chỉ tập trung 3 trường cốt lõi theo công thức chấm điểm của bạn (Bỏ Kinh nghiệm int)
          $cvContentText = "Vị trí công việc: {$candidate->title}\n"
            . "Ngành nghề: {$categoryName}\n"
            . "Kỹ năng chuyên môn: {$skillsList}";
          Log::info($cvContentText);
        }
      }

      // TRƯỜNG HỢP B: Chọn File CV cụ thể HOẶC Hệ thống tự động chuyển sang File vì không có Online
      if (empty($cvContentText)) {
        $query = CvFile::where('user_id', $userId)->where('type', 'uploaded');
        $latestCv = ($chosenId && $chosenType === 'upload') ? $query->find($chosenId) : $query->latest()->first();

        if (!$latestCv) {
          return response()->json(['success' => false, 'message' => 'Vui lòng tạo hồ sơ online hoặc tải lên CV để bắt đầu!'], 400);
        }

        $cvFullPath = public_path(ltrim($latestCv->file_path, '/'));
        if (!file_exists($cvFullPath)) {
          return response()->json(['success' => false, 'message' => 'File CV vật lý không tồn tại trên hệ thống.'], 500);
        }

        // Đọc file PDF
        $pdf = (new Parser())->parseFile($cvFullPath);
        $cvContentText = $pdf->getText();
        Log::info($cvContentText);
      }

      if (empty(trim($cvContentText))) {
        return response()->json(['success' => false, 'message' => 'Nội dung hồ sơ trống, không thể phân tích.'], 400);
      }

      // ----------------------------------------------------
      // BƯỚC 2: TẠO "VÂN TAY KÉP" KIỂM TRA TRẠNG THÁI HỆ THỐNG
      // ----------------------------------------------------

      // 1. Lấy ID lớn nhất của Job đang tuyển trong hệ thống
      $maxJobId = Job::where('status', 'active')->max('id') ?? 0;

      // 2. Mã hóa MD5 nội dung CV hiện tại
      $cvMd5 = md5(trim($cvContentText));

      // 3. Tạo vân tay tổng hợp đại diện cho trạng thái hiện tại
      $currentSystemFingerprint = "cv_{$cvMd5}_maxjob_{$maxJobId}";

      $cacheFingerprintKey = "user_ai_fingerprint_" . $userId;
      $cacheResultKey = "user_ai_jobs_result_" . $userId;

      // KIỂM TRA: Nếu vân tay trùng khớp hoàn toàn (CV không đổi và Không có Job mới)
      if (Cache::has($cacheFingerprintKey) && Cache::get($cacheFingerprintKey) === $currentSystemFingerprint) {
        if (Cache::has($cacheResultKey)) {
          Log::info("🎯 [TOKEN SAVED] Trả về kết quả từ Cache (Tốn 0 Token AI)!");
          return response()->json([
            'success' => true,
            'data' => Cache::get($cacheResultKey),
            'message' => 'Dữ liệu được tải từ bộ nhớ tạm thành công.'
          ], 200);
        }
      }

      // Nếu vân tay lệch -> Tiến hành gọi AI phân tích mới
      Log::info("🚀 [AI TRIGGERED] Phát hiện biến động (Sửa CV/Đổi CV/Có Job mới). Gọi Gemini AI...");

      // ----------------------------------------------------
      // BƯỚC 3: LẤY DANH SÁCH JOB LỌC THÔ
      // ----------------------------------------------------
      $jobs = Job::with(['category', 'skills'])->where('status', 'active')->latest()->take(20)->get();
      if ($jobs->isEmpty()) {
        return response()->json(['success' => true, 'data' => []], 200);
      }

      $jobsDataForAI = [];
      foreach ($jobs as $job) {
        $jobsDataForAI[] = [
          'job_id' => $job->id,
          'title' => $job->title,
          'category' => $job->category->name ?? 'Chưa phân loại',
          'skills' => $job->skills->pluck('name')->toArray(),
          'description' => mb_strimwidth($job->description, 0, 300, "...")
        ];
      }

      // ----------------------------------------------------
      // BƯỚC 4: THIẾT LẬP PROMPT & GỌI GEMINI API
      // ----------------------------------------------------
      $systemInstruction = "Bạn là một thuật toán logic toán học được tích hợp trong hệ thống ATS.\n"
                . "Nhiệm vụ của bạn là tính toán điểm số phù hợp (matching_score) từ 0.00 đến 100.00 giữa CV của ứng viên với DANH SÁCH các công việc được cung cấp.\n\n"
                . "CÔNG THỨC VÀ QUY TẮC CHẤM ĐIỂM CHI TIẾT CHO TỪNG JOB:\n"
                . "1. TITLE - VỊ TRÍ CÔNG VIỆC (Tối đa 30%):\n"
                . "   - Khớp hoàn toàn tiêu đề vị trí công việc: +30 điểm.\n"
                . "   - Khớp một phần hoặc cùng nhóm ngành (Ví dụ: CV 'Web Developer' ứng tuyển Job 'Laravel Developer'): +15 điểm.\n"
                . "   - Khác biệt hoàn toàn về vị trí: +0 điểm.\n\n"
                . "2. CATEGORY - NGÀNH NGHỀ (Tối đa 20%):\n"
                . "   - Trùng khớp hoàn toàn lĩnh vực ngành nghề: +20 điểm.\n"
                . "   - Ngành nghề có liên quan hoặc bổ trợ nhau: +10 điểm.\n"
                . "   - Không liên quan: +0 điểm.\n\n"
                . "3. SKILLS - KỸ NĂNG CHUYÊN MÔN (Tối đa 50%):\n"
                . "   - Điểm số phần này = (Số lượng kỹ năng trong CV đáp ứng được / Tổng số kỹ năng Job yêu cầu) * 50.\n\n"
                . "TỔNG ĐIỂM (matching_score) = Điểm TITLE + Điểm CATEGORY + Điểm SKILLS.\n\n"
                . "YÊU CẦU ĐẦU RA BẮT BUỘC:\n"
                . "- Tính toán số học khách quan, chính xác theo công thức trên cho từng công việc.\n"
                . "- CHỈ trả về duy nhất một chuỗi JSON hợp lệ dạng MẢNG (Array Object), không bọc markdown ```json.\n"
                . "- KHÔNG viết thêm bất kỳ chữ giải thích nào bên ngoài cấu trúc JSON.\n\n"
                . "Cấu trúc phần tử trong Mảng JSON đầu ra bắt buộc:\n"
                . "[\n"
                . "  {\n"
                . "    \"job_id\": 1,\n"
                . "    \"matching_score\": 12.50,\n"
                . "    \"reason\": \"Giải thích ngắn gọn lý do đạt số điểm này dựa trên Title, Category và Skills.\"\n"
                . "  }\n"
                . "]";

            $prompt = "Nội dung hồ sơ ứng viên:\n{$cvContentText}\n\n"
                    . "Danh sách các công việc cần chấm điểm (Dạng JSON):\n" . json_encode($jobsDataForAI, JSON_UNESCAPED_UNICODE);
      // 🚀 ĐOẠN URL ĐÃ ĐƯỢC LÀM SẠCH TINH KHIẾT:
      $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . env('GEMINI_API_KEY');
      $response = Http::withHeaders(['Content-Type' => 'application/json'])
        ->post($geminiUrl, [
          'contents' => [['parts' => [['text' => $prompt]]]],
          'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
          'generationConfig' => ['responseMimeType' => 'application/json']
        ]);

      if ($response->successful()) {
        $aiTextResponse = $response->json()['candidates'][0]['content']['parts'][0]['text'];
        $scoresList = json_decode($aiTextResponse, true) ?? [];

        $scoreMap = [];
        foreach ($scoresList as $item) {
          if (isset($item['job_id'])) {
            $scoreMap[$item['job_id']] = [
              'matching_score' => $item['matching_score'] ?? 0,
              'reason' => $item['reason'] ?? ''
            ];
          }
        }

        $recommendedJobs = [];
        foreach ($jobs as $job) {
          $aiScore = $scoreMap[$job->id]['matching_score'] ?? 0;
          $aiReason = $scoreMap[$job->id]['reason'] ?? 'Chưa có đánh giá chi tiết.';

          if ($aiScore >= 10) {
            $job->matching_score = $aiScore;
            $job->ai_reason = $aiReason;
            $recommendedJobs[] = $job;
          }
        }

        // Sắp xếp điểm số từ cao xuống thấp
        $recommendedJobs = collect($recommendedJobs)->sortByDesc('matching_score')->values()->all();

        // ----------------------------------------------------
        // BƯỚC 5: CẬP NHẬT LẠI KẾT QUẢ VÀ VÂN TAY VÀO CACHE
        // ----------------------------------------------------
        Cache::put($cacheFingerprintKey, $currentSystemFingerprint, now()->addDays(7));
        Cache::put($cacheResultKey, $recommendedJobs, now()->addDays(7));

        Log::info("✅ [AI SUCCESS] Đã lưu kết quả mới và cập nhật vân tay hệ thống.");
        Log::info("====================================================");

        return response()->json(['success' => true, 'data' => $recommendedJobs], 200);
      } else {
        Log::error("❌ [AI] Lỗi API Gemini: " . $response->body());
        return response()->json(['success' => false, 'message' => 'Lỗi kết nối bộ não AI.'], 500);
      }

    } catch (\Exception $e) {
      Log::error("❌ [AI] Khởi động thất bại: " . $e->getMessage());
      return response()->json(['success' => false, 'message' => 'Sự cố: ' . $e->getMessage()], 500);
    }
  }

  public function getUserCvList(): JsonResponse
{
    $userId = auth()->id(); // Lấy ID user đang đăng nhập
    
    // Nếu bạn test không qua Token Auth, hãy tạm thời bỏ cmt dòng dưới:
    // $userId = $userId ?? 2; 

    $cvList = [];

    // 1. Mặc định luôn có option Tự động ở đầu mảng
    $cvList[] = [
        'id' => 'auto',
        'name' => '✨ Tự động (Ưu tiên CV Online)',
        'type' => '',
        'cvId' => ''
    ];

    // 2. Lấy 1 CV Online (Candidate) duy nhất của User
    $candidate = Candidate::where('user_id', $userId)->first();
    if ($candidate) {
        $cvList[] = [
            'id' => 'cv-online-' . $candidate->id,
            'name' => '👨‍💻 Hồ sơ Trực tuyến: ' . ($candidate->title ?? 'Chưa đặt tiêu đề'),
            'type' => 'online',
            'cvId' => $candidate->id
        ];
    }

    // 3. Lấy tối đa 3 CV Uploaded còn hoạt động (Chưa bị xóa soft delete)
    // Laravel tự động lọc deleted_at = null nếu Model CvFile của bạn có dùng trait SoftDeletes
    $uploadedCvs = CvFile::where('user_id', $userId)
        ->where('type', 'uploaded')
        ->whereNull('deleted_at') // Đảm bảo lọc kỹ nếu chưa setup SoftDeletes ở Model
        ->latest()
        ->take(3)
        ->get();

    foreach ($uploadedCvs as $cv) {
        // Lấy tên file gốc từ đường dẫn (ví dụ: /uploads/cv/my-cv.pdf -> my-cv.pdf)
        $fileName = basename($cv->file_path);
        
        $cvList[] = [
            'id' => 'cv-upload-' . $cv->id,
            'name' => '📄 File: ' . $fileName,
            'type' => 'upload',
            'cvId' => $cv->id
        ];
    }

    return response()->json([
        'success' => true,
        'data' => $cvList
    ], 200);
}

}
