<?php
// filepath: c:\xampp\htdocs\parlor\user\services.php
$page_title = "Available Services";
require_once 'include/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

$where = "WHERE 1";
$params = [];
$types = "";

if ($search) {
    $where .= " AND name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}
if ($min_price !== '') {
    $where .= " AND price >= ?";
    $params[] = $min_price;
    $types .= "d";
}
if ($max_price !== '') {
    $where .= " AND price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

// Pagination count
$count_sql = "SELECT COUNT(*) as total FROM services $where";
$stmt = $conn->prepare($count_sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Main query
$sql = "SELECT * FROM services $where ORDER BY name ASC LIMIT ? OFFSET ?";
$params2 = $params;
$types2 = $types . "ii";
$params2[] = $limit; $params2[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<div class="container-fluid">
    <h2 class="mb-4 mt-2"><i class="fa fa-spa"></i> Our Services</h2>
    <!-- Filters -->
    <form class="row g-2 mb-4" method="get">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search by name..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-2">
            <input type="number" min="0" step="1" name="min_price" class="form-control" placeholder="Min Price" value="<?= htmlspecialchars($min_price) ?>">
        </div>
        <div class="col-md-2">
            <input type="number" min="0" step="1" name="max_price" class="form-control" placeholder="Max Price" value="<?= htmlspecialchars($max_price) ?>">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100"><i class="fa fa-search me-1"></i>Filter</button>
        </div>
        <div class="col-md-2">
            <a href="services.php" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
    </form>

    <div class="row g-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while($srv = $result->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($srv['name']) ?></h5>
                            <div class="mb-2 text-secondary" style="min-height: 3.2em;"><?= nl2br(htmlspecialchars($srv['description'])) ?></div>
                            <ul class="list-unstyled mb-3">
                                <li><i class="fa fa-clock text-primary me-1"></i> <?= $srv['duration_min'] ?> min</li>
                                <li><i class="fa fa-money-bill text-success me-1"></i> <b>৳<?= number_format($srv['price'],2) ?></b></li>
                            </ul>
                            <div class="mt-auto">
                                <a href="book.php?service_id=<?= $srv['id'] ?>" class="btn btn-success w-100"><i class="fa fa-calendar-plus me-1"></i>Book Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-info text-center">No services found.</div></div>
        <?php endif; ?>
    </div>
    <!-- Pagination -->
    <?php $pages = ceil($total / $limit); ?>
    <?php if ($pages > 1): ?>
    <nav aria-label="Service pagination" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i=1; $i <= $pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<style>
.card-title { font-weight: 600; }
</style>
<?php require_once 'include/footer.php'; ?>
