<?php
include 'core/header.php';

// Load categories into an array (used by filter, add form, edit form, excel button)
$cats = [];
$res = run_query($conn, "SELECT id, name FROM categories ORDER BY name")->get_result();
while ($row = $res->fetch_assoc()) { $cats[] = $row; }

$cat_options = '';
foreach ($cats as $c) {
    $cat_options .= '<option value="' . $c['id'] . '">' . h($c['name']) . '</option>';
}

$products = run_query($conn, "SELECT p.*, c.name AS category_name FROM products p
        JOIN categories c ON p.category_id = c.id
        ORDER BY (p.quantity < p.min_stock_threshold) DESC, p.id DESC")->get_result();
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Inventory</h3>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-danger" href="ac/export_pdf.php">Download Valuation &amp; Low-Stock PDF</a>
    <form class="d-flex gap-2" action="ac/export_excel.php" method="get">
      <select name="category_id" class="form-select">
        <option value="">All categories</option>
        <?php echo $cat_options; ?>
      </select>
      <button class="btn btn-outline-success text-nowrap">Download Excel Report (.xlsx)</button>
    </form>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Add product</button>
  </div>
</div>

<div class="row g-2 mb-3">
  <div class="col-md-6"><input id="search" class="form-control" placeholder="Search by name or SKU..."></div>
  <div class="col-md-3">
    <select id="catFilter" class="form-select">
      <option value="">All categories</option>
      <?php echo $cat_options; ?>
    </select>
  </div>
</div>

<div class="card shadow-sm"><div class="table-responsive">
<table id="productTable" class="table align-middle mb-0">
  <thead>
    <tr><th>Image</th><th>SKU</th><th>Product</th><th>Category</th><th>Price</th><th>Qty</th><th>Min</th><th>Status</th><th></th></tr>
  </thead>
  <tbody>
  <?php while ($p = $products->fetch_assoc()) {
      $low = $p['quantity'] < $p['min_stock_threshold']; ?>
    <tr class="<?php echo $low ? 'low-row' : ''; ?>"
        data-cat="<?php echo $p['category_id']; ?>"
        data-search="<?php echo h(strtolower($p['name'] . ' ' . $p['sku'])); ?>">
      <td>
        <?php if ($p['image_path']) { ?>
          <img class="thumb" src="../<?php echo h($p['image_path']); ?>">
        <?php } else { ?>
          <div class="thumb"></div>
        <?php } ?>
      </td>
      <td><?php echo h($p['sku']); ?></td>
      <td><?php echo h($p['name']); ?></td>
      <td><?php echo h($p['category_name']); ?></td>
      <td>$<?php echo number_format($p['unit_price'], 2); ?></td>
      <td><?php echo $p['quantity']; ?></td>
      <td><?php echo $p['min_stock_threshold']; ?></td>
      <td><?php echo $low ? '<span class="badge bg-danger">Low stock</span>' : '<span class="badge bg-success">OK</span>'; ?></td>
      <td class="text-nowrap">
        <button class="btn btn-sm btn-outline-primary edit-btn"
                data-bs-toggle="modal" data-bs-target="#editModal"
                data-id="<?php echo $p['id']; ?>"
                data-name="<?php echo h($p['name']); ?>"
                data-sku="<?php echo h($p['sku']); ?>"
                data-category="<?php echo $p['category_id']; ?>"
                data-price="<?php echo $p['unit_price']; ?>"
                data-qty="<?php echo $p['quantity']; ?>"
                data-min="<?php echo $p['min_stock_threshold']; ?>"
                data-desc="<?php echo h($p['description']); ?>">Edit</button>
        <button class="btn btn-sm btn-outline-danger delete-btn"
                data-bs-toggle="modal" data-bs-target="#deleteModal"
                data-id="<?php echo $p['id']; ?>"
                data-name="<?php echo h($p['name']); ?>">Delete</button>
      </td>
    </tr>
  <?php } ?>
  </tbody>
</table>
</div></div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form method="post" action="ac/product_add.php" enctype="multipart/form-data">
    <div class="modal-header"><h5 class="modal-title">Add product</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">SKU</label><input name="sku" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Category</label>
        <select name="category_id" class="form-select" required><?php echo $cat_options; ?></select></div>
      <div class="col-md-4"><label class="form-label">Unit price ($)</label><input type="number" step="0.01" min="0" name="unit_price" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label">Quantity</label><input type="number" min="0" name="quantity" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label">Min stock threshold</label><input type="number" min="0" name="min_stock_threshold" class="form-control" value="5" required></div>
      <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
      <div class="col-12"><label class="form-label">Image (JPG, PNG, WEBP, max 5MB)</label><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="form-control"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
      <button class="btn btn-primary">Save product</button></div>
  </form>
</div></div></div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form method="post" action="ac/product_edit.php" enctype="multipart/form-data">
    <input type="hidden" name="id" id="edit_id">
    <div class="modal-header"><h5 class="modal-title">Edit product</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-md-6"><label class="form-label">Name</label><input name="name" id="edit_name" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">SKU</label><input name="sku" id="edit_sku" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Category</label>
        <select name="category_id" id="edit_category" class="form-select" required><?php echo $cat_options; ?></select></div>
      <div class="col-md-4"><label class="form-label">Unit price ($)</label><input type="number" step="0.01" min="0" name="unit_price" id="edit_price" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label">Quantity</label><input type="number" min="0" name="quantity" id="edit_qty" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label">Min stock threshold</label><input type="number" min="0" name="min_stock_threshold" id="edit_min" class="form-control" required></div>
      <div class="col-12"><label class="form-label">Description</label><textarea name="description" id="edit_desc" class="form-control" rows="2"></textarea></div>
      <div class="col-12"><label class="form-label">New image (optional)</label><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="form-control"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
      <button class="btn btn-primary">Save changes</button></div>
  </form>
</div></div></div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="post" action="ac/product_delete.php">
    <input type="hidden" name="id" id="delete_id">
    <div class="modal-header"><h5 class="modal-title">Delete product</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">Delete <strong id="delete_name"></strong>? This cannot be undone.</div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
      <button class="btn btn-danger">Delete</button></div>
  </form>
</div></div></div>
<?php include 'core/footer.php'; ?>