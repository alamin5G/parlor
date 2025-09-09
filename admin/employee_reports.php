
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Employee Performance Reports";
require_once 'include/header.php';
require_once '../includes/db_connect.php';

// Get employee ID from URL, default to all if not specified
$employee_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;

// Date filtering
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get employee details if ID is provided
$employee_name = "All Employees";
if ($employee_id) {
    $stmt = $conn->prepare("
        SELECT u.name FROM employees e
        JOIN users u ON e.user_id = u.id
        WHERE e.id = ?
    ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $employee_name = $row['name'];
    }
}

// Build where clause
$where_clause = "WHERE a.status = 'completed' AND DATE(b.payment_time) BETWEEN ? AND ?";
$params = [$start_date, $end_date];
$param_types = "ss";

if ($employee_id) {
    $where_clause .= " AND e.id = ?";
    $params[] = $employee_id;
    $param_types .= "i";
}

// Get efficiency metrics
$stmt_efficiency = $conn->prepare("
    SELECT 
        e.id as employee_id,
        u.name as employee_name,
        COUNT(a.id) as appointment_count,
        SUM(s.duration_min) as total_minutes,
        COALESCE(SUM(b.amount), 0) as total_revenue,
        COALESCE(SUM(b.amount) / SUM(s.duration_min) * 60, 0) as revenue_per_hour,
        AVG(s.duration_min) as avg_service_duration,
        (
            SELECT ROUND(AVG(r.rating), 1)
            FROM reviews r
            JOIN appointments a2 ON r.appointment_id = a2.id
            WHERE a2.employee_id = e.id AND DATE(a2.scheduled_at) BETWEEN ? AND ?
        ) as avg_rating
    FROM appointments a
    JOIN employees e ON a.employee_id = e.id
    JOIN users u ON e.user_id = u.id
    JOIN services s ON a.service_id = s.id
    JOIN bills b ON a.id = b.appointment_id
    $where_clause
    GROUP BY e.id, u.name
    ORDER BY revenue_per_hour DESC
");

$stmt_efficiency->bind_param($param_types."ss", ...[...$params, $start_date, $end_date]);
$stmt_efficiency->execute();
$efficiency_data = $stmt_efficiency->get_result();

// Get services by employee
$stmt_services = $conn->prepare("
    SELECT 
        s.name as service_name,
        COUNT(a.id) as count,
        SUM(b.amount) as revenue,
        ROUND(AVG(s.duration_min), 0) as avg_duration
    FROM appointments a
    JOIN employees e ON a.employee_id = e.id
    JOIN services s ON a.service_id = s.id
    JOIN bills b ON a.id = b.appointment_id
    $where_clause
    GROUP BY s.name
    ORDER BY count DESC
");

$stmt_services->bind_param($param_types, ...$params);
$stmt_services->execute();
$services_data = $stmt_services->get_result();

// Get ratings data
$stmt_ratings = $conn->prepare("
    SELECT 
        r.rating,
        COUNT(*) as count,
        r.comment
    FROM reviews r
    JOIN appointments a ON r.appointment_id = a.id
    JOIN employees e ON a.employee_id = e.id
    WHERE DATE(a.scheduled_at) BETWEEN ? AND ?
    " . ($employee_id ? " AND e.id = ?" : "") . "
    GROUP BY r.rating
    ORDER BY r.rating DESC
");

if ($employee_id) {
    $stmt_ratings->bind_param("ssi", $start_date, $end_date, $employee_id);
} else {
    $stmt_ratings->bind_param("ss", $start_date, $end_date);
}
$stmt_ratings->execute();
$ratings_data = $stmt_ratings->get_result();

// Display format for date range
$display_start_date = date('M d, Y', strtotime($start_date));
$display_end_date = date('M d, Y', strtotime($end_date));
$report_period = $display_start_date . ' to ' . $display_end_date;
if ($start_date === $end_date) {
    $report_period = date('F d, Y', strtotime($start_date));
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><?= $employee_name ?> Performance</h1>
            <p class="text-muted mb-0">
                <i class="fas fa-calendar me-1"></i> 
                <?= $report_period ?>
            </p>
        </div>
        <div>
            <a href="reports.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i> Back to Reports
            </a>
            <a href="export_employee.php?id=<?= $employee_id ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
               class="btn btn-outline-success">
                <i class="fas fa-file-export me-1"></i> Export Data
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Options</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="employee_reports.php" class="row g-3">
                <?php if ($employee_id): ?>
                    <input type="hidden" name="id" value="<?= $employee_id ?>">
                <?php else: ?>
                    <div class="col-md-4">
                        <label class="form-label">Select Employee</label>
                        <select name="id" class="form-select">
                            <option value="">All Employees</option>
                            <?php
                            $stmt = $conn->prepare("SELECT e.id, u.name FROM employees e JOIN users u ON e.user_id = u.id ORDER BY u.name");
                            $stmt->execute();
                            $employees = $stmt->get_result();
                            while ($emp = $employees->fetch_assoc()):
                            ?>
                                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="row">
        <?php 
        // Reset result pointer
        $efficiency_data->data_seek(0);
        while ($employee = $efficiency_data->fetch_assoc()):
            $rating = $employee['avg_rating'] ?: 'N/A';
            $rating_class = $rating == 'N/A' ? 'secondary' : 
                           ($rating >= 4.5 ? 'success' : 
                           ($rating >= 3.5 ? 'primary' : 
                           ($rating >= 2.5 ? 'warning' : 'danger')));
        ?>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><?= htmlspecialchars($employee['employee_name']) ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <h6 class="text-muted">Revenue</h6>
                            <h4>৳<?= number_format($employee['total_revenue'], 2) ?></h4>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted">Services</h6>
                            <h4><?= number_format($employee['appointment_count']) ?></h4>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <h6 class="text-muted">Revenue/Hour</h6>
                            <h4>৳<?= number_format($employee['revenue_per_hour'], 2) ?></h4>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted">Avg. Rating</h6>
                            <h4 class="text-<?= $rating_class ?>">
                                <?= $rating != 'N/A' ? $rating : '-' ?>
                                <?php if ($rating != 'N/A'): ?>
                                    <i class="fas fa-star text-warning small"></i>
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted">Efficiency</span>
                            <span class="badge bg-<?= $rating_class ?>">
                                <?= number_format($employee['revenue_per_hour'] / 200 * 100, 0) ?>%
                            </span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-<?= $rating_class ?>" 
                                 style="width: <?= min(100, $employee['revenue_per_hour'] / 200 * 100) ?>%" 
                                 role="progressbar"></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="employee_reports.php?id=<?= $employee['employee_id'] ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
                       class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-search me-1"></i> View Details
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    
    <?php if ($employee_id): ?>
    <!-- Detailed Analysis for Single Employee -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-spa me-2"></i>Services Performed</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Service</th>
                                    <th class="text-center">Count</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-center">Avg. Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $services_data->data_seek(0);
                                $total_count = 0;
                                $total_revenue = 0;
                                
                                while($service = $services_data->fetch_assoc()): 
                                    $total_count += $service['count'];
                                    $total_revenue += $service['revenue'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($service['service_name']) ?></td>
                                    <td class="text-center"><?= number_format($service['count']) ?></td>
                                    <td class="text-end">৳<?= number_format($service['revenue'], 2) ?></td>
                                    <td class="text-center"><?= $service['avg_duration'] ?> mins</td>
                                </tr>
                                <?php endwhile; ?>
                                
                                <tr class="table-light fw-bold">
                                    <td>Total</td>
                                    <td class="text-center"><?= number_format($total_count) ?></td>
                                    <td class="text-end">৳<?= number_format($total_revenue, 2) ?></td>
                                    <td class="text-center">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-star me-2"></i>Ratings Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="ratingsChart" height="220"></canvas>
                    
                    <div class="mt-4">
                        <h6>Recent Comments</h6>
                        <?php
                        $stmt_comments = $conn->prepare("
                            SELECT r.comment, r.rating, DATE(r.created_at) as date
                            FROM reviews r
                            JOIN appointments a ON r.appointment_id = a.id
                            WHERE a.employee_id = ? AND r.comment != ''
                            ORDER BY r.created_at DESC
                            LIMIT 3
                        ");
                        $stmt_comments->bind_param("i", $employee_id);
                        $stmt_comments->execute();
                        $comments = $stmt_comments->get_result();
                        
                        if ($comments->num_rows > 0):
                            while ($comment = $comments->fetch_assoc()):
                                $rating_color = $comment['rating'] >= 4 ? 'success' : 
                                              ($comment['rating'] >= 3 ? 'primary' : 
                                              ($comment['rating'] >= 2 ? 'warning' : 'danger'));
                        ?>
                        <div class="alert alert-light mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <div class="text-<?= $rating_color ?>">
                                    <?php for($i=0; $i<$comment['rating']; $i++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                    <?php for($i=$comment['rating']; $i<5; $i++): ?>
                                        <i class="far fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <small class="text-muted"><?= date('M d, Y', strtotime($comment['date'])) ?></small>
                            </div>
                            <p class="mb-0 small"><?= htmlspecialchars($comment['comment']) ?></p>
                        </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <div class="alert alert-light">
                            <p class="mb-0">No comments available</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer_scripts.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($employee_id && $ratings_data->num_rows > 0): ?>
    // Prepare rating data
    const ratingLabels = [];
    const ratingData = [];
    const ratingColors = [
        'rgba(220, 53, 69, 0.7)',   // 1 star - danger
        'rgba(255, 193, 7, 0.7)',    // 2 star - warning
        'rgba(13, 110, 253, 0.7)',   // 3 star - primary
        'rgba(40, 167, 69, 0.7)',    // 4 star - success
        'rgba(23, 162, 184, 0.7)'    // 5 star - info
    ];
    
    <?php
    $ratings_data->data_seek(0);
    $rating_counts = [0, 0, 0, 0, 0];
    while ($rating = $ratings_data->fetch_assoc()) {
        $idx = $rating['rating'] - 1;
        $rating_counts[$idx] = $rating['count'];
    }
    ?>
    
    const ratingsChart = new Chart(document.getElementById('ratingsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
            datasets: [{
                label: 'Ratings',
                data: [<?= implode(',', $rating_counts) ?>],
                backgroundColor: ratingColors,
                borderColor: 'rgba(255, 255, 255, 0.8)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
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
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>