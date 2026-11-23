<?php
include 'php/core/header.php';

$kpi = run_query($conn, "SELECT COUNT(*) AS total,
        COALESCE(SUM(unit_price * quantity), 0) AS total_value,
        COALESCE(SUM(quantity < min_stock_threshold), 0) AS low_count
        FROM products")->get_result()->fetch_assoc();

$low_items = run_query($conn, "SELECT p.*, c.name AS category_name FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.quantity < p.min_stock_threshold ORDER BY p.quantity ASC")->get_result();
?>
<h3 class="mb-4">Dashboard</h3>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card kpi shadow-sm"><div class="card-body">
    <div class="text-muted">Total products</div>
    <div class="fs-2 fw-bold"><?php echo $kpi['total']; ?></div></div></div></div>
  <div class="col-md-4"><div class="card kpi shadow-sm"><div class="card-body">
    <div class="text-muted">Total inventory valuation</div>
    <div class="fs-2 fw-bold">$<?php echo number_format($kpi['total_value'], 2); ?></div></div></div></div>
  <div class="col-md-4"><div class="card kpi-low shadow-sm"><div class="card-body">
    <div class="text-muted">Low-stock alerts</div>
    <div class="fs-2 fw-bold text-danger"><?php echo $kpi['low_count']; ?></div></div></div></div>
</div>

<div class="card shadow-sm">
  <div class="card-header">Items below minimum stock</div>
  <div class="table-responsive">
  <table class="table mb-0">
    <thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Qty</th><th>Min</th></tr></thead>
    <tbody>
    <?php while ($p = $low_items->fetch_assoc()) { ?>
      <tr>
        <td><?php echo h($p['sku']); ?></td>
        <td><?php echo h($p['name']); ?></td>
        <td><?php echo h($p['category_name']); ?></td>
        <td><span class="badge bg-danger"><?php echo $p['quantity']; ?></span></td>
        <td><?php echo $p['min_stock_threshold']; ?></td>
      </tr>
    <?php } ?>
    </tbody>
  </table>
  </div>
</div>
<?php include 'php/core/footer.php'; ?>