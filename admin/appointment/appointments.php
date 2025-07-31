<?php
// filepath: c:\xampp\htdocs\parlor\admin\appointment\appointments.php
require_once '../include/header.php';

// Handle appointment status changes
if (isset($_GET['id']) && isset($_GET['status'])) {
    $appointment_id = $_GET['id'];
    $new_status = $_GET['status'];
    
    if (in_array($new_status, ['booked', 'completed', 'cancelled', 'rescheduled'])) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $appointment_id);
        
        if ($stmt->execute()) {
            $success_msg = "Appointment status updated successfully.";
        } else {
            $error_msg = "Error updating appointment status.";
        }
    }
}

// Handle filtering
$status_filter = isset($_GET['filter']) && $_GET['filter'] != '' ? $_GET['filter'] : 'all';
$date_filter = isset($_GET['date']) && $_GET['date'] != '' ? $_GET['date'] : '';

// Build the query based on filters
$query = "SELECT a.*, 
          u.name as customer_name, u.phone as customer_phone,
          e.id as employee_id, 
          eu.name as employee_name,
          s.name as service_name, 
          s.price,
          op.id as payment_id, op.status as payment_status
          FROM appointments a
          JOIN users u ON a.customer_id = u.id
          LEFT JOIN employees e ON a.employee_id = e.id
          LEFT JOIN users eu ON e.user_id = eu.id
          JOIN services s ON a.service_id = s.id
          LEFT JOIN online_payments op ON a.id = op.appointment_id";

// Apply filters only if they are set
if ($status_filter != 'all') {
    $query .= " WHERE a.status = '$status_filter'";
} else {
    $query .= " WHERE 1=1"; // Default WHERE clause that doesn't filter anything
}

if (!empty($date_filter)) {
    $query .= " AND DATE(a.scheduled_at) = '$date_filter'";
}

$query .= " ORDER BY a.scheduled_at DESC";

// Debug query
// echo "<pre>$query</pre>";

$result = $conn->query($query);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Manage Appointments</h1>
        <a href="appointment_add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Create Appointment
        </a>
    </div>
    
    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="filter" class="form-label">Status</label>
                    <select class="form-select" id="filter" name="filter">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending_payment" <?php echo $status_filter == 'pending_payment' ? 'selected' : ''; ?>>Pending Payment</option>
                        <option value="booked" <?php echo $status_filter == 'booked' ? 'selected' : ''; ?>>Booked</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="rescheduled" <?php echo $status_filter == 'rescheduled' ? 'selected' : ''; ?>>Rescheduled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="appointments.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Debug Info -->
            <?php if (isset($_GET['debug'])): ?>
            <div class="alert alert-info">
                <p><strong>Query:</strong> <?php echo $query; ?></p>
                <p><strong>Result Count:</strong> <?php echo $result ? $result->num_rows : 'Query error'; ?></p>
                <p><strong>Status Filter:</strong> <?php echo $status_filter; ?></p>
                <p><strong>Date Filter:</strong> <?php echo $date_filter; ?></p>
            </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover" id="appointmentTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Service</th>
                            <th>Beautician</th>
                            <th>Date & Time</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="<?= $row['status'] == 'pending_payment' ? 'table-warning' : '' ?>">
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_phone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['employee_name'] ?? 'Not Assigned'); ?></td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($row['scheduled_at'])); ?></td>
                                    <td>৳<?php echo number_format($row['price'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $badge_class = '';
                                        switch($row['status']) {
                                            case 'pending_payment': 
                                                $badge_class = 'bg-warning text-dark'; 
                                                $status_text = 'Pending Payment';
                                                // Check if payment is rejected
                                                if (isset($row['payment_status']) && $row['payment_status'] == 'rejected') {
                                                    $badge_class = 'bg-danger';
                                                    $status_text = 'Payment Rejected';
                                                }
                                                break;
                                            case 'booked': $badge_class = 'bg-primary'; $status_text = 'Booked'; break;
                                            case 'completed': $badge_class = 'bg-success'; $status_text = 'Completed'; break;
                                            case 'cancelled': $badge_class = 'bg-danger'; $status_text = 'Cancelled'; break;
                                            case 'rescheduled': $badge_class = 'bg-warning text-dark'; $status_text = 'Rescheduled'; break;
                                            default: $badge_class = 'bg-secondary'; $status_text = ucfirst($row['status']);
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                   <td>
    <div class="dropdown">
        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            Actions
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="appointment_view.php?id=<?php echo $row['id']; ?>">View Details</a></li>
            <li><a class="dropdown-item" href="appointment_edit.php?id=<?php echo $row['id']; ?>">Edit</a></li>
            
            <?php if ($row['status'] == 'pending_payment'): ?>
                <?php if (isset($row['payment_id']) && $row['payment_id']): ?>
                    <li><a class="dropdown-item" href="../verify_payment.php?id=<?php echo $row['payment_id']; ?>">Verify Payment</a></li>
                <?php else: ?>
                    <li><span class="dropdown-item text-muted">No payment record</span></li>
                <?php endif; ?>
            <?php else: ?>
                <li><a class="dropdown-item" href="appointments.php?id=<?php echo $row['id']; ?>&status=completed">Mark Completed</a></li>
                <li><a class="dropdown-item" href="appointments.php?id=<?php echo $row['id']; ?>&status=cancelled">Cancel</a></li>
            <?php endif; ?>
            
            <?php if ($row['status'] == 'booked' || $row['status'] == 'completed'): ?>
                <li><a class="dropdown-item" href="../generate_bill.php?appointment_id=<?php echo $row['id']; ?>">Generate Bill</a></li>
            <?php endif; ?>
        </ul>
    </div>
</td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No appointments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables if available
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#appointmentTable').DataTable({
            "order": [[5, "desc"]], // Default sort by date descending
            "pageLength": 25
        });
    }
});
</script>

<?php require_once '../include/footer.php'; ?>