<?php
// filepath: c:\xampp\htdocs\parlor\admin\reports.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Business Reports";
require_once 'include/header.php';
require_once '../includes/db_connect.php';

// --- Date Filtering Logic ---
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// --- Data Fetching ---
$where_clause = "WHERE a.status = 'completed' AND DATE(b.payment_time) BETWEEN ? AND ?";

// 1. Summary Stats
$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(b.amount), 0) as total_revenue,
        COUNT(DISTINCT a.id) as total_appointments,
        (SELECT COUNT(*) FROM users WHERE role='customer' AND DATE(created_at) BETWEEN ? AND ?) as new_customers
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    $where_clause
");
$stmt->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

// 2. Revenue by Service
$stmt = $conn->prepare("
    SELECT s.name, COUNT(a.id) as appointment_count, COALESCE(SUM(b.amount), 0) as revenue
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    JOIN services s ON a.service_id = s.id
    $where_clause
    GROUP BY s.name ORDER BY revenue DESC
");
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$service_revenue = $stmt->get_result();

// 3. Revenue by Employee
$stmt = $conn->prepare("
    SELECT u.name, COUNT(a.id) as appointment_count, COALESCE(SUM(b.amount), 0) as revenue
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    JOIN users u ON a.employee_id = u.id
    $where_clause
    GROUP BY u.name ORDER BY revenue DESC
");
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$employee_revenue = $stmt->get_result();
?>

<div class="container-fluid">
    <h1 class="mt-4">Business Insights & Reports</h1>
    <p class="text-muted">Analyze performance for the selected date range.</p>

    <!-- Date Filter Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="reports.php" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Generate Report</button>
                    <a href="reports.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Stat Cards -->
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4"><div class="card shadow-sm border-primary h-100"><div class="card-body"><p class="mb-2 text-muted">Total Revenue</p><h2 class="mb-0">৳<?php echo number_format($summary['total_revenue'], 2); ?></h2></div></div></div>
        <div class="col-xl-4 col-md-6 mb-4"><div class="card shadow-sm border-success h-100"><div class="card-body"><p class="mb-2 text-muted">Completed Appointments</p><h2 class="mb-0"><?php echo $summary['total_appointments']; ?></h2></div></div></div>
        <div class="col-xl-4 col-md-6 mb-4"><div class="card shadow-sm border-warning h-100"><div class="card-body"><p class="mb-2 text-muted">New Customers</p><h2 class="mb-0"><?php echo $summary['new_customers']; ?></h2></div></div></div>
    </div>

    <!-- Detailed Reports -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Detailed Reports</h5>
            <div class="btn-group">
                <a href="export_handler.php?type=csv&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-sm btn-success"><i class="fas fa-file-csv me-1"></i> Export CSV</a>
                <a href="export_handler.php?type=pdf&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-sm btn-danger" target="_blank"><i class="fas fa-file-pdf me-1"></i> Export PDF</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Revenue by Service -->
                <div class="col-lg-6 mb-4">
                    <h6>Revenue by Service</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light"><tr><th>Service</th><th>Appointments</th><th>Revenue</th></tr></thead>
                            <tbody>
                                <?php while($row = $service_revenue->fetch_assoc()): ?>
                                <tr><td><?= htmlspecialchars($row['name']) ?></td><td><?= $row['appointment_count'] ?></td><td>৳<?= number_format($row['revenue'], 2) ?></td></tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Revenue by Employee -->
                <div class="col-lg-6 mb-4">
                    <h6>Revenue by Employee</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light"><tr><th>Employee</th><th>Appointments</th><th>Revenue</th></tr></thead>
                            <tbody>
                                <?php while($row = $employee_revenue->fetch_assoc()): ?>
                                <tr><td><?= htmlspecialchars($row['name']) ?></td><td><?= $row['appointment_count'] ?></td><td>৳<?= number_format($row['revenue'], 2) ?></td></tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>