
<?php require_once '../includes/db_connect.php'; ?>
<?php require_once 'include/header.php'; 

// Get count of appointments
$query = "SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as pending_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments
          FROM appointments";
$result = $conn->query($query);
$appointment_stats = $result->fetch_assoc();

// Get count of employees
$query = "SELECT COUNT(*) as total_employees FROM employees";
$result = $conn->query($query);
$employee_stats = $result->fetch_assoc();

// Get count of services
$query = "SELECT COUNT(*) as total_services FROM services";
$result = $conn->query($query);
$service_stats = $result->fetch_assoc();

// Get count of customers
$query = "SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'";
$result = $conn->query($query);
$customer_stats = $result->fetch_assoc();

// Get today's appointments
$today = date('Y-m-d');
$query = "SELECT a.*, 
          u.name as customer_name, 
          eu.name as employee_name,
          s.name as service_name
          FROM appointments a
          JOIN users u ON a.customer_id = u.id
          JOIN employees e ON a.employee_id = e.id
          JOIN users eu ON e.user_id = eu.id
          JOIN services s ON a.service_id = s.id
          WHERE DATE(a.scheduled_at) = '$today'
          ORDER BY a.scheduled_at ASC
          LIMIT 5";
$today_appointments = $conn->query($query);
?>

<div class="container-fluid">
    <h1 class="mt-4">Dashboard</h1>
    <p>Welcome to the Labonno Glamour World admin panel. From here you can manage employees, services, appointments, and more.</p>
    
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h2><?php echo $appointment_stats['total_appointments'] ?? 0; ?></h2>
                    <p class="mb-0">Total Appointments</p>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="appointments.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h2><?php echo $employee_stats['total_employees'] ?? 0; ?></h2>
                    <p class="mb-0">Total Employees</p>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="employee_list.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h2><?php echo $service_stats['total_services'] ?? 0; ?></h2>
                    <p class="mb-0">Services Offered</p>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="services.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <h2><?php echo $customer_stats['total_customers'] ?? 0; ?></h2>
                    <p class="mb-0">Total Customers</p>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-calendar-day me-1"></i>
                    Today's Appointments
                </div>
                <div class="card-body">
                    <?php if ($today_appointments && $today_appointments->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
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
                                                $badge_class = '';
                                                switch($row['status']) {
                                                    case 'booked': $badge_class = 'bg-primary'; break;
                                                    case 'completed': $badge_class = 'bg-success'; break;
                                                    case 'cancelled': $badge_class = 'bg-danger'; break;
                                                    case 'rescheduled': $badge_class = 'bg-warning'; break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center py-3">No appointments scheduled for today.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Appointment Status Overview
                </div>
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="p-3 bg-light rounded">
                                <h3 class="text-primary"><?php echo $appointment_stats['pending_appointments'] ?? 0; ?></h3>
                                <p class="mb-0">Pending Appointments</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="p-3 bg-light rounded">
                                <h3 class="text-success"><?php echo $appointment_stats['completed_appointments'] ?? 0; ?></h3>
                                <p class="mb-0">Completed Appointments</p>
                            </div>
                        </div>
                    </div>
                    <a href="appointments.php" class="btn btn-primary">Manage All Appointments</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>