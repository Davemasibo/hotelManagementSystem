<?php
try {
    $conn = new mysqli('localhost', 'root', '', 'hotel_db');
    if ($conn->connect_error) {
        throw new RuntimeException($conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    $is_ajax = !empty($_GET['action']) || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
    if ($is_ajax) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database unavailable — run setup first.']);
        exit;
    }
    http_response_code(503);
    die('
    <style>body{font-family:sans-serif;background:#f8f9fa;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .box{background:#fff;border-radius:12px;padding:40px;max-width:600px;width:90%;box-shadow:0 4px 24px rgba(0,0,0,.1)}
    code{background:#f1f3f5;padding:2px 6px;border-radius:4px}ol{line-height:2.2}a{color:#0d6efd}</style>
    <div class="box">
      <h3>&#9888;&#65039; Database Setup Required</h3>
      <p>The <code>hotel_db</code> database was not found. Complete these steps once:</p>
      <ol>
        <li>Open <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin &rarr;</a></li>
        <li>Click <strong>New</strong> &rarr; name it <code>hotel_db</code> &rarr; <strong>Create</strong></li>
        <li>Select <code>hotel_db</code> &rarr; <strong>Import</strong> tab</li>
        <li>Import <code>C:\xampp\htdocs\hotel\database\hotel_db.sql</code></li>
        <li>Import <code>C:\xampp\htdocs\hotel\database\migration_v2.sql</code></li>
        <li><a href="">Refresh this page</a></li>
      </ol>
      <p><strong>Default login:</strong> username <code>admin</code> &nbsp; password <code>admin123</code></p>
      <p style="color:#dc3545;font-size:.85em">Error: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>');
}
