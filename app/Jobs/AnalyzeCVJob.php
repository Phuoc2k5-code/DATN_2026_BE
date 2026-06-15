<?php

namespace App\Jobs;

use App\Models\Application;
use Smalot\PdfParser\Parser; 
use Illuminate\Support\Facades\Http; 
use Illuminate\Support\Facades\Log; // 🚀 Bắt buộc import thư viện Log

use Illuminate\Bus\Queueable; 
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeCVJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $applicationId;

    public function __construct($applicationId)
    {
        $this->applicationId = $applicationId;
    }

    public function handle()
    {
        Log::info("====================================================");
        Log::info("🚀 [ATS QUEUE] Bắt đầu xử lý Job cho Application ID: " . $this->applicationId);

        // 1. Tìm đơn ứng tuyển kèm theo thông tin Job và File CV từ Database
        $application = Application::with(['job.category', 'job.skills', 'cvFile'])->find($this->applicationId);        
        
        if (!$application) {
            Log::error("❌ [ATS QUEUE] Thất bại: Không tìm thấy dữ liệu Application ID trong DB.");
            throw new \Exception("❌ LỖI QUEUE: Không tìm thấy Application ID: " . $this->applicationId);
        }
        
        if (!$application->cvFile) {
            Log::error("❌ [ATS QUEUE] Thất bại: Ứng tuyển tồn tại nhưng quan hệ cvFile bị trống (Null).");
            throw new \Exception("❌ LỖI QUEUE: Quan hệ cvFile bị trống! Hãy kiểm tra lại liên kết Model.");
        }

        Log::info("🔹 [ATS QUEUE] Đã tìm thấy đơn ứng tuyển. CV File ID liên kết: " . $application->cvFile->id);

        // 2. Lấy đường dẫn file PDF thực tế
        $cvFullPath = public_path(ltrim($application->cvFile->file_path, '/'));    
        Log::info("🔹 [ATS QUEUE] Đường dẫn file CV vật lý tính toán: " . $cvFullPath);

        if (!file_exists($cvFullPath)) {
            Log::error("❌ [ATS QUEUE] Thất bại: Không tồn tại file vật lý tại ổ đĩa.");
            throw new \Exception("❌ LỖI QUEUE: Không tìm thấy file CV vật lý tại đường dẫn: " . $cvFullPath);
        }

        // 3. Sử dụng thư viện Smalot Parser để đọc file thô thành chuỗi chữ (text)
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($cvFullPath);
            $cvContentText = $pdf->getText();
            
            if (empty(trim($cvContentText))) {
                Log::warning("⚠️ [ATS QUEUE] Cảnh báo: File PDF đọc ra chuỗi rỗng.");
                throw new \Exception("❌ LỖI QUEUE: File PDF này trống rỗng hoặc là file ảnh scan!");
            }
            Log::info("✅ [ATS QUEUE] Bóc tách chữ từ file PDF thành công. Độ dài văn bản: " . strlen($cvContentText) . " ký tự.");
        } catch (\Exception $e) {
            Log::error("❌ [ATS QUEUE] Thất bại tại bước đọc file PDF: " . $e->getMessage());
            throw new \Exception("❌ LỖI ĐỌC FILE PDF: " . $e->getMessage());
        }

        // 4. Lấy thông tin Job để làm dữ liệu so khớp
        $job = $application->job;
        if (!$job) {
            Log::error("❌ [ATS QUEUE] Thất bại: Không tìm thấy Job liên kết với đơn ứng tuyển này.");
            return;
        }

        $jobSkillsList = $job->skills->pluck('name')->implode(', ');
        $jobInfo = "Tiêu đề công việc (Title): {$job->title}\n"
                 . "Ngành nghề (Category): " . ($job->category->name ?? 'Chưa phân loại') . "\n"
                 . "Các kỹ năng yêu cầu cụ thể (Skills): {$jobSkillsList}\n" 
                 . "Mô tả công việc & Yêu cầu chi tiết: {$job->description} {$job->requirements}";
                 
        $prompt = "Thông tin Job:\n{$jobInfo}\n\nNội dung chữ trích xuất từ CV:\n{$cvContentText}";
        $systemInstruction = config('gemini.system_instruction');

        Log::info("🔹 [ATS QUEUE] Đang gửi yêu cầu và Prompt sang Gemini AI...");

        // 5. Tiến hành gọi API Gemini 2.5 Flash để lấy điểm số %
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . env('GEMINI_API_KEY'), [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json'
            ]
        ]);

        // 6. Xử lý kết quả trả về từ Gemini AI
        if ($response->successful()) {
            Log::info("✅ [ATS QUEUE] Gemini AI phản hồi thành công.");
            $aiTextResponse = $response->json()['candidates'][0]['content']['parts'][0]['text'];
            $resultData = json_decode($aiTextResponse, true);
            $score = $resultData['matching_score'] ?? 0;
            
            Log::info("🔮 [ATS QUEUE] Điểm số Gemini tính toán được: " . $score);
        } else {
            Log::error("❌ [ATS QUEUE] Lỗi gọi API Gemini: Status " . $response->status() . " - Body: " . $response->body());
            $score = 0;
        }

        // 7. ÉP CỨNG VÀ CẬP NHẬT TRỰC TIẾP XUỐNG DATABASE CỨNG BẰNG PHƯƠNG THỨC SAVE()
        Log::info("📝 [ATS QUEUE] Tiến hành ép ghi đè điểm số vào Database...");
        
        try {
            // Gán trực tiếp giá trị vào thuộc tính của Model đối tượng nhằm loại bỏ cache trạng thái
            $application->matching_score = $score; 
            $application->status = 'pending';
            
            // Thực thi lệnh lưu cứng, bỏ qua cơ chế so sánh mảng thay đổi cũ của hàm update()
            $isSaved = $application->save();

            if ($isSaved) {
                Log::info("🎯 [ATS QUEUE] ĐÃ GHI THÀNH CÔNG VÀO DB! Kiểm tra lại trường matching_score thực tế của bản ghi ID " . $application->id . " xem nhảy số chưa.");
            } else {
                Log::error("❌ [ATS QUEUE] Lệnh save() trả về false, không thể lưu dữ liệu xuống DB.");
            }
        } catch (\Exception $dbEx) {
            Log::error("❌ [ATS QUEUE] Lỗi trong quá trình thực thi lệnh SQL lưu điểm: " . $dbEx->getMessage());
        }

        Log::info("====================================================");
    }
}