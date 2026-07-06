<!DOCTYPE html>
<html>
<head>
    <title>Từ chối xác minh doanh nghiệp</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Xin chào Quản trị viên của {{ $company->name }},</h2>
    <p>Chúng tôi rất tiếc phải thông báo rằng hồ sơ đăng ký doanh nghiệp của bạn đã **bị từ chối phê duyệt**.</p>
    
    <div style="background-color: #fff5f5; border-left: 4px solid #e53e3e; padding: 15px; margin: 15px 0;">
        <strong style="color: #c53030;">Lý do từ chối kích hoạt:</strong>
        <p style="margin: 5px 0 0 0; font-style: italic; color: #4a5568;">"{{ $reason }}"</p>
    </div>

    <p>Tài khoản liên kết với doanh nghiệp này đã bị tạm khóa hoặc xóa khỏi hệ thống theo quy định kiểm duyệt nội dung.</p>
    <br>
    <p>Trân trọng,</p>
    <p><strong>Ban quản trị Website</strong></p>
</body>
</html>