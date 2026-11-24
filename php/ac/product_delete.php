<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
include __DIR__ . '/../core/db.php';

$root = __DIR__ . '/../../';
$id = (int)$_POST['id'];

$p = run_query($conn, "SELECT quantity, image_path FROM products WHERE id = ?", "i", [$id])
     ->get_result()->fetch_assoc();

if ($p) {
    // Log first (note: ON DELETE CASCADE removes this log together with the product)
    run_query($conn, "INSERT INTO stock_logs (product_id, user_id, action_type, quantity_changed, platform)
                      VALUES (?, ?, 'DELETED', ?, 'WEB')", "iii", [$id, $_SESSION['user_id'], $p['quantity']]);

    run_query($conn, "DELETE FROM products WHERE id = ?", "i", [$id]);

    if ($p['image_path'] && file_exists($root . $p['image_path'])) {
        unlink($root . $p['image_path']);
    }
    $_SESSION['msg'] = ['success', 'Product deleted.'];
}
header('Location: ../products.php');