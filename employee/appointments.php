<?php
$page_title = "My Appointments";
require_once 'include/header.php';

// Get employee_id
$stmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt->bind_param("i", $employee_user_id);
$stmt->execute();
$employee_id = $stmt->get_result()->fetch_assoc()['id'];
$stmt->close();

// Filtering and searching
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "WHERE a.employee_id = ?";
$params = [$employee_id];
$types = "i";

if ($status && in_array($status, ['booked', 'completed', 'cancelled', 'rescheduled'])) {
    $where .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}
if ($search) {
    $where .= " AND u.name LIKE ?";
    $params[] = '%' . $search . '%';
    $types .= "s";
}

// Count total for pagination
$count_sql = "SELECT COUNT(*) as total FROM appointments a JOIN users u ON a.customer_id = u.id $where";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Main query
$sql = "SELECT a.*, s.name as service_name, s.price, u.name as customer_name 
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        JOIN users u ON a.customer_id = u.id
        $where
        ORDER BY a.scheduled_at DESC
        LIMIT ? OFFSET ?";
$params[] = $limit; $types .= "ii";
$params[] = $offset;
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<div class="container-fluid">
    <h2 class="mt-4">My Appointments</h2>
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <?php foreach (['booked','completed','cancelled','rescheduled'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $opt==$status?'selected':'' ?>><?= ucfirst($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Search by customer" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d M Y, h:i A', strtotime($row['scheduled_at'])) ?></td>
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td><?= htmlspecialchars($row['service_name']) ?></td>
                            <td>৳<?= number_format($row['price'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= $row['status']=='completed' ? 'success' : ($row['status']=='cancelled' ? 'danger' : ($row['status']=='rescheduled' ? 'warning text-dark' : 'primary')) ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="appointment_view.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">View</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No appointments found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Pagination -->
    <nav aria-label="Appointments pagination" class="mt-3">
      <ul class="pagination">
        <?php $pages = ceil($total / $limit); ?>
        <?php for ($i=1; $i <= $pages; $i++): ?>
          <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
</div>
<?php require_once 'include/footer.php'; ?>
