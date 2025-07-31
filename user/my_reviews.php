<?php
// filepath: c:\xampp\htdocs\parlor\user\my_reviews.php
$page_title = "My Reviews";
require_once 'include/header.php';

// Fetch all reviews for the current user
$stmt = $conn->prepare("
    SELECT r.*, a.scheduled_at, s.name as service_name, 
           u.name as beautician_name
    FROM reviews r
    JOIN appointments a ON r.appointment_id = a.id
    JOIN services s ON a.service_id = s.id
    LEFT JOIN employees e ON a.employee_id = e.id
    LEFT JOIN users u ON e.user_id = u.id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $customer_user_id);
$stmt->execute();
$result = $stmt->get_result();
$reviews = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-star me-2"></i>My Reviews</h2>
    </div>

    <?php if(empty($reviews)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>You haven't submitted any reviews yet.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php foreach($reviews as $review): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center bg-light">
                            <span class="fw-bold"><?= htmlspecialchars($review['service_name']) ?></span>
                            <small class="text-muted"><?= date('M d, Y', strtotime($review['created_at'])) ?></small>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-2 text-warning">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa<?= ($i <= $review['rating']) ? 's' : 'r' ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="badge bg-success"><?= $review['rating'] ?>/5</div>
                            </div>
                            
                            <?php if(!empty($review['comments'])): ?>
                                <p class="card-text mb-0"><?= nl2br(htmlspecialchars($review['comments'])) ?></p>
                            <?php else: ?>
                                <p class="text-muted"><em>No comments provided.</em></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white">
                            <small class="text-muted">
                                <i class="far fa-calendar me-1"></i>Appointment: <?= date('M d, Y', strtotime($review['scheduled_at'])) ?>
                                <?php if($review['beautician_name']): ?>
                                    <span class="ms-2"><i class="far fa-user me-1"></i>Beautician: <?= htmlspecialchars($review['beautician_name']) ?></span>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'include/footer.php'; ?>