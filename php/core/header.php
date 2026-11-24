<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include __DIR__ . '/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Unified Inventory</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">Unified Inventory</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="products.php">Inventory</a></li>
        <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
        <li class="nav-item"><a class="nav-link" href="logs.php">Audit Log</a></li>
      </ul>
      <span class="navbar-text me-3"><?php echo h($_SESSION['full_name']); ?></span>
      <a class="btn btn-outline-light btn-sm" href="logout.php">Log out</a>
    </div>
  </div>
</nav>
<div class="container-fluid py-4">
<?php
// Show a one-time message (set by the action files)
if (isset($_SESSION['msg'])) {
    echo '<div class="alert alert-' . $_SESSION['msg'][0] . '">' . h($_SESSION['msg'][1]) . '</div>';
    unset($_SESSION['msg']);
}
?>