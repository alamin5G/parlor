<?php
ob_start(); // Start output buffering

// filepath: c:\xampp\htdocs\parlor\admin\appointment\appointment_edit.php
require_once '../include/header.php';

// Check for appointment ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: appointments.php");
    exit();
}
$appointment_id = $_GET['id'];

// Handle form submission for update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $service_id = $_POST['service_id'];
    $employee_id = !empty($_POST['employee_id']) ? $_POST['employee_id'] : null;
    $scheduled_at = $_POST['scheduled_at'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes'] ?? '');

    // Basic validation
    if (empty($customer_id) || empty($service_id) || empty($scheduled_at)) {
        $error_msg = "Required fields cannot be empty.";
    } else {
        // For NULL employee_id, use different SQL binding
        if ($employee_id !== null) {
            $stmt = $conn->prepare("UPDATE appointments SET customer_id = ?, service_id = ?, employee_id = ?, scheduled_at = ?, status = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("iiisssi", $customer_id, $service_id, $employee_id, $scheduled_at, $status, $notes, $appointment_id);
        } else {
            $stmt = $conn->prepare("UPDATE appointments SET customer_id = ?, service_id = ?, employee_id = NULL, scheduled_at = ?, status = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("iisssi", $customer_id, $service_id, $scheduled_at, $status, $notes, $appointment_id);
        }

        if ($stmt->execute()) {
            // If employee is selected, ensure they're assigned to this service in employee_services table
            if ($employee_id !== null) {
                // Check if assignment already exists
                $check_stmt = $conn->prepare("SELECT id FROM employee_services WHERE employee_id = ? AND service_id = ?");
                $check_stmt->bind_param("ii", $employee_id, $service_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $check_stmt->close();
                
                // If no assignment exists, create it
                if ($check_result->num_rows === 0) {
                    $assign_stmt = $conn->prepare("INSERT INTO employee_services (employee_id, service_id) VALUES (?, ?)");
                    $assign_stmt->bind_param("ii", $employee_id, $service_id);
                    $assign_stmt->execute();
                    $assign_stmt->close();
                }
            }
            
            // Check if we're changing to completed status
            if ($status === 'completed') {
                // Your existing completed status handling...
            }
            
            $_SESSION['success_msg'] = "Appointment updated successfully!";
            header("Location: appointments.php");
            exit();
        } else {
            $error_msg = "Error updating appointment: " . $stmt->error;
        }
    }
}

// --- Fetch existing appointment data ---
$stmt = $conn->prepare("
    SELECT a.*, a.notes, op.status AS payment_status, op.transaction_id
    FROM appointments a
    LEFT JOIN online_payments op ON a.id = op.appointment_id
    WHERE a.id = ?
");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();

if (!$appointment) {
    // No appointment found, redirect
    header("Location: appointments.php");
    exit();
}

// --- Fetch data for dropdowns ---
$customers_result = $conn->query("SELECT id, name FROM users WHERE role = 'customer' ORDER BY name ASC");
$services_result = $conn->query("SELECT id, name, price FROM services ORDER BY name ASC");
$employees_result = $conn->query("SELECT e.id, u.name FROM employees e JOIN users u ON e.user_id = u.id ORDER BY u.name ASC");
?>

<div class="container-fluid">
    <h1 class="mb-4">Edit Appointment #<?php echo $appointment_id; ?></h1>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>
    
    <?php if ($appointment['status'] === 'pending_payment'): ?>
    <div class="alert alert-warning">
        <div class="d-flex">
            <div class="me-3">
                <i class="fas fa-exclamation-circle fa-2x"></i>
            </div>
            <div>
                <h5>This appointment is awaiting payment verification</h5>
                <p class="mb-0">Once the payment is verified, the appointment status will automatically change to 'booked'.</p>
                <p class="mb-0">To verify the payment, please go to the <a href="../online_payments.php" class="alert-link">Online Payments</a> section.</p>
                <?php if (isset($appointment['transaction_id'])): ?>
                <p class="mt-2 mb-0"><strong>Transaction ID:</strong> <?= htmlspecialchars($appointment['transaction_id']) ?></p>
                <p class="mt-1 mb-0"><strong>Payment Status:</strong> 
                    <?php if ($appointment['payment_status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                    <?php elseif ($appointment['payment_status'] === 'approved'): ?>
                        <span class="badge bg-success">Approved</span>
                    <?php elseif ($appointment['payment_status'] === 'rejected'): ?>
                        <span class="badge bg-danger">Rejected</span>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="appointment_edit.php?id=<?php echo $appointment_id; ?>" method="POST">
                <div class="row g-3">
                    <!-- Customer -->
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label">Customer</label>
                        <select id="customer_id" name="customer_id" class="form-select" required>
                            <?php while ($customer = $customers_result->fetch_assoc()): ?>
                                <option value="<?php echo $customer['id']; ?>" <?php echo ($appointment['customer_id'] == $customer['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Service -->
                    <div class="col-md-6">
                        <label for="service_id" class="form-label">Service</label>
                        <select id="service_id" name="service_id" class="form-select" required>
                            <?php while ($service = $services_result->fetch_assoc()): ?>
                                <option value="<?php echo $service['id']; ?>" <?php echo ($appointment['service_id'] == $service['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['name']) . ' (৳' . number_format($service['price']) . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Employee -->
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Beautician</label>
                        <select id="employee_id" name="employee_id" class="form-select">
                            <option value="">Not Assigned</option>
                            <?php while ($employee = $employees_result->fetch_assoc()): ?>
                                <option value="<?php echo $employee['id']; ?>" <?php echo ($appointment['employee_id'] == $employee['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($employee['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Date and Time -->
                    <div class="col-md-6">
                        <label for="scheduled_at" class="form-label">Date and Time</label>
                        <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($appointment['scheduled_at'])); ?>" required>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="pending_payment" <?php echo ($appointment['status'] == 'pending_payment') ? 'selected' : ''; ?>>Pending Payment</option>
                            <option value="booked" <?php echo ($appointment['status'] == 'booked') ? 'selected' : ''; ?>>Booked</option>
                            <option value="completed" <?php echo ($appointment['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($appointment['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="rescheduled" <?php echo ($appointment['status'] == 'rescheduled') ? 'selected' : ''; ?>>Rescheduled</option>
                        </select>
                    </div>
                    
                    <!-- Notes -->
                    <div class="col-md-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($appointment['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update Appointment</button>
                    <a href="appointments.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../include/footer.php'; 
// End output buffering and flush the content
ob_end_flush();
?>