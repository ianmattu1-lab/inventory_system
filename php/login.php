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
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
</head>
<body class="login-page">
  
  <form method="post" class="login-form">
    
    <<div class="login-avatar">
      <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="8" r="4"></circle>
        <path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7z"></path>
      </svg>
    </div>

    <h1 class="login-title">INVENTORY LOGIN</h1>

    <?php if ($error != '') { ?>
      <div class="login-error"><?php echo h($error); ?></div>
    <?php } ?>

    <div class="login-field">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="#fff">
        <path d="M2 5h20v14H2zm2 2v.5l8 5 8-5V7zm16 3.8-8 5-8-5V17h16z"></path>
      </svg>
      <input type="email" name="email" placeholder="Email ID" required autofocus>
    </div>

    <div class="login-field">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="#fff">
        <path d="M6 10V8a6 6 0 0 1 12 0v2h1v12H5V10zm2 0h8V8a4 4 0 0 0-8 0z"></path>
      </svg>
      <input type="password" name="password" placeholder="Password" required>
    </div>

    <button type="submit" class="login-btn">LOGIN</button>

    <p class="login-foot">Unified Inventory Management System</p>
  </form>

</body>
</html>