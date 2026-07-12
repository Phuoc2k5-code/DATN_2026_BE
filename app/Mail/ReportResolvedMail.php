<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportResolvedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $type;
    public $targetName;
    public $adminNote;
    public $resolvedAt;

    /**
     * Khởi tạo class Mail với các dữ liệu cần thiết
     */
    public function __construct($type, $targetName, $adminNote, $resolvedAt)
    {
        $this->type = $type;
        $this->targetName = $targetName;
        $this->adminNote = $adminNote;
        $this->resolvedAt = $resolvedAt;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->type === 'job' 
            ? '[Thông báo] Tin tuyển dụng của bạn đã bị khóa do vi phạm' 
            : '[Thông báo] Tài khoản của bạn đã bị đình chỉ do vi phạm nghiêm trọng';

        return $this->subject($subject)
                    ->view('emails.report_resolved');
    }
}