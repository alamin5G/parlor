<?php
// --- LOGIC FIRST ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Admin Dashboard";
$page_specific_css = "/parlor/admin/assets/css/dashboard.css";
require_once 'include/header.php';
require_once '../includes/db_connect.php';

// --- DATA FETCHING ---

// 1. Monthly Statistics
$first_day_month = date('Y-m-01');
$stats_query = "
    SELECT
        (SELECT COALESCE(SUM(s.price), 0) FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.status = 'completed' AND a.scheduled_at >= '$first_day_month') as revenue_this_month,
        (SELECT COUNT(*) FROM appointments WHERE scheduled_at >= '$first_day_month') as appointments_this_month,
        (SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at >= '$first_day_month') as new_customers_this_month,
        (SELECT COUNT(*) FROM employees e JOIN users u ON e.user_id = u.id WHERE u.is_active = 1) as active_employees,
        (SELECT COUNT(*) FROM appointments WHERE status = 'booked') as booked_appointments,
        (SELECT COUNT(*) FROM appointments WHERE status = 'completed') as completed_appointments,
        (SELECT COUNT(*) FROM appointments WHERE status = 'cancelled') as cancelled_appointments
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// 2. Top Employees (by revenue this month)
$top_employees_query = "
    SELECT u.name, COALESCE(SUM(s.price), 0) as revenue
    FROM appointments a
    JOIN employees e ON a.employee_id = e.id
    JOIN users u ON e.user_id = u.id
    JOIN services s ON a.service_id = s.id
    WHERE a.status = 'completed' AND a.scheduled_at >= '$first_day_month'
    GROUP BY u.name ORDER BY revenue DESC LIMIT 3
";
$top_employees = $conn->query($top_employees_query);

// 3. Top Services (by bookings this month)
$top_services_query = "
    SELECT s.name, COUNT(a.id) as booking_count
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.scheduled_at >= '$first_day_month'
    GROUP BY s.name ORDER BY booking_count DESC LIMIT 3
";
$top_services = $conn->query($top_services_query);

// 4. Recent Activity Feed (Query updated to show up to 9 items)
$recent_activity_query = "
    (
        -- New Customers
        SELECT 'new_customer' as type, u.id as user_id, u.name, u.created_at as activity_time, NULL as amount
        FROM users u
        WHERE u.role = 'customer'
        ORDER BY u.created_at DESC
        LIMIT 3
    )
    UNION ALL
    (
        -- New Payments from Completed Appointments
        SELECT 'new_payment' as type, u.id as user_id, u.name, b.payment_time as activity_time, b.amount
        FROM bills b
        JOIN appointments a ON b.appointment_id = a.id
        JOIN users u ON a.customer_id = u.id
        ORDER BY b.payment_time DESC
        LIMIT 3
    )
    UNION ALL
    (
        -- Newly Booked Appointments
        SELECT 'new_booking' as type, u.id as user_id, u.name, a.created_at as activity_time, NULL as amount
        FROM appointments a
        JOIN users u ON a.customer_id = u.id
        WHERE a.status = 'booked'
        ORDER BY a.created_at DESC
        LIMIT 3
    )
    ORDER BY activity_time DESC
    LIMIT 9
";
$recent_activities = $conn->query($recent_activity_query);

// 5. Data for Monthly Appointments Chart
$six_months_ago = date('Y-m-01', strtotime('-5 months'));
$monthly_chart_query = "
    SELECT DATE_FORMAT(scheduled_at, '%b %Y') as month, COUNT(*) as count
    FROM appointments
    WHERE scheduled_at >= '$six_months_ago'
    GROUP BY DATE_FORMAT(scheduled_at, '%Y-%m')
    ORDER BY DATE_FORMAT(scheduled_at, '%Y-%m') ASC
";
$monthly_result = $conn->query($monthly_chart_query);
$monthly_chart_data = ['labels' => [], 'data' => []];
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_chart_data['labels'][] = $row['month'];
    $monthly_chart_data['data'][] = $row['count'];
}
?>

<!-- Include Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid">
    <h1 class="mt-4">Dashboard</h1>
    <p class="text-muted">Welcome back, <?php echo $admin_name; ?>. Here's your monthly performance overview.</p>
    
    <div class="row">
        <!-- Stat Cards -->
        <div class="col-xl-3 col-md-6 mb-4"><div class="card shadow-sm stat-card border-primary h-100"><div class="card-body"><div class="stat-text"><p class="mb-2 text-muted">Revenue This Month</p><h2 class="mb-0">৳<?php echo number_format($stats['revenue_this_month'] ?? 0, 2); ?></h2></div><div class="stat-icon"><i class="fas fa-dollar-sign"></i></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-4"><div class="card shadow-sm stat-card border-success h-100"><div class="card-body"><div class="stat-text"><p class="mb-2 text-muted">Appointments This Month</p><h2 class="mb-0"><?php echo $stats['appointments_this_month'] ?? 0; ?></h2></div><div class="stat-icon"><i class="fas fa-calendar-check"></i></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-4"><div class="card shadow-sm stat-card border-warning h-100"><div class="card-body"><div class="stat-text"><p class="mb-2 text-muted">New Customers This Month</p><h2 class="mb-0"><?php echo $stats['new_customers_this_month'] ?? 0; ?></h2></div><div class="stat-icon"><i class="fas fa-users"></i></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-4"><div class="card shadow-sm stat-card border-info h-100"><div class="card-body"><div class="stat-text"><p class="mb-2 text-muted">Active Employees</p><h2 class="mb-0"><?php echo $stats['active_employees'] ?? 0; ?></h2></div><div class="stat-icon"><i class="fas fa-user-tie"></i></div></div></div></div>
    </div>
    
    <div class="row">
        <!-- Monthly Appointments Chart -->
        <div class="col-xl-8 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header"><i class="fas fa-chart-area me-1"></i>Monthly Appointments (Last 6 Months)</div>
                <div class="card-body"><canvas id="monthlyAppointmentsChart" width="100%" height="50"></canvas></div>
            </div>
        </div>
        <!-- Insights Column -->
        <div class="col-xl-4 mb-4">
            <!-- Top Employees Card -->
            <div class="card shadow-sm mb-4 insight-card">
                <div class="card-header"><i class="fas fa-trophy me-1"></i>Top Employees (This Month)</div>
                <ul class="list-group list-group-flush">
                    <?php if ($top_employees && $top_employees->num_rows > 0): ?>
                        <?php while($row = $top_employees->fetch_assoc()): ?>
                            <li class="list-group-item"><?php echo htmlspecialchars($row['name']); ?> <span class="badge bg-success">৳<?php echo number_format($row['revenue']); ?></span></li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted">No revenue data for this month yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <!-- Top Services Card -->
            <div class="card shadow-sm insight-card mb-4">
                <div class="card-header"><i class="fas fa-star me-1"></i>Top Services (This Month)</div>
                <ul class="list-group list-group-flush">
                    <?php if ($top_services && $top_services->num_rows > 0): ?>
                        <?php while($row = $top_services->fetch_assoc()): ?>
                            <li class="list-group-item"><?php echo htmlspecialchars($row['name']); ?> <span class="badge bg-primary"><?php echo $row['booking_count']; ?> bookings</span></li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted">No bookings for this month yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <!-- Appointment Status Chart -->
            <div class="card shadow-sm insight-card">
                <div class="card-header"><i class="fas fa-chart-pie me-1"></i>Appointment Status Overview</div>
                <div class="card-body">
                    <canvas id="appointmentStatusChart" width="100%" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity (UPGRADED HTML) -->
    <div class="card shadow-sm mb-4 activity-feed">
        <div class="card-header"><i class="fas fa-history me-1"></i>Recent Activity</div>
        <div class="list-group list-group-flush">
            <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
                <?php while($activity = $recent_activities->fetch_assoc()): ?>
                    <?php 
                        $user_link = "view_user.php?id=" . $activity['user_id'];
                        $user_name_html = "<a href='{$user_link}' class='fw-bold text-decoration-none'>".htmlspecialchars($activity['name'])."</a>";
                    ?>
                    <?php if ($activity['type'] == 'new_customer'): ?>
                        <div class="list-group-item list-group-item-action border-warning">
                            <i class="fas fa-user-plus me-2 text-warning"></i>
                            New customer <?php echo $user_name_html; ?> signed up.
                            <small class="text-muted float-end"><?php echo date('d M, h:i A', strtotime($activity['activity_time'])); ?></small>
                        </div>
                    <?php elseif ($activity['type'] == 'new_payment'): ?>
                        <div class="list-group-item list-group-item-action border-success">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Payment of <strong>৳<?php echo number_format($activity['amount'], 2); ?></strong> received from <?php echo $user_name_html; ?>.
                            <small class="text-muted float-end"><?php echo date('d M, h:i A', strtotime($activity['activity_time'])); ?></small>
                        </div>
                    <?php elseif ($activity['type'] == 'new_booking'): ?>
                        <div class="list-group-item list-group-item-action border-info">
                            <i class="fas fa-calendar-plus me-2 text-info"></i>
                            New appointment booked by <?php echo $user_name_html; ?>.
                            <small class="text-muted float-end"><?php echo date('d M, h:i A', strtotime($activity['activity_time'])); ?></small>
                        </div>
                    <?php endif; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="list-group-item text-muted text-center py-3">No recent activity to show.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Monthly Appointments Chart (Bar Chart)
    var ctxBar = document.getElementById("monthlyAppointmentsChart").getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($monthly_chart_data['labels']); ?>,
            datasets: [{
                label: "Appointments",
                backgroundColor: "rgba(106, 17, 203, 0.7)",
                borderColor: "rgba(106, 17, 203, 1)",
                hoverBackgroundColor: "rgba(106, 17, 203, 0.9)",
                borderWidth: 1,
                borderRadius: 5,
                data: <?php echo json_encode($monthly_chart_data['data']); ?>,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { legend: { display: false } }
        }
    });

     // Appointment Status Chart (Doughnut Chart)
    var ctxPie = document.getElementById("appointmentStatusChart").getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ["Booked", "Completed", "Cancelled"],
            datasets: [{
                data: [
                    <?php echo $stats['booked_appointments'] ?? 0; ?>,
                    <?php echo $stats['completed_appointments'] ?? 0; ?>,
                    <?php echo $stats['cancelled_appointments'] ?? 0; ?>
                ],
                backgroundColor: ['#0dcaf0', '#198754', '#dc3545'],
                borderColor: '#fff',
                borderWidth: 2,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { 
                    position: 'top' 
                } 
            }
        }
    });
});
document.addEventListener("DOMContentLoaded", function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    }); 
    // Initialize dropdowns
    var dropdownTriggerList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownTriggerList.map(function (dropdownTriggerEl) {
        return new bootstrap.Dropdown(dropdownTriggerEl);
    });
</script>

<?php require_once 'include/footer.php'; ?>