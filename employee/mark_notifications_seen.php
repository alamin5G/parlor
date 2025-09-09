<?php
// filepath: c:\xampp\htdocs\parlor\employee\mark_notifications_seen.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/parlor/includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'beautician' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit;
}

// Get employee_id from session
$stmt_emp = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt_emp->bind_param("i", $_SESSION['user_id']);
$stmt_emp->execute();
$employee_id = $stmt_emp->get_result()->fetch_assoc()['id'] ?? 0;
$stmt_emp->close();

if ($employee_id) {
    // Mark all 'booked' appointments as seen by this employee
    $sql = "UPDATE appointments SET is_seen_by_employee = 1 WHERE employee_id = ? AND status = 'booked' AND is_seen_by_employee = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>