<?php
// filepath: c:\xampp\htdocs\parlor\user\edit_appointment.php
$page_title = "Edit Appointment";
require_once 'include/header.php';

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>No appointment selected.</div>";
    require_once 'include/footer.php';
    exit;
}
$appt_id = intval($_GET['id']);

// Fetch appointment, checking that it belongs to this customer and is still upcoming
$sql = "SELECT a.*, s.name AS service_name, s.price AS service_price, s.id as service_id, s.duration_min,
               op.status AS payment_status
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        LEFT JOIN online_payments op ON a.id = op.appointment_id
        WHERE a.id = ? AND a.customer_id = ? AND a.status = 'booked' AND a.scheduled_at > NOW()";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $appt_id, $customer_user_id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appt) {
    // Show appropriate message based on why the appointment can't be edited
    $sql_check = "SELECT a.status, a.scheduled_at, op.status AS payment_status 
                  FROM appointments a 
                  LEFT JOIN online_payments op ON a.id = op.appointment_id
                  WHERE a.id = ? AND a.customer_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $appt_id, $customer_user_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();
    
    if (!$check_result) {
        echo "<div class='alert alert-danger'>Appointment not found.</div>";
    } else if ($check_result['status'] == 'pending_payment') {
        echo "<div class='alert alert-warning'>
            <h5><i class='fas fa-exclamation-triangle me-2'></i>This appointment cannot be edited yet</h5>
            <p>Your payment is still being verified. Once your payment is confirmed, you'll be able to reschedule this appointment.</p>
            <p><a href='appointment_view.php?id={$appt_id}' class='btn btn-primary'>View Appointment Details</a></p>
        </div>";
    } else if ($check_result['status'] == 'pending_payment' && isset($check_result['payment_status']) && $check_result['payment_status'] == 'rejected') {
        echo "<div class='alert alert-danger'>
            <h5><i class='fas fa-times-circle me-2'></i>Payment Verification Failed</h5>
            <p>We could not verify your payment. Please book a new appointment.</p>
            <p><a href='my_appointments.php' class='btn btn-primary'>Back to My Appointments</a></p>
        </div>";
    } else if (strtotime($check_result['scheduled_at']) <= time()) {
        echo "<div class='alert alert-info'>
            <h5><i class='fas fa-info-circle me-2'></i>This appointment cannot be edited</h5>
            <p>You can only reschedule upcoming appointments before their scheduled time.</p>
            <p><a href='appointment_view.php?id={$appt_id}' class='btn btn-primary'>View Appointment Details</a></p>
        </div>";
    } else {
        echo "<div class='alert alert-danger'>
            <h5><i class='fas fa-times-circle me-2'></i>This appointment cannot be edited</h5>
            <p>The appointment may be cancelled or completed.</p>
            <p><a href='my_appointments.php' class='btn btn-primary'>Back to My Appointments</a></p>
        </div>";
    }
    
    require_once 'include/footer.php';
    exit;
}

$error_msg = '';
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduled_at = $_POST['scheduled_at'];
    $employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : null;
    $notes = trim($_POST['notes']);
    
    // Basic validation
    if (!$scheduled_at) {
        $error_msg = "Date and time are required.";
    } else if (strtotime($scheduled_at) <= time()) {
        $error_msg = "Please choose a future date and time.";
    } else {
        // Update the appointment
        $stmt = $conn->prepare("UPDATE appointments SET scheduled_at = ?, employee_id = ?, notes = ?, status = 'rescheduled' WHERE id = ? AND customer_id = ?");
        $stmt->bind_param("sisii", $scheduled_at, $employee_id, $notes, $appt_id, $customer_user_id);
        
        if ($stmt->execute()) {
            header("Location: my_appointments.php?rescheduled=1");
            exit;
        } else {
            $error_msg = "Failed to update appointment. Please try again.";
        }
        $stmt->close();
    }
}

// Get available beauticians
$beauticians_sql = "SELECT e.id, u.name FROM employees e 
                   JOIN employee_services es ON e.id = es.employee_id 
                   JOIN users u ON e.user_id = u.id 
                   WHERE es.service_id = ? AND e.status = 'active'";
$stmt_beaut = $conn->prepare($beauticians_sql);
$stmt_beaut->bind_param("i", $appt['service_id']);
$stmt_beaut->execute();
$beauticians = $stmt_beaut->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_beaut->close();
?>

<div class="container-fluid" style="max-width:700px;">
    <a href="my_appointments.php" class="btn btn-link mb-3">&larr; Back to My Appointments</a>
    <h2 class="mb-4"><i class="fa fa-edit"></i> Reschedule Appointment</h2>
    
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?= $error_msg ?></div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Reschedule: <?= htmlspecialchars($appt['service_name']) ?></h5>
            <p class="small text-muted mb-0">
                Price: ৳<?= number_format($appt['service_price'], 2) ?> | 
                Duration: <?= $appt['duration_min'] ?> minutes
            </p>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label for="scheduled_at" class="form-label">Date & Time</label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at" 
                           class="form-control" min="<?= date('Y-m-d\TH:i') ?>" 
                           value="<?= date('Y-m-d\TH:i', strtotime($appt['scheduled_at'])) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="employee_id" class="form-label">Beautician (Optional)</label>
                    <select name="employee_id" id="employee_id" class="form-select">
                        <option value="">Any available beautician</option>
                        <?php foreach($beauticians as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ($appt['employee_id'] == $b['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3"><?= htmlspecialchars($appt['notes']) ?></textarea>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Rescheduling will change your appointment time. Your payment will remain valid.
                </div>
                
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="appointment_view.php?id=<?= $appt_id ?>" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>