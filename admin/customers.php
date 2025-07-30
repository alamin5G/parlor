<?php
// --- LOGIC FIRST ---
// This block must run before any HTML is output to fix the header error.
// --- NOW INCLUDE THE HEADER ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Manage Customers";
$page_specific_css = "/parlor/admin/assets/css/customers.css";
require_once '../includes/db_connect.php';

// Security check: Ensure user is logged in and is an admin.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /parlor/login.php");
    exit;
}

// Handle user activation/deactivation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_user_id'], $_POST['action'])) {
    $userId = (int)$_POST['toggle_user_id'];
    $action = $_POST['action'] === 'activate' ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'customer'");
    $stmt->bind_param("ii", $action, $userId);
    $stmt->execute();
    $stmt->close();
    
    // Set a success message in the session to show after redirect
    $_SESSION['success_msg'] = "User status updated successfully.";
    
    // Redirect to the same page to show the changes and prevent form resubmission
    header("Location: customers.php");
    exit;
}


require_once 'include/header.php';

// Display success message from session if it exists
$success_msg = '';
if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']); // Clear the message after displaying it
}

// --- DATA FETCHING & FILTERING ---
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_verified = isset($_GET['verified']) ? $_GET['verified'] : 'all';
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Base query
$query = "
    SELECT 
        u.id, u.name, u.email, u.phone, u.created_at, u.is_active, u.is_verified,
        COUNT(a.id) as total_appointments,
        COALESCE(SUM(CASE WHEN a.status = 'completed' THEN s.price ELSE 0 END), 0) AS total_expense
    FROM users u
    LEFT JOIN appointments a ON u.id = a.customer_id
    LEFT JOIN services s ON a.service_id = s.id
    WHERE u.role = 'customer'
";

// Apply filters
if ($filter_status !== 'all') {
    $query .= ($filter_status == 'active') ? " AND u.is_active = 1" : " AND u.is_active = 0";
}
if ($filter_verified !== 'all') {
    $query .= ($filter_verified == 'yes') ? " AND u.is_verified = 1" : " AND u.is_verified = 0";
}

$query .= " GROUP BY u.id";

// Apply sorting
switch ($sort_order) {
    case 'top_expense':
        $query .= " ORDER BY total_expense DESC";
        break;
    case 'most_appointments':
        $query .= " ORDER BY total_appointments DESC";
        break;
    default:
        $query .= " ORDER BY u.id DESC";
}

$result = $conn->query($query);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Manage Customers</h1>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="status" class="form-label">Account Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="all" <?php if($filter_status == 'all') echo 'selected'; ?>>All</option>
                        <option value="active" <?php if($filter_status == 'active') echo 'selected'; ?>>Active</option>
                        <option value="inactive" <?php if($filter_status == 'inactive') echo 'selected'; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="verified" class="form-label">Email Verified</label>
                    <select name="verified" id="verified" class="form-select">
                        <option value="all" <?php if($filter_verified == 'all') echo 'selected'; ?>>All</option>
                        <option value="yes" <?php if($filter_verified == 'yes') echo 'selected'; ?>>Verified</option>
                        <option value="no" <?php if($filter_verified == 'no') echo 'selected'; ?>>Not Verified</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort" class="form-label">Sort By</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="newest" <?php if($sort_order == 'newest') echo 'selected'; ?>>Newest First</option>
                        <option value="top_expense" <?php if($sort_order == 'top_expense') echo 'selected'; ?>>Top Spenders</option>
                        <option value="most_appointments" <?php if($sort_order == 'most_appointments') echo 'selected'; ?>>Most Appointments</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Apply Filters</button>
                    <a href="customers.php" class="btn btn-outline-secondary w-100 mt-2">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Customers Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="customersTable" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Appointments</th>
                            <th>Total Spent</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($user = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($user['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                        <span class="badge <?php echo $user['is_verified'] ? 'bg-info' : 'bg-warning text-dark'; ?>">
                                            <?php echo $user['is_verified'] ? 'Verified' : 'Unverified'; ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?php echo $user['total_appointments']; ?></td>
                                    <td>৳<?php echo number_format($user['total_expense'], 2); ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="view_user.php?id=<?php echo $user['id']; ?>"><i class="fas fa-eye fa-fw me-2"></i>View Details</a></li>
                                                <li>
                                                    <button class="dropdown-item" type="button" onclick="showToggleModal('<?php echo $user['id']; ?>', '<?php echo ($user['is_active'] ? 'deactivate' : 'activate'); ?>', '<?php echo htmlspecialchars($user['name']); ?>')">
                                                        <?php if ($user['is_active']): ?>
                                                            <i class="fas fa-times-circle fa-fw me-2 text-danger"></i>Deactivate
                                                        <?php else: ?>
                                                            <i class="fas fa-check-circle fa-fw me-2 text-success"></i>Activate
                                                        <?php endif; ?>
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No customers found matching your criteria.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Activation/Deactivation Modal -->
<div class="modal fade" id="toggleUserModal" tabindex="-1" aria-labelledby="toggleUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" id="toggleUserForm">
        <div class="modal-header">
          <h5 class="modal-title" id="toggleUserModalLabel">Confirm Status Change</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="toggle_user_id" id="modal_user_id">
          <input type="hidden" name="action" id="modal_action">
          <p id="modal_message"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn" id="modal_confirm_btn">Confirm</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Initialize DataTables for sorting and searching
$(document).ready(function() {
    $('#customersTable').DataTable({
        "order": [], // Disable initial sorting by DataTables to respect PHP sorting
        "pageLength": 10
    });
});

// Function to populate and show the confirmation modal
function showToggleModal(userId, action, userName) {
    const modal = new bootstrap.Modal(document.getElementById('toggleUserModal'));
    document.getElementById('modal_user_id').value = userId;
    document.getElementById('modal_action').value = action;
    
    const messageEl = document.getElementById('modal_message');
    const confirmBtn = document.getElementById('modal_confirm_btn');
    
    if (action === 'activate') {
        messageEl.innerHTML = `Are you sure you want to <b>activate</b> the account for <b>${userName}</b>?`;
        confirmBtn.className = "btn btn-success";
        confirmBtn.innerText = "Activate";
    } else {
        messageEl.innerHTML = `Are you sure you want to <b>deactivate</b> the account for <b>${userName}</b>?`;
        confirmBtn.className = "btn btn-danger";
        confirmBtn.innerText = "Deactivate";
    }
    
    modal.show();
}
</script>

<?php require_once 'include/footer.php'; ?>