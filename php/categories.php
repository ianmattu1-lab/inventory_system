<?php
include 'php/core/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if ($_POST['action'] == 'add') {
            run_query($conn, "INSERT INTO categories (name, description) VALUES (?, ?)", "ss",
                      [trim($_POST['name']), trim($_POST['description'])]);
            $_SESSION['msg'] = ['success', 'Category added.'];
        } elseif ($_POST['action'] == 'delete') {
            run_query($conn, "DELETE FROM categories WHERE id = ?", "i", [(int)$_POST['id']]);
            $_SESSION['msg'] = ['success', 'Category deleted.'];
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['msg'] = ['danger', 'Cannot do that: the name already exists, or the category still has products.'];
    }
    header('Location: categories.php');
    exit;
}

$cats = run_query($conn, "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS total
        FROM categories c ORDER BY c.name")->get_result();
?>
<h3 class="mb-3">Categories</h3>

<div class="card shadow-sm mb-4"><div class="card-body">
  <form method="post" class="row g-2">
    <input type="hidden" name="action" value="add">
    <div class="col-md-4"><input name="name" class="form-control" placeholder="Category name" required></div>
    <div class="col-md-6"><input name="description" class="form-control" placeholder="Description"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Add category</button></div>
  </form>
</div></div>

<div class="card shadow-sm"><div class="table-responsive">
<table class="table align-middle mb-0">
  <thead><tr><th>Name</th><th>Description</th><th>Products</th><th></th></tr></thead>
  <tbody>
  <?php while ($c = $cats->fetch_assoc()) { ?>
    <tr>
      <td><?php echo h($c['name']); ?></td>
      <td><?php echo h($c['description']); ?></td>
      <td><?php echo $c['total']; ?></td>
      <td>
        <form method="post" onsubmit="return confirm('Delete this category?');">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
          <button class="btn btn-sm btn-outline-danger">Delete</button>
        </form>
      </td>
    </tr>
  <?php } ?>
  </tbody>
</table>
</div></div>
<?php include 'php/core/footer.php'; ?>