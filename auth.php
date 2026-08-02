<?php
// Prevent PHP warnings or notices from being sent to clients as HTML
@ini_set('display_errors', '0');
@error_reporting(0);
session_start();
header('Content-Type: application/json; charset=utf-8');

$db = new PDO('sqlite:' . __DIR__ . '/includes/users.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL, created_at TEXT NOT NULL)');

$existingColumns = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
$existingColumnNames = array_column($existingColumns, 'name');
if (!in_array('reset_token', $existingColumnNames, true)) {
    $db->exec('ALTER TABLE users ADD COLUMN reset_token TEXT');
}
if (!in_array('otp', $existingColumnNames, true)) {
    $db->exec('ALTER TABLE users ADD COLUMN otp TEXT');
}
if (!in_array('otp_expires', $existingColumnNames, true)) {
    $db->exec('ALTER TABLE users ADD COLUMN otp_expires TEXT');
}

$action = $_GET['action'] ?? '';

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function readRequestInput(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $rawBody = trim((string) file_get_contents('php://input'));
    if ($rawBody === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);
    if (is_array($decoded) && !empty($decoded)) {
        return $decoded;
    }

    $parsed = [];
    parse_str($rawBody, $parsed);

    return is_array($parsed) ? $parsed : [];
}

$input = readRequestInput();

if ($action === 'session') {
    respond(['authenticated' => isset($_SESSION['user']), 'user' => $_SESSION['user'] ?? null]);
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    respond(['success' => true]);
}

if ($action !== 'register' && $action !== 'login' && $action !== 'forgot_password' && $action !== 'verify_otp' && $action !== 'change_password') {
    respond(['error' => 'Invalid authentication action.'], 400);
}

$email = strtolower(trim((string) ($input['email'] ?? '')));

if ($action === 'forgot_password') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(['error' => 'Please enter a valid email address.'], 422);
    }

    $mode = (string) ($input['mode'] ?? 'link');
    $statement = $db->prepare('SELECT id, email FROM users WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $token = bin2hex(random_bytes(16));
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpires = date('c', strtotime('+15 minutes'));

        $updateStatement = $db->prepare('UPDATE users SET reset_token = ?, otp = ?, otp_expires = ? WHERE email = ?');
        $updateStatement->execute([$token, $otp, $otpExpires, $email]);

        // Attempt to send email (if mail is configured). If mail() fails or is not configured,
        // write the token/otp to a server-side log file so you can verify the values locally.
        $logLine = sprintf("[%s] reset for %s mode=%s token=%s otp=%s expires=%s\n", date('c'), $email, $mode, $token, $otp, $otpExpires);
        $logFile = __DIR__ . '/includes/reset_log.txt';
        try {
            // Try PHP mail() - this requires your server's mail to be configured.
            if ($mode === 'otp') {
                $subject = 'Your OTP code';
                $body = "Your OTP for Gujarat Weather is: {$otp} (valid 15 minutes)";
            } else {
                $resetUrl = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI']) . "/auth.php?action=change_password&token={$token}";
                $subject = 'Password reset link';
                $body = "Use this link to reset your password: {$resetUrl}\nThis link expires in 15 minutes.";
            }

            $headers = "From: no-reply@{$email}\r\n" .
                       "Reply-To: no-reply@{$email}\r\n" .
                       "X-Mailer: PHP/" . phpversion();

            $mailSent = false;
            if (function_exists('mail')) {
                $mailSent = @mail($email, $subject, $body, $headers);
            }

            // Always append to log for debugging (useful when mail isn't configured)
            @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

            if ($mailSent) {
                if ($mode === 'otp') {
                    respond(['success' => true, 'message' => "OTP sent to {$email}. Check your inbox."]);
                }
                respond(['success' => true, 'message' => "Reset link sent to {$email}. Check your inbox."]);
            }
        } catch (Exception $e) {
            // fall through to fallback message
        }

        // Fallback: do not reveal sensitive codes in production; for local debugging we inform where to find them.
        if ($mode === 'otp') {
            respond(['success' => true, 'message' => "OTP generated and logged on server (check includes/reset_log.txt)."]);
        }
        respond(['success' => true, 'message' => "Reset token generated and logged on server (check includes/reset_log.txt)."]);
    }

    respond(['success' => true, 'message' => 'If that email exists, recovery options are ready.']);
}

if ($action === 'verify_otp') {
    $otp = (string) ($input['otp'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($otp) < 4) {
        respond(['error' => 'Please enter a valid email and OTP.'], 422);
    }

    $statement = $db->prepare('SELECT otp, otp_expires FROM users WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['otp'] || $user['otp'] !== $otp || strtotime((string) $user['otp_expires']) < time()) {
        respond(['error' => 'OTP is invalid or expired.'], 401);
    }

    respond(['success' => true, 'message' => 'OTP verified. You can now change your password.']);
}

if ($action === 'change_password') {
    $newPassword = (string) ($input['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($newPassword) < 6) {
        respond(['error' => 'Please enter a valid email and a strong password.'], 422);
    }

    $statement = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        respond(['error' => 'User not found.'], 404);
    }

    $updateStatement = $db->prepare('UPDATE users SET password = ?, reset_token = NULL, otp = NULL, otp_expires = NULL WHERE email = ?');
    $updateStatement->execute([password_hash($newPassword, PASSWORD_DEFAULT), $email]);
    respond(['success' => true, 'message' => 'Password changed successfully.']);
}

$password = (string) ($input['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
    respond(['error' => 'Enter a valid email and a password of at least 6 characters.'], 422);
}

if ($action === 'register') {
    $name = trim((string) ($input['name'] ?? ''));
    if ($name === '') {
        respond(['error' => 'Please enter your name.'], 422);
    }

    try {
        $statement = $db->prepare('INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, ?)');
        $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), date('c')]);
        $_SESSION['user'] = ['name' => $name, 'email' => $email];
        respond(['success' => true, 'user' => $_SESSION['user']]);
    } catch (PDOException $error) {
        if ((int) $error->errorInfo[1] === 19) {
            respond(['error' => 'This email is already registered.'], 409);
        }
        respond(['error' => 'Registration could not be completed.'], 500);
    }
}

$statement = $db->prepare('SELECT name, email, password FROM users WHERE email = ? LIMIT 1');
$statement->execute([$email]);
$user = $statement->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    respond(['error' => 'Email or password is incorrect.'], 401);
}

$_SESSION['user'] = ['name' => $user['name'], 'email' => $user['email']];
respond(['success' => true, 'user' => $_SESSION['user']]);
