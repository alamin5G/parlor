<?php
// filepath: c:\xampp\htdocs\parlor\admin\generate_bill.php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'include/header.php';
require_once '../includes/db_connect.php';

// Check if appointment ID is provided
if (!isset($_GET['appointment_id']) || !is_numeric($_GET['appointment_id'])) {
    echo '<div class="alert alert-danger">No appointment selected.</div>';
    echo '<div class="mt-3"><a href="appointment/appointments.php" class="btn btn-primary">Back to Appointments</a></div>';
    require_once 'include/footer.php';
    exit;
}

$appointment_id = intval($_GET['appointment_id']);

// Check if bill already exists
$check_sql = "SELECT id FROM bills WHERE appointment_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$existing_bill = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing_bill) {
    // Bill exists, redirect to view it
    header("Location: view_bill.php?id=" . $existing_bill['id']);
    exit;
}

// Get appointment details
$sql = "SELECT a.*, s.name AS service_name, s.price, 
               u.name AS customer_name, op.method AS payment_method
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        JOIN users u ON a.customer_id = u.id
        LEFT JOIN online_payments op ON a.id = op.appointment_id AND op.status = 'approved'
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    echo '<div class="alert alert-danger">Appointment not found.</div>';
    echo '<div class="mt-3"><a href="appointment/appointments.php" class="btn btn-primary">Back to Appointments</a></div>';
    require_once 'include/footer.php';
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $payment_mode = $_POST['payment_mode'];
    
    // Basic validation
    if ($amount <= 0) {
        $error_msg = "Amount must be greater than zero.";
    } else {
        $stmt = $conn->prepare("INSERT INTO bills (appointment_id, amount, payment_mode, payment_time) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("ids", $appointment_id, $amount, $payment_mode);
        
        if ($stmt->execute()) {
            $bill_id = $conn->insert_id;
            
            // If appointment status isn't already completed, mark it as completed
            if ($appointment['status'] !== 'completed') {
                $conn->query("UPDATE appointments SET status = 'completed' WHERE id = $appointment_id");
            }
            
            // Redirect to view the new bill
            header("Location: view_bill.php?id=$bill_id");
            exit;
        } else {
            $error_msg = "Error creating bill: " . $stmt->error;
        }
    }
}
?>

<div class="container-fluid" style="max-width: 800px;">
    <h1 class="mb-4">Generate Bill</h1>
    
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?= $error_msg ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Appointment Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Customer:</strong> <?= htmlspecialchars($appointment['customer_name']) ?></p>
                    <p><strong>Service:</strong> <?= htmlspecialchars($appointment['service_name']) ?></p>
                    <p><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($appointment['scheduled_at'])) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Status:</strong> 
                        <span class="badge <?= ($appointment['status'] === 'completed') ? 'bg-success' : 'bg-primary' ?>">
                            <?= ucfirst($appointment['status']) ?>
                        </span>
                    </p>
                    <p><strong>Service Price:</strong> ৳<?= number_format($appointment['price'], 2) ?></p>
                    <?php if (!empty($appointment['payment_method'])): ?>
                        <p><strong>Online Payment Method:</strong> <?= strtoupper($appointment['payment_method']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Bill Details</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="amount" class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="<?= $appointment['price'] ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="payment_mode" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_mode" name="payment_mode" required>
                            <?php if (!empty($appointment['payment_method'])): ?>
                                <option value="<?= $appointment['payment_method'] ?>" selected><?= strtoupper($appointment['payment_method']) ?> (Online)</option>
                            <?php endif; ?>
                            <option value="cash" <?= empty($appointment['payment_method']) ? 'selected' : '' ?>>Cash</option>
                            <option value="card">Card</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="rocket">Rocket</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Generate Bill</button>
                    <a href="appointment/appointments.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>
<?ob_end_flush(); // Flush output buffer to ensure all content is sent?>