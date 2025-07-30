<?php
// --- LOGIC FIRST ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "View Bill";
$page_specific_css = "/parlor/admin/assets/css/view_bill.css"; // Link to the new CSS file
require_once 'include/header.php';
require_once '../includes/db_connect.php';

// Bill ID validation
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>No bill selected.</div>";
    require_once 'include/footer.php';
    exit;
}
$bill_id = intval($_GET['id']);

// Get bill details with all related info
$sql = "
    SELECT b.*, 
           a.scheduled_at, 
           s.name AS service_name, 
           u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
           eu.name AS beautician_name
    FROM bills b
    LEFT JOIN appointments a ON b.appointment_id = a.id
    LEFT JOIN services s ON a.service_id = s.id
    LEFT JOIN users u ON a.customer_id = u.id
    LEFT JOIN employees e ON a.employee_id = e.id
    LEFT JOIN users eu ON e.user_id = eu.id
    WHERE b.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Bill not found.</div>";
    require_once 'include/footer.php';
    exit;
}
$bill = $result->fetch_assoc();
$stmt->close();
?>

<div class="container-fluid">
    <!-- Actions Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 actions-bar">
        <a href="javascript:history.back()" class="btn btn-outline-secondary back-button"><i class="fas fa-arrow-left me-2"></i>Back</a>
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-2"></i>Print Invoice</button>
    </div>

    <!-- Invoice Card -->
    <div class="invoice-card">
        <!-- Header -->
        <div class="invoice-header row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-0">Labonno Glamour World</h2>
                <p class="text-muted mb-0">Your trusted beauty partner</p>
            </div>
            <div class="col-md-6 text-md-end">
                <h3>INVOICE #<?php echo str_pad($bill['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                <p class="mb-0"><strong>Date Issued:</strong> <?php echo date('d M Y', strtotime($bill['payment_time'])); ?></p>
            </div>
        </div>

        <!-- Details -->
        <div class="invoice-details row">
            <div class="col-md-6">
                <h5>Bill To:</h5>
                <address class="mb-0">
                    <strong><?php echo htmlspecialchars($bill['customer_name']); ?></strong><br>
                    <?php echo htmlspecialchars($bill['customer_email']); ?><br>
                    <?php echo htmlspecialchars($bill['customer_phone'] ?? 'N/A'); ?>
                </address>
            </div>
            <div class="col-md-6 text-md-end">
                <h5>Payment Details:</h5>
                <p class="mb-0">
                    <strong>Payment Method:</strong> <?php echo ucfirst($bill['payment_mode']); ?><br>
                    <strong>Payment Status:</strong> <span class="badge bg-success">Paid</span>
                </p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive">
            <table class="table invoice-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Service Description</th>
                        <th class="text-center">Appointment Date</th>
                        <th class="text-center">Beautician</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($bill['service_name']); ?></strong>
                            <p class="text-muted small mb-0">Service provided for appointment #<?php echo $bill['appointment_id']; ?></p>
                        </td>
                        <td class="text-center"><?php echo date('d M Y', strtotime($bill['scheduled_at'])); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($bill['beautician_name']); ?></td>
                        <td class="text-end">৳<?php echo number_format($bill['amount'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="invoice-summary row justify-content-end">
            <div class="col-md-4">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-end">৳<?php echo number_format($bill['amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Discount:</td>
                            <td class="text-end">৳0.00</td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td class="text-end"><strong>৳<?php echo number_format($bill['amount'], 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer border-top">
            <p class="mb-1">Thank you for your business!</p>
            <p>If you have any questions about this invoice, please contact us.</p>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>