<?php
// filepath: c:\xampp\htdocs\parlor\user\my_appointments.php
$page_title = "My Appointments";
require_once 'include/header.php';

// --- SETUP ---
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Define valid statuses for filtering
$valid_statuses = ['pending_payment', 'booked', 'completed', 'cancelled', 'rescheduled'];
$status = isset($_GET['status']) && in_array($_GET['status'], $valid_statuses) ? $_GET['status'] : '';

// --- DATA FETCHING ---
// Build the filter for the SQL query
$filter_sql = '';
$filter_params = [];
$filter_types = '';
if ($status) {
    $filter_sql .= " AND a.status = ? ";
    $filter_params[] = $status;
    $filter_types .= "s";
}

// Count total appointments for pagination
$count_sql = "SELECT COUNT(*) AS total FROM appointments a WHERE a.customer_id = ? $filter_sql";
$stmt_count = $conn->prepare($count_sql);
$stmt_count->bind_param("i" . $filter_types, ...array_merge([$customer_user_id], $filter_params));
$stmt_count->execute();
$total_appointments = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

// Fetch the main list of appointments
$sql = "SELECT a.*, s.name AS service_name, s.price, 
               u_emp.name AS beautician_name,
               op.status AS payment_status
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        LEFT JOIN employees e ON a.employee_id = e.id
        LEFT JOIN users u_emp ON e.user_id = u_emp.id
        LEFT JOIN online_payments op ON a.id = op.appointment_id
        WHERE a.customer_id = ? $filter_sql
        ORDER BY a.scheduled_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i" . $filter_types . "ii", ...array_merge([$customer_user_id], $filter_params, [$limit, $offset]));
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Fetch completed appointments that need a review
$review_sql = "SELECT a.id, s.name as service_name, a.scheduled_at
               FROM appointments a
               JOIN services s ON a.service_id = s.id
               LEFT JOIN reviews r ON r.appointment_id = a.id
               WHERE a.customer_id=? AND a.status='completed' AND r.id IS NULL
               ORDER BY a.scheduled_at DESC";
$stmt_review = $conn->prepare($review_sql);
$stmt_review->bind_param("i", $customer_user_id);
$stmt_review->execute();
$reviews_needed = $stmt_review->get_result();
$stmt_review->close();
?>

<div class="container-fluid">
    <h2 class="mt-4">My Appointments</h2>

    <!-- User Feedback Messages -->
    <?php if (isset($_GET['booked']) && $_GET['booked'] == 'pending'): ?>
      <div class="alert alert-success">
        <h5><i class="fas fa-check-circle me-2"></i>Booking Submitted!</h5>
        <p>Your booking request has been submitted with payment details. It will be confirmed once we verify your payment.</p>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['cancelled'])): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i>Appointment cancelled successfully.
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['rescheduled'])): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i>Appointment rescheduled successfully.
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['payment_rejected'])): ?>
      <div class="alert alert-danger">
        <h5><i class="fas fa-exclamation-triangle me-2"></i>Payment Rejected</h5>
        <p>Your payment could not be verified. Please contact our support team or try booking again.</p>
      </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <form method="get" class="row g-2 mb-3 align-items-center">
        <div class="col-md-3">
            <label for="status_filter" class="visually-hidden">Filter by Status</label>
            <select name="status" id="status_filter" class="form-select">
                <option value="">All Statuses</option>
                <?php foreach ($valid_statuses as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($opt == $status) ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $opt)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($status): ?>
                <a href="my_appointments.php" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Appointments Table -->
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Service</th>
                        <th>Beautician</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d M Y, h:i A', strtotime($row['scheduled_at'])) ?></td>
                            <td><?= htmlspecialchars($row['service_name']) ?></td>
                            <td><?= htmlspecialchars($row['beautician_name'] ?? 'N/A') ?></td>
                            <td>৳<?= number_format($row['price'], 2) ?></td>
                            <td>
                                <?php
                                    $status_text = ucfirst(str_replace('_', ' ', $row['status']));
                                    $status_class = 'primary'; // default
                                    
                                    // Special handling for pending payment with rejected status
                                    if ($row['status'] == 'pending_payment' && isset($row['payment_status']) && $row['payment_status'] == 'rejected') {
                                        $status_text = 'Payment Rejected';
                                        $status_class = 'danger';
                                    } else {
                                        switch ($row['status']) {
                                            case 'completed': $status_class = 'success'; break;
                                            case 'cancelled': $status_class = 'danger'; break;
                                            case 'rescheduled': $status_class = 'warning text-dark'; break;
                                            case 'pending_payment': $status_class = 'secondary'; break;
                                            case 'booked': $status_class = 'info'; break;
                                        }
                                    }
                                ?>
                                <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                            </td>
                            <td class="text-end">
                                <a href="appointment_view.php?id=<?= $row['id'] ?>" class="btn btn-outline-info btn-sm">View</a>
                                
                                <?php 
                                // Show edit/cancel buttons ONLY for confirmed upcoming appointments
                                $is_upcoming_booked = ($row['status'] == 'booked' && strtotime($row['scheduled_at']) > time()); 
                                if ($is_upcoming_booked): 
                                ?>
                                    <a href="edit_appointment.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm">Reschedule</a>
                                    <a href="cancel_appointment.php?id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                       Cancel
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                // If payment rejected, show a rebooking option
                                if ($row['status'] == 'pending_payment' && isset($row['payment_status']) && $row['payment_status'] == 'rejected'): 
                                ?>
                                    <a href="book.php?service_id=<?= $row['service_id'] ?>" class="btn btn-success btn-sm">Book Again</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-4">No appointments found for the selected filter.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_appointments > $limit): ?>
    <nav aria-label="Appointments pagination" class="mt-3">
      <ul class="pagination">
        <?php $pages = ceil($total_appointments / $limit); ?>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?status=<?= urlencode($status) ?>&page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>

    <!-- Review Section -->
    <?php if ($reviews_needed->num_rows > 0): ?>
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h5 class="mb-0">Leave a Review</h5>
        </div>
        <div class="card-body">
            <p>You have completed appointments that you haven't reviewed yet. Your feedback is valuable!</p>
            <table class="table table-sm">
                <?php while ($review_row = $reviews_needed->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($review_row['service_name']) ?> on <?= date('d M Y', strtotime($review_row['scheduled_at'])) ?></td>
                    <td class="text-end"><a href="review.php?appointment_id=<?= $review_row['id'] ?>" class="btn btn-sm btn-primary">Write Review</a></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php require_once 'include/footer.php'; ?>