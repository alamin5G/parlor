<?php
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
$status_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Build the query based on filters
$query = "SELECT a.*, 
          u.name as customer_name, 
          e.id as employee_id, 
          eu.name as employee_name,
          s.name as service_name, 
          s.price
          FROM appointments a
          JOIN users u ON a.customer_id = u.id
          JOIN employees e ON a.employee_id = e.id
          JOIN users eu ON e.user_id = eu.id
          JOIN services s ON a.service_id = s.id
          WHERE 1=1";

if ($status_filter != 'all') {
    $query .= " AND a.status = '$status_filter'";
}

if (!empty($date_filter)) {
    $query .= " AND DATE(a.scheduled_at) = '$date_filter'";
}

$query .= " ORDER BY a.scheduled_at DESC";
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
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
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
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($row['scheduled_at'])); ?></td>
                                    <td>৳<?php echo number_format($row['price'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $badge_class = '';
                                        switch($row['status']) {
                                            case 'booked': $badge_class = 'bg-primary'; break;
                                            case 'completed': $badge_class = 'bg-success'; break;
                                            case 'cancelled': $badge_class = 'bg-danger'; break;
                                            case 'rescheduled': $badge_class = 'bg-warning'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="appointment_edit.php?id=<?php echo $row['id']; ?>">Edit</a></li>
                                                <li><a class="dropdown-item" href="appointments.php?id=<?php echo $row['id']; ?>&status=completed">Mark Completed</a></li>
                                                <li><a class="dropdown-item" href="appointments.php?id=<?php echo $row['id']; ?>&status=cancelled">Cancel</a></li>
                                                <?php if ($row['status'] != 'completed'): ?>
                                                    <li><a class="dropdown-item" href="generate_bill.php?appointment_id=<?php echo $row['id']; ?>">Generate Bill</a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No appointments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../include/footer.php'; ?>