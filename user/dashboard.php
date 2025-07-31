<?php
// filepath: c:\xampp\htdocs\parlor\user\dashboard.php
$page_title = "My Dashboard";
require_once 'include/header.php';

// --- DATA FETCHING ---
// 1. Stats Cards
$stats_sql = "
    SELECT 
        SUM(CASE WHEN status = 'booked' AND scheduled_at >= NOW() THEN 1 ELSE 0 END) as upcoming,
        COUNT(*) as total_appointments,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN b.amount ELSE 0 END), 0) as total_spent
    FROM appointments a
    LEFT JOIN bills b ON a.id = b.appointment_id
    WHERE a.customer_id = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $customer_user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Upcoming appointments list
$upcoming_sql = "
    SELECT a.id, a.scheduled_at, s.name as service_name, u_emp.name as employee_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN employees e ON a.employee_id = e.id
    JOIN users u_emp ON e.user_id = u_emp.id
    WHERE a.customer_id = ? AND a.status = 'booked' AND a.scheduled_at >= NOW()
    ORDER BY a.scheduled_at ASC LIMIT 5";
$stmt = $conn->prepare($upcoming_sql);
$stmt->bind_param("i", $customer_user_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result();
$stmt->close();
?>

<div class="container-fluid">
    <h1 class="mt-4">My Dashboard</h1>
    <p class="text-muted">Welcome back! Here's a summary of your activity.</p>

    <!-- Stat Cards -->
    <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-primary h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><h6 class="card-title text-muted">Upcoming Appointments</h6><h2 class="mb-0"><?= $stats['upcoming'] ?? 0 ?></h2></div><i class="fas fa-calendar-day fa-3x text-primary opacity-50"></i></div></div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-info h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><h6 class="card-title text-muted">Total Appointments</h6><h2 class="mb-0"><?= $stats['total_appointments'] ?? 0 ?></h2></div><i class="fas fa-tasks fa-3x text-info opacity-50"></i></div></div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-success h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><h6 class="card-title text-muted">Total Spent</h6><h2 class="mb-0">৳<?= number_format($stats['total_spent'] ?? 0, 2) ?></h2></div><i class="fas fa-hand-holding-usd fa-3x text-success opacity-50"></i></div></div>
        </div>
    </div>

    <!-- Upcoming Appointments -->
    <div class="card shadow-sm">
       <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Your Confirmed Upcoming Appointments</h5>
            <a href="book.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Book a New Appointment</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <tbody>
                        <?php if ($upcoming_appointments->num_rows > 0): ?>
                            <?php while ($appt = $upcoming_appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><b><?= date('D, d M Y', strtotime($appt['scheduled_at'])); ?></b><br><span class="text-muted"><?= date('h:i A', strtotime($appt['scheduled_at'])); ?></span></td>
                                    <td><?= htmlspecialchars($appt['service_name']); ?></td>
                                    <td>with <?= htmlspecialchars($appt['employee_name']); ?></td>
                                    <td class="text-end"><a href="appointment_view.php?id=<?= $appt['id'] ?>" class="btn btn-sm btn-outline-primary">View Details</a></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-4">You have no upcoming appointments. Time to book one!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>