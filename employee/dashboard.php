<?php
// filepath: c:\xampp\htdocs\parlor\employee\dashboard.php
$page_title = "Employee Dashboard";
require_once 'include/header.php';

// --- DATA FETCHING ---
$stmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt->bind_param("i", $employee_user_id);
$stmt->execute();
$employee_id = $stmt->get_result()->fetch_assoc()['id'];
$stmt->close();

// 1. Stats for today
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');
$stats_today_sql = "
    SELECT 
        COUNT(*) as total_today,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_today,
        SUM(CASE WHEN status = 'booked' AND scheduled_at >= NOW() THEN 1 ELSE 0 END) as upcoming_today
    FROM appointments 
    WHERE employee_id = ? AND scheduled_at BETWEEN ? AND ?";
$stmt = $conn->prepare($stats_today_sql);
$stmt->bind_param("iss", $employee_id, $today_start, $today_end);
$stmt->execute();
$stats_today = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Upcoming appointments list
$upcoming_sql = "
    SELECT a.id, a.scheduled_at, s.name as service_name, u.name as customer_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.customer_id = u.id
    WHERE a.employee_id = ? AND a.scheduled_at >= NOW() AND a.status = 'booked'
    ORDER BY a.scheduled_at ASC LIMIT 5";
$stmt = $conn->prepare($upcoming_sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result();
$stmt->close();

// 3. Notifications
$sql_new = "SELECT a.id, a.scheduled_at, s.name as service_name, u.name as customer_name FROM appointments a JOIN services s ON a.service_id = s.id JOIN users u ON a.customer_id = u.id WHERE a.employee_id = ? AND a.status = 'booked' AND a.is_seen_by_employee = 0 ORDER BY a.scheduled_at ASC";
$stmt_new = $conn->prepare($sql_new);
$stmt_new->bind_param("i", $employee_id);
$stmt_new->execute();
$new_appts = $stmt_new->get_result();
?>

<div class="container-fluid">
    <h1 class="mt-4">My Dashboard</h1>
    <p class="text-muted">Here's a summary of your schedule and performance.</p>

    <!-- Stat Cards -->
    <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-primary h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><h6 class="card-title text-muted">Appointments Today</h6><h2 class="mb-0"><?= $stats_today['total_today'] ?? 0; ?></h2></div><i class="fas fa-calendar-day fa-3x text-primary opacity-50"></i></div></div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-warning h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><h6 class="card-title text-muted">Upcoming Today</h6><h2 class="mb-0"><?= $stats_today['upcoming_today'] ?? 0; ?></h2></div><i class="fas fa-hourglass-half fa-3x text-warning opacity-50"></i></div></div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-success h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><h6 class="card-title text-muted">Completed Today</h6><h2 class="mb-0"><?= $stats_today['completed_today'] ?? 0; ?></h2></div><i class="fas fa-check-double fa-3x text-success opacity-50"></i></div></div>
        </div>
    </div>

    <!-- Notifications & Upcoming Appointments -->
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notifications</h5></div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php if ($new_appts->num_rows > 0): ?>
                            <?php while ($row = $new_appts->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <b>New:</b> Appt with <?= htmlspecialchars($row['customer_name']) ?> at <?= date('h:i A', strtotime($row['scheduled_at'])) ?>.
                                    <a href="appointment_view.php?id=<?= $row['id'] ?>" class="float-end">View</a>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item">No new notifications.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-clock me-2"></i>Your Next Appointments</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive"><table class="table table-hover mb-0">
                        <tbody>
                            <?php if ($upcoming_appointments->num_rows > 0): ?>
                                <?php while ($appt = $upcoming_appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><b><?= date('h:i A', strtotime($appt['scheduled_at'])); ?></b></td>
                                        <td><?= htmlspecialchars($appt['customer_name']); ?></td>
                                        <td><?= htmlspecialchars($appt['service_name']); ?></td>
                                        <td><a href="appointment_view.php?id=<?= $appt['id'] ?>" class="btn btn-sm btn-outline-primary">Details</a></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4">No upcoming appointments for today.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table></div>
                </div>
                <div class="card-footer text-center"><a href="appointments.php">View All My Appointments</a></div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>