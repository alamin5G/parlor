<?php
// filepath: c:\xampp\htdocs\parlor\admin\appointment\appointment_view.php

// Start session and include necessary files
require_once '../include/header.php';
require_once '../../includes/db_connect.php';
require_once '../../includes/email_functions.php';


// Get appointment ID from URL parameter
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle status update
// Modify the status update section in the POST handler:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['status_action'])) {
        $new_status = $_POST['status_action'];
        $old_status = ''; // Will store the previous status
        $valid_statuses = ['booked', 'completed', 'cancelled', 'rescheduled', 'pending_payment'];
        
        if (in_array($new_status, $valid_statuses)) {
            // Get the current status before updating
            $stmt = $conn->prepare("SELECT status FROM appointments WHERE id = ?");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $old_status = $row['status'];
            }
            $stmt->close();
            
            // Only proceed if status is actually changing
            if ($new_status != $old_status) {
                $update = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
                $update->bind_param("si", $new_status, $appointment_id);
                
                if ($update->execute()) {
                    $success_msg = "Appointment status updated to " . ucfirst($new_status);
                    
                    // Fetch appointment details for the email
                    $stmt = $conn->prepare("
                        SELECT a.*, s.name as service_name, 
                               u.name as customer_name, u.email as customer_email
                        FROM appointments a
                        JOIN services s ON a.service_id = s.id
                        JOIN users u ON a.customer_id = u.id
                        WHERE a.id = ?
                    ");
                    $stmt->bind_param("i", $appointment_id);
                    $stmt->execute();
                    $appt_details = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    // Send email notification to the customer
                    if ($appt_details) {
                        $notes = isset($_POST['status_notes']) ? $_POST['status_notes'] : '';
                        $email_result = send_appointment_status_email(
                            $appt_details['customer_email'],
                            $appt_details['customer_name'],
                            $appointment_id,
                            $appt_details['service_name'],
                            $appt_details['scheduled_at'],
                            $new_status,
                            $notes
                        );
                        
                        if ($email_result['success']) {
                            $success_msg .= " and customer has been notified via email.";
                        } else {
                            $success_msg .= " but email notification failed: " . $email_result['message'];
                        }
                    }
                } else {
                    $error_msg = "Error updating status: " . $conn->error;
                }
                $update->close();
            } else {
                $success_msg = "Status remains unchanged: " . ucfirst($new_status);
            }
        }
    }
}


// Fetch appointment details
$stmt = $conn->prepare("
    SELECT a.*, 
           s.name as service_name, s.price, s.duration_min, 
           u_cust.name as customer_name, u_cust.phone as customer_phone, u_cust.email as customer_email,
           e.id as employee_id,
           u_emp.name as employee_name, u_emp.phone as employee_phone,
           op.id as payment_id, op.method as payment_method, op.transaction_id, op.amount as payment_amount, op.status as payment_status, op.submitted_at as payment_time,
           b.id as bill_id, b.amount as bill_amount, b.payment_mode, b.payment_time as bill_payment_time
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u_cust ON a.customer_id = u_cust.id
    LEFT JOIN employees e ON a.employee_id = e.id
    LEFT JOIN users u_emp ON e.user_id = u_emp.id
    LEFT JOIN online_payments op ON a.id = op.appointment_id
    LEFT JOIN bills b ON a.id = b.appointment_id
    WHERE a.id = ?
");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If appointment not found, show error
if (!$appt) {
    echo "<div class='alert alert-danger'>Appointment not found.</div>";
    require_once '../include/footer.php';
    exit;
}

// Get notes/messages related to this appointment
$notes = [];
// You could fetch notes from a separate table if you have one

// Get all status options for dropdown
$status_options = [
    'pending_payment' => 'Pending Payment',
    'booked' => 'Booked',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'rescheduled' => 'Rescheduled'
];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Appointment Details</h1>
        <div>
            <a href="appointments.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Appointments
            </a>
        </div>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Left Column: Appointment Details -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Appointment #<?php echo $appointment_id; ?></h5>
                    
                    <?php 
                    $badge_class = '';
                    switch($appt['status']) {
                        case 'pending_payment': $badge_class = 'bg-warning text-dark'; break;
                        case 'booked': $badge_class = 'bg-primary'; break;
                        case 'completed': $badge_class = 'bg-success'; break;
                        case 'cancelled': $badge_class = 'bg-danger'; break;
                        case 'rescheduled': $badge_class = 'bg-info'; break;
                        default: $badge_class = 'bg-secondary';
                    }
                    ?>
                    <span class="badge <?php echo $badge_class; ?>">
                        <?php echo ucfirst($appt['status']); ?>
                    </span>
                </div>
                
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Service</h6>
                            <p class="mb-1"><?php echo htmlspecialchars($appt['service_name']); ?></p>
                            <p class="mb-0">
                                <span class="text-success">৳<?php echo number_format($appt['price'], 2); ?></span>
                                <span class="text-muted ms-2">(<?php echo $appt['duration_min']; ?> min)</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Schedule</h6>
                            <p class="mb-1">
                                <i class="far fa-calendar-alt me-1"></i>
                                <?php echo date('d M Y', strtotime($appt['scheduled_at'])); ?>
                            </p>
                            <p class="mb-0">
                                <i class="far fa-clock me-1"></i>
                                <?php echo date('h:i A', strtotime($appt['scheduled_at'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Customer</h6>
                            <p class="mb-1"><?php echo htmlspecialchars($appt['customer_name']); ?></p>
                            <p class="mb-1">
                                <i class="fas fa-phone-alt me-1 text-muted"></i>
                                <?php echo htmlspecialchars($appt['customer_phone']); ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-envelope me-1 text-muted"></i>
                                <?php echo htmlspecialchars($appt['customer_email']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Beautician</h6>
                            <?php if ($appt['employee_name']): ?>
                                <p class="mb-1"><?php echo htmlspecialchars($appt['employee_name']); ?></p>
                                <p class="mb-0">
                                    <i class="fas fa-phone-alt me-1 text-muted"></i>
                                    <?php echo htmlspecialchars($appt['employee_phone']); ?>
                                </p>
                            <?php else: ?>
                                <p class="text-muted">Not assigned</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Notes</h6>
                        <?php if (!empty($appt['notes'])): ?>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($appt['notes'])); ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No notes available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Payment and Actions -->
        <div class="col-lg-4">
            <!-- Payment Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment</h5>
                </div>
                
                <div class="card-body">
                    <?php if ($appt['bill_id']): ?>
                        <div class="alert alert-success mb-3">
                            <h6 class="mb-1"><i class="fas fa-check-circle me-1"></i> Paid by Bill</h6>
                            <p class="mb-1">Amount: <strong>৳<?php echo number_format($appt['bill_amount'], 2); ?></strong></p>
                            <p class="mb-0">
                                Method: <?php echo ucfirst($appt['payment_mode']); ?>
                                <span class="ms-2 text-muted small">
                                    (<?php echo date('d M Y, h:i A', strtotime($appt['bill_payment_time'])); ?>)
                                </span>
                            </p>
                        </div>
                    <?php elseif ($appt['payment_id']): ?>
                        <div class="card bg-light mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Online Payment</h6>
                                    <?php if ($appt['payment_status'] == 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending Verification</span>
                                    <?php elseif ($appt['payment_status'] == 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($appt['payment_status'] == 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="mb-1">
                                    <strong>Amount:</strong> ৳<?php echo number_format($appt['payment_amount'], 2); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Method:</strong> <?php echo strtoupper($appt['payment_method']); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Transaction ID:</strong> <?php echo htmlspecialchars($appt['transaction_id']); ?>
                                </p>
                                <p class="mb-0 text-muted small">
                                    Submitted: <?php echo date('d M Y, h:i A', strtotime($appt['payment_time'])); ?>
                                </p>
                                
                                <?php if ($appt['payment_status'] == 'pending'): ?>
                                    <hr>
                                    <a href="../verify_payment.php?id=<?php echo $appt['payment_id']; ?>" class="btn btn-warning btn-sm w-100">
                                        <i class="fas fa-check-circle me-1"></i> Verify Payment
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-3">
                            <p class="mb-0">No payment record found.</p>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <a href="../generate_bill.php?appointment_id=<?php echo $appointment_id; ?>" class="btn btn-primary">
                                <i class="fas fa-file-invoice-dollar me-1"></i> Generate Bill
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Status and Actions -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Actions</h5>
                </div>
                
                <div class="card-body">
                    <!-- Status Update Form -->
                    <form method="post">
                        <div class="mb-3">
                            <label for="status_action" class="form-label">Update Status</label>
                            <select name="status_action" id="status_action" class="form-select">
                                <?php foreach ($status_options as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo ($appt['status'] == $value) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to update the status?')">
                                <i class="fas fa-save me-1"></i> Update Status
                            </button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <a href="appointment_edit.php?id=<?php echo $appointment_id; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-edit me-1"></i> Edit Appointment
                        </a>
                        <a href="appointment_reschedule.php?id=<?php echo $appointment_id; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-calendar-alt me-1"></i> Reschedule
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../include/footer.php'; ?>