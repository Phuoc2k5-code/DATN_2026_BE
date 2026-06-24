<!DOCTYPE html>
<html>
<head>
    <title>Mã xác thực OTP</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Chào bạn,</h2>

    @if($mailType === 'forgot_password')
        <p>Chúng tôi nhận được yêu cầu khôi phục mật khẩu cho tài khoản của bạn tại hệ thống JobPortal.</p>
        <p>Mã OTP để đặt lại mật khẩu của bạn là: 
            <strong style="font-size: 22px; color: #dc2626;">{{ $otpCode }}</strong>
        </p>
    @else
        <p>Cảm ơn bạn đã đăng ký tài khoản tại hệ thống JobPortal.</p>
        <p>Mã OTP để kích hoạt tài khoản của bạn là: 
            <strong style="font-size: 22px; color: #1e40af;">{{ $otpCode }}</strong>
        </p>
    @endif

    <p>Mã này có hiệu lực trong vòng 15 phút. Tuyệt đối không chia sẻ mã này cho bất kỳ ai để bảo mật tài khoản.</p>
    <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.</p>
    <br>
    <p>Trân trọng,<br><strong>Ban quản trị JobPortal</strong></p>
</body>
</html>