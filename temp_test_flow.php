<?php
$db = new PDO('sqlite:includes/users.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$email = 'testuser+flow@example.com';
$password = 'Test1234!';
$name = 'Flow Test';
$session = [];
try {
    $stmt = $db->prepare('INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), date('c')]);
    echo "Created user $email\n";
} catch (PDOException $ex) {
    echo "User exists or insert failed: " . $ex->getMessage() . "\n";
}

function post($action, $data) {
    $url = 'http://127.0.0.1/Khan%27s/auth.php?action=' . $action;
    $opts = ['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => http_build_query($data)]];
    $ctx = stream_context_create($opts);
    $resp = file_get_contents($url, false, $ctx);
    echo "ACTION=$action RESPONSE=$resp\n";
    return json_decode($resp, true);
}

$post = post('forgot_password', ['email' => $email, 'mode' => 'otp']);
if (!empty($post['success'])) {
    $rows = $db->query('SELECT otp FROM users WHERE email = ' . $db->quote($email))->fetch(PDO::FETCH_ASSOC);
    echo 'Stored OTP=' . $rows['otp'] . "\n";
    if ($rows['otp']) {
        post('verify_otp', ['email' => $email, 'otp' => $rows['otp']]);
    }
}
