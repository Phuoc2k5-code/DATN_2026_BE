<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; background-color: #f9f9f9; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #e8e8e8;">
        
        <h2 style="color: #1a1a1a; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
            Thông Báo Từ {{ $companyName }}
        </h2>
        
        <p>Xin chào <strong>{{ $candidate->full_name }}</strong>,</p>
        
        <p>Cảm ơn bạn đã quan tâm và nộp hồ sơ ứng tuyển vào công ty của chúng tôi. Chúng tôi xin trân trọng thông báo hồ sơ của bạn đã được cập nhật trạng thái sang: 
            <span style="font-weight: bold; text-transform: uppercase; color: #ff6b00;">
                {{ $status }}
            </span>
        </p>

        <div style="background-color: #fafafa; padding: 15px; border-left: 4px solid #ff6b00; margin: 20px 0;">
            @if($status == 'Phỏng vấn' || $status == 'interviewing')
                <p style="margin: 0;">Bộ phận nhân sự sẽ chủ động liên hệ trực tiếp với bạn qua Số điện thoại hoặc Email này trong vòng 1-2 ngày tới để sắp xếp lịch phỏng vấn cụ thể. Bạn vui lòng chú ý điện thoại nhé!</p>
            @elseif($status == 'Từ chối' || $status == 'rejected')
                <p style="margin: 0;">Dù rất ấn tượng với hồ sơ của bạn, tuy nhiên ở thời điểm hiện tại, các tiêu chí của vị trí này chưa hoàn toàn tương thích với định hướng của bạn. Thông tin của bạn đã được lưu trữ trong kho dữ liệu tài năng của chúng tôi để ưu tiên cho các cơ hội hợp tác sau này.</p>
            @else
                <p style="margin: 0;">Hồ sơ của bạn hiện đang được ban nhân sự và quản lý chuyên môn xét duyệt kỹ lưỡng thông tin.</p>
            @endif
        </div>

        <p>Chúc bạn luôn nhiều sức khỏe và gặt hái được nhiều thành công trên con đường sự nghiệp.</p>
        
        <hr style="border: none; border-top: 1px solid #f0f0f0; margin: 25px 0;">
        <p style="font-size: 12px; color: #888; margin: 0;">
            Trân trọng,<br>
            <strong>Ban Tuyển Dụng {{ $companyName }}</strong><br>
            Đây là email tự động từ hệ thống, vui lòng không phản hồi lại email này.
        </p>
    </div>
</body>
</html>