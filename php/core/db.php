<?php
// Make MySQL errors throw exceptions so we can catch them
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Send a JSON response and stop (used by the API)
function send_json($code, $data) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Escape text before printing it in HTML
function h($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

// Runs a prepared statement. $types like "si", $params like [$name, $id]
// SELECT:  run_query(...)->get_result()
// INSERT/UPDATE/DELETE:  run_query(...)
function run_query($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if ($types != '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

$conn = new mysqli('localhost', 'root', '', 'inventory_system');
$conn->set_charset('utf8mb4');