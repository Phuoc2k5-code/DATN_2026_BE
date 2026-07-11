<?php

namespace App\Jobs;

use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessAiRecommendation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $cvContentText;
    protected $jobsDataForAI;
    protected $cacheResultKey;
    protected $cacheStatusKey;
    protected $chosenType;
    protected $chosenId;

    /**
     * Khởi tạo Job và truyền các tham số cần thiết vào chạy ngầm
     */
    public function __construct($userId, $cvContentText, $jobsDataForAI, $cacheResultKey, $cacheStatusKey, $chosenType, $chosenId)
    {
        $this->userId = $userId;
        $this->cvContentText = $cvContentText;
        $this->jobsDataForAI = $jobsDataForAI;
        $this->cacheResultKey = $cacheResultKey;
        $this->cacheStatusKey = $cacheStatusKey;
        $this->chosenType = $chosenType;
        $this->chosenId = $chosenId;
    }

    /**
     * Nơi Robot chạy ngầm gọi API Gemini (Mất bao lâu cũng không sợ nghẽn UI)
     */
    public function handle()
    {
        Log::info("⚙️ [QUEUE RUNNING] Robot đang âm thầm gọi Gemini API cho User ID: " . $this->userId);

        try {
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

            $prompt = "Nội dung hồ sơ ứng viên:\n{$this->cvContentText}\n\n"
                . "Danh sách các công việc cần chấm điểm (JSON):\n" . json_encode($this->jobsDataForAI, JSON_UNESCAPED_UNICODE);

            $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . env('GEMINI_API_KEY');

            $response = Http::withHeaders(['Content-Type' => 'application/json'])->timeout(120)
                ->post($geminiUrl, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
                    'generationConfig' => ['responseMimeType' => 'application/json']
                ]);

            if ($response->successful()) {
                Log::info("🎉 [QUEUE SUCCESS] Gemini đã xử lý xong! Tiến hành phân tách map dữ liệu...");
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
                // Lấy lại danh sách gốc đầy đủ các Job để map thông tin (kèm quan hệ model)
                $jobs = Job::with(['category', 'skills', 'company'])->where('status', 'active')->latest()->take(20)->get();

                $recommendedJobsArray = $jobs->map(function ($job) use ($scoreMap) {
                    $aiScore = $scoreMap[$job->id]['matching_score'] ?? 0;
                    $aiReason = $scoreMap[$job->id]['reason'] ?? 'Chưa có đánh giá chi tiết.';

                    $job->setAttribute('matching_score', $aiScore);
                    $job->setAttribute('ai_reason', $aiReason);

                    return $job;
                })
                ->filter(function ($job) {
                    return $job->matching_score >= 30; // Lọc bỏ job < 30 điểm
                })
                ->sortByDesc('matching_score')
                ->values()
                ->toArray();
                // Lưu kết quả vào bộ nhớ đệm 7 ngày
                Cache::put($this->cacheResultKey, $recommendedJobsArray, now()->addDays(7));
                // CẬP NHẬT TRẠNG THÁI HOÀN THÀNH
                Cache::put($this->cacheStatusKey, 'completed', now()->addHours(2));

                Log::info("💾 [QUEUE DONE] Đã lưu kết quả thành công cho User ID: " . $this->userId);
            } else {
                Log::error("❌ [QUEUE GEMINI ERROR] API thất bại: " . $response->body());
                Cache::put($this->cacheStatusKey, 'failed', now()->addHours(1));
            }

        } catch (\Exception $e) {
            Log::error("❌ [QUEUE CRITICAL EXCEPTION] Lỗi hệ thống: " . $e->getMessage());
            Cache::put($this->cacheStatusKey, 'failed', now()->addHours(1));
        }
    }
}