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
                . "QUY TẮC NGÔN NGỮ QUAN TRỌNG:\n"
                . "- BẮT BUỘC tất cả nội dung trong thuộc tính \"reason\" phải được viết hoàn toàn bằng TIẾNG VIỆT. Tuyệt đối không sử dụng tiếng Anh.\n\n"
                . "CÔNG THỨC VÀ QUY TẮC CHẤM ĐIỂM CHI TIẾT CHO TỪNG JOB:\n"
                . "1. TITLE - VỊ TRÍ CÔNG VIỆC (Tối đa 30%):\n"
                . "   - Khớp hoàn toàn tiêu đề vị trí công việc: +30 điểm.\n"
                . "     *Lưu ý: Nếu tiêu đề công việc và CV chứa các từ khóa chuyên môn tương đương cốt lõi (Ví dụ: Job là 'Lập trình viên Laravel / PHP' và CV ứng viên là 'Lập trình viên PHP', 'Laravel Developer', 'PHP Developer' hoặc 'Backend Developer (PHP)'): Hãy tính là KHỚP HOÀN TOÀN để cộng 30 điểm.*\n"
                . "   - Khớp một phần hoặc cùng nhóm ngành nghề nhưng khác level hoặc khác ngôn ngữ lập trình phụ: +15 điểm.\n"
                . "   - Khác biệt hoàn toàn về vị trí chuyên môn: +0 điểm.\n\n"
                . "2. CATEGORY - NGÀNH NGHỀ (Tối đa 20%):\n"
                . "   - Trùng khớp hoàn toàn lĩnh vực ngành nghề (Ví dụ: Cùng thuộc Công nghệ thông tin): +20 điểm.\n"
                . "   - Ngành nghề có liên quan hoặc có tính chất bổ trợ lẫn nhau: +10 điểm.\n"
                . "   - Không liên quan: +0 điểm.\n\n"
                . "3. SKILLS - KỸ NĂNG CHUYÊN MÔN (Tối đa 50%):\n"
                . "   - Điểm số phần này = (Số lượng kỹ năng trong CV đáp ứng được / Tổng số kỹ năng Job yêu cầu) * 50.\n"
                . "   - Hãy đối chiếu linh hoạt các từ khóa kỹ năng viết tắt hoặc đồng nghĩa (Ví dụ: JS và JavaScript, Vue và VueJS, Sql và MySQL).\n\n"
                . "TỔNG ĐIỂM (matching_score) = Điểm TITLE + Điểm CATEGORY + Điểm SKILLS.\n\n"
                . "YÊU CẦU ĐẦU RA BẮT BUỘC:\n"
                . "- Bạn PHẢI tính toán và chấm điểm đầy đủ cho TẤT CẢ các job_id có trong danh sách được gửi qua, KHÔNG ĐƯỢC BỎ SÓT bất kỳ job_id nào.\n"
                . "- Tính toán số học khách quan, chính xác theo công thức trên cho từng công việc.\n"
                . "- CHỈ trả về duy nhất một chuỗi JSON hợp lệ dạng MẢNG (Array Object), không bọc markdown ```json.\n"
                . "- KHÔNG viết thêm bất kỳ chữ giải thích nào bên ngoài cấu trúc JSON.\n\n"
                . "Cấu trúc phần tử trong Mảng JSON đầu ra bắt buộc:\n"
                . "[\n"
                . "  {\n"
                . "    \"job_id\": 1,\n"
                . "    \"matching_score\": 50.00,\n"
                . "    \"reason\": \"Giải thích ngắn gọn lý do đạt số điểm này bằng tiếng Việt (Ví dụ: Vị trí công việc khớp hoàn toàn đạt 30đ. Ngành nghề trùng khớp đạt 20đ. Tuy nhiên các kỹ năng yêu cầu không trùng khớp với CV đạt 0đ).\"\n"
                . "  }\n"
                . "]";

            $prompt = "Nội dung hồ sơ ứng viên:\n{$this->cvContentText}\n\n"
                . "Danh sách các công việc cần chấm điểm (JSON):\n" . json_encode($this->jobsDataForAI, JSON_UNESCAPED_UNICODE);

            $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . env('GEMINI_API_KEY');

            $response = Http::withHeaders(['Content-Type' => 'application/json'])->timeout(120)
                ->post($geminiUrl, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
                    'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.0]
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
                        return $job->matching_score >= 65; // Lọc bỏ job < 65 điểm
                    })
                    ->sortByDesc('matching_score')
                    ->values()
                    ->toArray();
                // Lưu kết quả vào bộ nhớ đệm 7 ngày
                Cache::put($this->cacheResultKey, $recommendedJobsArray, now()->addDays(7));
                // CẬP NHẬT TRẠNG THÁI HOÀN THÀNH
                Cache::put($this->cacheStatusKey, 'completed', now()->addDays(7));

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