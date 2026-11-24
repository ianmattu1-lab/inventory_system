<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
include __DIR__ . '/../core/db.php';

$root = __DIR__ . '/../../';

$id = (int)$_POST['id'];
$name = trim($_POST['name']);
$sku = trim($_POST['sku']);
$category_id = (int)$_POST['category_id'];
$description = trim($_POST['description']);
$price = (float)$_POST['unit_price'];
$qty = (int)$_POST['quantity'];
$min = (int)$_POST['min_stock_threshold'];

// Get the old product (to compare quantity and keep the old image)
$old = run_query($conn, "SELECT quantity, image_path FROM products WHERE id = ?", "i", [$id])
       ->get_result()->fetch_assoc();
if (!$old) {
    $_SESSION['msg'] = ['danger', 'Product not found.'];
    header('Location: ../../products.php'); exit;
}

$image_path = $old['image_path'];
$replaced_old = false;

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
    $new_path = 'uploads/products/' . $filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $root . $new_path);
    $image_path = $new_path;
    $replaced_old = true;
}

try {
    run_query($conn, "UPDATE products SET category_id=?, sku=?, name=?, description=?, unit_price=?,
                      quantity=?, min_stock_threshold=?, image_path=? WHERE id=?", "isssdiisi",
              [$category_id, $sku, $name, $description, $price, $qty, $min, $image_path, $id]);

    if ($replaced_old && $old['image_path'] && file_exists($root . $old['image_path'])) {
        unlink($root . $old['image_path']);
    }

    // Work out what to write in the audit log
    $diff = $qty - $old['quantity'];
    if ($diff > 0) { $action = 'STOCK_IN'; }
    elseif ($diff < 0) { $action = 'STOCK_OUT'; }
    else { $action = 'UPDATED'; }

    run_query($conn, "INSERT INTO stock_logs (product_id, user_id, action_type, quantity_changed, platform)
                      VALUES (?, ?, ?, ?, 'WEB')", "iisi", [$id, $_SESSION['user_id'], $action, abs($diff)]);

    $_SESSION['msg'] = ['success', 'Product updated.'];
} catch (mysqli_sql_exception $e) {
    $_SESSION['msg'] = ['danger', 'Could not update. That SKU may already exist.'];
}
header('Location: ../products.php');