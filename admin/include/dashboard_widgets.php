<?php
// filepath: c:\xampp\htdocs\parlor\admin\include\dashboard_widgets.php

/**
 * Renders a widget showing pending payments that need verification
 */
function render_pending_payments_widget($conn) {
    // Count pending payments
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM online_payments 
        WHERE status = 'pending'
    ");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $pending_count = $result['count'];
    
    // Get the latest pending payments
    $stmt = $conn->prepare("
        SELECT op.id, op.amount, op.method, op.transaction_id, u.name AS customer_name
        FROM online_payments op
        JOIN appointments a ON op.appointment_id = a.id
        JOIN users u ON a.customer_id = u.id
        WHERE op.status = 'pending'
        ORDER BY op.submitted_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $pending_payments = $stmt->get_result();
    ?>
    
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Pending Payment Verification</h5>
            <?php if ($pending_count > 0): ?>
                <span class="badge bg-danger"><?= $pending_count ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($pending_count > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Transaction ID</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $pending_payments->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                    <td>৳<?= number_format($row['amount'], 2) ?></td>
                                    <td><?= strtoupper($row['method']) ?></td>
                                    <td><?= htmlspecialchars($row['transaction_id']) ?></td>
                                    <td class="text-end">
                                        <a href="online_payments.php" class="btn btn-sm btn-primary">Verify</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <a href="online_payments.php?status=pending" class="btn btn-warning mt-2">Verify All Payments</a>
            <?php else: ?>
                <p class="text-center mb-0">No pending payments to verify.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>