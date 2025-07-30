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

// --- QUERY OPTIMIZATION: Fetch summary and total count in one go ---
$summary_sql = "SELECT COUNT(a.id) as total_appointments, IFNULL(SUM(b.amount),0) as total_earned
    FROM appointments a
    LEFT JOIN bills b ON a.id = b.appointment_id
    WHERE a.employee_id = ? AND a.status = 'completed'";
$stmt_summary = $conn->prepare($summary_sql);
$stmt_summary->bind_param("i", $employee_id);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();
$total_appointments = $summary['total_appointments'];
$total_earned = $summary['total_earned'];
$stmt_summary->close();

// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// --- REFINEMENT: Fetch customer name along with other details ---
$sql = "SELECT a.scheduled_at, s.name as service_name, u.name as customer_name, b.amount, b.id as bill_id
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        JOIN users u ON a.customer_id = u.id
        LEFT JOIN bills b ON a.id = b.appointment_id
        WHERE a.employee_id = ? AND a.status = 'completed'
        ORDER BY a.scheduled_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $employee_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<div class="container-fluid">
    <h1 class="mt-4">Service History & Earnings</h1>
    <p class="text-muted">A detailed log of your completed work and total revenue generated.</p>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-info h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><h6 class="card-title text-muted">Completed Services</h6><h2 class="mb-0"><?= $total_appointments ?></h2></div><i class="fas fa-tasks fa-3x text-info opacity-50"></i></div></div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-success h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><h6 class="card-title text-muted">Total Earnings</h6><h2 class="mb-0">৳<?= number_format($total_earned, 2) ?></h2></div><i class="fas fa-hand-holding-usd fa-3x text-success opacity-50"></i></div></div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header"><h5 class="mb-0">Completed Appointments</h5></div>
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
                                    <!-- FIX: Link to secure employee bill view -->
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

    <!-- Pagination -->
    <?php if ($total_appointments > $limit): ?>
    <nav aria-label="History pagination" class="mt-4">
      <!-- UI POLISH: Center pagination -->
      <ul class="pagination justify-content-center">
        <?php $pages = ceil($total_appointments / $limit); ?>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once 'include/footer.php'; ?>