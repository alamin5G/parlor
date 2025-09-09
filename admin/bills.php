<?php
// --- LOGIC FIRST ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Manage Bills";
$page_specific_css = "/parlor/admin/assets/css/bills.css";
require_once 'include/header.php';
require_once '../includes/db_connect.php';

// --- DATA FETCHING ---
$query = "
    SELECT 
        b.id as bill_id, b.amount, b.payment_mode, b.payment_time,
        a.id as appointment_id,
        u_cust.name as customer_name,
        s.name as service_name,
        u_emp.name as beautician_name
    FROM bills b
    LEFT JOIN appointments a ON b.appointment_id = a.id
    LEFT JOIN users u_cust ON a.customer_id = u_cust.id
    LEFT JOIN services s ON a.service_id = s.id
    LEFT JOIN employees e ON a.employee_id = e.id
    LEFT JOIN users u_emp ON e.user_id = u_emp.id
    ORDER BY b.payment_time DESC
";
$result = $conn->query($query);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Manage Bills & Invoices</h1>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="billsTable" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice ID</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Beautician</th>
                            <th>Amount</th>
                            <th>Payment Mode</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo str_pad($row['bill_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['service_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['beautician_name'] ?? 'N/A'); ?></td>
                                    <td>৳<?php echo number_format($row['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?php echo ucfirst($row['payment_mode']); ?></span>
                                    </td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($row['payment_time'])); ?></td>
                                    <td>
                                        <a href="view_bill.php?id=<?php echo $row['bill_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye fa-fw"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-4">No bills found. Generate a bill from a completed appointment.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // This JavaScript is correct and does not need to be changed.
    $('#billsTable').DataTable({
        "order": [[6, "desc"]], // Default sort by date descending
        "pageLength": 10
    });
});
</script>

<?php require_once 'include/footer.php'; ?>