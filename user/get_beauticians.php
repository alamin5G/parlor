<?php
// filepath: c:\xampp\htdocs\parlor\user\get_beauticians.php

// Use the absolute path from the server's document root. This is the fix.
require_once $_SERVER['DOCUMENT_ROOT'] . '/parlor/includes/db_connect.php';

// The old, failing path is commented out below for reference.
// require_once '../../include/db_connect.php';

// Check for connection errors right after including the file
if (!isset($conn) || $conn->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error')]);
    exit;
}


$query = "SELECT e.id, u.name, e.specialization 
          FROM employees e 
          JOIN users u ON e.user_id = u.id 
          WHERE e.status = 'active' AND u.is_active = 1
          ORDER BY u.name ASC";

try {
    $result = $conn->query($query);
    $beauticians = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $beauticians[] = $row;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($beauticians);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => $e->getMessage()]);
}

// The exit is not strictly necessary here, but it's good practice.
exit;