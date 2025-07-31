<?php
// filepath: c:\xampp\htdocs\parlor\user\book.php
$page_title = "Book & Pay";
require_once 'include/header.php';

// FIX #1: Get the logged-in user's ID from the session.
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if user is not logged in
    header("Location: login.php");
    exit;
}
$customer_user_id = $_SESSION['user_id'];


$error_msg = '';
$preselected_service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

// Fetch all services for the dropdown
$services = $conn->query("SELECT id, name, price, duration_min FROM services ORDER BY name ASC");
$services_array = [];
if ($services) {
    $services_array = $services->fetch_all(MYSQLI_ASSOC);
}

// Handle booking & payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Form Data ---
    $service_id = intval($_POST['service_id']);
    $scheduled_at = trim($_POST['scheduled_at']);
    $employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : null;
    $notes = trim($_POST['notes']);
    $payment_method = $_POST['payment_method'];
    $transaction_id = trim($_POST['transaction_id']);
    $valid_methods = ['bkash', 'nagad', 'rocket'];

    // --- Validation ---
    if (!$service_id || !$scheduled_at || !$payment_method || !$transaction_id) {
        $error_msg = "All required fields must be filled, including payment details.";
    } elseif (strtotime($scheduled_at) <= time()) {
        $error_msg = "Please choose a future date and time.";
    } elseif (!in_array($payment_method, $valid_methods)) {
        $error_msg = "Invalid payment method selected.";
    } else {
        // --- Database Transaction ---
        $conn->begin_transaction();
        try {
            // 1. Create the appointment with 'pending_payment' status
            if ($employee_id) {
                $stmt_appt = $conn->prepare("INSERT INTO appointments (customer_id, service_id, employee_id, scheduled_at, status, notes) VALUES (?, ?, ?, ?, 'pending_payment', ?)");
                $stmt_appt->bind_param("iiiss", $customer_user_id, $service_id, $employee_id, $scheduled_at, $notes);
            } else {
                $stmt_appt = $conn->prepare("INSERT INTO appointments (customer_id, service_id, employee_id, scheduled_at, status, notes) VALUES (?, ?, NULL, ?, 'pending_payment', ?)");
                $stmt_appt->bind_param("iiss", $customer_user_id, $service_id, $scheduled_at, $notes);
            }
            
            $stmt_appt->execute();
            $new_appointment_id = $conn->insert_id;
            $stmt_appt->close();

            if (!$new_appointment_id) {
                throw new Exception("Failed to create appointment record.");
            }
            
            // If employee is selected, update employee_services table if needed
            if ($employee_id) {
                // Check if this employee-service relationship already exists
                $check = $conn->prepare("SELECT id FROM employee_services WHERE employee_id = ? AND service_id = ?");
                $check->bind_param("ii", $employee_id, $service_id);
                $check->execute();
                $check_result = $check->get_result();
                $exists = $check_result && $check_result->num_rows > 0;
                $check->close();
                
                // If not exists, create the relationship
                if (!$exists) {
                    $es_stmt = $conn->prepare("INSERT INTO employee_services (employee_id, service_id) VALUES (?, ?)");
                    $es_stmt->bind_param("ii", $employee_id, $service_id);
                    $es_stmt->execute();
                    $es_stmt->close();
                }
            }
            
            // 2. Get the price of the service
            $price_query = $conn->prepare("SELECT price FROM services WHERE id = ?");
            $price_query->bind_param("i", $service_id);
            $price_query->execute();
            $price_result = $price_query->get_result();
            $price_data = $price_result->fetch_assoc();
            $service_price = $price_data['price'];
            $price_query->close();

            // 3. Record the payment
            // FIX #2: Removed created_at and NOW() because the database now handles it by default.
            $payment_stmt = $conn->prepare("INSERT INTO online_payments (appointment_id, customer_id, amount, method, transaction_id, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $payment_stmt->bind_param("iidss", $new_appointment_id, $customer_user_id, $service_price, $payment_method, $transaction_id);
            $payment_stmt->execute();
            $payment_id = $conn->insert_id;
            $payment_stmt->close();

            if (!$payment_id) {
                throw new Exception("Failed to record payment information.");
            }

            // If all queries succeeded, commit the transaction
            $conn->commit();

            // Redirect to appointments page with a success message
            header("Location: my_appointments.php?booked=pending");
            exit;

        } catch (Exception $e) {
            // If any query fails, roll back all changes
            $conn->rollback();
            $error_msg = "An error occurred during booking. Please try again. Error: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid" style="max-width:800px;">
    <h2 class="mb-2 mt-2"><i class="fa fa-calendar-plus"></i> Book Your Appointment</h2>
    
    <?php if ($error_msg): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error_msg) ?></div><?php endif; ?>

    <form method="post" class="card shadow-sm">
        <div class="card-body p-3">
            <div class="row">
                <div class="col-md-6 border-end">
                    <h5 class="fs-6 fw-bold mb-2">Service & Schedule</h5>
                    
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small mb-1">Service <span class="text-danger">*</span></label>
                            <select name="service_id" id="service_id" class="form-select form-select-sm" required>
                                <option value="">Select Service</option>
                                <?php foreach ($services_array as $srv): ?>
                                    <option value="<?= $srv['id'] ?>" data-price="<?= $srv['price'] ?>" <?php if ($srv['id'] == $preselected_service_id) echo 'selected'; ?>>
                                        <?= htmlspecialchars($srv['name']) ?> (৳<?= number_format($srv['price'],2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small mb-1">Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control form-control-sm" min="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small mb-1">Beautician</label>
                            <select name="employee_id" id="employee_id" class="form-select form-select-sm">
                                <option value="">Any Available</option>
                                </select>
                            <div class="form-text small" id="beautician-loading">
                                <i class="fas fa-spinner fa-spin"></i> Loading available beauticians...
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label small mb-1">Notes</label>
                            <input type="text" id="notes" name="notes" class="form-control form-control-sm" placeholder="Any special requests?">
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h5 class="fs-6 fw-bold mb-2">Payment Details</h5>
                    
                    <div class="alert alert-info py-2 px-3 mb-2 small">
                        <div><strong><i class="fas fa-info-circle me-1"></i> Send payment to:</strong></div>
                        <div class="ms-2">
                            <strong>bKash/Nagad/Rocket:</strong> 01822679672
                        </div>
                        <div class="mt-1"><strong>Amount:</strong> <span id="payment-amount" class="text-success fw-bold">৳0.00</span></div>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="payment_method" class="form-select form-select-sm" required>
                                <option value="">Select Method</option>
                                <option value="bkash">bKash</option>
                                <option value="nagad">Nagad</option>
                                <option value="rocket">Rocket</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Transaction ID <span class="text-danger">*</span></label>
                            <input type="text" id="transaction_id" name="transaction_id" class="form-control form-control-sm" placeholder="Enter TrxID" required>
                        </div>
                        <div class="col-12 small text-muted mt-1">
                            <i class="fas fa-info-circle"></i> Payment will be verified within 24 hours.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light py-2">
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-calendar-check me-1"></i> Book Now
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceSelect = document.getElementById('service_id');
    const beauticianSelect = document.getElementById('employee_id');
    const amountDisplay = document.getElementById('payment-amount');
    const loadingIndicator = document.getElementById('beautician-loading');
    
    loadingIndicator.style.display = 'none';

    loadBeauticians();

    function updatePaymentAmount() {
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        const price = selectedOption?.getAttribute('data-price') || "0";
        amountDisplay.textContent = '৳' + parseFloat(price).toFixed(2);
    }

    serviceSelect.addEventListener('change', updatePaymentAmount);
    updatePaymentAmount(); // Run on page load

    function loadBeauticians() {
        loadingIndicator.style.display = 'block';
        fetch('get_beauticians.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                loadingIndicator.style.display = 'none';
                beauticianSelect.innerHTML = '<option value="">Any Available</option>';
                if (data && data.length > 0) {
                    data.forEach(beautician => {
                        const option = document.createElement('option');
                        option.value = beautician.id;
                        let displayText = beautician.name;
                        if (beautician.specialization) {
                            displayText += ` (${beautician.specialization})`;
                        }
                        option.textContent = displayText;
                        beauticianSelect.appendChild(option);
                    });
                } else {
                    beauticianSelect.innerHTML = '<option value="" disabled>No beauticians available</option>';
                }
            })
            .catch(error => {
                loadingIndicator.style.display = 'none';
                console.error('Fetch error:', error);
                beauticianSelect.innerHTML = '<option value="" disabled>Error loading beauticians</option>';
            });
    }
});
</script>

<?php require_once 'include/footer.php'; ?>