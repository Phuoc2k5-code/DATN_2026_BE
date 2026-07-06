<!DOCTYPE html>
<html>
<head>
    <title>Tin tuyển dụng bị từ chối</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Xin chào {{ $job->company->name ?? 'Nhà tuyển dụng' }},</h2>
    <p>Chúng tôi rất tiếc phải thông báo tin tuyển dụng <strong>"{{ $job->title }}"</strong> của bạn chưa đạt yêu cầu phê duyệt.</p>
    
    <div style="background-color: #fff5f5; border-left: 4px solid #e53e3e; padding: 15px; margin: 15px 0;">
        <strong style="color: #c53030;">Lý do từ chối bài đăng:</strong>
        <p style="margin: 5px 0 0 0; italic; color: #4a5568;">"{{ $reason }}"</p>
    </div>

    <p>Vui lòng đăng nhập vào hệ thống, điều chỉnh lại nội dung theo lý do trên và gửi duyệt lại.</p>
    <br>
    <p>Trân trọng,</p>
    <p><strong>Ban quản trị Website</strong></p>
</body>
</html>