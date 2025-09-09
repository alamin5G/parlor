<?php
// filepath: c:\xampp\htdocs\parlor\user\my_billing.php
$page_title = "My Billing";
require_once 'include/header.php';

// Fetch all payments for the current user
$stmt = $conn->prepare("
    SELECT p.*, a.scheduled_at, s.name as service_name, s.price, 
           u.name as beautician_name
    FROM online_payments p
    JOIN appointments a ON p.appointment_id = a.id
    JOIN services s ON a.service_id = s.id
    LEFT JOIN employees e ON a.employee_id = e.id
    LEFT JOIN users u ON e.user_id = u.id
    WHERE p.customer_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $customer_user_id);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-receipt me-2"></i>My Billing History</h2>
    </div>

    <?php if(empty($payments)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>You don't have any payment records yet.
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Service</th>
                            <th>Appointment</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $payment): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($payment['created_at'])) ?></td>
                                <td><?= htmlspecialchars($payment['service_name']) ?></td>
                                <td>
                                    <?= date('M d, Y g:i A', strtotime($payment['scheduled_at'])) ?>
                                    <?php if($payment['beautician_name']): ?>
                                        <div class="small text-muted">with <?= htmlspecialchars($payment['beautician_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold">৳<?= number_format($payment['amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= ucfirst(htmlspecialchars($payment['method'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted"><?= htmlspecialchars($payment['transaction_id']) ?></small>
                                </td>
                                <td>
                                    <?php if($payment['status'] == 'verified'): ?>
                                        <span class="badge bg-success">Verified</span>
                                    <?php elseif($payment['status'] == 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'include/footer.php'; ?>