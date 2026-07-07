<?php

namespace App\Mail;

use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $job;
    public $reason;

    // Nhận cả thông tin Job và Lý do từ chối gửi lên từ Admin
    public function __construct(Job $job, $reason)
    {
        $this->job = $job;
        $this->reason = $reason;
    }

    public function build()
    {
        return $this->subject('⚠️ Thông báo về tin tuyển dụng của bạn')
                    ->view('emails.job_rejected'); // Giao diện chứa lý do từ chối
    }
}