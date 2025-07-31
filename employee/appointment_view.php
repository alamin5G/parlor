<?php
// filepath: c:\xampp\htdocs\parlor\employee\appointment_view.php
$page_title = "Appointment Details";
require_once 'include/header.php';

// Validate & fetch appointment
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>No appointment selected.</div>";
    require_once 'include/footer.php';
    exit;
}
$appt_id = intval($_GET['id']);

// Get this employee's id
$employee_stmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
$employee_stmt->bind_param("i", $employee_user_id);
$employee_stmt->execute();
$employee_id = $employee_stmt->get_result()->fetch_assoc()['id'];
$employee_stmt->close();

// Fetch appointment with joined info
$appt_stmt = $conn->prepare("SELECT a.*, 
        u.name AS customer_name, u.phone AS customer_phone, u.email AS customer_email,
        s.name AS service_name, s.price AS service_price
        FROM appointments a
        JOIN users u ON a.customer_id = u.id
        JOIN services s ON a.service_id = s.id
        WHERE a.id = ? AND a.employee_id = ?");
$appt_stmt->bind_param("ii", $appt_id, $employee_id);
$appt_stmt->execute();
$result = $appt_stmt->get_result();
$appt_stmt->close();

// Mark as seen
$seen_stmt = $conn->prepare("UPDATE appointments SET is_seen_by_employee = 1 WHERE id = ? AND employee_id = ?");
$seen_stmt->bind_param("ii", $appt_id, $employee_id);
$seen_stmt->execute();
$seen_stmt->close();

if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Appointment not found or not assigned to you.</div>";
    require_once 'include/footer.php';
    exit;
}
$appt = $result->fetch_assoc();

// Get bill info (if exists)
$bill = null;
$bill_stmt = $conn->prepare("SELECT * FROM bills WHERE appointment_id = ?");
$bill_stmt->bind_param("i", $appt_id);
$bill_stmt->execute();
$bill_result = $bill_stmt->get_result();
if ($bill_result->num_rows > 0) {
    $bill = $bill_result->fetch_assoc();
}
$bill_stmt->close();

// After fetching $appt and $bill above, fetch review
$review_stmt = $conn->prepare("SELECT * FROM reviews WHERE appointment_id = ?");
$review_stmt->bind_param("i", $appt_id);
$review_stmt->execute();
$review_result = $review_stmt->get_result();
$review = $review_result->num_rows > 0 ? $review_result->fetch_assoc() : null;
$review_stmt->close();

// Handle status change (complete/cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_action'])) {
    $action = $_POST['status_action'];
    if (in_array($action, ['completed', 'cancelled']) && $appt['status'] == 'booked') {
        $update_stmt = $conn->prepare("UPDATE appointments SET status=? WHERE id=? AND employee_id=?");
        $update_stmt->bind_param("sii", $action, $appt_id, $employee_id);
        if ($update_stmt->execute()) {
            // Refresh data
            header("Location: appointment_view.php?id=$appt_id");
            exit;
        } else {
            $error_msg = "Failed to update appointment status.";
        }
        $update_stmt->close();
    }
}
?>

<div class="container-fluid" style="max-width:700px;">
    <a href="dashboard.php" class="btn btn-light mb-3">&larr; Back to Dashboard</a>
    <h2 class="mb-4"><i class="fa fa-calendar-check"></i> Appointment Details</h2>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger"><?= $error_msg ?></div>
    <?php endif; ?>

    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($appt['service_name']) ?> <span class="badge bg-primary ms-2"><?= ucfirst($appt['status']) ?></span></h5>
            <ul class="list-group mb-3">
                <li class="list-group-item"><b>Customer:</b> <?= htmlspecialchars($appt['customer_name']) ?> (<?= htmlspecialchars($appt['customer_phone']) ?>, <?= htmlspecialchars($appt['customer_email']) ?>)</li>
                <li class="list-group-item"><b>Date/Time:</b> <?= date('d M Y, h:i A', strtotime($appt['scheduled_at'])) ?></li>
                <li class="list-group-item"><b>Service Price:</b> ৳<?= number_format($appt['service_price'], 2) ?></li>
                <li class="list-group-item"><b>Notes:</b> <?= htmlspecialchars($appt['notes']) ?: '<span class="text-muted">-</span>' ?></li>
            </ul>
            <?php if ($bill): ?>
                <div class="mb-2">
                    <b>Payment:</b>
                    <span class="badge bg-success">Paid</span>
                    <b>Amount:</b> ৳<?= number_format($bill['amount'], 2) ?>
                    <span class="text-muted">(<?= date('d M Y, h:i A', strtotime($bill['payment_time'])) ?>)</span>
                </div>
            <?php endif; ?>
            <?php if ($review): ?>
                <div class="mb-2">
                    <b>Customer Review:</b>
                    <span class="badge bg-warning text-dark"><?= str_repeat('★', $review['rating']) . str_repeat('☆', 5-$review['rating']) ?></span>
                    <div class="mt-2">
                        <i class="fa fa-quote-left text-secondary"></i>
                        <?= htmlspecialchars($review['comments']) ?>
                        <i class="fa fa-quote-right text-secondary"></i>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <?php if ($appt['status'] == 'booked'): ?>
                <form method="post" class="d-inline">
                    <button name="status_action" value="completed" class="btn btn-success"
                        onclick="return confirm('Mark this appointment as completed?')">
                        <i class="fa fa-check"></i> Mark as Completed
                    </button>
                </form>
                <form method="post" class="d-inline">
                    <button name="status_action" value="cancelled" class="btn btn-danger"
                        onclick="return confirm('Cancel this appointment?')">
                        <i class="fa fa-times"></i> Cancel Appointment
                    </button>
                </form>
            <?php else: ?>
                <span class="badge bg-info">Status: <?= ucfirst($appt['status']) ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>