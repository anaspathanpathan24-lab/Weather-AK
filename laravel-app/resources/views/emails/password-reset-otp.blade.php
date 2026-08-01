<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background:#0f172a; color:#f8fafc; margin:0; padding:24px;">
    <div style="max-width:600px; margin:auto; background:#111827; border:1px solid #334155; border-radius:16px; overflow:hidden;">
        <div style="padding:24px; text-align:center; background:linear-gradient(135deg,#0ea5e9,#22c55e);">
            <h1 style="margin:0; font-size:24px; color:#fff;">Weather Dashboard</h1>
        </div>
        <div style="padding:24px;">
            <p style="font-size:16px;">Hello,</p>
            <p style="font-size:16px;">Your One-Time Password (OTP) is:</p>
            <div style="margin:24px 0; padding:20px; text-align:center; background:#1f2937; border-radius:12px; font-size:34px; letter-spacing:4px; font-weight:bold; color:#fef08a;">
                {{ $otp }}
            </div>
            <p style="font-size:14px; color:#cbd5e1;">This OTP is valid for only 10 minutes.</p>
            <p style="font-size:14px; color:#cbd5e1;">Do not share this OTP with anyone.</p>
        </div>
        <div style="padding:24px; text-align:center; font-size:12px; color:#94a3b8; border-top:1px solid #334155;">
            This email was sent for password reset purposes.
        </div>
    </div>
</body>
</html>
