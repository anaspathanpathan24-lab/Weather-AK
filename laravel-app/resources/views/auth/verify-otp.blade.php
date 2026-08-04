<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | Weather Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #071120 0%, #102542 100%); min-height: 100vh; }
        .card { border: 0; border-radius: 1.25rem; box-shadow: 0 18px 48px rgba(0,0,0,0.25); }
        .form-control { border-radius: 0.9rem; padding: 0.85rem 1rem; letter-spacing: 0.3em; text-align: center; font-size: 1.25rem; }
        .btn-primary { border-radius: 0.9rem; padding: 0.8rem 1rem; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card p-4 p-md-5 text-body">
                <div class="text-center mb-4">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning" style="width: 64px; height: 64px; font-size: 1.4rem;">✉️</div>
                    <h1 class="h3 mt-3 mb-2">Verify OTP</h1>
                    <p class="text-muted mb-0">Enter the 6-digit verification code sent to <strong>{{ $email }}</strong>.</p>
                </div>
                <div id="form-message" class="alert d-none" role="alert"></div>
                <form id="verify-otp-form" class="needs-validation" novalidate>
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <div class="mb-3">
                        <label class="form-label" for="otp">One-time password</label>
                        <input id="otp" name="otp" type="text" maxlength="6" class="form-control" placeholder="123456" required>
                    </div>
                    <button id="submit-btn" type="submit" class="btn btn-primary w-100">Verify OTP</button>
                </form>
                <div class="text-center mt-4">
                    <a href="{{ route('password.request') }}" class="text-decoration-none">Request a new OTP</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
