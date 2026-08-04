<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Application - Password Reset OTP</title>
</head>
<body style="margin:0; padding:0; background:#f4f7fb; font-family:Arial, sans-serif; color:#0f172a;">
    <div style="max-width:640px; margin:32px auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 12px 32px rgba(15,23,42,0.08);">
        <div style="background:linear-gradient(135deg,#0f172a 0%,#2563eb 100%); padding:28px 24px; text-align:center; color:#ffffff;">
            <h1 style="margin:0; font-size:28px;">Weather Application</h1>
            <p style="margin:8px 0 0; opacity:0.9;">Secure password recovery</p>
        </div>
        <div style="padding:32px 24px;">
            <p style="margin:0 0 10px; font-size:16px;">Hello,</p>
            <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">Use the one-time password below to continue resetting your password.</p>
            <div style="text-align:center; margin:24px 0; padding:20px 16px; border-radius:14px; background:#eff6ff; border:1px solid #bfdbfe;">
                <div style="font-size:13px; text-transform:uppercase; letter-spacing:0.2em; color:#2563eb; margin-bottom:8px;">Your OTP</div>
                <div style="font-size:34px; font-weight:bold; letter-spacing:0.3em; color:#0f172a;">{{ $otp }}</div>
            </div>
            <p style="margin:0 0 8px; font-size:14px; color:#475569;">This OTP is valid for 10 minutes.</p>
            <p style="margin:0; font-size:14px; color:#475569;">Please do not share this code with anyone.</p>
        </div>
        <div style="padding:24px; background:#f8fafc; text-align:center; font-size:12px; color:#64748b; border-top:1px solid #e2e8f0;">
            Need help? Contact <a href="mailto:support@weatherapp.com" style="color:#2563eb; text-decoration:none;">support@weatherapp.com</a>
        </div>
    </div>
</body>
</html>
