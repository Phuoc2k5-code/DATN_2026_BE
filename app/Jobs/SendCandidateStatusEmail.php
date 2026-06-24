<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCandidateStatusEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $data; // Chứa thông tin tên, trạng thái, tên job...

    // 1. Nhận dữ liệu từ Controller truyền sang
    public function __construct($email, $data)
    {
        $this->email = $email;
        $this->data = $data;
    }

    // 2. Chạy ngầm việc gửi mail ở đây
    public function handle()
    {
        // Copy ĐOẠN CODE GỬI MAIL CŨ của bạn vào đây
        // Ví dụ:
        Mail::send('emails.candidate_status', $this->data, function ($message) {
            $message->to($this->email)
                    ->subject('Cập nhật trạng thái ứng tuyển');
        });
    }
}