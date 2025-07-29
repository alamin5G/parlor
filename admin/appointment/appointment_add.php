<?php
require_once '../include/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $service_id = $_POST['service_id'];
    $employee_id = $_POST['employee_id'];
    $scheduled_at = $_POST['scheduled_at'];
    $status = $_POST['status']; // e.g., 'booked'

    // Basic validation
    if (empty($customer_id) || empty($service_id) || empty($employee_id) || empty($scheduled_at)) {
        $error_msg = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO appointments (customer_id, service_id, employee_id, scheduled_at, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $customer_id, $service_id, $employee_id, $scheduled_at, $status);

        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Appointment created successfully!";
            header("Location: appointments.php");
            exit();
        } else {
            $error_msg = "Error creating appointment: " . $stmt->error;
        }
    }
}

// --- Fetch data for dropdowns ---
// 1. Fetch Customers
$customers_result = $conn->query("SELECT id, name FROM users WHERE role = 'customer' ORDER BY name ASC");
// 2. Fetch Services
$services_result = $conn->query("SELECT id, name, price FROM services ORDER BY name ASC");
// 3. Fetch Employees (Beauticians)
$employees_result = $conn->query("SELECT e.id, u.name FROM employees e JOIN users u ON e.user_id = u.id ORDER BY u.name ASC");
?>

<div class="container-fluid">
    <h1 class="mb-4">Create New Appointment</h1>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="appointment_add.php" method="POST">
                <div class="row g-3">
                    <!-- Customer -->
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label">Customer</label>
                        <select id="customer_id" name="customer_id" class="form-select" required>
                            <option value="">Select a Customer</option>
                            <?php while ($customer = $customers_result->fetch_assoc()): ?>
                                <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Service -->
                    <div class="col-md-6">
                        <label for="service_id" class="form-label">Service</label>
                        <select id="service_id" name="service_id" class="form-select" required>
                            <option value="">Select a Service</option>
                            <?php while ($service = $services_result->fetch_assoc()): ?>
                                <option value="<?php echo $service['id']; ?>">
                                    <?php echo htmlspecialchars($service['name']) . ' (৳' . number_format($service['price']) . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Employee -->
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Beautician</label>
                        <select id="employee_id" name="employee_id" class="form-select" required>
                            <option value="">Select a Beautician</option>
                            <?php while ($employee = $employees_result->fetch_assoc()): ?>
                                <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Date and Time -->
                    <div class="col-md-6">
                        <label for="scheduled_at" class="form-label">Date and Time</label>
                        <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control" required>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="booked" selected>Booked</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Create Appointment</button>
                    <a href="appointments.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../include/footer.php'; ?>