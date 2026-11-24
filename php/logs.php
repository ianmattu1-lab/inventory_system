<?php
include 'core/header.php';

$logs = run_query($conn, "SELECT l.*, p.name AS product_name, p.sku, u.full_name
        FROM stock_logs l
        JOIN products p ON l.product_id = p.id
        JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC, l.id DESC LIMIT 300")->get_result();
?>
<h3 class="mb-3">Audit log</h3>
<div class="card shadow-sm"><div class="table-responsive">
<table class="table align-middle mb-0">
  <thead><tr><th>When</th><th>User</th><th>Product</th><th>Action</th><th>Qty changed</th><th>Platform</th></tr></thead>
  <tbody>
  <?php while ($l = $logs->fetch_assoc()) { ?>
    <tr>
      <td><?php echo $l['created_at']; ?></td>
      <td><?php echo h($l['full_name']); ?></td>
      <td><?php echo h($l['product_name']); ?> <small class="text-muted"><?php echo h($l['sku']); ?></small></td>
      <td><?php echo $l['action_type']; ?></td>
      <td><?php echo $l['quantity_changed']; ?></td>
      <td><span class="badge bg-<?php echo $l['platform'] == 'WEB' ? 'info' : 'dark'; ?>"><?php echo $l['platform']; ?></span></td>
    </tr>
  <?php } ?>
  </tbody>
</table>
</div></div>
<?php include 'core/footer.php'; ?>