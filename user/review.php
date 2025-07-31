<?php
// filepath: c:\xampp\htdocs\parlor\user\review.php
$page_title = "Leave Review";
require_once 'include/header.php';

// Validate appointment ID
if (!isset($_GET['appointment_id']) || !is_numeric($_GET['appointment_id'])) {
    echo "<div class='alert alert-danger'>No appointment selected.</div>";
    require_once 'include/footer.php';
    exit;
}
$appt_id = intval($_GET['appointment_id']);

// Fetch the appointment, ensure it's completed and belongs to this user
$sql = "SELECT a.*, s.name AS service_name, u_emp.name AS beautician_name,
               r.id AS review_id
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        LEFT JOIN employees e ON a.employee_id = e.id
        LEFT JOIN users u_emp ON e.user_id = u_emp.id
        LEFT JOIN reviews r ON a.id = r.appointment_id
        WHERE a.id = ? AND a.customer_id = ? AND a.status = 'completed'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $appt_id, $customer_user_id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If not found or already has a review
if (!$appt) {
    echo "<div class='alert alert-danger'>You can't review this appointment. It may not exist, not be completed, or not belong to you.</div>";
    echo "<p><a href='my_appointments.php' class='btn btn-primary'>Go back</a></p>";
    require_once 'include/footer.php';
    exit;
}

if ($appt['review_id']) {
    echo "<div class='alert alert-info'>You've already reviewed this service.</div>";
    echo "<p><a href='my_appointments.php' class='btn btn-primary'>Go back</a></p>";
    require_once 'include/footer.php';
    exit;
}

$error_msg = '';
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    if ($rating < 1 || $rating > 5) {
        $error_msg = "Please select a rating between 1 and 5 stars.";
    } elseif (empty($comment) || strlen($comment) < 3) {
        $error_msg = "Please provide a comment with your review.";
    } else {
        // Save the review
        $stmt = $conn->prepare("INSERT INTO reviews (appointment_id, customer_id, rating, comments) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $appt_id, $customer_user_id, $rating, $comment);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error_msg = "Error saving your review. Please try again.";
        }
        $stmt->close();
    }
}
?>

<div class="container-fluid" style="max-width:700px;">
    <a href="my_appointments.php" class="btn btn-link mb-3">&larr; Back to My Appointments</a>
    <h2 class="mb-4"><i class="fas fa-star"></i> Leave a Review</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <h5><i class="fas fa-check-circle"></i> Thank You!</h5>
            <p>Your review has been submitted successfully. We truly appreciate your feedback!</p>
            <a href="my_appointments.php" class="btn btn-primary mt-3">Back to My Appointments</a>
        </div>
    <?php else: ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?= $error_msg ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><?= htmlspecialchars($appt['service_name']) ?></h5>
                <p class="small text-muted mb-0">
                    Beautician: <?= htmlspecialchars($appt['beautician_name']) ?> | 
                    Date: <?= date('d M Y', strtotime($appt['scheduled_at'])) ?>
                </p>
            </div>
            
            <div class="card-body">
                <form method="post">
                    <div class="mb-4 text-center">
                        <label class="form-label d-block"><b>How would you rate this service?</b></label>
                        <div class="rating-stars">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required />
                            <label for="star<?= $i ?>" title="<?= $i ?> star" class="fs-3">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label"><b>Your Review</b></label>
                        <textarea id="comments" name="comments" class="form-control" rows="5" required 
    placeholder="Share your experience with this service..."></textarea>
                        <div class="form-text">Please provide at least 3 characters.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.rating-stars {
    display: inline-block;
    direction: rtl; /* Right to left to show stars properly */
}
.rating-stars input {
    display: none;
}
.rating-stars label {
    color: #ccc;
    cursor: pointer;
    margin: 0 5px;
}
.rating-stars label:hover, .rating-stars label:hover ~ label,
.rating-stars input:checked ~ label {
    color: #f8ce0b;
}
</style>

<?php require_once 'include/footer.php'; ?>