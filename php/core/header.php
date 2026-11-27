<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include __DIR__ . '/db.php';

$current = basename($_SERVER['SCRIPT_NAME']);
function nav_active($file, $current) {
    return $file == $current ? ' active' : '';
}
$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Unified Inventory</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php"><span class="brand-mark">U</span>Unified Inventory</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav me-auto ms-lg-3">
        <li class="nav-item"><a class="nav-link<?php echo nav_active('dashboard.php', $current); ?>" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link<?php echo nav_active('products.php', $current); ?>" href="products.php">Inventory</a></li>
        <li class="nav-item"><a class="nav-link<?php echo nav_active('categories.php', $current); ?>" href="categories.php">Categories</a></li>
        <li class="nav-item"><a class="nav-link<?php echo nav_active('logs.php', $current); ?>" href="logs.php">Audit Log</a></li>
      </ul>
      <div class="user-chip">
        <span class="user-avatar"><?php echo h($initial); ?></span>
        <div>
          <strong><?php echo h($_SESSION['full_name']); ?></strong>
          <small><?php echo h(ucfirst($_SESSION['role'])); ?></small>
        </div>
      </div>
      <a class="btn btn-logout btn-sm" href="logout.php">Log out</a>
    </div>
  </div>
</nav>
<div class="container-fluid app-main">
<?php
// Show a one-time message (set by the action files)
if (isset($_SESSION['msg'])) {
    echo '<div class="alert alert-' . $_SESSION['msg'][0] . '">' . h($_SESSION['msg'][1]) . '</div>';
    unset($_SESSION['msg']);
}
?>