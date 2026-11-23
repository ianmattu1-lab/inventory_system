<?php
ini_set('display_errors', 0);

// Turns a DB row into the JSON the app expects
function format_product($p) {
    $p['id'] = (int)$p['id'];
    $p['category_id'] = (int)$p['category_id'];
    $p['unit_price'] = (float)$p['unit_price'];
    $p['quantity'] = (int)$p['quantity'];
    $p['min_stock_threshold'] = (int)$p['min_stock_threshold'];
    $p['is_low_stock'] = $p['quantity'] < $p['min_stock_threshold'];
    if ($p['image_path']) {
        $p['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/inventory/' . $p['image_path'];
    } else {
        $p['image_url'] = null;
    }
    return $p;
}

function get_product($conn, $id) {
    $row = run_query($conn, "SELECT p.*, c.name AS category_name FROM products p
            JOIN categories c ON p.category_id = c.id WHERE p.id = ?", "i", [$id])
           ->get_result()->fetch_assoc();
    return $row ? format_product($row) : null;
}

// Validates and saves an uploaded image. Returns the path, or null if none was sent.
function save_image() {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] != 0) {
        return null;
    }
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        send_json(400, ['status' => 'error', 'message' => 'Only JPG, PNG or WEBP images are allowed.']);
    }
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        send_json(400, ['status' => 'error', 'message' => 'Image must be 5MB or smaller.']);
    }
    $path = 'uploads/products/item_' . time() . rand(100, 999) . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], '../' . $path);
    return $path;
}

try {
    include '../php/core/db.php';

    $method = $_SERVER['REQUEST_METHOD'];
    // Allow POST with _method=PUT (needed for multipart edits)
    if ($method == 'POST' && isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    }

    // ---------------- GET: list products ----------------
    if ($method == 'GET') {
        $sql = "SELECT p.*, c.name AS category_name FROM products p
                JOIN categories c ON p.category_id = c.id WHERE 1=1";
        $types = '';
        $params = [];
        if (!empty($_GET['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $types .= 'i';
            $params[] = (int)$_GET['category_id'];
        }
        if (!empty($_GET['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
            $types .= 'ss';
            $like = '%' . $_GET['search'] . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= " ORDER BY (p.quantity < p.min_stock_threshold) DESC, p.id DESC"; // low stock first

        $result = run_query($conn, $sql, $types, $params)->get_result();
        $list = [];
        while ($row = $result->fetch_assoc()) {
            $list[] = format_product($row);
        }
        send_json(200, $list);
    }

    // ---------------- POST: create product ----------------
    elseif ($method == 'POST') {
        foreach (['name', 'sku', 'category_id', 'user_id'] as $field) {
            if (empty($_POST[$field])) {
                send_json(400, ['status' => 'error', 'message' => "Field '$field' is required."]);
            }
        }
        $image_path = save_image();
        $qty = (int)($_POST['quantity'] ?? 0);

        run_query($conn, "INSERT INTO products (category_id, sku, name, description, unit_price, quantity, min_stock_threshold, image_path)
                          VALUES (?,?,?,?,?,?,?,?)", "isssdiis",
                  [(int)$_POST['category_id'], trim($_POST['sku']), trim($_POST['name']), $_POST['description'] ?? '',
                   (float)($_POST['unit_price'] ?? 0), $qty, (int)($_POST['min_stock_threshold'] ?? 5), $image_path]);
        $new_id = $conn->insert_id;

        run_query($conn, "INSERT INTO stock_logs (product_id, user_id, action_type, quantity_changed, platform)
                          VALUES (?, ?, 'CREATED', ?, 'MOBILE')", "iii", [$new_id, (int)$_POST['user_id'], $qty]);

        send_json(201, get_product($conn, $new_id));
    }

    // ---------------- PUT: update product / adjust stock ----------------
    elseif ($method == 'PUT') {
        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            parse_str(file_get_contents('php://input'), $data);
        } else {
            $data = $_POST;
        }
        if (empty($data['id']) || empty($data['user_id'])) {
            send_json(400, ['status' => 'error', 'message' => "Fields 'id' and 'user_id' are required."]);
        }
        $id = (int)$data['id'];

        $old = run_query($conn, "SELECT * FROM products WHERE id = ?", "i", [$id])->get_result()->fetch_assoc();
        if (!$old) {
            send_json(404, ['status' => 'error', 'message' => 'Product not found.']);
        }

        // Use the new value if sent, otherwise keep the old one
        $name = $data['name'] ?? $old['name'];
        $sku = $data['sku'] ?? $old['sku'];
        $category_id = (int)($data['category_id'] ?? $old['category_id']);
        $description = $data['description'] ?? $old['description'];
        $price = (float)($data['unit_price'] ?? $old['unit_price']);
        $qty = (int)($data['quantity'] ?? $old['quantity']);
        $min = (int)($data['min_stock_threshold'] ?? $old['min_stock_threshold']);

        $image_path = save_image();
        if ($image_path == null) {
            $image_path = $old['image_path'];
        } elseif ($old['image_path'] && file_exists('../' . $old['image_path'])) {
            unlink('../' . $old['image_path']);
        }

        run_query($conn, "UPDATE products SET category_id=?, sku=?, name=?, description=?, unit_price=?,
                          quantity=?, min_stock_threshold=?, image_path=? WHERE id=?", "isssdiisi",
                  [$category_id, $sku, $name, $description, $price, $qty, $min, $image_path, $id]);

        $diff = $qty - $old['quantity'];
        if ($diff > 0) { $action = 'STOCK_IN'; }
        elseif ($diff < 0) { $action = 'STOCK_OUT'; }
        else { $action = 'UPDATED'; }

        run_query($conn, "INSERT INTO stock_logs (product_id, user_id, action_type, quantity_changed, platform)
                          VALUES (?, ?, ?, ?, 'MOBILE')", "iisi", [$id, (int)$data['user_id'], $action, abs($diff)]);

        send_json(200, get_product($conn, $id));
    }

    // ---------------- DELETE: remove product ----------------
    elseif ($method == 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        $user_id = (int)($_GET['user_id'] ?? 0);
        if ($id == 0 || $user_id == 0) {
            send_json(400, ['status' => 'error', 'message' => "Query parameters 'id' and 'user_id' are required."]);
        }

        $p = run_query($conn, "SELECT quantity, image_path FROM products WHERE id = ?", "i", [$id])
             ->get_result()->fetch_assoc();
        if (!$p) {
            send_json(404, ['status' => 'error', 'message' => 'Product not found.']);
        }

        run_query($conn, "INSERT INTO stock_logs (product_id, user_id, action_type, quantity_changed, platform)
                          VALUES (?, ?, 'DELETED', ?, 'MOBILE')", "iii", [$id, $user_id, $p['quantity']]);
        run_query($conn, "DELETE FROM products WHERE id = ?", "i", [$id]);

        if ($p['image_path'] && file_exists('../' . $p['image_path'])) {
            unlink('../' . $p['image_path']);
        }
        send_json(200, ['status' => 'success', 'message' => 'Product deleted.']);
    }

    else {
        send_json(400, ['status' => 'error', 'message' => 'Unsupported method.']);
    }

} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) {
        send_json(400, ['status' => 'error', 'message' => 'That SKU already exists.']);
    }
    if ($e->getCode() == 1452) {
        send_json(400, ['status' => 'error', 'message' => 'Invalid category_id or user_id.']);
    }
    send_json(500, ['status' => 'error', 'message' => 'Database error.']);
} catch (Exception $e) {
    send_json(500, ['status' => 'error', 'message' => 'Server error.']);
}