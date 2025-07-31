<?php
// filepath: c:\xampp\htdocs\parlor\admin\verify_payment.php

// Start session and include necessary files
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'include/header.php';
require_once '../includes/db_connect.php';
require_once '../includes/email_functions.php';

// Get payment ID from URL parameter
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle payment verification actions
$success_msg = '';
$error_msg = '';

// Modify the payment verification section in the POST handler:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $note = isset($_POST['rejection_note']) ? trim($_POST['rejection_note']) : '';
    
    if ($action == 'approve' || $action == 'reject') {
        try {
            $conn->begin_transaction();
            
            // 1. Get the payment and appointment details first
            $stmt = $conn->prepare("SELECT op.*, 
                                    a.status as appointment_status, a.id as appointment_id, a.scheduled_at,
                                    s.name as service_name, s.price,
                                    u.name as customer_name, u.email as customer_email
                                    FROM online_payments op 
                                    JOIN appointments a ON op.appointment_id = a.id 
                                    JOIN services s ON a.service_id = s.id
                                    JOIN users u ON a.customer_id = u.id
                                    WHERE op.id = ?");
            $stmt->bind_param('i', $payment_id);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$payment) {
                throw new Exception("Payment record not found");
            }
            
            // 2. Update the payment status
            $payment_status = $action == 'approve' ? 'approved' : 'rejected';
            $stmt = $conn->prepare("UPDATE online_payments SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $payment_status, $payment_id);
            $stmt->execute();
            $stmt->close();
            
            // 3. If approved, update appointment status to 'booked'
            //    If rejected, keep it as 'pending_payment'
            if ($action == 'approve') {
                $stmt = $conn->prepare("UPDATE appointments SET status = 'booked' WHERE id = ?");
                $stmt->bind_param('i', $payment['appointment_id']);
                $stmt->execute();
                $stmt->close();
            }
            
            // 4. Add rejection note if provided
            if ($action == 'reject' && !empty($note)) {
                $stmt = $conn->prepare("UPDATE appointments SET notes = CONCAT(IFNULL(notes, ''), '\n[PAYMENT REJECTED]: ', ?) WHERE id = ?");
                $stmt->bind_param('si', $note, $payment['appointment_id']);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->commit();
            
            // 5. Send email notification to customer
            $email_result = send_payment_status_email(
                $payment['customer_email'],
                $payment['customer_name'],
                $payment_id,
                $payment['amount'],
                $payment['method'],
                $payment['transaction_id'],
                $payment_status,
                $payment['appointment_id'],
                $payment['service_name'],
                $payment['scheduled_at'],
                $note
            );
            
            $success_msg = "Payment has been " . ($action == 'approve' ? 'approved' : 'rejected') . " successfully.";
            
            if ($email_result['success']) {
                $success_msg .= " Customer has been notified via email.";
            } else {
                $success_msg .= " However, the email notification failed: " . $email_result['message'];
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

// Fetch payment details
$stmt = $conn->prepare("
    SELECT op.*, 
           a.scheduled_at, a.status as appointment_status, a.id as appointment_id, a.notes,
           s.name as service_name, s.price, 
           u.name as customer_name, u.phone as customer_phone
    FROM online_payments op
    JOIN appointments a ON op.appointment_id = a.id
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.customer_id = u.id
    WHERE op.id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If payment not found, redirect to all payments
if (!$payment) {
    echo "<div class='alert alert-danger'>Payment not found.</div>";
    echo "<a href='online_payments.php' class='btn btn-primary'>View All Payments</a>";
    require_once 'include/footer.php';
    exit;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Verify Payment</h1>
        <div>
            <a href="appointment/appointment_view.php?id=<?= $payment['appointment_id'] ?>" class="btn btn-outline-primary">
                <i class="fas fa-calendar me-1"></i> View Appointment
            </a>
            <a href="online_payments.php" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-list me-1"></i> All Payments
            </a>
        </div>
    </div>
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success">
            <h5><i class="fas fa-check-circle me-2"></i>Success</h5>
            <p class="mb-0"><?= $success_msg ?></p>
            <hr>
            <div class="d-flex justify-content-between">
                <a href="online_payments.php" class="btn btn-outline-success">View All Payments</a>
                <a href="appointment/appointment_view.php?id=<?= $payment['appointment_id'] ?>" class="btn btn-primary">
                    View Appointment Details
                </a>
            </div>
        </div>
    <?php elseif ($error_msg): ?>
        <div class="alert alert-danger"><?= $error_msg ?></div>
    <?php endif; ?>
    
    <?php if (!$success_msg): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-credit-card me-2"></i>
                Payment #<?= $payment['id'] ?>
                
                <?php if ($payment['status'] == 'pending'): ?>
                    <span class="badge bg-warning text-dark">Pending</span>
                <?php elseif ($payment['status'] == 'approved'): ?>
                    <span class="badge bg-success">Approved</span>
                <?php elseif ($payment['status'] == 'rejected'): ?>
                    <span class="badge bg-danger">Rejected</span>
                <?php endif; ?>
            </h5>
            <span class="text-muted small">
                <?= date('d M Y, h:i A', strtotime($payment['submitted_at'])) ?>
            </span>
        </div>
        
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Customer Information</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($payment['customer_name']) ?></p>
                            <p class="mb-0"><strong>Phone:</strong> <?= htmlspecialchars($payment['customer_phone']) ?></p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Appointment Details</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Service:</strong> <?= htmlspecialchars($payment['service_name']) ?></p>
                            <p class="mb-1"><strong>Price:</strong> <span class="text-success">৳<?= number_format($payment['price'], 2) ?></span></p>
                            <p class="mb-1">
                                <strong>Date & Time:</strong> 
                                <?= date('d M Y, h:i A', strtotime($payment['scheduled_at'])) ?>
                            </p>
                            <p class="mb-0">
                                <strong>Status:</strong> 
                                <span class="badge <?= $payment['appointment_status'] == 'pending_payment' ? 'bg-warning text-dark' : 'bg-primary' ?>">
                                    <?= ucfirst($payment['appointment_status']) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Payment Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <p class="mb-0"><strong>Amount:</strong></p>
                                <p class="mb-0 text-success fw-bold">৳<?= number_format($payment['amount'], 2) ?></p>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <p class="mb-0"><strong>Payment Method:</strong></p>
                                <p class="mb-0">
                                    <span class="badge bg-info"><?= strtoupper($payment['method']) ?></span>
                                </p>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <p class="mb-0"><strong>Transaction ID:</strong></p>
                                <p class="mb-0 text-primary font-monospace"><?= htmlspecialchars($payment['transaction_id']) ?></p>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-0">
                                <p class="mb-0"><strong>Submitted:</strong></p>
                                <p class="mb-0 text-muted"><?= date('d M Y, h:i A', strtotime($payment['submitted_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($payment['notes'])): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Notes</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><?= nl2br(htmlspecialchars($payment['notes'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($payment['status'] == 'pending'): ?>
            <div class="card border-warning mt-4">
                <div class="card-header bg-warning bg-opacity-10">
                    <h5 class="mb-0">Payment Verification</h5>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i> 
                        Verify this payment by checking the transaction ID with your payment provider's account.
                    </div>
                    
                    <!-- Verification Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#approvePaymentModal">
                            <i class="fas fa-check-circle me-1"></i> Approve Payment
                        </button>
                        <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#rejectPaymentModal">
                            <i class="fas fa-times-circle me-1"></i> Reject Payment
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($payment['status'] == 'pending'): ?>
<!-- Approve Payment Modal -->
<div class="modal fade" id="approvePaymentModal" tabindex="-1" aria-labelledby="approvePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approvePaymentModalLabel">
                    <i class="fas fa-check-circle me-1"></i> Approve Payment #<?= $payment['id'] ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="alert alert-success">
                        <h5><i class="fas fa-info-circle me-1"></i> Confirmation Required</h5>
                        <p class="mb-0">You are about to approve this payment. This will confirm the appointment and notify the customer.</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Payment Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Amount:</strong></td>
                                    <td class="text-end text-success fw-bold">৳<?= number_format($payment['amount'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Method:</strong></td>
                                    <td class="text-end"><?= strtoupper($payment['method']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Transaction ID:</strong></td>
                                    <td class="text-end text-primary"><?= htmlspecialchars($payment['transaction_id']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Date:</strong></td>
                                    <td class="text-end"><?= date('d M Y, h:i A', strtotime($payment['submitted_at'])) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Appointment Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Customer:</strong></td>
                                    <td class="text-end"><?= htmlspecialchars($payment['customer_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Service:</strong></td>
                                    <td class="text-end"><?= htmlspecialchars($payment['service_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Scheduled:</strong></td>
                                    <td class="text-end"><?= date('d M Y, h:i A', strtotime($payment['scheduled_at'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i> Please ensure that you have verified the transaction details with your payment provider before approving.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="action" value="approve" class="btn btn-success">
                        <i class="fas fa-check-circle me-1"></i> Confirm Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Payment Modal -->
<div class="modal fade" id="rejectPaymentModal" tabindex="-1" aria-labelledby="rejectPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectPaymentModalLabel">
                    <i class="fas fa-times-circle me-1"></i> Reject Payment #<?= $payment['id'] ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle me-1"></i> Warning</h5>
                        <p class="mb-0">You are about to reject this payment. This action cannot be undone and the customer will be notified.</p>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold mb-2">Payment Summary</h6>
                        <p><strong>Transaction ID:</strong> <?= htmlspecialchars($payment['transaction_id']) ?></p>
                        <p><strong>Amount:</strong> ৳<?= number_format($payment['amount'], 2) ?></p>
                        <p><strong>Method:</strong> <?= strtoupper($payment['method']) ?></p>
                        <p><strong>Customer:</strong> <?= htmlspecialchars($payment['customer_name']) ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rejection_note" class="form-label">
                            <strong>Rejection Reason</strong> <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="rejection_note" name="rejection_note" rows="4" placeholder="Please provide a reason for rejection..." required></textarea>
                        <div class="form-text">This reason will be included in the notification sent to the customer.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i> Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'include/footer.php'; ?>