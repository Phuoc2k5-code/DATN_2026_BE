<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otpCode;
    public $mailType; // Thêm biến này để phân biệt 'register' hoặc 'forgot_password'

    /**
     * Nhận cả mã OTP và Loại Mail
     */
    public function __construct($otpCode, $mailType = 'register')
    {
        $this->otpCode = $otpCode;
        $this->mailType = $mailType;
    }

    /**
     * Thay đổi tiêu đề linh hoạt theo loại Mail
     */
    public function envelope(): Envelope
    {
        // Mặc định tiêu đề là Đăng ký tài khoản
        $subject = '[Tuyển Dụng Thông Minh] - Mã Xác Thực Đăng Ký Tài Khoản';

        // Nếu truyền vào là forgot_password thì đổi tiêu đề
        if ($this->mailType === 'forgot_password') {
            $subject = '[Tuyển Dụng Thông Minh] - Yêu Cầu Khôi Phục Mật Khẩu';
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp', 
        );
    }

    public function attachments(): array
    {
        return [];
    }
}