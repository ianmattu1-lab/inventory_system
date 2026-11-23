<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
include __DIR__ . '/../core/db.php';
require __DIR__ . '/../../vendor/autoload.php';

$root = __DIR__ . '/../../';

$products = run_query($conn, "SELECT p.*, c.name AS category_name FROM products p
        JOIN categories c ON p.category_id = c.id
        ORDER BY (p.quantity < p.min_stock_threshold) DESC, p.name")->get_result();

$html = '
<style>
  body { font-family: sans-serif; font-size: 10pt; }
  table { width: 100%; border-collapse: collapse; }
  th { background-color: #212529; color: #ffffff; padding: 6px; text-align: left; }
  td { padding: 5px; border-bottom: 1px solid #cccccc; }
</style>
<h2>Unified Inventory Management System</h2>
<p>Inventory Valuation &amp; Low-Stock Report<br>
Generated: ' . date('Y-m-d H:i') . '<br>
By: ' . h($_SESSION['full_name']) . '</p>
<table>
<tr><th>Image</th><th>SKU</th><th>Product</th><th>Category</th><th>Unit price</th><th>Qty (min)</th><th>Subtotal</th></tr>';

$grand_total = 0;
while ($p = $products->fetch_assoc()) {
    $subtotal = $p['unit_price'] * $p['quantity'];
    $grand_total += $subtotal;

    // Highlight low-stock rows
    $style = '';
    if ($p['quantity'] < $p['min_stock_threshold']) {
        $style = ' style="background-color: #fde2e2;"';
    }

    // Thumbnail (only if the file exists)
    $img = '-';
    $file = $p['image_path'] ? realpath($root . $p['image_path']) : false;
    if ($file) {
        $img = '<img src="' . $file . '" width="40" height="40">';
    }

    $html .= '<tr' . $style . '>
        <td>' . $img . '</td>
        <td>' . h($p['sku']) . '</td>
        <td>' . h($p['name']) . '</td>
        <td>' . h($p['category_name']) . '</td>
        <td>$' . number_format($p['unit_price'], 2) . '</td>
        <td>' . $p['quantity'] . ' (min ' . $p['min_stock_threshold'] . ')</td>
        <td>$' . number_format($subtotal, 2) . '</td>
      </tr>';
}

$html .= '<tr>
    <td colspan="6" align="right"><b>Grand Total Valuation</b></td>
    <td><b>$' . number_format($grand_total, 2) . '</b></td>
  </tr></table>';

$mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
$mpdf->WriteHTML($html);
$mpdf->Output('inventory_report.pdf', 'D');