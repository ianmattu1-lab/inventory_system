<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
include __DIR__ . '/../core/db.php';

$root = __DIR__ . '/../../';

$name = trim($_POST['name']);
$sku = trim($_POST['sku']);
$category_id = (int)$_POST['category_id'];
$description = trim($_POST['description']);
$price = (float)$_POST['unit_price'];
$qty = (int)$_POST['quantity'];
$min = (int)$_POST['min_stock_threshold'];
$image_path = null;

// Save the image (if one was chosen)
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        $_SESSION['msg'] = ['danger', 'Only JPG, PNG or WEBP images are allowed.'];
        header('Location: ../../products.php'); exit;
    }
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        $_SESSION['msg'] = ['danger', 'Image must be 5MB or smaller.'];
        header('Location: ../../products.php'); exit;
    }
    $filename = 'item_' . time() . rand(100, 999) . '.' . $ext;
    $image_path = 'uploads/products/' . $filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $root . $image_path);
}

try {
    run_query($conn, "INSERT INTO products (category_id, sku, name, description, unit_price, quantity, min_stock_threshold, image_path)
                      VALUES (?,?,?,?,?,?,?,?)", "isssdiis",
              [$category_id, $sku, $name, $description, $price, $qty, $min, $image_path]);
    $new_id = $conn->insert_id;

    run_query($conn, "INSERT INTO stock_logs (product_id, user_id, action_type, quantity_changed, platform)
                      VALUES (?, ?, 'CREATED', ?, 'WEB')", "iii", [$new_id, $_SESSION['user_id'], $qty]);

    $_SESSION['msg'] = ['success', 'Product added.'];
} catch (mysqli_sql_exception $e) {
    if ($image_path) { unlink($root . $image_path); }
    $_SESSION['msg'] = ['danger', 'Could not save. That SKU may already exist.'];
}
header('Location: ../products.php');