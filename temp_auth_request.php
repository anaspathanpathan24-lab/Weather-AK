<?php
$url = 'http://127.0.0.1/Khan%27s/auth.php?action=forgot_password';
$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query(['email' => 'test@example.com', 'mode' => 'otp'])
    ]
];
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
if ($result === false) {
    $err = error_get_last();
    echo 'ERROR: ' . ($err['message'] ?? 'unknown');
    exit(1);
}
echo $result;
