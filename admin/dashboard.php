<?php
require_once '../includes/db_connect.php';
require_once 'include/header.php';

// --- DATA FETCHING ---

// 1. Appointment Statistics (All statuses) & Total Revenue
$query_appointments = "SELECT 
    COUNT(*) as total_appointments,
    SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as pending_appointments,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
    SUM(CASE WHEN status = 'completed' THEN s.price ELSE 0 END) as total_revenue
    FROM appointments a
    JOIN services s ON a.service_id = s.id";
$result = $conn->query($query_appointments);
$appointment_stats = $result->fetch_assoc();

// 2. Employee Count
$query_employees = "SELECT COUNT(*) as total_employees FROM employees";
$result = $conn->query($query_employees);
$employee_stats = $result->fetch_assoc();

// 3. Service Count
$query_services = "SELECT COUNT(*) as total_services FROM services";
$result = $conn->query($query_services);
$service_stats = $result->fetch_assoc();

// 4. Customer Count
$query_customers = "SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'";
$result = $conn->query($query_customers);
$customer_stats = $result->fetch_assoc();

// 5. Today's Appointments (using prepared statement)
$today = date('Y-m-d');
$query_today = "SELECT a.scheduled_at, a.status, u.name as customer_name, eu.name as employee_name, s.name as service_name
                FROM appointments a
                JOIN users u ON a.customer_id = u.id
                JOIN employees e ON a.employee_id = e.id
                JOIN users eu ON e.user_id = eu.id
                JOIN services s ON a.service_id = s.id
                WHERE DATE(a.scheduled_at) = ?
                ORDER BY a.scheduled_at ASC
                LIMIT 5";
$stmt = $conn->prepare($query_today);
$stmt->bind_param("s", $today);
$stmt->execute();
$today_appointments = $stmt->get_result();

// 6. Data for Monthly Appointments Chart (Last 6 Months)
$monthly_chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M Y', strtotime("-$i months"));
    $query = "SELECT COUNT(*) as count FROM appointments WHERE DATE_FORMAT(scheduled_at, '%Y-%m') = '$month'";
    $result = $conn->query($query);
    $data = $result->fetch_assoc();
    $monthly_chart_data['labels'][] = $month_name;
    $monthly_chart_data['data'][] = $data['count'];
}
?>

<!-- Include Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid">
    <h1 class="mt-4">Dashboard</h1>
    <p>Welcome to the Aura Salon & Spa admin panel. Here's a summary of your business activity.</p>
    
    <div class="row">
        <!-- Total Appointments -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0"><?php echo $appointment_stats['total_appointments'] ?? 0; ?></h2>
                            <p class="mb-0">Total Appointments</p>
                        </div>
                        <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="appointments.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <!-- Total Revenue -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">$<?php echo number_format($appointment_stats['total_revenue'] ?? 0, 2); ?></h2>
                            <p class="mb-0">Total Revenue</p>
                        </div>
                        <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="reports.php">View Reports</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <!-- Total Customers -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-dark mb-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0"><?php echo $customer_stats['total_customers'] ?? 0; ?></h2>
                            <p class="mb-0">Total Customers</p>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-dark stretched-link" href="customers.php">Manage Customers</a>
                    <div class="small text-dark"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <!-- Total Employees -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0"><?php echo $employee_stats['total_employees'] ?? 0; ?></h2>
                            <p class="mb-0">Total Employees</p>
                        </div>
                        <i class="fas fa-user-tie fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="employees.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Monthly Appointments Chart -->
        <div class="col-xl-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Monthly Appointments (Last 6 Months)
                </div>
                <div class="card-body"><canvas id="monthlyAppointmentsChart" width="100%" height="40"></canvas></div>
            </div>
        </div>
        <!-- Appointment Status Chart -->
        <div class="col-xl-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Appointment Status
                </div>
                <div class="card-body"><canvas id="appointmentStatusChart" width="100%" height="100"></canvas></div>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <i class="fas fa-calendar-day me-1"></i>
            Today's Appointments
        </div>
        <div class="card-body">
            <?php if ($today_appointments && $today_appointments->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Beautician</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $today_appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('h:i A', strtotime($row['scheduled_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                                    <td>
                                        <?php 
                                        $badge_class = 'bg-secondary';
                                        switch($row['status']) {
                                            case 'booked': $badge_class = 'bg-info'; break;
                                            case 'completed': $badge_class = 'bg-success'; break;
                                            case 'cancelled': $badge_class = 'bg-danger'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($row['status']); ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted py-3">No appointments scheduled for today.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Monthly Appointments Chart (Bar Chart)
    var ctxBar = document.getElementById("monthlyAppointmentsChart").getContext('2d');
    var monthlyChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($monthly_chart_data['labels']); ?>,
            datasets: [{
                label: "Appointments",
                backgroundColor: "rgba(106, 17, 203, 0.7)",
                borderColor: "rgba(106, 17, 203, 1)",
                borderWidth: 1,
                data: <?php echo json_encode($monthly_chart_data['data']); ?>,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Appointment Status Chart (Pie Chart)
    var ctxPie = document.getElementById("appointmentStatusChart").getContext('2d');
    var statusChart = new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ["Booked", "Completed", "Cancelled"],
            datasets: [{
                data: [
                    <?php echo $appointment_stats['pending_appointments'] ?? 0; ?>,
                    <?php echo $appointment_stats['completed_appointments'] ?? 0; ?>,
                    <?php echo $appointment_stats['cancelled_appointments'] ?? 0; ?>
                ],
                backgroundColor: ['#0dcaf0', '#198754', '#dc3545'],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false,
                    text: 'Appointment Status'
                }
            }
        }
    });
});
</script>

<?php require_once 'include/footer.php'; ?>