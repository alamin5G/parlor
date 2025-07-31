<?php
    // filepath: c:\xampp\htdocs\parlor\user\pay_online.php
    // filepath: user/pay_online.php
    session_start();
    require_once '../includes/db_connect.php';

    if (! isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
        header("Location: /parlor/login.php");
        exit;
    }
    $page_title = "Pay for Appointment";
    require_once 'include/header.php';

    if (! isset($_GET['appointment_id']) || ! is_numeric($_GET['appointment_id'])) {
        echo "<div class='alert alert-danger'>Invalid appointment.</div>";
        require_once 'include/footer.php';exit;
    }
    $appointment_id = intval($_GET['appointment_id']);
    $customer_id    = $_SESSION['user_id'];

    // Fetch appointment/bill info
    $stmt = $conn->prepare("
    SELECT a.*, s.name as service_name, s.price
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    LEFT JOIN bills b ON a.id = b.appointment_id
    WHERE a.id = ? AND a.customer_id = ?
");
    $stmt->bind_param("ii", $appointment_id, $customer_id);
    $stmt->execute();
    $appt = $stmt->get_result()->fetch_assoc();
    $stmt->close();

   if (!$appt) {
    echo "<div class='alert alert-danger'>Appointment not found or does not belong to you.</div>";
    require_once 'include/footer.php';
    exit;
}

// Check if appointment status allows payment
if (!in_array($appt['status'], ['booked', 'completed', 'pending_payment'])) {
    echo "<div class='alert alert-warning'>
        <h5><i class='fas fa-exclamation-circle me-2'></i>Payment Not Available</h5>
        <p>This appointment is not eligible for online payment. Current status: " . ucfirst($appt['status']) . "</p>
        <a href='my_appointments.php' class='btn btn-primary'>View My Appointments</a>
    </div>";
    require_once 'include/footer.php';
    exit;
}

    // Check if already paid
    $bill_check = $conn->prepare("SELECT id FROM bills WHERE appointment_id = ?");
    $bill_check->bind_param("i", $appointment_id);
    $bill_check->execute();
    $bill_exists = $bill_check->get_result()->num_rows > 0;
    $bill_check->close();

    $payment_check = $conn->prepare("SELECT status FROM online_payments WHERE appointment_id = ?");
    $payment_check->bind_param("i", $appointment_id);
    $payment_check->execute();
    $payment_result = $payment_check->get_result();
    $online_payment = $payment_result->num_rows > 0 ? $payment_result->fetch_assoc() : null;
    $payment_check->close();

    // If bill exists or payment approved, payment is complete
    if ($bill_exists || ($online_payment && $online_payment['status'] === 'approved')) {
        echo "<div class='alert alert-success'>
            <h5><i class='fas fa-check-circle me-2'></i>Payment Already Completed</h5>
            <p>This appointment has already been paid for.</p>
            <a href='my_appointments.php' class='btn btn-primary'>View My Appointments</a>
          </div>";
        require_once 'include/footer.php';exit;
    }

    // If payment pending verification
    if ($online_payment && $online_payment['status'] === 'pending') {
        echo "<div class='alert alert-warning'>
            <h5><i class='fas fa-clock me-2'></i>Payment Being Verified</h5>
            <p>Your payment for this appointment is currently being verified. Please check back later.</p>
            <a href='my_appointments.php' class='btn btn-primary'>View My Appointments</a>
          </div>";
        require_once 'include/footer.php';exit;
    }

    // Handle payment form
    $success_msg = $error_msg = "";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $method         = $_POST['payment_method']; // Changed to match book.php
        $transaction_id = trim($_POST['transaction_id']);
        $valid_methods  = ['bkash', 'nagad', 'rocket']; // Added rocket to match book.php

        if (! in_array($method, $valid_methods) || ! $transaction_id) {
            $error_msg = "Payment method and transaction ID are required.";
        } else {
            // Begin transaction for database safety
            $conn->begin_transaction();

            try {
                // Insert the payment with pending status
                $stmt = $conn->prepare("INSERT INTO online_payments (appointment_id, amount, method, transaction_id, status)
                           VALUES (?, ?, ?, ?, 'pending')");
                $stmt->bind_param("idss", $appointment_id, $appt['price'], $method, $transaction_id);
                $stmt->execute();
                $stmt->close();

                // Update appointment status to pending_payment if it's currently booked
                if ($appt['status'] === 'booked') {
                    $update_stmt = $conn->prepare("UPDATE appointments SET status = 'pending_payment' WHERE id = ?");
                    $update_stmt->bind_param("i", $appointment_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }

                $conn->commit();

                // Redirect to appointments with success message
                header("Location: my_appointments.php?payment=pending");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error_msg = "Could not submit payment. Error: " . $e->getMessage();
            }
        }
    }
?>

<div class="container-fluid" style="max-width:700px;">
    <h2 class="mb-4 mt-3"><i class="fa fa-credit-card"></i> Online Payment</h2>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo $error_msg ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Pay for Service:                                              <?php echo htmlspecialchars($appt['service_name']) ?></h5>
        </div>

        <div class="card-body">
    <div class="mb-4">
        <h6>Appointment Details:</h6>
        <ul class="list-unstyled">
            <li><strong>Date:</strong>                                       <?php echo date('d M Y, h:i A', strtotime($appt['scheduled_at'])) ?></li>
            <li><strong>Status:</strong>
                <?php if ($appt['status'] == 'booked'): ?>
                    <span class="badge bg-primary">Booked</span>
                <?php elseif ($appt['status'] == 'completed'): ?>
                    <span class="badge bg-success">Completed</span>
                <?php else: ?>
                    <span class="badge bg-secondary"><?php echo ucfirst($appt['status']) ?></span>
                <?php endif; ?>
            </li>
            <li><strong>Amount Due:</strong> <span class="fs-5 text-success">৳<?php echo number_format($appt['price'], 2) ?></span></li>
        </ul>
    </div>

    <form method="post">
        <h5 class="mb-3">Payment Information</h5>

        <div class="alert alert-info">
            Please send the service fee to one of the numbers below and enter the Transaction ID to confirm.
            <br><b>bKash: (Personal) 01822679672 <br> Nagad: (Personal) 01822679672 <br> Rocket: (Personal) 01822679672</b>
            <br>Amount to Pay: <strong>৳<?php echo number_format($appt['price'], 2) ?></strong>
        </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="payment_method" class="form-select" required>
                            <option value="">Select Method</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="rocket">Rocket</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="transaction_id">Transaction ID (TrxID) <span class="text-danger">*</span></label>
                        <input type="text" id="transaction_id" name="transaction_id" class="form-control" required
                               placeholder="Enter the transaction ID from your payment app">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Submit Payment</button>
            </form>
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="my_appointments.php" class="btn btn-outline-secondary">Back to My Appointments</a>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>