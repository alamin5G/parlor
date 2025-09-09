<?php
// filepath: c:\xampp\htdocs\parlor\employee\history.php
$page_title = "Service History & Earnings";
require_once 'include/header.php';

// Get employee_id
$stmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt->bind_param("i", $employee_user_id);
$stmt->execute();
$employee_id = $stmt->get_result()->fetch_assoc()['id'];
$stmt->close();

// Handle filters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

// Build filter conditions
$filter_conditions = "a.employee_id = ? AND a.status = 'completed'";
$filter_params = [$employee_id];
$filter_types = "i";

if (!empty($date_from)) {
    $filter_conditions .= " AND DATE(a.scheduled_at) >= ?";
    $filter_params[] = $date_from;
    $filter_types .= "s";
}

if (!empty($date_to)) {
    $filter_conditions .= " AND DATE(a.scheduled_at) <= ?";
    $filter_params[] = $date_to;
    $filter_types .= "s";
}

if ($service_id > 0) {
    $filter_conditions .= " AND a.service_id = ?";
    $filter_params[] = $service_id;
    $filter_types .= "i";
}

// Get services for filter dropdown
$services = $conn->query("SELECT id, name FROM services ORDER BY name");

// --- QUERY OPTIMIZATION: Fetch summary with filters ---
$summary_sql = "SELECT COUNT(a.id) as total_appointments, IFNULL(SUM(b.amount),0) as total_earned
    FROM appointments a
    LEFT JOIN bills b ON a.id = b.appointment_id
    WHERE $filter_conditions";
$stmt_summary = $conn->prepare($summary_sql);
$stmt_summary->bind_param($filter_types, ...$filter_params);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();
$total_appointments = $summary['total_appointments'];
$total_earned = $summary['total_earned'];
$stmt_summary->close();

// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// --- REFINEMENT: Fetch with filters ---
$sql = "SELECT a.scheduled_at, s.name as service_name, u.name as customer_name, b.amount, b.id as bill_id
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        JOIN users u ON a.customer_id = u.id
        LEFT JOIN bills b ON a.id = b.appointment_id
        WHERE $filter_conditions
        ORDER BY a.scheduled_at DESC
        LIMIT ? OFFSET ?";
        
// Add limit and offset to params
$filter_params[] = $limit;
$filter_params[] = $offset;
$filter_types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($filter_types, ...$filter_params);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Build pagination URL with filters
function build_pagination_url($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return '?' . http_build_query($params);
}
?>

<div class="container-fluid">
    <h1 class="mt-4">Service History & Earnings</h1>
    <p class="text-muted">A detailed log of your completed work and total revenue generated.</p>

    <!-- Filter Controls -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filter Results
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Service</label>
                    <select name="service_id" class="form-select">
                        <option value="0">All Services</option>
                        <?php while ($service = $services->fetch_assoc()): ?>
                            <option value="<?= $service['id'] ?>" <?= ($service_id == $service['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($service['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="d-grid gap-2 d-md-flex w-100">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                        <a href="history.php" class="btn btn-secondary">
                            <i class="fas fa-redo me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-info h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Completed Services</h6>
                        <h2 class="mb-0"><?= $total_appointments ?></h2>
                        <?php if (!empty($date_from) || !empty($date_to) || $service_id > 0): ?>
                            <small class="text-muted">(Filtered results)</small>
                        <?php endif; ?>
                    </div>
                    <i class="fas fa-tasks fa-3x text-info opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-success h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Total Earnings</h6>
                        <h2 class="mb-0">৳<?= number_format($total_earned, 2) ?></h2>
                        <?php if (!empty($date_from) || !empty($date_to) || $service_id > 0): ?>
                            <small class="text-muted">(Filtered results)</small>
                        <?php endif; ?>
                    </div>
                    <i class="fas fa-hand-holding-usd fa-3x text-success opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Completed Appointments</h5>
            <?php if (!empty($date_from) || !empty($date_to) || $service_id > 0): ?>
                <span class="badge bg-info">Filtered</span>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Amount</th>
                        <th>Bill</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d M Y, h:i A', strtotime($row['scheduled_at'])) ?></td>
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td><?= htmlspecialchars($row['service_name']) ?></td>
                            <td>৳<?= number_format($row['amount'], 2) ?></td>
                            <td>
                                <?php if ($row['bill_id']): ?>
                                    <a href="bill_view.php?id=<?= $row['bill_id'] ?>" class="btn btn-info btn-sm">View Bill</a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-4">No completed appointments found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination with filter parameters preserved -->
    <?php if ($total_appointments > $limit): ?>
    <nav aria-label="History pagination" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php $pages = ceil($total_appointments / $limit); ?>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= build_pagination_url($i) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once 'include/footer.php'; ?>