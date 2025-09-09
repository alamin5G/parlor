<?php
// filepath: c:\xampp\htdocs\parlor\admin\get_services_report.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once '../includes/db_connect.php';

// Get parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Optional filters
$filter_service = isset($_GET['service_id']) && $_GET['service_id'] !== '' ? intval($_GET['service_id']) : null;
$filter_employee = isset($_GET['employee_id']) && $_GET['employee_id'] !== '' ? intval($_GET['employee_id']) : null;
$filter_payment = isset($_GET['payment_method']) && $_GET['payment_method'] !== '' ? $_GET['payment_method'] : null;

// Build query conditions
$where_conditions = ["a.status = 'completed'", "DATE(b.payment_time) BETWEEN ? AND ?"];
$params = [$start_date, $end_date];
$param_types = 'ss';

if ($filter_service !== null) {
    $where_conditions[] = "a.service_id = ?";
    $params[] = $filter_service;
    $param_types .= 'i';
}

if ($filter_employee !== null) {
    $where_conditions[] = "a.employee_id = ?";
    $params[] = $filter_employee;
    $param_types .= 'i';
}

if ($filter_payment !== null) {
    $where_conditions[] = "b.payment_method = ?";
    $params[] = $filter_payment;
    $param_types .= 's';
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Fetch all service data
$stmt = $conn->prepare("
    SELECT 
        s.id,
        s.name, 
        COUNT(a.id) as appointment_count, 
        COALESCE(SUM(b.amount), 0) as revenue,
        COALESCE(SUM(b.amount) / COUNT(a.id), 0) as avg_revenue
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    JOIN services s ON a.service_id = s.id
    $where_clause
    GROUP BY s.id, s.name 
    ORDER BY revenue DESC
");

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$services = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($services);
?>