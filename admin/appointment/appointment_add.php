<?php
// filepath: c:\xampp\htdocs\parlor\admin\appointment\appointment_add.php
// Start session and include necessary files
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Start output buffering to prevent header issues
ob_start();

require_once '../../includes/db_connect.php';
require_once '../../includes/email_functions.php';

// Process form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    // Fix: Properly handle empty employee selection
    $employee_id = (isset($_POST['employee_id']) && !empty($_POST['employee_id'])) ? intval($_POST['employee_id']) : null;
    $scheduled_at = isset($_POST['scheduled_at']) ? trim($_POST['scheduled_at']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'booked';
    
    // Validate required fields
    if (!$customer_id || !$service_id || !$scheduled_at) {
        $message = "Customer, service and appointment time are required.";
        $message_type = "danger";
    } else {
        // Insert appointment
        try {
            $conn->begin_transaction();
            
            // Debug: Check if selected employee exists (if one is selected)
            if ($employee_id !== null) {
                $check_employee = $conn->prepare("SELECT id FROM employees WHERE id = ?");
                $check_employee->bind_param("i", $employee_id);
                $check_employee->execute();
                $employee_exists = $check_employee->get_result()->num_rows > 0;
                $check_employee->close();
                
                if (!$employee_exists) {
                    throw new Exception("Selected employee ID ($employee_id) does not exist in the employees table.");
                }
            }
            
            // Fix: Use the proper SQL statement based on whether employee_id is NULL or not
            if ($employee_id !== null) {
                $stmt = $conn->prepare("INSERT INTO appointments (customer_id, service_id, employee_id, scheduled_at, notes, status, created_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("iiisss", $customer_id, $service_id, $employee_id, $scheduled_at, $notes, $status);
            } else {
                $stmt = $conn->prepare("INSERT INTO appointments (customer_id, service_id, employee_id, scheduled_at, notes, status, created_at) 
                                      VALUES (?, ?, NULL, ?, ?, ?, NOW())");
                $stmt->bind_param("iisss", $customer_id, $service_id, $scheduled_at, $notes, $status);
            }
            
            $result = $stmt->execute();
            $new_appointment_id = $conn->insert_id;
            $stmt->close();
            
            if (!$result || !$new_appointment_id) {
                throw new Exception("Failed to create appointment.");
            }
            
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
            
            // Get customer, service and employee details for the email
            $details_stmt = $conn->prepare("
                SELECT u.name as customer_name, u.email as customer_email,
                       s.name as service_name, s.price,
                       e.id as beautician_id,
                       eu.name as beautician_name
                FROM users u
                JOIN services s ON s.id = ?
                LEFT JOIN employees e ON e.id = ?
                LEFT JOIN users eu ON e.user_id = eu.id
                WHERE u.id = ?
            ");
            
            // Fix: Handle NULL employee_id in the prepared statement
            if ($employee_id === null) {
                $temp_employee_id = 0; // Use a dummy value that won't match any ID
                $details_stmt->bind_param("iii", $service_id, $temp_employee_id, $customer_id);
            } else {
                $details_stmt->bind_param("iii", $service_id, $employee_id, $customer_id);
            }
            
            $details_stmt->execute();
            $details = $details_stmt->get_result()->fetch_assoc();
            $details_stmt->close();
            
            $conn->commit();
            
            // Send email notification to the customer
            if ($details) {
                // Fix: Handle case where beautician_name might be NULL
                $beautician_name = isset($details['beautician_name']) ? $details['beautician_name'] : null;
                
                $email_result = send_new_appointment_email(
                    $details['customer_email'],
                    $details['customer_name'],
                    $new_appointment_id,
                    $details['service_name'],
                    $scheduled_at,
                    $beautician_name,
                    $details['price'],
                    $status
                );
                
                $_SESSION['success_message'] = "Appointment created successfully!";
                if ($email_result['success']) {
                    $_SESSION['success_message'] .= " Confirmation email sent to customer.";
                } else {
                    $_SESSION['success_message'] .= " However, the email notification failed: " . $email_result['message'];
                }
            } else {
                $_SESSION['success_message'] = "Appointment created successfully!";
            }
            
            header("Location: appointments.php");
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Set up page
$page_title = "Add New Appointment";
require_once '../include/header.php';

// Get customers, services and employees for dropdown
// Fix: Only get active customers and employees
$customers = $conn->query("SELECT id, name FROM users WHERE role = 'customer' AND is_active = 1 ORDER BY name");
$services = $conn->query("SELECT id, name, price, duration_min FROM services ORDER BY name");
$employees = $conn->query("SELECT e.id, u.name FROM employees e 
                          JOIN users u ON e.user_id = u.id 
                          WHERE u.is_active = 1 AND e.status = 'active' 
                          ORDER BY u.name");

// Initialize the beautician dropdown data
$beauticians_by_service = [];
$stmt = $conn->prepare("SELECT es.service_id, e.id as employee_id, u.name as employee_name 
                        FROM employee_services es
                        JOIN employees e ON es.employee_id = e.id
                        JOIN users u ON e.user_id = u.id
                        WHERE e.status = 'active' AND u.is_active = 1");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (!isset($beauticians_by_service[$row['service_id']])) {
        $beauticians_by_service[$row['service_id']] = [];
    }
    $beauticians_by_service[$row['service_id']][] = [
        'id' => $row['employee_id'],
        'name' => $row['employee_name']
    ];
}
$stmt->close();
?>

<div class="container-fluid">
    <h1 class="mt-4 mb-4">Add New Appointment</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-header">
            <i class="fas fa-calendar-plus me-1"></i> Appointment Details
        </div>
        <div class="card-body">
            <form method="post" id="appointmentForm">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" class="form-select" required>
                            <option value="">-- Select Customer --</option>
                            <?php while ($customer = $customers->fetch_assoc()): ?>
                                <option value="<?php echo $customer['id']; ?>">
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="service_id" class="form-label">Service <span class="text-danger">*</span></label>
                        <select name="service_id" id="service_id" class="form-select" required>
                            <option value="">-- Select Service --</option>
                            <?php while ($service = $services->fetch_assoc()): ?>
                                <option value="<?php echo $service['id']; ?>" data-duration="<?php echo $service['duration_min']; ?>" data-price="<?php echo $service['price']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> 
                                    (৳<?php echo number_format($service['price'], 2); ?>, <?php echo $service['duration_min']; ?> min)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Beautician</label>
                        <select name="employee_id" id="employee_id" class="form-select">
                            <option value="">-- Any Available --</option>
                            <?php while ($employee = $employees->fetch_assoc()): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="form-text" id="employee-info">
                            <small class="text-muted">When you assign a beautician to this service, they'll be added to the service specialists list.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="scheduled_at" class="form-label">Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="booked">Booked</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="pending_payment">Pending Payment</option>
                        </select>
                        <div class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> 
                            <small>If you select "Booked", the customer will be able to pay online after appointment creation.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Create Appointment
                    </button>
                    <a href="appointments.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Date/Time Picker Enhancement -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Store beauticians by service
    const beauticians = <?php echo json_encode($beauticians_by_service); ?>;
    
    // Set minimum date to today
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    const today = `${year}-${month}-${day}T${hours}:${minutes}`;
    document.getElementById('scheduled_at').setAttribute('min', today);
    
    // Update employee dropdown based on service selection
    const serviceSelect = document.getElementById('service_id');
    const employeeSelect = document.getElementById('employee_id');
    const employeeInfo = document.getElementById('employee-info');
    
    serviceSelect.addEventListener('change', function() {
        const serviceId = this.value;
        
        // Store current selection
        const currentSelection = employeeSelect.value;
        
        // Reset employee dropdown
        employeeSelect.innerHTML = '<option value="">-- Any Available --</option>';
        
        if (serviceId && beauticians[serviceId] && beauticians[serviceId].length > 0) {
            // Add specialists for this service
            const specialists = beauticians[serviceId];
            const specialistGroup = document.createElement('optgroup');
            specialistGroup.label = 'Service Specialists';
            
            specialists.forEach(function(specialist) {
                const option = document.createElement('option');
                option.value = specialist.id;
                option.textContent = specialist.name;
                specialistGroup.appendChild(option);
            });
            
            employeeSelect.appendChild(specialistGroup);
            employeeInfo.innerHTML = '<small class="text-success">✓ ' + specialists.length + ' beauticians are trained for this service</small>';
        } else {
            // Add all employees without grouping
            <?php 
            $employees->data_seek(0); // Reset the result pointer
            ?>
            <?php while ($employee = $employees->fetch_assoc()): ?>
                const option = document.createElement('option');
                option.value = "<?php echo $employee['id']; ?>";
                option.textContent = "<?php echo addslashes(htmlspecialchars($employee['name'])); ?>";
                employeeSelect.appendChild(option);
            <?php endwhile; ?>
            
            employeeInfo.innerHTML = '<small class="text-muted">When you assign a beautician to this service, they\'ll be added to the service specialists list.</small>';
        }
        
        // Try to restore previous selection
        if (currentSelection) {
            const option = employeeSelect.querySelector(`option[value="${currentSelection}"]`);
            if (option) option.selected = true;
        }
    });
    
    // Helper function to check if a time slot is available
    function checkAvailability() {
        const scheduledAt = document.getElementById('scheduled_at').value;
        const serviceId = document.getElementById('service_id').value;
        const employeeId = document.getElementById('employee_id').value;
        
        if (scheduledAt && serviceId) {
            // Get service duration from the selected option
            const selectedOption = document.querySelector(`#service_id option[value="${serviceId}"]`);
            const duration = selectedOption ? selectedOption.getAttribute('data-duration') : 0;
            
            console.log(`Checking availability for service ${serviceId} (${duration} min) at ${scheduledAt}`);
            
            // You can implement an AJAX call to check availability
            // This is a placeholder for that functionality
            /* Uncomment when you have the check_availability.php file
            fetch(`check_availability.php?date=${scheduledAt}&service=${serviceId}&employee=${employeeId}&duration=${duration}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.available) {
                        alert('This time slot is not available. Please select another time.');
                    }
                });
            */
        }
    }
    
    // Add event listeners
    document.getElementById('scheduled_at').addEventListener('change', checkAvailability);
    document.getElementById('service_id').addEventListener('change', checkAvailability);
    document.getElementById('employee_id').addEventListener('change', checkAvailability);
});
</script>

<?php 
require_once '../include/footer.php'; 
// End output buffering
ob_end_flush();