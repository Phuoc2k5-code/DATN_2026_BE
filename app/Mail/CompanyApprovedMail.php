<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function build()
    {
        return $this->subject('🏢 Doanh nghiệp của bạn đã được xác minh thành công!')
                    ->view('emails.company_approved');
    }
}