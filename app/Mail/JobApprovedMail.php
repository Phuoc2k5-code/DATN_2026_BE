<?php

namespace App\Mail;

use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $job;

    // Truyền dữ liệu Job vào Mail khi gọi
    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    public function build()
    {
        return $this->subject('🎉 Tin tuyển dụng của bạn đã được phê duyệt!')
                    ->view('emails.job_approved'); // Đường dẫn đến file giao diện blade
    }
}