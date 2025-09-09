<?php
// --- LOGIC FIRST ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "View Employee Profile";
$page_specific_css = "/parlor/admin/assets/css/view_employee.css"; // Link to the new CSS file
require_once 'include/header.php';
require_once '../includes/db_connect.php';

// Employee ID validation
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: employees.php');
    exit;
}
$employee_id = intval($_GET['id']);

// --- DATA FETCHING ---

// 1. Get main employee info
$stmt = $conn->prepare("SELECT e.*, u.name, u.email, u.phone, u.created_at, u.is_active FROM employees e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: employees.php');
    exit;
}
$employee = $result->fetch_assoc();
$stmt->close();

// 2. Get performance statistics (including revenue)
$stats_sql = "
    SELECT 
        COUNT(a.id) AS total_appointments,
        SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
        COALESCE(SUM(CASE WHEN a.status = 'completed' THEN s.price ELSE 0 END), 0) AS total_revenue
    FROM appointments a
    LEFT JOIN services s ON a.service_id = s.id
    WHERE a.employee_id = ?
";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$appointments_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 3. Get recent appointments for this employee
$recent_app_sql = "
    SELECT a.id, a.scheduled_at, a.status, s.name as service_name, u.name as customer_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.customer_id = u.id
    WHERE a.employee_id = ?
    ORDER BY a.scheduled_at DESC
    LIMIT 5
";
$stmt = $conn->prepare($recent_app_sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$recent_appointments = $stmt->get_result();
$stmt->close();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Employee Profile</h1>
        <div>
            <a href="edit_employee.php?id=<?php echo $employee_id; ?>" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Edit Profile</a>
            <a href="employees.php" class="btn btn-outline-secondary ms-2"><i class="fas fa-arrow-left me-2"></i>Back to List</a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Profile Card -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4 profile-card">
                <div class="card-body">
                    <div class="profile-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h4 class="card-title"><?php echo htmlspecialchars($employee['name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($employee['email']); ?></p>
                    <hr>
                    <div class="profile-details text-start">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>Phone:</strong> <span><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></span></li>
                            <li class="list-group-item"><strong>Specialization:</strong> <span><?php echo htmlspecialchars($employee['specialization']); ?></span></li>
                            <li class="list-group-item"><strong>Hire Date:</strong> <span><?php echo date('d M Y', strtotime($employee['hire_date'])); ?></span></li>
                            <li class="list-group-item"><strong>Status:</strong> 
                                <span><span class="badge <?php echo $employee['is_active'] ? 'bg-success' : 'bg-danger'; ?>"><?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?></span></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Stats and Appointments -->
        <div class="col-lg-8">
            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm stat-card border-primary">
                        <div class="card-body">
                            <div>
                                <h6 class="card-title text-muted">Total Appointments</h6>
                                <h4 class="mb-0"><?php echo $appointments_stats['total_appointments']; ?></h4>
                            </div>
                            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm stat-card border-success">
                        <div class="card-body">
                            <div>
                                <h6 class="card-title text-muted">Completed</h6>
                                <h4 class="mb-0"><?php echo $appointments_stats['completed_appointments']; ?></h4>
                            </div>
                            <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm stat-card border-danger">
                        <div class="card-body">
                            <div>
                                <h6 class="card-title text-muted">Revenue Generated</h6>
                                <h4 class="mb-0">৳<?php echo number_format($appointments_stats['total_revenue'], 2); ?></h4>
                            </div>
                            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments History Card -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Appointment History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 appointments-table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Appt. ID</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_appointments->num_rows > 0): ?>
                                    <?php while ($a = $recent_appointments->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $a['id']; ?></td>
                                            <td><?php echo htmlspecialchars($a['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($a['service_name']); ?></td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($a['scheduled_at'])); ?></td>
                                            <td>
                                                <span class="badge <?php
                                                    switch($a['status']) {
                                                        case 'booked': echo 'bg-primary'; break;
                                                        case 'completed': echo 'bg-success'; break;
                                                        case 'cancelled': echo 'bg-danger'; break;
                                                        default: echo 'bg-secondary';
                                                    }
                                                ?>"><?php echo ucfirst($a['status']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-4">No appointment history found for this employee.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>