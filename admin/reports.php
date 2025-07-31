<?php
// filepath: c:\xampp\htdocs\parlor\admin\reports.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Business Reports";
require_once 'include/header.php';
require_once '../includes/db_connect.php';

// --- Date Filtering Logic ---
$current_year = date('Y');
$current_month = date('m');

// Default to current month if no dates provided
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Optional filters
$filter_service = isset($_GET['service_id']) && $_GET['service_id'] !== '' ? intval($_GET['service_id']) : null;
$filter_employee = isset($_GET['employee_id']) && $_GET['employee_id'] !== '' ? intval($_GET['employee_id']) : null;
$filter_payment = isset($_GET['payment_method']) && $_GET['payment_method'] !== '' ? $_GET['payment_method'] : null;

// Quick date filters
$date_range = $_GET['date_range'] ?? '';
if ($date_range) {
    switch ($date_range) {
        case 'today':
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
            break;
        case 'yesterday':
            $start_date = date('Y-m-d', strtotime('-1 day'));
            $end_date = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'week':
            $start_date = date('Y-m-d', strtotime('monday this week'));
            $end_date = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            break;
        case 'last_month':
            $start_date = date('Y-m-01', strtotime('last month'));
            $end_date = date('Y-m-t', strtotime('last month'));
            break;
        case 'quarter':
            $current_quarter = ceil($current_month / 3);
            $start_month = (($current_quarter - 1) * 3) + 1;
            $end_month = $current_quarter * 3;
            $start_date = date('Y-' . str_pad($start_month, 2, '0', STR_PAD_LEFT) . '-01');
            $end_date = date('Y-' . str_pad($end_month, 2, '0', STR_PAD_LEFT) . '-' . date('t', strtotime($current_year . '-' . str_pad($end_month, 2, '0', STR_PAD_LEFT) . '-01')));
            break;
        case 'year':
            $start_date = date('Y-01-01');
            $end_date = date('Y-12-31');
            break;
        case 'last_7_days':
            $start_date = date('Y-m-d', strtotime('-6 days'));
            $end_date = date('Y-m-d');
            break;
        case 'last_30_days':
            $start_date = date('Y-m-d', strtotime('-29 days'));
            $end_date = date('Y-m-d');
            break;
        case 'last_90_days':
            $start_date = date('Y-m-d', strtotime('-89 days'));
            $end_date = date('Y-m-d');
            break;
    }
}

// --- Build filter clauses ---
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
    $where_conditions[] = "b.payment_mode = ?";
    $params[] = $filter_payment;
    $param_types .= 's';
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// --- Data Fetching ---
// 1. Get data for dropdown filters
// 1.1 Get services for filter dropdown
$services_stmt = $conn->prepare("SELECT id, name FROM services ORDER BY name");
$services_stmt->execute();
$services = $services_stmt->get_result();

// 1.2 Get employees for filter dropdown
$employees_stmt = $conn->prepare("
    SELECT e.id, u.name 
    FROM employees e
    JOIN users u ON e.user_id = u.id 
    ORDER BY u.name
");
$employees_stmt->execute();
$employees = $employees_stmt->get_result();

// 1.3 Get payment methods for filter dropdown - COMBINED APPROACH
// First get payment modes from bills table
$payment_methods_stmt = $conn->prepare("
    SELECT DISTINCT payment_mode as payment_method
    FROM bills 
    ORDER BY payment_mode
");
$payment_methods_stmt->execute();
$payment_methods_from_bills = $payment_methods_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Now get methods from online_payments table
$online_payment_methods_stmt = $conn->prepare("
    SELECT DISTINCT method as payment_method
    FROM online_payments 
    ORDER BY method
");
$online_payment_methods_stmt->execute();
$payment_methods_from_online = $online_payment_methods_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Combine and deduplicate payment methods
$payment_methods = array_merge($payment_methods_from_bills, $payment_methods_from_online);
$unique_methods = [];
$payment_methods_list = [];

foreach ($payment_methods as $method) {
    if (!in_array($method['payment_method'], $unique_methods)) {
        $unique_methods[] = $method['payment_method'];
        $payment_methods_list[] = $method;
    }
}

// 2. Summary Stats
$stmt_summary = $conn->prepare("
    SELECT
        COALESCE(SUM(b.amount), 0) as total_revenue,
        COUNT(DISTINCT a.id) as total_appointments,
        COUNT(DISTINCT a.customer_id) as unique_customers,
        (SELECT COUNT(*) FROM users WHERE role='customer' AND DATE(created_at) BETWEEN ? AND ?) as new_customers,
        COALESCE(AVG(b.amount), 0) as avg_transaction,
        (
            SELECT COUNT(DISTINCT op.id) 
            FROM online_payments op 
            WHERE op.status = 'approved' AND DATE(op.submitted_at) BETWEEN ? AND ?
        ) as online_payment_count,
        (
            SELECT COALESCE(SUM(op.amount), 0) 
            FROM online_payments op 
            WHERE op.status = 'approved' AND DATE(op.submitted_at) BETWEEN ? AND ?
        ) as online_payment_amount,
        (
            SELECT COUNT(DISTINCT id) 
            FROM appointments 
            WHERE status = 'cancelled' AND DATE(created_at) BETWEEN ? AND ?
        ) as cancelled_appointments
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    $where_clause
");

// Updated binding parameters for the additional WHERE clauses
array_unshift($params, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
$param_types = 'ssssssss' . $param_types;
$stmt_summary->bind_param($param_types, ...$params);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();

// Reset parameters for other queries
for ($i = 0; $i < 8; $i++) {
    array_shift($params);
}
$param_types = substr($param_types, 8);

// 3. Revenue by Service
$stmt_service = $conn->prepare("
    SELECT 
        s.id,
        s.name, 
        s.price as base_price,
        COUNT(a.id) as appointment_count, 
        COALESCE(SUM(b.amount), 0) as revenue,
        COALESCE(SUM(b.amount) / COUNT(a.id), 0) as avg_revenue,
        ROUND((COUNT(a.id) * 100.0) / (
            SELECT COUNT(*) FROM bills b2 
            JOIN appointments a2 ON b2.appointment_id = a2.id
            $where_clause
        ), 1) as percentage
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    JOIN services s ON a.service_id = s.id
    $where_clause
    GROUP BY s.id, s.name, s.price
    ORDER BY revenue DESC
    LIMIT 10
");
$stmt_service->bind_param($param_types . $param_types, ...array_merge($params, $params));
$stmt_service->execute();
$service_revenue = $stmt_service->get_result();

// 4. Revenue by Employee
$stmt_employee = $conn->prepare("
    SELECT 
        e.id,
        u.name, 
        COUNT(a.id) as appointment_count, 
        COALESCE(SUM(b.amount), 0) as revenue,
        COALESCE(AVG(b.amount), 0) as avg_bill,
        (
            SELECT AVG(r.rating) 
            FROM reviews r 
            JOIN appointments a2 ON r.appointment_id = a2.id 
            WHERE a2.employee_id = e.id AND DATE(a2.scheduled_at) BETWEEN ? AND ?
        ) as avg_rating,
        ROUND((COUNT(a.id) * 100.0) / (
            SELECT COUNT(*) FROM bills b2 
            JOIN appointments a2 ON b2.appointment_id = a2.id
            $where_clause
        ), 1) as percentage
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    JOIN employees e ON a.employee_id = e.id
    JOIN users u ON e.user_id = u.id
    $where_clause
    GROUP BY e.id, u.name 
    ORDER BY revenue DESC
    LIMIT 10
");
$stmt_employee->bind_param($param_types . 'ss' . $param_types, ...array_merge($params, [$start_date, $end_date], $params));
$stmt_employee->execute();
$employee_revenue = $stmt_employee->get_result();


// 5. Payment Methods breakdown - Fixed version
$stmt_payment = $conn->prepare("
    SELECT 
        'In-Store Payments' as source,
        b.payment_mode as payment_method, 
        COUNT(*) as count, 
        SUM(b.amount) as total,
        -- Note: This percentage is relative to the total number of *bills*, not all payments.
        ROUND(COUNT(*) * 100.0 / (
            SELECT COUNT(*) FROM bills b2 
            JOIN appointments a2 ON b2.appointment_id = a2.id
            $where_clause
        ), 1) as percentage
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    $where_clause
    GROUP BY b.payment_mode

    UNION ALL

    SELECT 
        'Online Payments' as source,
        op.method as payment_method,
        COUNT(*) as count,
        SUM(op.amount) as total,
        -- Note: This percentage is relative to the total number of *online payments*, not all payments.
        ROUND(COUNT(*) * 100.0 / (
            SELECT COUNT(*) FROM online_payments WHERE status = 'approved' AND DATE(submitted_at) BETWEEN ? AND ?
        ), 1) as percentage
    FROM online_payments op
    WHERE op.status = 'approved' AND DATE(op.submitted_at) BETWEEN ? AND ?
    GROUP BY op.method
    ORDER BY source, payment_method
");

// THE FIX: Provide 8 types and 8 variables to match the 8 placeholders (?)
$stmt_payment->bind_param(
    $param_types . $param_types . 'ssss',  // Results in 'ssssssss'
    ...array_merge($params, $params, [$start_date, $end_date, $start_date, $end_date])
);

$stmt_payment->execute();
$payment_methods_data = $stmt_payment->get_result();


// 6. Daily trend for the selected period
$stmt_daily = $conn->prepare("
    SELECT 
        DATE(b.payment_time) as date,
        COUNT(DISTINCT a.id) as appointments,
        SUM(b.amount) as revenue
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    $where_clause
    GROUP BY DATE(b.payment_time)
    ORDER BY date
");
$stmt_daily->bind_param($param_types, ...$params);
$stmt_daily->execute();
$daily_trend = $stmt_daily->get_result()->fetch_all(MYSQLI_ASSOC);


// Add online vs offline revenue comparison
$stmt_online_offline = $conn->prepare("
    SELECT 
        'In-Store Payments' as type,
        COUNT(*) as count,
        SUM(b.amount) as revenue
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    $where_clause
    
    UNION ALL
    
    SELECT 
        'Online Payments' as type,
        COUNT(*) as count,
        SUM(op.amount) as revenue
    FROM online_payments op
    WHERE op.status = 'approved' AND DATE(op.submitted_at) BETWEEN ? AND ?
");
$stmt_online_offline->bind_param($param_types . 'ss', ...array_merge($params, [$start_date, $end_date]));
$stmt_online_offline->execute();
$online_offline_data = $stmt_online_offline->get_result();

// Add overall revenue by service category
$stmt_overall_revenue = $conn->prepare("
    SELECT 
        CASE 
            WHEN s.name LIKE 'Hair%' THEN 'Hair Services'
            WHEN s.name LIKE 'Face%' THEN 'Facial Services'
            WHEN s.name LIKE 'Spa%' THEN 'Spa Treatments'
            WHEN s.name LIKE 'Nail%' THEN 'Nail Services'
            WHEN s.name LIKE 'Make%' THEN 'Makeup Services'
            ELSE 'Other Services'
        END as service_name,
        COUNT(*) as count,
        SUM(b.amount) as total_revenue,
        CASE 
            WHEN s.name LIKE 'Hair%' THEN 'rgba(255, 99, 132, 0.7)'
            WHEN s.name LIKE 'Face%' THEN 'rgba(54, 162, 235, 0.7)'
            WHEN s.name LIKE 'Spa%' THEN 'rgba(255, 206, 86, 0.7)'
            WHEN s.name LIKE 'Nail%' THEN 'rgba(75, 192, 192, 0.7)'
            WHEN s.name LIKE 'Make%' THEN 'rgba(153, 102, 255, 0.7)'
            ELSE 'rgba(201, 203, 207, 0.7)'
        END as color
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    JOIN services s ON a.service_id = s.id
    $where_clause
    GROUP BY service_name
    ORDER BY total_revenue DESC
");
$stmt_overall_revenue->bind_param($param_types, ...$params);
$stmt_overall_revenue->execute();
$overall_revenue_data = $stmt_overall_revenue->get_result();

// Add comparison data preparation for chart
$comparison_period = $_GET['comparison_period'] ?? 'weekly';
$comparison_data = [];
$comparison_labels = [];

// Generate comparison data based on selected period
switch ($comparison_period) {
    case 'daily':
        $stmt_comp = $conn->prepare("
            SELECT 
                DATE(b.payment_time) as period,
                SUM(b.amount) as revenue
            FROM bills b
            JOIN appointments a ON b.appointment_id = a.id
            WHERE a.status = 'completed' AND DATE(b.payment_time) BETWEEN DATE_SUB(?, INTERVAL 30 DAY) AND ?
            GROUP BY DATE(b.payment_time)
            ORDER BY period
        ");
        $stmt_comp->bind_param('ss', $end_date, $end_date);
        $format = 'M d';
        break;
    
    case 'weekly':
        $stmt_comp = $conn->prepare("
            SELECT 
                YEARWEEK(b.payment_time, 1) as period_num,
                CONCAT('Week ', WEEK(b.payment_time, 1)) as period,
                SUM(b.amount) as revenue
            FROM bills b
            JOIN appointments a ON b.appointment_id = a.id
            WHERE a.status = 'completed' AND DATE(b.payment_time) BETWEEN DATE_SUB(?, INTERVAL 12 WEEK) AND ?
            GROUP BY YEARWEEK(b.payment_time, 1), period
            ORDER BY period_num
        ");
        $stmt_comp->bind_param('ss', $end_date, $end_date);
        $format = 'Week W';
        break;
    
    case 'monthly':
    default:
        $stmt_comp = $conn->prepare("
            SELECT 
                DATE_FORMAT(b.payment_time, '%Y-%m') as period_num,
                DATE_FORMAT(b.payment_time, '%b %Y') as period,
                SUM(b.amount) as revenue
            FROM bills b
            JOIN appointments a ON b.appointment_id = a.id
            WHERE a.status = 'completed' AND DATE(b.payment_time) BETWEEN DATE_SUB(?, INTERVAL 12 MONTH) AND ?
            GROUP BY DATE_FORMAT(b.payment_time, '%Y-%m'), period
            ORDER BY period_num
        ");
        $stmt_comp->bind_param('ss', $end_date, $end_date);
        $format = 'M Y';
        break;
}

$stmt_comp->execute();
$comparison_result = $stmt_comp->get_result();

$comparison_data = [
    [
        'label' => 'Revenue',
        'data' => [],
        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
        'borderColor' => 'rgba(54, 162, 235, 1)',
        'borderWidth' => 2,
        'tension' => 0.1
    ]
];

while ($row = $comparison_result->fetch_assoc()) {
    $comparison_labels[] = $row['period'];
    $comparison_data[0]['data'][] = $row['revenue'];
}


// Employee Efficiency Analysis
$stmt_efficiency = $conn->prepare("
    SELECT 
        e.id as employee_id,
        u.name as employee_name,
        COUNT(a.id) as appointment_count,
        SUM(s.duration_min) as total_minutes,
        COALESCE(SUM(b.amount), 0) as total_revenue,
        COALESCE(SUM(b.amount) / SUM(s.duration_min) * 60, 0) as revenue_per_hour,
        AVG(s.duration_min) as avg_service_duration
    FROM appointments a
    JOIN employees e ON a.employee_id = e.id
    JOIN users u ON e.user_id = u.id
    JOIN services s ON a.service_id = s.id
    JOIN bills b ON a.id = b.appointment_id
    WHERE a.status = 'completed' AND DATE(b.payment_time) BETWEEN ? AND ?
    GROUP BY e.id, u.name
    ORDER BY revenue_per_hour DESC
");
$stmt_efficiency->bind_param("ss", $start_date, $end_date);
$stmt_efficiency->execute();
$efficiency_data = $stmt_efficiency->get_result();


// 7. Time of day analysis
$stmt_time = $conn->prepare("
    SELECT 
        CASE 
            WHEN HOUR(a.scheduled_at) BETWEEN 6 AND 11 THEN 'Morning (6AM-12PM)'
            WHEN HOUR(a.scheduled_at) BETWEEN 12 AND 16 THEN 'Afternoon (12PM-5PM)'
            WHEN HOUR(a.scheduled_at) BETWEEN 17 AND 20 THEN 'Evening (5PM-9PM)'
            ELSE 'Night (9PM-6AM)'
        END as time_slot,
        COUNT(*) as appointment_count,
        SUM(b.amount) as revenue
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    $where_clause
    GROUP BY time_slot
    ORDER BY 
        CASE time_slot
            WHEN 'Morning (6AM-12PM)' THEN 1
            WHEN 'Afternoon (12PM-5PM)' THEN 2
            WHEN 'Evening (5PM-9PM)' THEN 3
            ELSE 4
        END
");
$stmt_time->bind_param($param_types, ...$params);
$stmt_time->execute();
$time_analysis = $stmt_time->get_result();

// 8. Customer frequency analysis
$stmt_customer = $conn->prepare("
    SELECT 
        visit_count,
        COUNT(*) as customer_count
    FROM (
        SELECT 
            a.customer_id, 
            COUNT(DISTINCT a.id) as visit_count
        FROM bills b
        JOIN appointments a ON b.appointment_id = a.id
        $where_clause
        GROUP BY a.customer_id
    ) as customer_visits
    GROUP BY visit_count
    ORDER BY visit_count
");
$stmt_customer->bind_param($param_types, ...$params);
$stmt_customer->execute();
$customer_frequency = $stmt_customer->get_result();

// 9. Day of week analysis
$stmt_dow = $conn->prepare("
    SELECT 
        DAYOFWEEK(a.scheduled_at) as day_num,
        DAYNAME(a.scheduled_at) as day_name,
        COUNT(*) as appointment_count,
        SUM(b.amount) as revenue
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    $where_clause
    GROUP BY day_num, day_name
    ORDER BY day_num
");
$stmt_dow->bind_param($param_types, ...$params);
$stmt_dow->execute();
$day_of_week_analysis = $stmt_dow->get_result();

// 10. Online payment analysis
$stmt_online = $conn->prepare("
    SELECT
        op.method,
        COUNT(*) as count,
        SUM(op.amount) as total,
        ROUND(AVG(TIMESTAMPDIFF(HOUR, op.created_at, op.submitted_at)), 1) as avg_processing_hours
    FROM online_payments op
    WHERE op.status = 'approved' AND DATE(op.submitted_at) BETWEEN ? AND ?
    GROUP BY op.method
    ORDER BY total DESC
");
$stmt_online->bind_param('ss', $start_date, $end_date); // Fixed: only binding 2 parameters
$stmt_online->execute();
$online_payment_analysis = $stmt_online->get_result();

// 11. Service category performance
$service_categories = [];
$service_revenue->data_seek(0);
while($row = $service_revenue->fetch_assoc()) {
    // Extract category from service name (e.g. "Hair Cut" -> "Hair")
    $parts = explode(' ', $row['name']);
    $category = $parts[0];
    
    if(!isset($service_categories[$category])) {
        $service_categories[$category] = [
            'category' => $category,
            'appointment_count' => 0,
            'revenue' => 0
        ];
    }
    
    $service_categories[$category]['appointment_count'] += $row['appointment_count'];
    $service_categories[$category]['revenue'] += $row['revenue'];
}
$service_categories = array_values($service_categories);
usort($service_categories, function($a, $b) {
    return $b['revenue'] <=> $a['revenue'];
});

// 12. Customer retention rate
$stmt_retention = $conn->prepare("
    SELECT 
        COUNT(DISTINCT a.customer_id) as returning_customers
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    $where_clause
    AND a.customer_id IN (
        SELECT DISTINCT a2.customer_id
        FROM appointments a2
        JOIN bills b2 ON a2.id = b2.appointment_id
        WHERE a2.status = 'completed' 
        AND DATE(a2.scheduled_at) < ?
    )
");
array_unshift($params, $start_date);
$param_types = 's' . $param_types;
$stmt_retention->bind_param($param_types, ...$params);
$stmt_retention->execute();
$retention_data = $stmt_retention->get_result()->fetch_assoc();
array_shift($params);
$param_types = substr($param_types, 1);

// Calculate retention rate
$retention_rate = $summary['unique_customers'] > 0 ? 
    round(($retention_data['returning_customers'] / $summary['unique_customers']) * 100, 1) : 0;

// Convert daily trend to JSON for chart
$dates = [];
$revenues = [];
$appointments = [];

foreach ($daily_trend as $day) {
    $dates[] = date('M d', strtotime($day['date']));
    $revenues[] = $day['revenue'];
    $appointments[] = $day['appointments'];
}

$chart_data = [
    'dates' => $dates,
    'revenues' => $revenues,
    'appointments' => $appointments
];

// Format the date for display
$display_start_date = date('M d, Y', strtotime($start_date));
$display_end_date = date('M d, Y', strtotime($end_date));
$report_period = $display_start_date . ' to ' . $display_end_date;
if ($start_date === $end_date) {
    $report_period = date('F d, Y', strtotime($start_date));
}

// Calculate date difference for insight text
$date_diff = (new DateTime($end_date))->diff(new DateTime($start_date))->days + 1;

// NEW: Get efficiency metrics
$stmt_efficiency = $conn->prepare("
    SELECT 
        e.id as employee_id,
        u.name as employee_name,
        COUNT(a.id) as appointment_count,
        SUM(s.duration_min) as total_minutes,
        COALESCE(SUM(b.amount), 0) as total_revenue,
        COALESCE(SUM(b.amount) / SUM(s.duration_min) * 60, 0) as revenue_per_hour,
        AVG(s.duration_min) as avg_service_duration
    FROM appointments a
    JOIN employees e ON a.employee_id = e.id
    JOIN users u ON e.user_id = u.id
    JOIN services s ON a.service_id = s.id
    JOIN bills b ON a.id = b.appointment_id
    WHERE a.status = 'completed' AND DATE(b.payment_time) BETWEEN ? AND ?
    GROUP BY e.id, u.name
    ORDER BY revenue_per_hour DESC
");
$stmt_efficiency->bind_param("ss", $start_date, $end_date);
$stmt_efficiency->execute();
$efficiency_data = $stmt_efficiency->get_result();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Business Reports</h1>
            <p class="text-muted mb-0">
                <i class="fas fa-calendar me-1"></i> 
                <?= $report_period ?> <span class="badge bg-light text-dark border ms-1"><?= $date_diff ?> days</span>
            </p>
        </div>
        <div class="btn-group">
            <a href="export_handler.php?type=csv&start_date=<?= $start_date ?>&end_date=<?= $end_date ?><?= $filter_service ? '&service_id='.$filter_service : '' ?><?= $filter_employee ? '&employee_id='.$filter_employee : '' ?><?= $filter_payment ? '&payment_method='.$filter_payment : '' ?>" 
               class="btn btn-outline-success">
                <i class="fas fa-file-csv me-1"></i> Export CSV
            </a>
            <a href="export_handler.php?type=pdf&start_date=<?= $start_date ?>&end_date=<?= $end_date ?><?= $filter_service ? '&service_id='.$filter_service : '' ?><?= $filter_employee ? '&employee_id='.$filter_employee : '' ?><?= $filter_payment ? '&payment_method='.$filter_payment : '' ?>" 
               class="btn btn-outline-danger" target="_blank">
                <i class="fas fa-file-pdf me-1"></i> Export PDF
            </a>
        </div>
    </div>

    <!-- Enhanced Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Report Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="reports.php" id="report-filter-form">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Quick Date Range</label>
                        <select class="form-select" id="date-range-select" name="date_range">
                            <option value="" <?= $date_range == '' ? 'selected' : '' ?>>Custom Range</option>
                            <option value="today" <?= $date_range == 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="yesterday" <?= $date_range == 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                            <option value="last_7_days" <?= $date_range == 'last_7_days' ? 'selected' : '' ?>>Last 7 Days</option>
                            <option value="last_30_days" <?= $date_range == 'last_30_days' ? 'selected' : '' ?>>Last 30 Days</option>
                            <option value="week" <?= $date_range == 'week' ? 'selected' : '' ?>>This Week</option>
                            <option value="month" <?= $date_range == 'month' ? 'selected' : '' ?>>This Month</option>
                            <option value="last_month" <?= $date_range == 'last_month' ? 'selected' : '' ?>>Last Month</option>
                            <option value="quarter" <?= $date_range == 'quarter' ? 'selected' : '' ?>>This Quarter</option>
                            <option value="year" <?= $date_range == 'year' ? 'selected' : '' ?>>This Year</option>
                        </select>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <label for="service_id" class="form-label">Service</label>
                        <select class="form-select" id="service_id" name="service_id">
                            <option value="">All Services</option>
                            <?php while($service = $services->fetch_assoc()): ?>
                                <option value="<?= $service['id'] ?>" <?= $filter_service == $service['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($service['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <label for="employee_id" class="form-label">Employee</label>
                        <select class="form-select" id="employee_id" name="employee_id">
                            <option value="">All Employees</option>
                            <?php while($employee = $employees->fetch_assoc()): ?>
                                <option value="<?= $employee['id'] ?>" <?= $filter_employee == $employee['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($employee['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="">All Methods</option>
                            <?php foreach($payment_methods_list as $method): ?>
                                <option value="<?= $method['payment_method'] ?>" <?= $filter_payment == $method['payment_method'] ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $method['payment_method'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-6 col-md-12 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-sync-alt me-1"></i> Generate Report
                        </button>
                        <a href="reports.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-undo me-1"></i> Reset Filters
                        </a>
                        <button type="button" class="btn btn-outline-info" data-bs-toggle="collapse" data-bs-target="#savedReportsSection">
                            <i class="fas fa-save me-1"></i> Saved Reports
                        </button>
                    </div>
                </div>
                
                <div class="collapse mt-3" id="savedReportsSection">
                    <div class="card card-body bg-light">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="report-name" placeholder="Report name...">
                                    <button class="btn btn-outline-secondary" type="button" id="save-report">
                                        <i class="fas fa-save me-1"></i> Save Current
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadSavedReport('monthly')">Monthly Overview</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadSavedReport('quarterly')">Quarterly Report</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadSavedReport('yearly')">Yearly Summary</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadSavedReport('employees')">Employee Performance</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadSavedReport('online_payments')">Online Payments</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Dashboard -->
    <div class="row">
        <!-- Summary KPI Cards -->
        <div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Key Insights</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-calendar-day text-primary me-2"></i> 
                            <span>Busiest Day</span>
                        </div>
                        <span class="badge bg-primary rounded-pill"><?= $busiest_day['name'] ?? 'N/A' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-clock text-success me-2"></i> 
                            <span>Peak Hours</span>
                        </div>
                        <span class="badge bg-success rounded-pill"><?= $busiest_time_slot['name'] ?? 'N/A' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-user-tie text-warning me-2"></i> 
                            <span>Top Employee</span>
                        </div>
                        <?php 
                        $employee_revenue->data_seek(0);
                        $top_employee = $employee_revenue->fetch_assoc();
                        ?>
                        <span class="badge bg-warning rounded-pill"><?= $top_employee ? $top_employee['name'] : 'N/A' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-spa text-info me-2"></i> 
                            <span>Top Service</span>
                        </div>
                        <?php 
                        $service_revenue->data_seek(0);
                        $top_service = $service_revenue->fetch_assoc();
                        ?>
                        <span class="badge bg-info rounded-pill"><?= $top_service ? $top_service['name'] : 'N/A' ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Revenue Trend</h5>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary active" id="view-revenue">Revenue</button>
                    <button type="button" class="btn btn-outline-secondary" id="view-appointments">Appointments</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Revenue</h6>
                            <h2 class="mb-0 text-primary">৳<?= number_format($summary['total_revenue'], 2) ?></h2>
                            <p class="text-muted small mb-0">
                                <i class="fas fa-receipt me-1"></i> Avg. ৳<?= number_format($summary['avg_transaction'], 2) ?> per transaction
                            </p>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-money-bill-wave fa-2x text-primary"></i>
                        </div>
                    </div>
                    <?php if ($date_range === 'month' || $date_range === 'last_month'): ?>
                        <div class="progress mt-3" style="height: 5px;">
                            <?php 
                            $days_in_month = date('t', strtotime($start_date));
                            $day_of_month = min($days_in_month, intval(date('d', strtotime($end_date))));
                            $target_progress = ($day_of_month / $days_in_month) * 100;
                            ?>
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $target_progress ?>%" aria-valuenow="<?= $target_progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="small text-muted mt-1 mb-0">
                            <?= $day_of_month ?> of <?= $days_in_month ?> days (<?= round($target_progress) ?>%)
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Completed Services</h6>
                            <h2 class="mb-0 text-success"><?= number_format($summary['total_appointments']) ?></h2>
                            <p class="text-muted small mb-0">
                                <i class="fas fa-users me-1"></i> <?= number_format($summary['unique_customers']) ?> unique customers
                            </p>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-calendar-check fa-2x text-success"></i>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex align-items-center small">
                        <div class="flex-grow-1">
                            <span class="d-inline-block" data-bs-toggle="tooltip" title="Customer retention rate">
                                <i class="fas fa-user-clock text-muted me-1"></i> Retention Rate:
                            </span>
                        </div>
                        <span class="badge <?= $retention_rate >= 50 ? 'bg-success' : ($retention_rate >= 30 ? 'bg-warning' : 'bg-danger') ?>">
                            <?= $retention_rate ?>%
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">New Customers</h6>
                            <h2 class="mb-0 text-warning"><?= number_format($summary['new_customers']) ?></h2>
                            <p class="text-muted small mb-0">
                                <i class="fas fa-chart-line me-1"></i> During this period
                            </p>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-user-plus fa-2x text-warning"></i>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex align-items-center small">
                        <div class="flex-grow-1">
                            <span class="d-inline-block" data-bs-toggle="tooltip" title="Cancelled appointments during this period">
                                <i class="fas fa-calendar-times text-muted me-1"></i> Cancelled:
                            </span>
                        </div>
                        <span class="badge bg-danger">
                            <?= number_format($summary['cancelled_appointments']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Online Payments</h6>
                            <h2 class="mb-0 text-info"><?= number_format($summary['online_payment_count']) ?></h2>
                            <p class="text-muted small mb-0">
                                <i class="fas fa-credit-card me-1"></i> ৳<?= number_format($summary['online_payment_amount'], 2) ?> total
                            </p>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-mobile-alt fa-2x text-info"></i>
                        </div>
                    </div>
                    <?php 
                    $online_percentage = $summary['total_revenue'] > 0 ? 
                        round(($summary['online_payment_amount'] / $summary['total_revenue']) * 100, 1) : 0;
                    ?>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?= min(100, $online_percentage) ?>%" aria-valuenow="<?= $online_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="small text-muted mt-1 mb-0">
                        <?= $online_percentage ?>% of total revenue
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Revenue Chart -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Revenue Trend</h5>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary active" id="view-revenue">Revenue</button>
                <button type="button" class="btn btn-outline-secondary" id="view-appointments">Appointments</button>
            </div>
        </div>
        <div class="card-body">
            <canvas id="revenueChart" height="100"></canvas>
        </div>
    </div>
    
    <!-- Main Report Content: Now with two columns for better layout -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Day of Week Analysis</h5>
                </div>
                <div class="card-body d-flex flex-wrap align-items-center p-3">
                    <div style="flex: 1 1 200px; min-width: 200px; padding-right: 1rem;">
                        <canvas id="dayOfWeekChart" height="220"></canvas>
                    </div>
                    <div style="flex: 1 1 300px; min-width: 300px;">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Day</th>
                                        <th class="text-center">Services</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $day_of_week_analysis->data_seek(0);
                                    $day_labels = [];
                                    $day_data = [];
                                    $day_colors = ['Sunday' => 'rgba(255, 99, 132, 0.7)','Monday' => 'rgba(54, 162, 235, 0.7)','Tuesday' => 'rgba(255, 206, 86, 0.7)','Wednesday' => 'rgba(75, 192, 192, 0.7)','Thursday' => 'rgba(153, 102, 255, 0.7)','Friday' => 'rgba(255, 159, 64, 0.7)','Saturday' => 'rgba(201, 203, 207, 0.7)'];
                                    $day_backgrounds = [];
                                    $busiest_day = ['name' => 'N/A','revenue' => 0,'count' => 0];
                                    ?>
                                    <?php while($row = $day_of_week_analysis->fetch_assoc()): ?>
                                        <?php 
                                        $day_labels[] = $row['day_name'];
                                        $day_data[] = $row['revenue'];
                                        $color = $day_colors[$row['day_name']] ?? 'rgba(108, 117, 125, 0.7)';
                                        $day_backgrounds[] = $color;
                                        if($row['revenue'] > $busiest_day['revenue']) {
                                            $busiest_day = ['name' => $row['day_name'],'revenue' => $row['revenue'],'count' => $row['appointment_count']];
                                        }
                                        ?>
                                        <tr>
                                            <td><span class="badge" style="background-color: <?= $color ?>"><?= $row['day_name'] ?></span></td>
                                            <td class="text-center"><?= number_format($row['appointment_count']) ?></td>
                                            <td class="text-end text-nowrap">৳<?= number_format($row['revenue'], 2) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-2 mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Busiest day:</strong> <?= htmlspecialchars($busiest_day['name']) ?> with <?= number_format($busiest_day['count']) ?> services (৳<?= number_format($busiest_day['revenue'], 2) ?>)
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-spa me-2"></i>Top Services</h5>
                    <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#allServicesModal">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Service</th>
                                    <th class="text-center">Count</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-end">% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $service_revenue->data_seek(0);
                                $total_service_count = 0;
                                $total_service_revenue = 0;
                                while($row = $service_revenue->fetch_assoc()): 
                                    $total_service_count += $row['appointment_count'];
                                    $total_service_revenue += $row['revenue'];
                                ?>
                                <tr>
                                    <td>
                                        <a href="reports.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&service_id=<?= $row['id'] ?>">
                                            <?= htmlspecialchars($row['name']) ?>
                                        </a>
                                        <div class="progress mt-1" style="height: 3px;">
                                            <div class="progress-bar bg-primary" style="width: <?= $row['percentage'] ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="text-center"><?= number_format($row['appointment_count']) ?></td>
                                    <td class="text-end">৳<?= number_format($row['revenue'], 2) ?></td>
                                    <td class="text-end"><?= number_format($row['percentage'], 1) ?>%</td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if($total_service_count > 0): ?>
                                <tr class="table-light fw-bold">
                                    <td>Total</td>
                                    <td class="text-center"><?= number_format($total_service_count) ?></td>
                                    <td class="text-end">৳<?= number_format($total_service_revenue, 2) ?></td>
                                    <td class="text-end">100.0%</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Employee Performance</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th class="text-center">Services</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-center">Avg Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $employee_revenue->data_seek(0);
                                while($row = $employee_revenue->fetch_assoc()): 
                                    $rating = $row['avg_rating'] ? round($row['avg_rating'], 1) : 'N/A';
                                    $rating_class = $rating == 'N/A' ? 'text-muted' : ($rating >= 4.5 ? 'text-success' : ($rating >= 3.5 ? 'text-primary' : ($rating >= 2.5 ? 'text-warning' : 'text-danger')));
                                ?>
                                <tr>
                                    <td>
                                        <a href="reports.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&employee_id=<?= $row['id'] ?>">
                                            <?= htmlspecialchars($row['name']) ?>
                                        </a>
                                        <div class="progress mt-1" style="height: 3px;">
                                            <div class="progress-bar bg-success" style="width: <?= $row['percentage'] ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="text-center"><?= number_format($row['appointment_count']) ?></td>
                                    <td class="text-end">৳<?= number_format($row['revenue'], 2) ?></td>
                                    <td class="text-center <?= $rating_class ?>">
                                        <?php if($rating != 'N/A'): ?>
                                            <i class="fas fa-star me-1"></i>
                                        <?php endif; ?>
                                        <?= $rating ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>Customer Segments Analysis</h5>
                </div>
                <div class="card-body d-flex flex-wrap align-items-center p-3">
                    <div style="flex: 1 1 200px; min-width: 200px; padding-right: 1rem;">
                        <canvas id="customerSegmentChart" height="220"></canvas>
                    </div>
                    <div style="flex: 1 1 300px; min-width: 300px;">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer Segment</th>
                                        <th class="text-center">Customers</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $customer_frequency->data_seek(0);
                                    $segments = [['name' => 'One-time', 'count' => 0, 'revenue' => 0, 'color' => 'rgba(220, 53, 69, 0.7)'],['name' => 'Occasional (2-3)', 'count' => 0, 'revenue' => 0, 'color' => 'rgba(255, 193, 7, 0.7)'],['name' => 'Regular (4-6)', 'count' => 0, 'revenue' => 0, 'color' => 'rgba(13, 110, 253, 0.7)'],['name' => 'Loyal (7+)', 'count' => 0, 'revenue' => 0, 'color' => 'rgba(40, 167, 69, 0.7)']];
                                    $total_customers = 0;
                                    while($row = $customer_frequency->fetch_assoc()) {
                                        $total_customers += $row['customer_count'];
                                        if($row['visit_count'] == 1) {$segments[0]['count'] += $row['customer_count'];$segments[0]['revenue'] += ($row['visit_count'] * $row['customer_count'] * ($summary['avg_transaction'] ?? 0));}
                                        else if($row['visit_count'] >= 2 && $row['visit_count'] <= 3) {$segments[1]['count'] += $row['customer_count'];$segments[1]['revenue'] += ($row['visit_count'] * $row['customer_count'] * ($summary['avg_transaction'] ?? 0));}
                                        else if($row['visit_count'] >= 4 && $row['visit_count'] <= 6) {$segments[2]['count'] += $row['customer_count'];$segments[2]['revenue'] += ($row['visit_count'] * $row['customer_count'] * ($summary['avg_transaction'] ?? 0));}
                                        else {$segments[3]['count'] += $row['customer_count'];$segments[3]['revenue'] += ($row['visit_count'] * $row['customer_count'] * ($summary['avg_transaction'] ?? 0));}
                                    }
                                    foreach($segments as $segment):
                                        if($segment['count'] > 0):
                                            $percentage = $total_customers > 0 ? round(($segment['count'] / $total_customers) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge me-1" style="background-color: <?= $segment['color'] ?>">&nbsp;</span>
                                            <?= $segment['name'] ?>
                                            <div class="progress mt-1" style="height: 3px;"><div class="progress-bar" style="background-color: <?= $segment['color'] ?>; width: <?= $percentage ?>%"></div></div>
                                            <span class="small text-muted"><?= $percentage ?>% of customers</span>
                                        </td>
                                        <td class="text-center"><?= number_format($segment['count']) ?></td>
                                        <td class="text-end">৳<?= number_format($segment['revenue'], 2) ?></td>
                                    </tr>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3 mb-0">
                            <h6 class="mb-2"><i class="fas fa-lightbulb me-2"></i>Insight</h6>
                            <?php
                            $loyal_percentage = $total_customers > 0 ? round(($segments[3]['count'] / $total_customers) * 100, 1) : 0;
                            $loyal_revenue_percentage = $summary['total_revenue'] > 0 ? round(($segments[3]['revenue'] / $summary['total_revenue']) * 100, 1) : 0;
                            if ($loyal_percentage > 20): ?>
                                <p class="mb-0">Your loyal customers (<?= $loyal_percentage ?>% of customer base) contribute approximately <?= $loyal_revenue_percentage ?>% of your total revenue. Focus on retaining these valuable customers.</p>
                            <?php elseif ($segments[0]['count'] > ($total_customers / 2)): ?>
                                <p class="mb-0">More than half of your customers are one-time visitors. Consider implementing a customer loyalty program to encourage repeat business.</p>
                            <?php else: ?>
                                <p class="mb-0">Regular customers make up a significant portion of your business. Consider special promotions for these customers to convert them into loyal clients.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Methods</h5>
                </div>
                <div class="card-body d-flex flex-wrap align-items-center p-3">
                    <div style="flex: 1 1 200px; min-width: 200px; padding-right: 1rem;">
                        <canvas id="paymentMethodsChart" height="220"></canvas>
                    </div>
                    <div style="flex: 1 1 300px; min-width: 300px;">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Method</th>
                                        <th class="text-center">Count</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $payment_methods_data->data_seek(0);
                                    $payment_method_labels = [];
                                    $payment_method_data = [];
                                    $payment_method_colors = ['cash' => 'rgba(40, 167, 69, 0.7)','bkash' => 'rgba(220, 53, 69, 0.7)','rocket' => 'rgba(13, 110, 253, 0.7)','nagad' => 'rgba(255, 193, 7, 0.7)'];
                                    $payment_method_backgrounds = [];
                                    $total_payments = 0;
                                    $total_payment_revenue = 0;
                                    ?>
                                    <?php while($row = $payment_methods_data->fetch_assoc()): ?>
                                        <?php 
                                        $method_name = ucfirst(str_replace('_', ' ', $row['payment_method']));
                                        $payment_method_labels[] = $method_name;
                                        $payment_method_data[] = $row['total'];
                                        $color = $payment_method_colors[$row['payment_method']] ?? 'rgba(108, 117, 125, 0.7)';
                                        $payment_method_backgrounds[] = $color;
                                        $total_payments += $row['count'];
                                        $total_payment_revenue += $row['total'];
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge me-1" style="background-color: <?= $color ?>">&nbsp;</span>
                                                <?= $method_name ?>
                                            </td>
                                            <td class="text-center"><?= number_format($row['count']) ?></td>
                                            <td class="text-end">৳<?= number_format($row['total'], 2) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <tr class="table-light fw-bold">
                                        <td>Total</td>
                                        <td class="text-center"><?= number_format($total_payments) ?></td>
                                        <td class="text-end">৳<?= number_format($total_payment_revenue, 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Time of Day Analysis</h5>
                </div>
                <div class="card-body d-flex flex-wrap align-items-center p-3">
                    <div style="flex: 1 1 200px; min-width: 200px; padding-right: 1rem;">
                        <canvas id="timeOfDayChart" height="220"></canvas>
                    </div>
                    <div style="flex: 1 1 300px; min-width: 300px;">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Time Slot</th>
                                        <th class="text-center">Services</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $time_analysis->data_seek(0);
                                    $time_slot_labels = [];
                                    $time_slot_data = [];
                                    $time_slot_colors = ['Morning (6AM-12PM)' => 'rgba(255, 193, 7, 0.7)','Afternoon (12PM-5PM)' => 'rgba(40, 167, 69, 0.7)','Evening (5PM-9PM)' => 'rgba(13, 110, 253, 0.7)','Night (9PM-6AM)' => 'rgba(108, 117, 125, 0.7)'];
                                    $time_slot_backgrounds = [];
                                    $busiest_time_slot = ['name' => 'N/A','revenue' => 0,'count' => 0];
                                    ?>
                                    <?php while($row = $time_analysis->fetch_assoc()): ?>
                                        <?php 
                                        $time_slot_labels[] = $row['time_slot'];
                                        $time_slot_data[] = $row['revenue'];
                                        $color = $time_slot_colors[$row['time_slot']] ?? 'rgba(108, 117, 125, 0.7)';
                                        $time_slot_backgrounds[] = $color;
                                        if($row['revenue'] > $busiest_time_slot['revenue']) {
                                            $busiest_time_slot = ['name' => $row['time_slot'],'revenue' => $row['revenue'],'count' => $row['appointment_count']];
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge me-1" style="background-color: <?= $color ?>">&nbsp;</span>
                                                <?= $row['time_slot'] ?>
                                            </td>
                                            <td class="text-center"><?= number_format($row['appointment_count']) ?></td>
                                            <td class="text-end">৳<?= number_format($row['revenue'], 2) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3 mb-0">
                            <h6 class="mb-2"><i class="fas fa-lightbulb me-2"></i>Insight</h6>
                            <p class="mb-0">Your busiest time slot is <strong><?= $busiest_time_slot['name'] ?></strong> with <?= number_format($busiest_time_slot['count']) ?> services completed. Consider scheduling your best staff during this time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Performance Comparison -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Revenue Performance Comparison</h5>
            <div class="btn-group btn-group-sm" id="comparison-period-buttons">
                <button type="button"<?php if($comparison_period == 'daily') echo ' class="active"'; ?> class="btn btn-outline-secondary" data-period="daily">Daily</button>
                <button type="button"<?php if($comparison_period == 'weekly') echo ' class="active"'; ?> class="btn btn-outline-secondary" data-period="weekly">Weekly</button>
                <button type="button"<?php if($comparison_period == 'monthly') echo ' class="active"'; ?> class="btn btn-outline-secondary" data-period="monthly">Monthly</button>
            </div>
        </div>
        <div class="card-body">
            <canvas id="revenueComparisonChart" height="100"></canvas>
        </div>
    </div>  
    <!-- Online vs Offline Revenue -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-globe me-2"></i>Online vs Offline Revenue</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <canvas id="onlineOfflineChart" height="220"></canvas>
                </div>
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th class="text-center">Count</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $online_offline_data->data_seek(0);
                                while ($row = $online_offline_data->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td><?= $row['type'] ?></td>
                                        <td class="text-center"><?= number_format($row['count']) ?></td>
                                        <td class="text-end">৳<?= number_format($row['revenue'], 2) ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>  
            <div class="card-footer">
                <button type="button" class="btn btn-primary" id="download-report">Download Report</button>
            </div>
        </div>
    </div>  
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Overall Revenue Breakdown</h5>
        </div>
        <div class="card-body">
            <canvas id="overallRevenueChart" height="220"></canvas>
        </div>
    </div>
</div>
</div>
</div>
<?php require_once 'includes/footer_scripts.php'; ?>



<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    const ctxCustomerSegment = document.getElementById('customerSegmentChart').getContext('2d');
    const customerSegmentChart = new Chart(ctxCustomerSegment, {
        type: 'pie',
        data: {
            labels: <?= json_encode($segment_labels) ?>,
            datasets: [{
                data: <?= json_encode($segment_data) ?>,
                backgroundColor: <?= json_encode($segment_backgrounds) ?>,              
                borderColor: 'rgba(255, 255, 255, 0.8)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#333',  
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.raw + ' (' + ((context.raw / <?= $total_customers ?>) * 100).toFixed(1) + '%)';
                            return label;
                        }
                    }
                }
            }
        }
    });
    const ctxPaymentMethods = document.getElementById('paymentMethodsChart').getContext('2d');
    const paymentMethodsChart = new Chart(ctxPaymentMethods, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($payment_method_labels) ?>,
            datasets: [{
                data: <?= json_encode($payment_method_data) ?>,
                backgroundColor: <?= json_encode($payment_method_backgrounds) ?>,
                borderColor: 'rgba(255, 255, 255, 0.8)',
                borderWidth: 2
            }]
        },  
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#333',  
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '৳' + context.raw.toLocaleString();
                            return label;
                        }
                    }
                }
            }
        }
    });
    const ctxTimeOfDay = document.getElementById('timeOfDayChart').getContext('2d');
    const timeOfDayChart = new Chart(ctxTimeOfDay, {
        type: 'bar',
        data: {
            labels: <?= json_encode($time_slot_labels) ?>,  
            datasets: [{
                label: 'Revenue',
                data: <?= json_encode($time_slot_data) ?>,
                backgroundColor: <?= json_encode($time_slot_backgrounds) ?>,
                borderColor: 'rgba(255, 255, 255, 0.8)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }   
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {   
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                },

                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '৳' + context.raw.toLocaleString();
                            return label;
                        }
                    }
                },

                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '৳' + context.raw.toLocaleString();
                            return label;
                        }
                    }
                }
            }
        }
    });
    const ctxRevenueComparison = document.getElementById('revenueComparisonChart').getContext('2d');
    const revenueComparisonChart = new Chart(ctxRevenueComparison, {
        type: 'line',
        data: {
            labels: <?= json_encode($comparison_labels) ?>,
            datasets: <?= json_encode($comparison_data) ?>
        },
        options: {
            responsive: true,       
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                },
                y: {    
                    beginAtZero: true,
                    ticks: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '৳' + context.raw.toLocaleString();
                            return label;
                        }
                    }
                }
            }
        }
    });
    const ctxRevenueComparison = document.getElementById('revenueComparisonChart').getContext('2d');
    const revenueComparisonChart = new Chart(ctxRevenueComparison, {
        type: 'line',
        data: {
            labels: <?= json_encode($comparison_labels) ?>,
            datasets: <?= json_encode($comparison_data) ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '৳' + context.raw.toLocaleString();
                            return label;
                        }
                    }
                }
            }
        }
    });
    const ctxRevenueComparison = document.getElementById('revenueComparisonChart').getContext('2d');
    const revenueComparisonChart = new Chart(ctxRevenueComparison, {
        type: 'line',
        data: {
            labels: <?= json_encode($comparison_labels) ?>,
            datasets: <?= json_encode($comparison_data) ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '৳' + context.raw.toLocaleString();
                            return label;
                        }
                    }
                }
            }
        }
    });
    const ctxOnlineOffline = document.getElementById('onlineOfflineChart').getContext('2d');
    const onlineOfflineChart = new Chart(ctxOnlineOffline, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($online_offline_data->fetch_all(MYSQLI_ASSOC), 'type')) ?>,
            datasets: [{
                label: 'Revenue',
                data: <?= json_encode(array_column($online_offline_data->fetch_all(MYSQLI_ASSOC), 'revenue')) ?>,
                backgroundColor: ['rgba(40, 167, 69, 0.7)', 'rgba(220, 53, 69, 0.7)'],
                borderColor: 'rgba(255, 255, 255, 0.8)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                }
            }
        }
    }); 
    const ctxOverallRevenue = document.getElementById('overallRevenueChart').getContext('2d');
    const overallRevenueChart = new Chart(ctxOverallRevenue, {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($overall_revenue_data->fetch_all(MYSQLI_ASSOC), 'service_name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($overall_revenue_data->fetch_all(MYSQLI_ASSOC), 'total_revenue')) ?>,
                backgroundColor: <?= json_encode(array_column($overall_revenue_data->fetch_all(MYSQLI_ASSOC), 'color')) ?>,
                borderColor: 'rgba(255, 255, 255, 0.8)',
                borderWidth: 2
            }]  
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }   
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '৳' + context.raw.toLocaleString();
                            return label;
                        }
                    }
                }   
            }
        }
    }); 
    // Handle comparison period button clicks
    document.querySelectorAll('#comparison-period-buttons button').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('#comparison-period-buttons button').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const period = this.getAttribute('data-period');
            fetch(`admin/reports.php?comparison_period=${period}`)
                .then(response => response.text())
                .then(html => {
                    document.querySelector('.card-body').innerHTML = html;
                    // Reinitialize charts after new data is loaded
                    initCharts();
                }); 
        });
    });
    // Initialize charts on page load
    function initCharts() {
        customerSegmentChart.update();
        paymentMethodsChart.update();
        timeOfDayChart.update();
        revenueComparisonChart.update();
        onlineOfflineChart.update();
        overallRevenueChart.update();
    }
    initCharts();   
    // Handle download report button click
    document.getElementById('download-report').addEventListener('click', function() {
        const link = document.createElement('a');
        link.href = 'admin/export_handler.php';
        link.download = 'report.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }); 
});
</script>
<?php require_once 'includes/footer.php'; ?>