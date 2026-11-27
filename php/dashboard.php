<?php
include 'core/header.php';

//kpi numbers
$kpi = run_query($conn, "SELECT COUNT(*) AS total,
        COALESCE(SUM(unit_price * quantity), 0) AS total_value,
        COALESCE(SUM(quantity < min_stock_threshold), 0) AS low_count,
        COALESCE(SUM(quantity = 0), 0) AS out_count
        FROM products")->get_result()->fetch_assoc();

$cat_total = run_query($conn, "SELECT COUNT(*) AS n FROM categories")->get_result()->fetch_assoc()['n'];
$today_total = run_query($conn, "SELECT COUNT(*) AS n FROM stock_logs WHERE DATE(created_at) = CURDATE()")
               ->get_result()->fetch_assoc()['n'];

//stock value per category (for the bar panel)
$by_cat = [];
$res = run_query($conn, "SELECT c.name, COALESCE(SUM(p.unit_price * p.quantity), 0) AS val
        FROM categories c LEFT JOIN products p ON p.category_id = c.id
        GROUP BY c.id, c.name ORDER BY val DESC")->get_result();
$max_val = 0;
while ($row = $res->fetch_assoc()) {
    $by_cat[] = $row;
    if ($row['val'] > $max_val) { $max_val = $row['val']; }
}

//recent activity
$recent = run_query($conn, "SELECT l.*, p.name AS product_name, u.full_name
        FROM stock_logs l
        JOIN products p ON l.product_id = p.id
        JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC, l.id DESC LIMIT 6")->get_result();

//low stock table
$low_items = run_query($conn, "SELECT p.*, c.name AS category_name FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.quantity < p.min_stock_threshold ORDER BY p.quantity ASC")->get_result();
?>

<div class="page-head">
  <div>
    <h3 class="mb-1">Dashboard</h3>
    <p>Here you can get insights about your inventory.</p>
  </div>
  <p><?php echo date('l, F j, Y'); ?></p>
</div>

<div class="row g-3">

  <!-- LEFT: Reminders -->
  <div class="col-xl-3 col-lg-4">
    <div class="portlet">
      <div class="portlet-header">Reminders</div>
      <div class="portlet-body py-2">
        <a class="reminder" href="products.php">
          <span class="reminder-bar red"></span>
          <span class="reminder-num"><?php echo $kpi['low_count']; ?></span>
          <span class="reminder-text">Items below minimum stock</span>
        </a>
        <a class="reminder" href="products.php">
          <span class="reminder-bar red"></span>
          <span class="reminder-num"><?php echo $kpi['out_count']; ?></span>
          <span class="reminder-text">Items out of stock</span>
        </a>
        <a class="reminder" href="logs.php">
          <span class="reminder-bar amber"></span>
          <span class="reminder-num"><?php echo $today_total; ?></span>
          <span class="reminder-text">Stock actions logged today</span>
        </a>
        <a class="reminder" href="categories.php">
          <span class="reminder-bar"></span>
          <span class="reminder-num"><?php echo $cat_total; ?></span>
          <span class="reminder-text">Product categories</span>
        </a>
      </div>
    </div>
  </div>

  <!-- MIDDLE: Tiles, KPIs, category value -->
  <div class="col-xl-6 col-lg-8">
    <div class="portlet mb-3">
      <div class="portlet-header">Shortcuts</div>
      <div class="portlet-body">
        <div class="row g-3">
          <div class="col-4"><a class="tile tile-indigo" href="products.php"><span class="tile-icon">+</span>Inventory</a></div>
          <div class="col-4"><a class="tile tile-blue" href="categories.php"><span class="tile-icon">#</span>Categories</a></div>
          <div class="col-4"><a class="tile tile-slate" href="logs.php"><span class="tile-icon">&#9776;</span>Audit Log</a></div>
          <div class="col-4"><a class="tile tile-red" href="ac/export_pdf.php"><span class="tile-icon">PDF</span>Valuation PDF</a></div>
          <div class="col-4"><a class="tile tile-green" href="ac/export_excel.php"><span class="tile-icon">XLS</span>Excel Report</a></div>
          <div class="col-4"><a class="tile tile-navy" href="products.php"><span class="tile-icon">!</span>Low Stock</a></div>
        </div>
      </div>
    </div>

    <div class="portlet mb-3">
      <div class="portlet-header">Key Performance Indicators</div>
      <div class="portlet-body">
        <div class="row g-3">
          <div class="col-md-4"><div class="card kpi-card">
            <div class="kpi-label">Total products</div>
            <div class="kpi-value"><?php echo $kpi['total']; ?></div></div></div>
          <div class="col-md-4"><div class="card kpi-card kpi-green">
            <div class="kpi-label">Inventory valuation</div>
            <div class="kpi-value">$<?php echo number_format($kpi['total_value'], 2); ?></div></div></div>
          <div class="col-md-4"><div class="card kpi-card kpi-danger">
            <div class="kpi-label">Low-stock alerts</div>
            <div class="kpi-value"><?php echo $kpi['low_count']; ?></div></div></div>
        </div>
      </div>
    </div>

    <div class="portlet">
      <div class="portlet-header">Stock value by category</div>
      <div class="portlet-body">
        <?php foreach ($by_cat as $c) {
            $width = $max_val > 0 ? round($c['val'] / $max_val * 100) : 0; ?>
          <div class="bar-row">
            <div class="bar-top"><span><?php echo h($c['name']); ?></span><span>$<?php echo number_format($c['val'], 2); ?></span></div>
            <div class="bar-track"><div class="bar-fill" style="width: <?php echo $width; ?>%"></div></div>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>

  <!-- RIGHT: Recent activity -->
  <div class="col-xl-3 col-12">
    <div class="portlet">
      <div class="portlet-header">Recent activity <a href="logs.php" class="small text-decoration-none">View all</a></div>
      <div class="portlet-body py-2">
        <?php while ($r = $recent->fetch_assoc()) { ?>
          <div class="activity-item">
            <strong><?php echo h($r['product_name']); ?></strong>
            <span class="badge bg-info ms-1"><?php echo $r['action_type']; ?></span><br>
            <small><?php echo h($r['full_name']); ?> &middot; <?php echo $r['platform']; ?> &middot; <?php echo $r['created_at']; ?></small>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<!-- LOW STOCK TABLE -->
<div class="card mt-3">
  <div class="card-header">Items below minimum stock</div>
  <div class="table-responsive">
  <table class="table">
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

<?php include 'core/footer.php'; ?>