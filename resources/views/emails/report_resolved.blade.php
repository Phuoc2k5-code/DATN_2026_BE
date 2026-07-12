<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Thông báo xử lý vi phạm</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eeeeee; border-radius: 5px; }
        .header { background-color: #d9534f; color: white; padding: 10px 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; font-size: 12px; color: #777777; margin-top: 20px; border-top: 1px solid #eeeeee; padding-top: 10px; }
        .reason-box { background-color: #f9f9f9; border-left: 4px solid #d9534f; padding: 15px; margin: 15px 0; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>THÔNG BÁO XỬ LÝ VI PHẠM</h2>
        </div>
        <div class="content">
            <p>Xin chào <strong>{{ $targetName }}</strong>,</p>
            
            <p>Hệ thống quản trị vừa thực hiện xử lý báo cáo vi phạm liên quan đến tài khoản/nội dung của bạn.</p>
            
            <table border="0" cellpadding="5" cellspacing="0">
                <tr>
                    <td><strong>Loại vi phạm:</strong></td>
                    <td>
                        @if($type == 'job')
                            Báo cáo tin tuyển dụng
                        @else
                            Báo cáo doanh nghiệp / Khóa tài khoản
                        @endif
                    </td>
                </tr>
                <tr>
                    <td><strong>Thời gian xử lý:</strong></td>
                    <td>{{ $resolvedAt }}</td>
                </tr>
            </table>

            <p><strong>Lý do từ Ban quản trị:</strong></p>
            <div class="reason-box">
                "{{ $adminNote }}"
            </div>

            @if($type == 'job')
                <p><strong>Hình thức xử lý:</strong> Tin tuyển dụng liên quan đã bị ẩn và tạm khóa trên hệ thống để rà soát.</p>
            @else
                <p><strong>Hình thức xử lý:</strong> Doanh nghiệp đã bị đình chỉ hoạt động và tài khoản nhà tuyển dụng của bạn đã bị vô hiệu hóa.</p>
            @endif

            <p>Nếu bạn cho rằng đây là một sự nhầm lẫn, vui lòng liên hệ trực tiếp với bộ phận hỗ trợ của chúng tôi.</p>
        </div>
        <div class="footer">
            <p>Đây là tin nhắn tự động từ hệ thống, vui lòng không phản hồi email này.</p>
        </div>
    </div>
</body>
</html>