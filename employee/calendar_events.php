<?php
// filepath: c:\xampp\htdocs\parlor\employee\calendar_events.php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/parlor/includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'beautician') {
    echo json_encode([]);
    exit;
}

// Get employee_id from session
$stmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$employee_id = $stmt->get_result()->fetch_assoc()['id'] ?? 0;
$stmt->close();

if (!$employee_id) {
    echo json_encode([]);
    exit;
}

// Get date range and status filter from FullCalendar
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-t');
$status = $_GET['status'] ?? '';

// Build the query dynamically and securely
$params = [$employee_id, $start, $end];
$types = "iss";
$where_clause = "a.employee_id = ? AND a.scheduled_at BETWEEN ? AND ?";

if ($status && in_array($status, ['booked','completed','cancelled','rescheduled'])) {
    $where_clause .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql = "SELECT a.id, a.scheduled_at, a.status, s.name as service_name, u.name as customer_name
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        JOIN users u ON a.customer_id = u.id
        WHERE $where_clause";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$colors = [
    'booked' => '#1abc9c',      // Teal
    'completed' => '#2ecc71',   // Green
    'cancelled' => '#e74c3c',   // Red
    'rescheduled' => '#f39c12', // Orange
];

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = [
        'id' => $row['id'],
        'title' => $row['service_name'], // Title is now just the service name
        'start' => $row['scheduled_at'],
        'color' => $colors[$row['status']] ?? '#95a5a6', // Default grey
        'allDay' => false,
        'extendedProps' => [
            'status' => ucfirst($row['status']),
            'customerName' => htmlspecialchars($row['customer_name']),
            'serviceName' => htmlspecialchars($row['service_name'])
        ]
    ];
}

echo json_encode($events);
$stmt->close();
?>