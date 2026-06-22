<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    // Khai báo các biến công khai để truyền dữ liệu sang giao diện Blade
    public $candidate;
    public $status;
    public $companyName;

    /**
     * Hàm khởi tạo nhận dữ liệu từ Controller truyền sang
     */
    public function __construct($candidate, $status, $companyName)
    {
        $this->candidate = $candidate;
        $this->status = $status;
        $this->companyName = $companyName;
    }

    /**
     * Cấu hình Tiêu đề và Tên hiển thị người gửi (From Name) động
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), $this->companyName),
            subject: '[' . $this->companyName . '] Thông báo trạng thái hồ sơ ứng tuyển',
        );
    }

    /**
     * Định nghĩa file giao diện hiển thị nội dung Email
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.status_update',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}