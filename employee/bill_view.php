<?php
// filepath: c:\xampp\htdocs\parlor\employee\bill_view.php
$page_title = "View Bill";
require_once 'include/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid Bill ID.</div>";
    require_once 'include/footer.php';
    exit;
}
$bill_id = intval($_GET['id']);

// Get employee_id
$stmt_emp = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt_emp->bind_param("i", $employee_user_id);
$stmt_emp->execute();
$employee_id = $stmt_emp->get_result()->fetch_assoc()['id'];
$stmt_emp->close();

// Fetch bill details ensuring it belongs to an appointment handled by this employee
$sql = "SELECT b.*, a.scheduled_at, s.name as service_name, u.name as customer_name
        FROM bills b
        JOIN appointments a ON b.appointment_id = a.id
        JOIN services s ON a.service_id = s.id
        JOIN users u ON a.customer_id = u.id
        WHERE b.id = ? AND a.employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $bill_id, $employee_id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bill) {
    echo "<div class='alert alert-danger'>Bill not found or you do not have permission to view it.</div>";
    require_once 'include/footer.php';
    exit;
}
?>
<style>
    /* Styles for the print view */
    @media print {
        body * { visibility: hidden; }
        .main-content { margin-left: 0 !important; }
        #invoice, #invoice * { visibility: visible; }
        #invoice {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            box-shadow: none !important;
            border: none !important;
        }
        .no-print { display: none; }
    }
</style>

<div class="container-fluid" style="max-width: 800px;">
    <!-- Action Buttons -->
    <div class="d-flex justify-content-between mb-3 no-print">
        <a href="history.php" class="btn btn-light">&larr; Back to History</a>
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-2"></i>Print Bill</button>
    </div>

    <!-- Invoice Card -->
    <div class="card shadow-sm" id="invoice">
        <div class="card-body p-4">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-6">
                    <h2 class="mb-0">Labonno Glamour World</h2>
                    <p class="text-muted">Invoice</p>
                </div>
                <div class="col-6 text-end">
                    <h4>Bill #<?= htmlspecialchars($bill['id']) ?></h4>
                    <p class="mb-0"><strong>Date Issued:</strong> <?= date('d M Y', strtotime($bill['payment_time'])) ?></p>
                    <p class="mb-0 text-success fw-bold fs-4">PAID</p>
                </div>
            </div>

            <!-- Customer & Service Info -->
            <div class="row border-top pt-3 mb-4">
                <div class="col-md-6">
                    <h5>Bill To:</h5>
                    <p class="mb-0"><?= htmlspecialchars($bill['customer_name']) ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Appointment Details:</h5>
                    <p class="mb-0"><strong>Service Date:</strong> <?= date('d M Y, h:i A', strtotime($bill['scheduled_at'])) ?></p>
                </div>
            </div>

            <!-- Service Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Service Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= htmlspecialchars($bill['service_name']) ?></td>
                            <td class="text-end">৳<?= number_format($bill['amount'], 2) ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td class="text-end">Total Paid</td>
                            <td class="text-end fs-5">৳<?= number_format($bill['amount'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer Note -->
            <div class="text-center mt-4">
                <p class="text-muted">Thank you for your business!</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php';