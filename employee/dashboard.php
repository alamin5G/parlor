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

// 1. IMPROVED Stats for today - fixed count for proper status values
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');
$stats_today_sql = "
    SELECT 
        COUNT(*) as total_today,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_today,
        SUM(CASE WHEN (status = 'booked' OR status = 'confirmed') AND scheduled_at >= NOW() THEN 1 ELSE 0 END) as upcoming_today
    FROM appointments 
    WHERE employee_id = ? AND DATE(scheduled_at) = CURRENT_DATE()";
$stmt = $conn->prepare($stats_today_sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$stats_today = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Upcoming appointments list
$upcoming_sql = "
    SELECT a.id, a.scheduled_at, s.name as service_name, u.name as customer_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.customer_id = u.id
    WHERE a.employee_id = ? AND a.scheduled_at >= NOW() AND (a.status = 'booked' OR a.status = 'confirmed')
    ORDER BY a.scheduled_at ASC LIMIT 5";
$stmt = $conn->prepare($upcoming_sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result();
$stmt->close();

// 3. Notifications - keep track of unread count
$sql_new = "SELECT a.id, a.scheduled_at, s.name as service_name, u.name as customer_name 
           FROM appointments a 
           JOIN services s ON a.service_id = s.id 
           JOIN users u ON a.customer_id = u.id 
           WHERE a.employee_id = ? AND (a.status = 'booked' OR a.status = 'confirmed') AND a.is_seen_by_employee = 0 
           ORDER BY a.scheduled_at ASC";
$stmt_new = $conn->prepare($sql_new);
$stmt_new->bind_param("i", $employee_id);
$stmt_new->execute();
$new_appts = $stmt_new->get_result();
$notification_count = $new_appts->num_rows;

// 4. NEW: Weekly performance stats
$this_week_start = date('Y-m-d', strtotime('monday this week'));
$this_week_end = date('Y-m-d', strtotime('sunday this week'));

$weekly_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_week,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_week,
        AVG(CASE WHEN r.rating IS NOT NULL THEN r.rating ELSE NULL END) as avg_rating
    FROM appointments a
    LEFT JOIN reviews r ON a.id = r.appointment_id
    WHERE a.employee_id = ? 
    AND a.scheduled_at BETWEEN ? AND ?");
$weekly_stats->bind_param("iss", $employee_id, $this_week_start, $this_week_end);
$weekly_stats->execute();
$stats_week = $weekly_stats->get_result()->fetch_assoc();
$weekly_stats->close();

// 5. NEW: Earnings stats for this month
$this_month_start = date('Y-m-01');
$this_month_end = date('Y-m-t');

$earnings_query = $conn->prepare("
    SELECT 
        IFNULL(SUM(b.amount), 0) as monthly_earnings,
        COUNT(DISTINCT a.id) as monthly_services,
        (SELECT COUNT(*) FROM appointments 
         WHERE employee_id = ? AND (status = 'booked' OR status = 'confirmed') 
         AND scheduled_at > NOW()) as future_bookings
    FROM appointments a
    LEFT JOIN bills b ON a.id = b.appointment_id
    WHERE a.employee_id = ? 
    AND a.status = 'completed'
    AND a.scheduled_at BETWEEN ? AND ?");
$earnings_query->bind_param("iiss", $employee_id, $employee_id, $this_month_start, $this_month_end);
$earnings_query->execute();
$earnings_data = $earnings_query->get_result()->fetch_assoc();
$earnings_query->close();

// 6. NEW: Get most popular service for this employee
$popular_service = $conn->prepare("
    SELECT s.name, COUNT(*) as service_count
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.employee_id = ?
    GROUP BY a.service_id
    ORDER BY service_count DESC
    LIMIT 1");
$popular_service->bind_param("i", $employee_id);
$popular_service->execute();
$most_popular = $popular_service->get_result()->fetch_assoc();
$popular_service->close();
?>

<div class="container-fluid">
    <h1 class="mt-4">My Dashboard</h1>
    <p class="text-muted">Here's a summary of your schedule and performance.</p>

    <!-- Stat Cards -->
    <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-primary h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Appointments Today</h6>
                        <h2 class="mb-0"><?= $stats_today['total_today'] ?? 0; ?></h2>
                    </div>
                    <i class="fas fa-calendar-day fa-3x text-primary opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-warning h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Upcoming Today</h6>
                        <h2 class="mb-0"><?= $stats_today['upcoming_today'] ?? 0; ?></h2>
                    </div>
                    <i class="fas fa-hourglass-half fa-3x text-warning opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-success h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Completed Today</h6>
                        <h2 class="mb-0"><?= $stats_today['completed_today'] ?? 0; ?></h2>
                    </div>
                    <i class="fas fa-check-double fa-3x text-success opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW: Performance Metrics Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-info h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Weekly Performance</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <h6 class="text-muted">This Week</h6>
                            <h3 class="mb-0"><?= $stats_week['total_week'] ?? 0 ?></h3>
                            <div class="small text-muted">appointments</div>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <h6 class="text-muted">Completed</h6>
                            <h3 class="mb-0"><?= $stats_week['completed_week'] ?? 0 ?></h3>
                            <div class="small text-muted">services</div>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <h6 class="text-muted">Avg Rating</h6>
                            <h3 class="mb-0 text-warning">
                                <?php if(!is_null($stats_week['avg_rating'])): ?>
                                    <?= number_format($stats_week['avg_rating'], 1) ?>
                                    <small>/5</small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </h3>
                            <div class="small text-muted">from clients</div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted">Upcoming Bookings</h6>
                            <h4 class="mb-0"><?= $earnings_data['future_bookings'] ?? 0 ?></h4>
                        </div>
                        <div class="text-end">
                            <h6 class="text-muted">Most Popular Service</h6>
                            <h4 class="mb-0"><?= $most_popular['name'] ?? 'N/A' ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-success h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-coins me-2"></i>Monthly Performance</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6 text-center mb-3">
                            <h6 class="text-muted">Services Completed</h6>
                            <h3 class="mb-0"><?= $earnings_data['monthly_services'] ?? 0 ?></h3>
                            <div class="small text-muted"><?= date('F Y') ?></div>
                        </div>
                        <div class="col-md-6 text-center mb-3">
                            <h6 class="text-muted">Total Earnings</h6>
                            <h3 class="mb-0 text-success">৳<?= number_format($earnings_data['monthly_earnings'] ?? 0, 2) ?></h3>
                            <div class="small text-muted"><?= date('F Y') ?></div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Monthly Target</span>
                            <span class="fw-bold"><?= round(($earnings_data['monthly_services'] ?? 0) / 50 * 100) ?>%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?= min(100, round(($earnings_data['monthly_services'] ?? 0) / 50 * 100)) ?>%"
                                 aria-valuenow="<?= ($earnings_data['monthly_services'] ?? 0) ?>" aria-valuemin="0" aria-valuemax="50"></div>
                        </div>
                        <div class="text-end small text-muted mt-1">Goal: 50 services</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications & Upcoming Appointments -->
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-bell me-2"></i>Notifications
                        <?php if ($notification_count > 0): ?>
                            <span class="badge bg-danger ms-2"><?= $notification_count ?></span>
                        <?php endif; ?>
                    </h5>
                    <?php if ($notification_count > 0): ?>
                        <button id="mark-all-read" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-check-double me-1"></i>Mark all as read
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div id="notifications-list">
                        <?php if ($notification_count > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php while ($row = $new_appts->fetch_assoc()): ?>
                                    <li class="list-group-item notification-item d-flex justify-content-between align-items-center" data-id="<?= $row['id'] ?>">
                                        <div>
                                            <span class="badge bg-danger me-2">New</span>
                                            Appt with <?= htmlspecialchars($row['customer_name']) ?> on 
                                            <strong><?= date('M d', strtotime($row['scheduled_at'])) ?></strong> at 
                                            <strong><?= date('h:i A', strtotime($row['scheduled_at'])) ?></strong> for
                                            <?= htmlspecialchars($row['service_name']) ?>.
                                        </div>
                                        <div class="d-flex">
                                            <button class="btn btn-sm btn-outline-secondary mark-read me-2" data-id="<?= $row['id'] ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <a href="appointment_view.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-bell-slash fa-3x mb-3 text-muted"></i>
                                <p class="mb-0">No new notifications.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-clock me-2"></i>Your Next Appointments</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <!-- IMPROVED: Table with Date Column -->
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Client</th>
                                    <th>Service</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($upcoming_appointments->num_rows > 0): ?>
                                    <?php while ($appt = $upcoming_appointments->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($appt['scheduled_at'])); ?></td>
                                            <td><b><?= date('h:i A', strtotime($appt['scheduled_at'])); ?></b></td>
                                            <td><?= htmlspecialchars($appt['customer_name']); ?></td>
                                            <td><?= htmlspecialchars($appt['service_name']); ?></td>
                                            <td><a href="appointment_view.php?id=<?= $appt['id'] ?>" class="btn btn-sm btn-outline-primary">Details</a></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-4">No upcoming appointments scheduled.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center"><a href="appointments.php">View All My Appointments</a></div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for handling notification marking -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark individual notification as read
    document.querySelectorAll('.mark-read').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            markAsRead([id]);
        });
    });

    // Mark all notifications as read
    const markAllBtn = document.getElementById('mark-all-read');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
            const ids = Array.from(document.querySelectorAll('.notification-item'))
                .map(item => item.getAttribute('data-id'));
            markAsRead(ids);
        });
    }

    function markAsRead(appointmentIds) {
        // Show loading state
        appointmentIds.forEach(id => {
            const item = document.querySelector(`.notification-item[data-id="${id}"]`);
            if (item) {
                item.classList.add('bg-light');
                item.querySelector('.mark-read').disabled = true;
            }
        });

        // Send AJAX request to mark as read
        fetch('mark_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ appointment_ids: appointmentIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the notifications from the UI with animation
                appointmentIds.forEach(id => {
                    const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                    if (item) {
                        item.style.transition = 'all 0.5s ease';
                        item.style.opacity = '0';
                        item.style.maxHeight = '0';
                        setTimeout(() => {
                            item.remove();
                            
                            // If no more notifications, update the UI
                            if (document.querySelectorAll('.notification-item').length === 0) {
                                document.getElementById('notifications-list').innerHTML = `
                                    <div class="text-center py-4">
                                        <i class="fas fa-bell-slash fa-3x mb-3 text-muted"></i>
                                        <p class="mb-0">No new notifications.</p>
                                    </div>
                                `;
                                
                                // Hide the "Mark all as read" button
                                if (markAllBtn) markAllBtn.style.display = 'none';
                            }
                        }, 500);
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error marking notifications as read:', error);
        });
    }
});
</script>

<?php require_once 'include/footer.php'; ?>