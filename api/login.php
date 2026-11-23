<?php
ini_set('display_errors', 0);

try {
    include '../php/core/db.php';

    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        send_json(400, ['status' => 'error', 'message' => 'Use POST.']);
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email == '' || $password == '') {
        send_json(400, ['status' => 'error', 'message' => 'Email and password are required.']);
    }

    $user = run_query($conn, "SELECT id, full_name, email, role, password_hash FROM users WHERE email = ?", "s", [$email])
            ->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        send_json(401, ['status' => 'error', 'message' => 'Invalid email or password.']);
    }

    send_json(200, [
        'id' => (int)$user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role']
    ]);
} catch (Exception $e) {
    send_json(500, ['status' => 'error', 'message' => 'Server error.']);
}