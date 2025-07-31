<?php
// filepath: c:\xampp\htdocs\parlor\admin\get_employees_report.php
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

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Prepare the employee performance query
$query = "
    SELECT 
        e.id as employee_id,
        u.name as employee_name,
        COUNT(a.id) as appointment_count,
        SUM(s.duration_min) as total_minutes,
        COALESCE(SUM(b.amount), 0) as total_revenue,
        COALESCE(AVG(b.amount), 0) as avg_transaction_value,
        COALESCE(SUM(b.amount) / SUM(s.duration_min) * 60, 0) as revenue_per_hour,
        AVG(s.duration_min) as avg_service_duration,
        (
            SELECT COUNT(r.id)
            FROM reviews r
            WHERE r.employee_id = e.id 
            AND DATE(r.created_at) BETWEEN ? AND ?
        ) as review_count,
        (
            SELECT AVG(r.rating)
            FROM reviews r
            WHERE r.employee_id = e.id 
            AND DATE(r.created_at) BETWEEN ? AND ?
        ) as avg_rating,
        (
            SELECT GROUP_CONCAT(DISTINCT s2.name SEPARATOR ', ')
            FROM appointments a2
            JOIN services s2 ON a2.service_id = s2.id
            JOIN bills b2 ON a2.id = b2.appointment_id
            WHERE a2.employee_id = e.id
            AND a2.status = 'completed'
            AND DATE(b2.payment_time) BETWEEN ? AND ?
            GROUP BY a2.employee_id
            LIMIT 1
        ) as services_provided
    FROM appointments a
    JOIN employees e ON a.employee_id = e.id
    JOIN users u ON e.user_id = u.id
    JOIN services s ON a.service_id = s.id
    JOIN bills b ON a.id = b.appointment_id
    $where_clause
    GROUP BY e.id, u.name
    ORDER BY total_revenue DESC
";

// Add the extra date parameters for subqueries
$params = array_merge($params, [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
$param_types .= 'ssssss';

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all employees data
$employees_data = [];
while ($row = $result->fetch_assoc()) {
    // Calculate additional metrics
    $total_hours = round($row['total_minutes'] / 60, 1);
    $row['total_hours'] = $total_hours;
    $row['daily_avg_revenue'] = 0;
    
    // Calculate daily average revenue
    if ($total_hours > 0) {
        $date_diff = (new DateTime($end_date))->diff(new DateTime($start_date))->days + 1;
        $days_worked = min($date_diff, 30); // Assume max 30 days in period
        $row['daily_avg_revenue'] = $row['total_revenue'] / $days_worked;
    }
    
    // Format rating to 1 decimal place and handle null
    $row['avg_rating'] = $row['avg_rating'] ? round($row['avg_rating'], 1) : 'N/A';
    
    // Add to employees data array
    $employees_data[] = $row;
}

// Get the overall summary stats
$summary_query = "
    SELECT
        COUNT(DISTINCT a.id) as total_appointments,
        COALESCE(SUM(b.amount), 0) as total_revenue,
        COUNT(DISTINCT e.id) as employee_count,
        COALESCE(AVG(
            SELECT COUNT(a2.id)
            FROM appointments a2
            JOIN bills b2 ON a2.id = b2.appointment_id
            WHERE a2.employee_id = e.id
            AND a2.status = 'completed'
            AND DATE(b2.payment_time) BETWEEN ? AND ?
        ), 0) as avg_appointments_per_employee
    FROM appointments a
    JOIN employees e ON a.employee_id = e.id
    JOIN bills b ON a.id = b.appointment_id
    WHERE a.status = 'completed' AND DATE(b.payment_time) BETWEEN ? AND ?
";

$summary_stmt = $conn->prepare($summary_query);
$summary_stmt->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();
$summary = $summary_result->fetch_assoc();

// Get the top services by revenue
$top_services_query = "
    SELECT 
        s.id,
        s.name,
        COUNT(a.id) as appointment_count,
        SUM(b.amount) as total_revenue
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN bills b ON a.id = b.appointment_id
    WHERE a.status = 'completed' AND DATE(b.payment_time) BETWEEN ? AND ?
    GROUP BY s.id, s.name
    ORDER BY total_revenue DESC
    LIMIT 5
";

$top_services_stmt = $conn->prepare($top_services_query);
$top_services_stmt->bind_param('ss', $start_date, $end_date);
$top_services_stmt->execute();
$top_services_result = $top_services_stmt->get_result();
$top_services = [];

while ($row = $top_services_result->fetch_assoc()) {
    $top_services[] = $row;
}

// Prepare the response
$response = [
    'status' => 'success',
    'employees' => $employees_data,
    'summary' => $summary,
    'top_services' => $top_services,
    'filters' => [
        'start_date' => $start_date,
        'end_date' => $end_date,
        'service_id' => $filter_service,
        'employee_id' => $filter_employee,
        'payment_method' => $filter_payment
    ]
];

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;