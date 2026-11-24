<?php
session_start();
include 'core/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $user = run_query($conn, "SELECT * FROM users WHERE email = ?", "s", [$email])
            ->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['role'] != 'admin') {
            $error = 'The web portal is for managers (admin) only.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $error = 'Wrong email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-box card shadow-sm"><div class="card-body p-4">
  <h4 class="mb-3">Unified Inventory</h4>
  <?php if ($error != '') { echo '<div class="alert alert-danger">' . h($error) . '</div>'; } ?>
  <form method="post">
    <div class="mb-3"><label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required></div>
    <button class="btn btn-primary w-100">Log in</button>
  </form>
</div></div>
</body>
</html>