<?php
// --- LOGIC FIRST ---
session_start();
$page_title = "View User Profile";
$page_specific_css = "/parlor/admin/assets/css/view_user.css"; // Link to the new CSS file
require_once 'include/header.php';
require_once '../includes/db_connect.php';

// User ID validation
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>No user selected.</div>";
    require_once 'include/footer.php';
    exit;
}
$user_id = intval($_GET['id']);

// --- DATA FETCHING ---

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
if ($user_result->num_rows == 0) {
    echo "<div class='alert alert-danger'>User not found.</div>";
    require_once 'include/footer.php';
    exit;
}
$user = $user_result->fetch_assoc();
$stmt->close();

// Summary info
$summary_sql = "
    SELECT 
        COUNT(a.id) AS total_appointments,
        COALESCE(SUM(CASE WHEN a.status = 'completed' THEN s.price ELSE 0 END), 0) AS total_expense,
        COALESCE(AVG(CASE WHEN a.status = 'completed' THEN s.price ELSE NULL END), 0) AS avg_expense,
        MAX(a.scheduled_at) AS last_appointment
    FROM appointments a
    LEFT JOIN services s ON a.service_id = s.id
    WHERE a.customer_id = ?
";
$sum_stmt = $conn->prepare($summary_sql);
$sum_stmt->bind_param("i", $user_id);
$sum_stmt->execute();
$summary = $sum_stmt->get_result()->fetch_assoc();
$sum_stmt->close();

// Get paginated appointments
$per_page = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$app_sql = "
    SELECT a.*, s.name as service_name, s.price, 
           u2.name as beautician_name,
           b.id as bill_id
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    LEFT JOIN employees e ON a.employee_id = e.id
    LEFT JOIN users u2 ON e.user_id = u2.id
    LEFT JOIN bills b ON b.appointment_id = a.id
    WHERE a.customer_id = ?
    ORDER BY a.scheduled_at DESC
    LIMIT ? OFFSET ?
";
$app_stmt = $conn->prepare($app_sql);
$app_stmt->bind_param("iii", $user_id, $per_page, $offset);
$app_stmt->execute();
$app_result = $app_stmt->get_result();

$total_pages = ceil($summary['total_appointments'] / $per_page);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>User Profile</h1>
        <a href="customers.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Customers
        </a>
    </div>

    <div class="row">
        <!-- Left Column: Profile Card -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4 profile-card">
                <div class="card-body">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h4 class="card-title"><?php echo htmlspecialchars($user['name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <hr>
                    <div class="profile-details text-start">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>Phone:</strong> <span><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></span></li>
                            <li class="list-group-item"><strong>Joined:</strong> <span><?php echo date('d M Y', strtotime($user['created_at'])); ?></span></li>
                            <li class="list-group-item"><strong>Role:</strong> <span><?php echo ucfirst($user['role']); ?></span></li>
                            <li class="list-group-item"><strong>Status:</strong> 
                                <span>
                                    <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span>
                                    <span class="badge <?php echo $user['is_verified'] ? 'bg-info' : 'bg-warning text-dark'; ?>"><?php echo $user['is_verified'] ? 'Verified' : 'Unverified'; ?></span>
                                </span>
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
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm stat-card border-primary">
                        <div class="card-body">
                            <div>
                                <h6 class="card-title text-muted">Total Appointments</h6>
                                <h4 class="mb-0"><?php echo $summary['total_appointments']; ?></h4>
                            </div>
                            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm stat-card border-success">
                        <div class="card-body">
                            <div>
                                <h6 class="card-title text-muted">Total Spent</h6>
                                <h4 class="mb-0">৳<?php echo number_format($summary['total_expense'], 2); ?></h4>
                            </div>
                            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm stat-card border-info">
                        <div class="card-body">
                            <div>
                                <h6 class="card-title text-muted">Avg. Per Visit</h6>
                                <h4 class="mb-0">৳<?php echo number_format($summary['avg_expense'], 2); ?></h4>
                            </div>
                            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm stat-card border-warning">
                        <div class="card-body">
                            <div>
                                <h6 class="card-title text-muted">Last Visit</h6>
                                <h6 class="mb-0"><?php echo $summary['last_appointment'] ? date('d M Y', strtotime($summary['last_appointment'])) : 'N/A'; ?></h6>
                            </div>
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments History Card -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Appointment History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 appointments-table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Service</th>
                                    <th>Beautician</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Bill</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($app_result->num_rows > 0): ?>
                                    <?php while ($a = $app_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $a['id']; ?></td>
                                            <td><?php echo htmlspecialchars($a['service_name']); ?><br><small class="text-muted">৳<?php echo number_format($a['price'], 2); ?></small></td>
                                            <td><?php echo htmlspecialchars($a['beautician_name'] ?? 'N/A'); ?></td>
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
                                            <td>
                                                <?php if ($a['bill_id']): ?>
                                                    <a href="view_bill.php?id=<?php echo $a['bill_id']; ?>" class="btn btn-sm btn-outline-primary">View Bill</a>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-4">No appointment history found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <li class="page-item <?php if ($p == $page) echo 'active'; ?>">
                                    <a class="page-link" href="?id=<?php echo $user_id; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>