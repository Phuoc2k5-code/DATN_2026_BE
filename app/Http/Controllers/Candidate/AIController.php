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
use App\Jobs\ProcessAiRecommendation;

class AIController extends Controller
{

    // hàm dùng AI để gợi ý việc làm
    // public function getRecommendations(Request $request): JsonResponse
    // {
    //     $userId = auth()->id();

    //     // Dữ liệu nhận từ Frontend gửi lên
    //     $chosenType = $request->input('type'); // 'online' hoặc 'uploaded'
    //     $chosenId = $request->input('id');     // ID cụ thể của hồ sơ/file

    //     Log::info("=====================================================================");
    //     Log::info("🤖 [AI ATS START] Bắt đầu xử lý đề xuất cho User ID: " . $userId);
    //     Log::info("📥 [PARAMS INPUT] Frontend gửi lên -> Type: [" . ($chosenType ?? 'NULL') . "], ID: [" . ($chosenId ?? 'NULL') . "]");

    //     try {
    //         // Biến lưu trữ nội dung văn bản CV thô sau khi bóc tách
    //         $cvContentText = "";

    //         // Cờ đánh dấu xem Người dùng đang chủ động click chọn hay đang chạy tự động
    //         $isUserSelecting = !empty($chosenType) && !empty($chosenId);

    //         // USER CHỦ ĐỘNG CHỌN CV TRÊN DROPDOWN (CÓ PARAMS CỤ THỂ)
    //         if ($isUserSelecting) {
    //             Log::info("Phát hiện thao tác click chọn cụ thể từ người dùng.");

    //             // Người dùng chọn Hồ sơ Online cụ thể
    //             if ($chosenType === 'online') {
    //                 Log::info("🔍 [TRUY VẤN] Đang tìm kiếm Hồ sơ Online có ID: {$chosenId} của User: {$userId}");
    //                 $candidate = Candidate::with(['skills', 'category'])->where('user_id', $userId)->find($chosenId);

    //                 if ($candidate) {
    //                     Log::info("✅ [BÓC TÁCH SUCCESS] Tìm thấy Hồ sơ Online. Tiến hành trích xuất chữ...");
    //                     $skillsList = $candidate->skills ? $candidate->skills->pluck('name')->implode(', ') : '';
    //                     $categoryName = $candidate->category->name ?? 'Chưa phân loại';

    //                     $cvContentText = "Vị trí công việc: {$candidate->title}\n"
    //                         . "Ngành nghề: {$categoryName}\n"
    //                         . "Kỹ năng chuyên môn: {$skillsList}";
    //                 } else {
    //                     Log::warning("⚠️ [HÀNG RÀO PHÒNG THỦ] Hồ sơ Online ID [{$chosenId}] KHÔNG TỒN TẠI (Đã bị xóa). Kích hoạt chế độ Fallback!");
    //                 }
    //             }

    //             //Người dùng chọn File CV PDF cụ thể
    //             if ($chosenType === 'uploaded' || $chosenType === 'upload') {
    //                 Log::info("🔍 [TRUY VẤN] Đang tìm kiếm File CV có ID: {$chosenId} của User: {$userId}");
    //                 $latestCv = CvFile::where('user_id', $userId)->where('type', 'uploaded')->find($chosenId);

    //                 if ($latestCv) {
    //                     Log::info("📂 [FILE CHECK] Tìm thấy bản ghi File CV trên Database. Kiểm tra file vật lý...");
    //                     $cvFullPath = public_path(ltrim($latestCv->file_path, '/'));

    //                     if (file_exists($cvFullPath)) {
    //                         Log::info("📝 [PARSING PDF] File vật lý hợp lệ. Đang dùng thư viện Smalot để đọc Text thô...");
    //                         $pdf = (new Parser())->parseFile($cvFullPath);
    //                         $cvContentText = $pdf->getText();
    //                         $chosenType = 'uploaded'; // Chuẩn hóa lại chữ 'upload' thành 'uploaded' đồng bộ với hệ thống
    //                     } else {
    //                         Log::warning("⚠️ [HÀNG RÀO PHÒNG THỦ] Bản ghi DB tồn tại nhưng File vật lý tại [{$cvFullPath}] đã bị xóa mất!");
    //                     }
    //                 } else {
    //                     Log::warning("⚠️ [HÀNG RÀO PHÒNG THỦ] File CV ID [{$chosenId}] KHÔNG TỒN TẠI trên Database (Đã bị xóa). Kích hoạt chế độ Fallback!");
    //                 }
    //             }
    //         }

    //         // LUỒNG TỰ ĐỘNG 
    //         if (empty(trim($cvContentText))) {
    //             Log::info("Kích hoạt cơ chế Tự động tìm kiếm (Ưu tiên: Online trước -> File PDF sau)");

    //             // Thử bốc cái hồ sơ Online đầu tiên của user
    //             $candidate = Candidate::with(['skills', 'category'])->where('user_id', $userId)->first();

    //             if ($candidate) {
    //                 Log::info("Khớp thành công: Chọn Hồ sơ Online mặc định (ID thực tế: {$candidate->id})");
    //                 $skillsList = $candidate->skills ? $candidate->skills->pluck('name')->implode(', ') : '';
    //                 $categoryName = $candidate->category->name ?? 'Chưa phân loại';

    //                 $cvContentText = "Vị trí công việc: {$candidate->title}\n"
    //                     . "Ngành nghề: {$categoryName}\n"
    //                     . "Kỹ năng chuyên môn: {$skillsList}";

    //                 // Đồng bộ lại thông tin thực tế để gán key Cache và phản hồi cho Frontend
    //                 $chosenType = 'online';
    //                 $chosenId = $candidate->id;
    //             } else {
    //                 Log::info("User không có hồ sơ Online. Chuyển sang tìm kiếm File PDF...");

    //                 // Bước 2.2: Nếu không có Online, bốc file PDF mới nhất tải lên
    //                 $latestCv = CvFile::where('user_id', $userId)->where('type', 'uploaded')->latest()->first();

    //                 if ($latestCv) {
    //                     Log::info("Khớp thành công: Chọn File PDF mới nhất (ID thực tế: {$latestCv->id})");
    //                     $cvFullPath = public_path(ltrim($latestCv->file_path, '/'));

    //                     if (file_exists($cvFullPath)) {
    //                         $pdf = (new Parser())->parseFile($cvFullPath);
    //                         $cvContentText = $pdf->getText();

    //                         // Đồng bộ lại thông tin thực tế
    //                         $chosenType = 'uploaded';
    //                         $chosenId = $latestCv->id;
    //                     } else {
    //                         Log::error("❌ File PDF vật lý của luồng Auto cũng không tồn tại trên ổ cứng.");
    //                     }
    //                 }
    //             }
    //         }

    //         if (empty(trim($cvContentText))) {
    //             Log::warning("🛑 [STOP] Hệ thống dừng lại vì User ID [{$userId}] trống rỗng toàn bộ dữ liệu hồ sơ.");
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Vui lòng tạo ít nhất một hồ sơ online hoặc tải lên một file CV để hệ thống có dữ liệu phân tích!'
    //             ], 400);
    //         }

    //         // TẠO "VÂN TAY KÉP" KIỂM TRA TRẠNG THÁI CACHE HỆ THỐNG
    //         $maxJobId = Job::where('status', 'active')->max('id') ?? 0;
    //         $cvMd5 = md5(trim($cvContentText));
    //         $currentSystemFingerprint = "cv_{$cvMd5}_maxjob_{$maxJobId}";

    //         $cacheFingerprintKey = "user_ai_fingerprint_{$userId}_{$chosenType}_{$chosenId}";
    //         $cacheResultKey = "user_ai_jobs_result_{$userId}_{$chosenType}_{$chosenId}";

    //         Log::info("Mã định danh hiện tại: {$currentSystemFingerprint}");

    //         // KIỂM TRA CACHE HIT
    //         if (Cache::has($cacheFingerprintKey) && Cache::get($cacheFingerprintKey) === $currentSystemFingerprint) {
    //             if (Cache::has($cacheResultKey)) {
    //                 Log::info("Trùng khớp vân tay! Trả kết quả lưu tạm, KHÔNG GỌI API GEMINI.");
    //                 Log::info("=====================================================================");
    //                 return response()->json([
    //                     'success' => true,
    //                     'data' => Cache::get($cacheResultKey),
    //                     'analyzed_type' => $chosenType,
    //                     'analyzed_id' => $chosenId,
    //                     'message' => 'Dữ liệu tải nhanh từ bộ nhớ đệm hệ thống.'
    //                 ], 200);
    //             }
    //         }

    //         Log::info("Vân tay không trùng khớp (Hoặc chưa từng quét). Bắt đầu gọi Gemini AI...");

    //         // LẤY DANH SÁCH JOB LỌC THÔ (ĐÃ LOAD ĐỦ COMPANY, CATEGORY, SKILLS)
    //         $jobs = Job::with(['category', 'skills', 'company'])->where('status', 'active')->latest()->take(20)->get();
    //         if ($jobs->isEmpty()) {
    //             Log::info("📭 Hệ thống đang không có bất kỳ Job tuyển dụng nào hoạt động.");
    //             return response()->json(['success' => true, 'data' => []], 200);
    //         }

    //         // Gom dữ liệu Job gửi đi tối ưu Token
    //         $jobsDataForAI = [];
    //         foreach ($jobs as $job) {
    //             $jobsDataForAI[] = [
    //                 'job_id' => $job->id,
    //                 'title' => $job->title,
    //                 'category' => $job->category->name ?? 'Chưa phân loại',
    //                 'skills' => $job->skills->pluck('name')->toArray(),
    //             ];
    //         }

    //         // TRAIN CHO AI & GỌI GEMINI API
    //         $systemInstruction = "Bạn là một thuật toán logic toán học được tích hợp trong hệ thống ATS.\n"
    //             . "Nhiệm vụ của bạn là tính toán điểm số phù hợp (matching_score) từ 0.00 đến 100.00 giữa CV của ứng viên với DANH SÁCH các công việc được cung cấp.\n\n"
    //             . "CÔNG THỨC VÀ QUY TẮC CHẤM ĐIỂM CHI TIẾT CHO TỪNG JOB:\n"
    //             . "1. TITLE - VỊ TRÍ CÔNG VIỆC (Tối đa 30%):\n"
    //             . "   - Khớp hoàn toàn tiêu đề vị trí công việc: +30 điểm.\n"
    //             . "   - Khớp một phần hoặc cùng nhóm ngành (Ví dụ: CV 'Web Developer' ứng tuyển Job 'Laravel Developer'): +15 điểm.\n"
    //             . "   - Khác biệt hoàn toàn về vị trí: +0 điểm.\n\n"
    //             . "2. CATEGORY - NGÀNH NGHỀ (Tối đa 20%):\n"
    //             . "   - Trùng khớp hoàn toàn lĩnh vực ngành nghề: +20 điểm.\n"
    //             . "   - Ngành nghề có liên quan hoặc bổ trợ nhau: +10 điểm.\n"
    //             . "   - Không liên quan: +0 điểm.\n\n"
    //             . "3. SKILLS - KỸ NĂNG CHUYÊN MÔN (Tối đa 50%):\n"
    //             . "   - Điểm số phần này = (Số lượng kỹ năng trong CV đáp ứng được / Tổng số kỹ năng Job yêu cầu) * 50.\n\n"
    //             . "TỔNG ĐIỂM (matching_score) = Điểm TITLE + Điểm CATEGORY + Điểm SKILLS.\n\n"
    //             . "YÊU CẦU ĐẦU RA BẮT BUỘC:\n"
    //             . "- Tính toán số học khách quan, chính xác theo công thức trên cho từng công việc.\n"
    //             . "- CHỈ trả về duy nhất một chuỗi JSON hợp lệ dạng MẢNG (Array Object), không bọc markdown ```json.\n"
    //             . "- KHÔNG viết thêm bất kỳ chữ giải thích nào bên ngoài cấu trúc JSON.\n\n"
    //             . "Cấu trúc phần tử trong Mảng JSON đầu ra bắt buộc:\n"
    //             . "[\n"
    //             . "  {\n"
    //             . "    \"job_id\": 1,\n"
    //             . "    \"matching_score\": 12.50,\n"
    //             . "    \"reason\": \"Giải thích ngắn gọn lý do đạt số điểm này dựa trên Title, Category và Skills.\"\n"
    //             . "  }\n"
    //             . "]";

    //         $prompt = "Nội dung hồ sơ ứng viên:\n{$cvContentText}\n\n"
    //             . "Danh sách các công việc cần chấm điểm (Dạng JSON):\n" . json_encode($jobsDataForAI, JSON_UNESCAPED_UNICODE);

    //         $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . env('GEMINI_API_KEY');

    //         Log::info("🌐 [API HTTP CALL] Đang tạo Request gửi tới Google Gemini API...");
    //         $response = Http::withHeaders(['Content-Type' => 'application/json'])->timeout(120)
    //             ->post($geminiUrl, [
    //                 'contents' => [['parts' => [['text' => $prompt]]]],
    //                 'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
    //                 'generationConfig' => ['responseMimeType' => 'application/json']
    //             ]);

    //         if ($response->successful()) {
    //             Log::info("🎉 [API HTTP SUCCESS] Google Gemini trả kết quả về thành công!");
    //             $aiTextResponse = $response->json()['candidates'][0]['content']['parts'][0]['text'];
    //             $scoresList = json_decode($aiTextResponse, true) ?? [];

    //             $scoreMap = [];
    //             foreach ($scoresList as $item) {
    //                 if (isset($item['job_id'])) {
    //                     $scoreMap[$item['job_id']] = [
    //                         'matching_score' => $item['matching_score'] ?? 0,
    //                         'reason' => $item['reason'] ?? ''
    //                     ];
    //                 }
    //             }

    //             // 🌟 BƯỚC QUAN TRỌNG: KHỚP ĐIỂM AI VÀ ÉP GIỮ NGUYÊN HOÀN TOÀN CÁC LIÊN KẾT (RELATIONS)
    //             $recommendedJobsArray = $jobs->map(function ($job) use ($scoreMap) {
    //                 $aiScore = $scoreMap[$job->id]['matching_score'] ?? 0;
    //                 $aiReason = $scoreMap[$job->id]['reason'] ?? 'Chưa có đánh giá chi tiết.';

    //                 // Gán thuộc tính động trực tiếp vào thuộc tính của Model
    //                 $job->setAttribute('matching_score', $aiScore);
    //                 $job->setAttribute('ai_reason', $aiReason);

    //                 return $job;
    //             })
    //                 // Lọc bỏ những công việc có điểm thấp hơn 10
    //                 ->filter(function ($job) {
    //                     return $job->matching_score >= 10;
    //                 })
    //                 // Sắp xếp điểm giảm dần
    //                 ->sortByDesc('matching_score')
    //                 ->values()
    //                 // Chuyển toàn bộ dữ liệu kèm toàn bộ Relations (company, skills, category) sang mảng Array thuần lồng nhau
    //                 ->toArray();

    //             // BƯỚC 5: LƯU TRỮ VÀO BỘ NHỚ ĐỆM (Hạn lưu 7 ngày)
    //             Cache::put($cacheFingerprintKey, $currentSystemFingerprint, now()->addDays(7));
    //             Cache::put($cacheResultKey, $recommendedJobsArray, now()->addDays(7));

    //             Log::info("💾 [CACHE SAVED] Đã đóng gói lưu kết quả kèm đầy đủ thông tin liên kết.");
    //             Log::info("=====================================================================");
    //             Log::info($recommendedJobsArray);
    //             return response()->json([
    //                 'success' => true,
    //                 'data' => $recommendedJobsArray,
    //                 'analyzed_type' => $chosenType,
    //                 'analyzed_id' => $chosenId
    //             ], 200);
    //         } else {
    //             Log::error("❌ [AI API ERROR] Google Gemini từ chối xử lý: " . $response->body());
    //             return response()->json(['success' => false, 'message' => 'Hệ thống AI đang quá tải, vui lòng thử lại sau!'], 500);
    //         }

    //     } catch (\Exception $e) {
    //         Log::error("❌ [AI ATS CRITICAL EXCEPTION] Sự cố hệ thống nghiêm trọng: " . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Gặp sự cố khi bóc tách dữ liệu: ' . $e->getMessage()], 500);
    //     }
    // }

    public function getRecommendations(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $chosenType = $request->input('type'); // 'online' hoặc 'uploaded'
        $chosenId = $request->input('id');     // ID cụ thể

        Log::info("=====================================================================");
        Log::info("🤖 [AI ATS TRIGGER] Tiếp nhận Request từ Frontend cho User ID: " . $userId);

        try {
            $cvContentText = "";
            $isUserSelecting = !empty($chosenType) && !empty($chosenId);

            // LUỒNG 1: USER CHỦ ĐỘNG CHỌN TRÊN UI
            if ($isUserSelecting) {
                if ($chosenType === 'online') {
                    $candidate = Candidate::with(['skills', 'category'])->where('user_id', $userId)->find($chosenId);
                    if ($candidate) {
                        $skillsList = $candidate->skills ? $candidate->skills->pluck('name')->implode(', ') : '';
                        $categoryName = $candidate->category->name ?? 'Chưa phân loại';
                        $cvContentText = "Vị trí công việc: {$candidate->title}\nNgành nghề: {$categoryName}\nKỹ năng: {$skillsList}";
                    }
                }

                if ($chosenType === 'uploaded' || $chosenType === 'upload') {
                    $latestCv = CvFile::where('user_id', $userId)->where('type', 'uploaded')->find($chosenId);
                    if ($latestCv) {
                        $cvFullPath = public_path(ltrim($latestCv->file_path, '/'));
                        if (file_exists($cvFullPath)) {
                            // Tối ưu: Dùng cache bóc tách PDF tránh đọc đĩa nhiều lần
                            $rawText = Cache::remember("cv_raw_text_id_{$latestCv->id}", now()->addDays(7), function () use ($cvFullPath) {
                                return (new Parser())->parseFile($cvFullPath)->getText();
                            });

                            // 🔥 FIX CHÍ MẠNG: Lọc sạch ký tự Malformed UTF-8 trước khi truyền đi tiếp
                            $cvContentText = mb_convert_encoding($rawText, 'UTF-8', 'UTF-8');
                            $chosenType = 'uploaded';
                        }
                    }
                }
            }

            // LUỒNG 2: TỰ ĐỘNG LẤY NẾU TRỐNG (FALLBACK)
            if (empty(trim($cvContentText))) {
                $candidate = Candidate::with(['skills', 'category'])->where('user_id', $userId)->first();
                if ($candidate) {
                    $skillsList = $candidate->skills ? $candidate->skills->pluck('name')->implode(', ') : '';
                    $categoryName = $candidate->category->name ?? 'Chưa phân loại';
                    $cvContentText = "Vị trí công việc: {$candidate->title}\nNgành nghề: {$categoryName}\nKỹ năng: {$skillsList}";
                    $chosenType = 'online';
                    $chosenId = $candidate->id;
                } else {
                    $latestCv = CvFile::where('user_id', $userId)->where('type', 'uploaded')->latest()->first();
                    if ($latestCv) {
                        $cvFullPath = public_path(ltrim($latestCv->file_path, '/'));
                        if (file_exists($cvFullPath)) {
                            $rawText = Cache::remember("cv_raw_text_id_{$latestCv->id}", now()->addDays(7), function () use ($cvFullPath) {
                                return (new Parser())->parseFile($cvFullPath)->getText();
                            });

                            // 🔥 FIX CHÍ MẠNG: Lọc sạch ký tự Malformed UTF-8 ở luồngfallback
                            $cvContentText = mb_convert_encoding($rawText, 'UTF-8', 'UTF-8');
                            $chosenType = 'uploaded';
                            $chosenId = $latestCv->id;
                        }
                    }
                }
            }

            if (empty(trim($cvContentText))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng tạo hồ sơ online hoặc tải lên một file CV để hệ thống có dữ liệu!'
                ], 400);
            }

            // TẠO ĐỊNH DANH ĐỂ KIỂM TRA PHIÊN
            $maxJobId = Job::where('status', 'active')->max('id') ?? 0;
            $cvMd5 = md5(trim($cvContentText));
            $currentSystemFingerprint = "cv_{$cvMd5}_maxjob_{$maxJobId}";

            $cacheFingerprintKey = "user_ai_fingerprint_{$userId}_{$chosenType}_{$chosenId}";
            $cacheStatusKey = "ai_status_{$userId}_{$chosenType}_{$chosenId}";
            $cacheResultKey = "user_ai_jobs_result_{$userId}_{$chosenType}_{$chosenId}";

            if (Cache::has($cacheFingerprintKey) && Cache::get($cacheFingerprintKey) === $currentSystemFingerprint) {
                if (Cache::get($cacheStatusKey) === 'completed' && Cache::has($cacheResultKey)) {
                    Log::info("⚡ [CACHE HIT] Trả nhanh kết quả từ bộ nhớ đệm.");
                    return response()->json([
                        'success' => true,
                        'status' => 'completed',
                        'data' => Cache::get($cacheResultKey),
                        'analyzed_type' => $chosenType,
                        'analyzed_id' => $chosenId
                    ]);
                }
            }

            // BƯỚC 2: KIỂM TRA XEM JOB NÀY CÓ ĐANG CHẠY NỬA CHỪNG KHÔNG (TRÁNH GỌI TRÙNG ĐÈ)
            if (Cache::get($cacheStatusKey) === 'processing') {
                Log::info("⏳ [ALREADY RUNNING] Hệ thống đang xử lý request này rồi, trả về trạng thái chờ...");
                return response()->json([
                    'success' => true,
                    'status' => 'processing',
                    'message' => 'Hệ thống vẫn đang phân tích dữ liệu cũ...'
                ]);
            }

            // BƯỚC 3: CHUẨN BỊ LẤY DANH SÁCH JOB LỌC THÔ ĐỂ GỬI QUA QUEUE
            $jobs = Job::with(['category', 'skills'])->where('status', 'active')->latest()->take(20)->get();
            if ($jobs->isEmpty()) {
                return response()->json(['success' => true, 'status' => 'completed', 'data' => []]);
            }

            $jobsDataForAI = [];
            foreach ($jobs as $job) {
                $jobsDataForAI[] = [
                    'job_id' => $job->id,
                    'title' => $job->title,
                    'category' => $job->category->name ?? 'Chưa phân loại',
                    'skills' => $job->skills->pluck('name')->toArray()
                ];
            }

            // THIẾT LẬP TRẠNG THÁI BAN ĐẦU LÀ ĐANG XỬ LÝ VÀ ĐĂNG KÝ VÂN TAY VÀO CACHE
            Cache::put($cacheStatusKey, 'processing', now()->addMinutes(15));
            Cache::put($cacheFingerprintKey, $currentSystemFingerprint, now()->addDays(7));

            // 🔥 ĐẨY VÀO QUEUE CHẠY NGẦM VÀ LẬP TỨC THOÁT RA TRẢ LỜI FRONTEND
            ProcessAiRecommendation::dispatch(
                $userId,
                $cvContentText,
                $jobsDataForAI,
                $cacheResultKey,
                $cacheStatusKey,
                $chosenType,
                $chosenId
            );

            return response()->json([
                'success' => true,
                'status' => 'processing',
                'message' => 'Đã tiếp nhận! Hệ thống AI đang phân tích dữ liệu ngầm.',
                'analyzed_type' => $chosenType,
                'analyzed_id' => $chosenId
            ], 202, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error("❌ Lỗi API chính: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gặp sự cố hệ thống.'], 500);
        }
    }

    // Hàm lấy danh sách cv để hiện lên giao diện gợi ý để người dùng chọn để phân tích.
    public function getUserCvList(): JsonResponse
    {
        $userId = auth()->id();

        $cvList = []; // biến chứa danh sách các cv lấy ddc

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
        $uploadedCvs = CvFile::where('user_id', $userId)
            ->where('type', 'uploaded')
            ->whereNull('deleted_at')
            ->latest()
            ->take(3)
            ->get();

        // vòng lặp đưa danh sách cv uploaded vào mãng
        foreach ($uploadedCvs as $cv) {
            // Lấy tên file gốc từ đường dẫn
            //$fileName = basename($cv->file_path);

            $cvList[] = [
                'id' => 'cv-upload-' . $cv->id,
                'name' => '📄 File: ' . $cv->file_name,
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
