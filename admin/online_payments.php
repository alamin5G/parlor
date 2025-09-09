<?php
// filepath: c:\xampp\htdocs\parlor\admin\online_payments.php

// Start session and include necessary files
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'include/header.php';
require_once '../includes/db_connect.php';

// Handle status update
$success_msg = '';
$error_msg = '';

if (isset($_POST['payment_id']) && isset($_POST['action'])) {
    $payment_id = intval($_POST['payment_id']);
    $action = $_POST['action'];
    $note = isset($_POST['rejection_note']) ? trim($_POST['rejection_note']) : '';
    
    if ($action == 'approve' || $action == 'reject') {
        try {
            $conn->begin_transaction();
            
            // 1. Get the payment and appointment details first
            $stmt = $conn->prepare("SELECT op.*, a.status as appointment_status, a.id as appointment_id 
                                    FROM online_payments op 
                                    JOIN appointments a ON op.appointment_id = a.id 
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
                // You could store this in a notes table, or add a rejection_note column to online_payments
                // For now, we'll assume you want to add this to the appointment notes
                $stmt = $conn->prepare("UPDATE appointments SET notes = CONCAT(IFNULL(notes, ''), '\n[PAYMENT REJECTED]: ', ?) WHERE id = ?");
                $stmt->bind_param('si', $note, $payment['appointment_id']);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->commit();
            $success_msg = "Payment has been " . ($action == 'approve' ? 'approved' : 'rejected') . " successfully.";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Prepare the query based on filters
$query = "
    SELECT op.*, 
           a.scheduled_at, a.status as appointment_status,
           s.name as service_name, s.price, 
           u.name as customer_name, u.phone as customer_phone
    FROM online_payments op
    JOIN appointments a ON op.appointment_id = a.id
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.customer_id = u.id
    WHERE op.status = ?
    ORDER BY op.submitted_at ASC";  // Changed from created_at to submitted_at

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $status_filter);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Online Payment Verification</h1>
    </div>
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= $success_msg ?></div>
    <?php endif; ?>
    
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?= $error_msg ?></div>
    <?php endif; ?>
    
    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $status_filter == 'pending' ? 'active' : '' ?>" href="?status=pending">
                Pending <span class="badge bg-warning text-dark"><?= count($payments) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter == 'approved' ? 'active' : '' ?>" href="?status=approved">
                Approved
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter == 'rejected' ? 'active' : '' ?>" href="?status=rejected">
                Rejected
            </a>
        </li>
    </ul>
    
    <?php if (empty($payments)): ?>
        <div class="alert alert-info">
            No <?= $status_filter ?> payments found.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($payments as $payment): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card <?= $payment['status'] == 'pending' ? 'border-warning' : '' ?>">
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
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Customer</h6>
                                    <p class="mb-0"><?= htmlspecialchars($payment['customer_name']) ?></p>
                                    <p class="mb-0 small"><?= htmlspecialchars($payment['customer_phone']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Service</h6>
                                    <p class="mb-0"><?= htmlspecialchars($payment['service_name']) ?></p>
                                    <p class="mb-0 small">
                                        <span class="text-success">৳<?= number_format($payment['price'], 2) ?></span>
                                        <span class="text-muted ms-2"><?= date('d M Y, h:i A', strtotime($payment['scheduled_at'])) ?></span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="bg-light p-3 rounded mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6 class="text-muted mb-1">Payment Method</h6>
                                        <p class="mb-0">
                                            <span class="badge bg-info"><?= strtoupper($payment['method']) ?></span>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-muted mb-1">Transaction ID</h6>
                                        <p class="mb-0 text-primary"><?= htmlspecialchars($payment['transaction_id']) ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-muted mb-1">Amount</h6>
                                        <p class="mb-0 text-success fw-bold">৳<?= number_format($payment['amount'], 2) ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($payment['status'] == 'pending'): ?>
                                <div class="d-flex justify-content-between mt-3">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?= $payment['id'] ?>">
                                        <i class="fas fa-check me-1"></i> Approve Payment
                                    </button>
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $payment['id'] ?>">
                                        <i class="fas fa-times me-1"></i> Reject
                                    </button>
                                </div>
                                
                                <!-- Approve Modal -->
                                <div class="modal fade" id="approveModal<?= $payment['id'] ?>" tabindex="-1" aria-labelledby="approveModalLabel<?= $payment['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title" id="approveModalLabel<?= $payment['id'] ?>">
                                                    <i class="fas fa-check-circle me-1"></i> Approve Payment
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="post">
                                                <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                                <div class="modal-body">
                                                    <div class="alert alert-success">
                                                        <i class="fas fa-info-circle me-1"></i> You are about to approve payment #<?= $payment['id'] ?>.
                                                    </div>
                                                    <p><strong>Transaction ID:</strong> <?= htmlspecialchars($payment['transaction_id']) ?></p>
                                                    <p><strong>Amount:</strong> ৳<?= number_format($payment['amount'], 2) ?></p>
                                                    <p class="mb-0"><strong>Method:</strong> <?= strtoupper($payment['method']) ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="action" value="approve" class="btn btn-success">
                                                        <i class="fas fa-check me-1"></i> Approve Payment
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal<?= $payment['id'] ?>" tabindex="-1" aria-labelledby="rejectModalLabel<?= $payment['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title" id="rejectModalLabel<?= $payment['id'] ?>">
                                                    <i class="fas fa-times-circle me-1"></i> Reject Payment
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="post">
                                                <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                                <div class="modal-body">
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle me-1"></i> You are about to reject payment #<?= $payment['id'] ?>.
                                                    </div>
                                                    <p><strong>Transaction ID:</strong> <?= htmlspecialchars($payment['transaction_id']) ?></p>
                                                    <p><strong>Amount:</strong> ৳<?= number_format($payment['amount'], 2) ?></p>
                                                    <p><strong>Method:</strong> <?= strtoupper($payment['method']) ?></p>
                                                    <div class="mb-3">
                                                        <label for="rejection_note<?= $payment['id'] ?>" class="form-label">Rejection Note (optional)</label>
                                                        <textarea class="form-control" id="rejection_note<?= $payment['id'] ?>" name="rejection_note" rows="3" placeholder="Reason for rejection..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger">
                                                        <i class="fas fa-times me-1"></i> Reject Payment
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer">
                            <a href="appointment/appointment_view.php?id=<?= $payment['appointment_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-calendar me-1"></i> View Appointment
                            </a>
                            <?php if ($payment['status'] == 'pending'): ?>
                                <a href="verify_payment.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-primary float-end">
                                    <i class="fas fa-search-dollar me-1"></i> Detailed Verification
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'include/footer.php'; ?>