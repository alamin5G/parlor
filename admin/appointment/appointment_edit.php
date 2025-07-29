<?php
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
    $employee_id = $_POST['employee_id'];
    $scheduled_at = $_POST['scheduled_at'];
    $status = $_POST['status'];

    // Basic validation
    if (empty($customer_id) || empty($service_id) || empty($employee_id) || empty($scheduled_at)) {
        $error_msg = "All fields are required.";
    } else {
        $stmt = $conn->prepare("UPDATE appointments SET customer_id = ?, service_id = ?, employee_id = ?, scheduled_at = ?, status = ? WHERE id = ?");
        $stmt->bind_param("iiissi", $customer_id, $service_id, $employee_id, $scheduled_at, $status, $appointment_id);

        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Appointment updated successfully!";
            header("Location: appointments.php");
            exit();
        } else {
            $error_msg = "Error updating appointment: " . $stmt->error;
        }
    }
}

// --- Fetch existing appointment data ---
$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ?");
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
                        <select id="employee_id" name="employee_id" class="form-select" required>
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
                            <option value="booked" <?php echo ($appointment['status'] == 'booked') ? 'selected' : ''; ?>>Booked</option>
                            <option value="completed" <?php echo ($appointment['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($appointment['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="rescheduled" <?php echo ($appointment['status'] == 'rescheduled') ? 'selected' : ''; ?>>Rescheduled</option>
                        </select>
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

<?php require_once '../include/footer.php'; ?>