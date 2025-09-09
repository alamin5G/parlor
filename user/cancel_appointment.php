<?php
// filepath: c:\xampp\htdocs\parlor\user\cancel_appointment.php
$page_title = "Cancel Appointment";
require_once 'include/header.php';

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>No appointment selected.</div>";
    require_once 'include/footer.php';
    exit;
}
$appt_id = intval($_GET['id']);

// Check if appointment exists and belongs to this customer
$sql = "SELECT a.*, s.name AS service_name, op.id AS payment_id, op.status AS payment_status
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        LEFT JOIN online_payments op ON a.id = op.appointment_id
        WHERE a.id = ? AND a.customer_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $appt_id, $customer_user_id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appt) {
    echo "<div class='alert alert-danger'>Appointment not found.</div>";
    require_once 'include/footer.php';
    exit;
}

// Check if the appointment can be cancelled
$can_cancel = false;
$message = '';

if ($appt['status'] == 'pending_payment') {
    $message = "Your payment is still being verified. You cannot cancel this appointment until your payment is verified.";
} else if ($appt['status'] == 'cancelled') {
    $message = "This appointment is already cancelled.";
} else if ($appt['status'] == 'completed') {
    $message = "You cannot cancel a completed appointment.";
} else if (strtotime($appt['scheduled_at']) < time()) {
    $message = "You cannot cancel an appointment that has already passed.";
} else if ($appt['status'] == 'booked' || $appt['status'] == 'rescheduled') {
    $can_cancel = true;
}

// Handle confirmed cancellation
if ($can_cancel && isset($_POST['confirm_cancel'])) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Update appointment status
        $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $appt_id);
        $stmt->execute();
        $stmt->close();
        
        // 2. If they paid online, record that a refund might be needed
        if (!empty($appt['payment_id']) && $appt['payment_status'] == 'approved') {
            // This is where you might add logic to handle refunds
            // For now, we'll just note it in the system
            $notes = "Appointment cancelled by customer. Potential refund needed.";
            $stmt = $conn->prepare("UPDATE online_payments SET notes = ? WHERE appointment_id = ?");
            $stmt->bind_param("si", $notes, $appt_id);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        header("Location: my_appointments.php?cancelled=1");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Could not cancel appointment: " . $e->getMessage();
    }
}
?>

<div class="container-fluid" style="max-width:700px;">
    <a href="my_appointments.php" class="btn btn-link mb-3">&larr; Back to My Appointments</a>
    <h2 class="mb-4"><i class="fa fa-calendar-times"></i> Cancel Appointment</h2>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Unable to Cancel</h5>
            <p><?= $message ?></p>
            <a href="my_appointments.php" class="btn btn-primary mt-2">Back to My Appointments</a>
        </div>
    <?php elseif ($can_cancel): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Are you sure you want to cancel this appointment?</h5>
                <p class="mb-1"><strong>Service:</strong> <?= htmlspecialchars($appt['service_name']) ?></p>
                <p class="mb-3"><strong>Date & Time:</strong> <?= date('D, d M Y, h:i A', strtotime($appt['scheduled_at'])) ?></p>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> 
                    <strong>Please note:</strong> 
                    <?php if (!empty($appt['payment_id']) && $appt['payment_status'] == 'approved'): ?>
                        You have already paid for this service. Refunds are subject to our cancellation policy.
                    <?php else: ?>
                        Cancelling an appointment might affect your booking history.
                    <?php endif; ?>
                </div>
                
                <form method="post">
                    <button type="submit" name="confirm_cancel" class="btn btn-danger">Yes, Cancel this Appointment</button>
                    <a href="appointment_view.php?id=<?= $appt_id ?>" class="btn btn-outline-secondary">No, Keep my Appointment</a>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'include/footer.php'; ?>