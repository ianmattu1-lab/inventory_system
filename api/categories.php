<?php
ini_set('display_errors', 0);

try {
    include '../php/core/db.php';

    $result = run_query($conn, "SELECT id, name, description FROM categories ORDER BY name")->get_result();
    $list = [];
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $list[] = $row;
    }
    send_json(200, $list);
} catch (Exception $e) {
    send_json(500, ['status' => 'error', 'message' => 'Server error.']);
}