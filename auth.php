<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$db = new PDO('sqlite:' . __DIR__ . '/includes/users.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL, created_at TEXT NOT NULL)');

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($action === 'session') {
    respond(['authenticated' => isset($_SESSION['user']), 'user' => $_SESSION['user'] ?? null]);
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    respond(['success' => true]);
}

if ($action !== 'register' && $action !== 'login') {
    respond(['error' => 'Invalid authentication action.'], 400);
}

$email = strtolower(trim((string) ($input['email'] ?? '')));
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
