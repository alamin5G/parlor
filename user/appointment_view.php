<?php
// filepath: c:\xampp\htdocs\parlor\user\appointment_view.php
$page_title = "Appointment Details";
require_once 'include/header.php';

// Validate appointment ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>No appointment selected.</div>";
    require_once 'include/footer.php';
    exit;
}
$appt_id = intval($_GET['id']);

// Fetch the appointment, only if it belongs to this customer
$sql = "SELECT a.*, 
               s.name AS service_name, s.price AS service_price, s.duration_min, s.id AS service_id,
               u_emp.name AS beautician_name, u_emp.phone AS beautician_phone,
               b.id AS bill_id, b.amount AS bill_amount, b.payment_mode, b.payment_time,
               op.id AS payment_id, op.method AS online_method, op.transaction_id, op.status AS payment_status
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        LEFT JOIN employees e ON a.employee_id = e.id
        LEFT JOIN users u_emp ON e.user_id = u_emp.id
        LEFT JOIN bills b ON a.id = b.appointment_id
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

// Helper: Can this appointment be edited/cancelled?
$is_upcoming_booked = $appt['status']=='booked' && strtotime($appt['scheduled_at']) > time();

?>
<div class="container-fluid" style="max-width:700px;">
    <a href="my_appointments.php" class="btn btn-link mb-3">&larr; Back to My Appointments</a>
    <h2 class="mb-4"><i class="fa fa-calendar-check"></i> Appointment Details</h2>

    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($appt['service_name']) ?>
                <?php 
                // Special handling for pending payment with rejected status
                if ($appt['status'] == 'pending_payment' && isset($appt['payment_status']) && $appt['payment_status'] == 'rejected') {
                    $status_text = 'Payment Rejected';
                    $status_class = 'danger';
                } else {
                    $status_text = ucfirst(str_replace('_', ' ', $appt['status']));
                    $status_class = $appt['status']=='completed' ? 'success' :
                        ($appt['status']=='cancelled' ? 'danger' :
                        ($appt['status']=='pending_payment' ? 'warning text-dark' : 
                        ($appt['status']=='rescheduled' ? 'warning text-dark' : 'primary')));
                }
                ?>
                <span class="badge bg-<?= $status_class ?>">
                    <?= $status_text ?>
                </span>
            </h5>
            <ul class="list-group mb-3">
                <li class="list-group-item"><b>Date & Time:</b> <?= date('d M Y, h:i A', strtotime($appt['scheduled_at'])) ?></li>
                <li class="list-group-item"><b>Beautician:</b> <?= htmlspecialchars($appt['beautician_name'] ?? 'Not yet assigned') ?><?php if (!empty($appt['beautician_phone'])) echo " (".htmlspecialchars($appt['beautician_phone']).")"; ?></li>
                <li class="list-group-item"><b>Service Price:</b> ৳<?= number_format($appt['service_price'],2) ?> (<?= $appt['duration_min'] ?> min)</li>
                <?php if (!empty($appt['notes'])): ?>
                <li class="list-group-item"><b>Notes:</b> <?= htmlspecialchars($appt['notes']) ?></li>
                <?php endif; ?>
                <!-- Payment Section -->
                 <!-- Payment Section -->
<?php if ($appt['payment_status'] === 'pending'): ?>
    <li class="list-group-item">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <b>Payment Status:</b> 
                <span class="badge bg-warning text-dark">Waiting Verification</span>
            </div>
        </div>
        <?php if ($appt['transaction_id']): ?>
        <small class="text-muted d-block mt-2">
            <?= strtoupper($appt['online_method']) ?> Transaction ID: <?= htmlspecialchars($appt['transaction_id']) ?>
        </small>
        <small class="d-block mt-1">Your payment is being verified. This usually takes less than 24 hours.</small>
        <?php endif; ?>
    </li>
<?php elseif ($appt['payment_status'] === 'rejected'): ?>
    <li class="list-group-item">
        <div class="alert alert-danger mb-0">
            <h6 class="alert-heading">Payment Verification Failed</h6>
            <p class="mb-1">We could not verify your payment with the transaction ID provided.</p>
            <hr>
            <p class="mb-0">
                <a href="book.php?service_id=<?= $appt['service_id'] ?>" class="btn btn-success btn-sm">Book Again</a>
                <a href="#" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#contactModal">Contact Support</a>
            </p>
        </div>
    </li>
<?php elseif ($appt['bill_id']): ?>
    <li class="list-group-item">
        <b>Bill:</b>
        <span class="badge bg-success">Paid</span>
        <b>Amount:</b> ৳<?= number_format($appt['bill_amount'],2) ?>
        <span class="text-muted">(<?= date('d M Y, h:i A', strtotime($appt['payment_time'])) ?>)</span>
    </li>
<?php elseif ($appt['transaction_id'] && $appt['payment_status'] === 'approved'): ?>
    <li class="list-group-item">
        <b>Online Payment:</b>
        <span class="badge bg-success">Verified</span>
        <div class="mt-2 small">
            <b><?= strtoupper($appt['online_method']) ?> Transaction:</b> <?= htmlspecialchars($appt['transaction_id']) ?>
        </div>
    </li>
<?php else: ?>
    <li class="list-group-item">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <b>Payment:</b>
                <span class="badge bg-secondary">Not paid yet</span>
            </div>
            
            <!-- Show Pay Now button for unpaid appointments regardless of status -->
            <?php if (in_array($appt['status'], ['booked', 'completed', 'pending_payment'])): ?>
                <a href="pay_online.php?appointment_id=<?= $appt['id'] ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-credit-card me-1"></i> Pay Now
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Add a note for pending_payment status -->
        <?php if ($appt['status'] == 'pending_payment'): ?>
            <div class="small text-danger mt-1">
                <i class="fas fa-exclamation-circle"></i> Payment required to confirm this appointment
            </div>
        <?php endif; ?>
    </li>
<?php endif; ?>
               
            </ul>
            
            <!-- Action Buttons -->
            <div class="mb-2">
                <?php if ($is_upcoming_booked): ?>
                    <a href="edit_appointment.php?id=<?= $appt['id'] ?>" class="btn btn-outline-primary">Reschedule</a>
                    <a href="cancel_appointment.php?id=<?= $appt['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Cancel this appointment?')">Cancel</a>
                <?php endif; ?>
                
                <a href="my_appointments.php" class="btn btn-outline-secondary">Back to All Appointments</a>
            </div>
        </div>
    </div>
</div>

<!-- Contact Support Modal - For rejected payments -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="contactModalLabel">Contact Support</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>If you believe there's been an error with your payment verification, please contact our customer support:</p>
        <p><strong>Phone:</strong> 01XXXXXXXXX</p>
        <p><strong>Email:</strong> support@labonnoglamourworld.com</p>
        <p>Please mention your appointment ID: <strong><?= $appt_id ?></strong> and Transaction ID when contacting support.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php require_once 'include/footer.php'; ?>