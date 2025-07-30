<?php
// filepath: c:\xampp\htdocs\parlor\employee\reviews.php
$page_title = "My Reviews";
require_once 'include/header.php';

// Get employee_id
$stmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt->bind_param("i", $employee_user_id);
$stmt->execute();
$employee_id = $stmt->get_result()->fetch_assoc()['id'];
$stmt->close();

// --- STATS & DATA FETCHING ---

// 1. Get average rating and total count in one query
$avg_sql = "SELECT AVG(r.rating) AS avg_rating, COUNT(r.id) AS total_reviews FROM reviews r JOIN appointments a ON r.appointment_id = a.id WHERE a.employee_id = ?";
$stmt_avg = $conn->prepare($avg_sql);
$stmt_avg->bind_param("i", $employee_id);
$stmt_avg->execute();
$stats = $stmt_avg->get_result()->fetch_assoc();
$total_reviews = $stats['total_reviews'] ?? 0;
$avg_rating = $stats['avg_rating'] ?? 0;
$stmt_avg->close();

// 2. Get rating breakdown
$breakdown_sql = "SELECT rating, COUNT(r.id) as count FROM reviews r JOIN appointments a ON r.appointment_id = a.id WHERE a.employee_id = ? GROUP BY rating ORDER BY rating DESC";
$stmt_breakdown = $conn->prepare($breakdown_sql);
$stmt_breakdown->bind_param("i", $employee_id);
$stmt_breakdown->execute();
$breakdown_result = $stmt_breakdown->get_result();
$ratings_breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
while ($row = $breakdown_result->fetch_assoc()) {
    $ratings_breakdown[$row['rating']] = $row['count'];
}
$stmt_breakdown->close();

// 3. Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 5; // Number of reviews per page
$offset = ($page - 1) * $limit;

// 4. Fetch reviews for the current page
$sql = "SELECT r.*, u.name AS customer_name, s.name AS service_name, a.scheduled_at
    FROM reviews r
    JOIN appointments a ON r.appointment_id = a.id
    JOIN users u ON r.customer_id = u.id
    JOIN services s ON a.service_id = s.id
    WHERE a.employee_id = ?
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $employee_id, $limit, $offset);
$stmt->execute();
$reviews = $stmt->get_result();
$stmt->close();

function render_stars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="fa-star' . ($i <= $rating ? ' fas text-warning' : ' far text-secondary') . '"></i>';
    }
    return $html;
}
?>
<style>
    .rating-breakdown .progress { height: 10px; }
    .rating-breakdown .row { align-items: center; margin-bottom: 5px; }
</style>

<div class="container-fluid">
    <h1 class="mt-4">My Ratings & Reviews</h1>
    <p class="text-muted">An overview of the feedback you've received from customers.</p>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center border-end">
                    <h5 class="text-muted">Average Rating</h5>
                    <?php if ($total_reviews > 0): ?>
                        <h1 class="display-4 fw-bold"><?= number_format($avg_rating, 2) ?></h1>
                        <div class="mb-2"><?= render_stars($avg_rating) ?></div>
                        <p class="text-muted">Based on <?= $total_reviews ?> reviews</p>
                    <?php else: ?>
                        <p class="mt-4">No reviews yet.</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-8 rating-breakdown">
                    <h5 class="text-muted mb-3">Rating Breakdown</h5>
                    <?php foreach ($ratings_breakdown as $star => $count): ?>
                        <div class="row">
                            <div class="col-2"><?= $star ?> star</div>
                            <div class="col-8">
                                <div class="progress">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $total_reviews > 0 ? ($count / $total_reviews * 100) : 0 ?>%;" aria-valuenow="<?= $count ?>" aria-valuemin="0" aria-valuemax="<?= $total_reviews ?>"></div>
                                </div>
                            </div>
                            <div class="col-2 text-end text-muted"><?= $count ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mb-3">Latest Reviews</h4>
    <?php if ($reviews->num_rows > 0): ?>
        <div class="list-group shadow-sm">
            <?php while($row = $reviews->fetch_assoc()): ?>
                <div class="list-group-item list-group-item-action flex-column align-items-start p-3">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><?= htmlspecialchars($row['customer_name']) ?></h5>
                        <small class="text-muted"><?= date('d M Y', strtotime($row['created_at'])) ?></small>
                    </div>
                    <div class="mb-2"><?= render_stars($row['rating']) ?></div>
                    <p class="mb-1"><?= nl2br(htmlspecialchars($row['comments'])) ?></p>
                    <small class="text-muted">For service: <?= htmlspecialchars($row['service_name']) ?> on <?= date('d M Y', strtotime($row['scheduled_at'])) ?></small>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card shadow-sm"><div class="card-body text-center">No reviews found.</div></div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($total_reviews > $limit): ?>
    <nav aria-label="Reviews pagination" class="mt-4">
      <ul class="pagination justify-content-center">
        <?php $pages = ceil($total_reviews / $limit); ?>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
</div>
<?php require_once 'include/footer.php'; ?>