Password reset debugging and email setup
=====================================

When the app generates an OTP or a reset token it will attempt to send an email using PHP's `mail()`.
If your local XAMPP / PHP is not configured to send mail, the server will append the generated values to:

- `includes/reset_log.txt`

Check that file to find the OTP / token and timestamp for local testing.

To enable real emails in production, configure one of the following options:

- Configure your system MTA so PHP's `mail()` works (system-specific).
- Integrate PHPMailer and SMTP (recommended). I can add PHPMailer wiring if you provide SMTP credentials (host, port, username, password, encryption).

If you want me to enable SMTP/PHPMailer now, share the SMTP details or say "I'll provide SMTP" and I'll implement it.
