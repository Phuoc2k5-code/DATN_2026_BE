<!DOCTYPE html>
<html>
<head>
    <title>Tin tuyển dụng đã được duyệt</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Xin chào {{ $job->company->name ?? 'Nhà tuyển dụng' }},</h2>
    <p>Chúc mừng bạn! Tin tuyển dụng <strong>"{{ $job->title }}"</strong> của bạn đã được hệ thống phê duyệt thành công.</p>
    <p>Hiện tại tin đăng đã được hiển thị trên trang chủ để các ứng viên có thể ứng tuyển.</p>
    <br>
    <p>Trân trọng,</p>
    <p><strong>Ban quản trị Website</strong></p>
</body>
</html>