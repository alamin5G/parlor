<?php
// --- LOGIC FIRST ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Manage Employees";
$page_specific_css = "/parlor/admin/assets/css/employees.css"; // Link to the new CSS file
require_once 'include/header.php';
require_once '../includes/db_connect.php';

// --- Delete employee functionality (with safety check) ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $employee_id = intval($_GET['delete']);

    // 1. Check if the employee has any associated appointments
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE employee_id = ?");
    $check_stmt->bind_param("i", $employee_id);
    $check_stmt->execute();
    $appointment_count = $check_stmt->get_result()->fetch_assoc()['count'];
    $check_stmt->close();

    if ($appointment_count > 0) {
        // If they have appointments, show an error and do not delete
        $error_msg = "Cannot delete employee. They have {$appointment_count} associated appointments. Please reassign or cancel them first.";
    } else {
        // 2. If no appointments, proceed with deletion
        $stmt = $conn->prepare("SELECT user_id FROM employees WHERE id = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $user_id = $row['user_id'];
            
            $conn->begin_transaction();
            try {
                $conn->prepare("DELETE FROM employees WHERE id = ?")->execute([$employee_id]);
                $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                $conn->commit();
                $success_msg = "Employee deleted successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $error_msg = "Error deleting employee: " . $e->getMessage();
            }
        }
        $stmt->close();
    }
}

// Fetch all employees with performance data
$query = "
    SELECT 
        e.id, e.specialization, e.hire_date,
        u.name, u.email, u.phone, u.is_active,
        COUNT(a.id) as total_appointments,
        COALESCE(SUM(CASE WHEN a.status = 'completed' THEN s.price ELSE 0 END), 0) AS total_revenue
    FROM employees e 
    JOIN users u ON e.user_id = u.id 
    LEFT JOIN appointments a ON e.id = a.employee_id
    LEFT JOIN services s ON a.service_id = s.id
    GROUP BY e.id, u.id
    ORDER BY e.id DESC
";
$result = $conn->query($query);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Manage Employees</h1>
        <a href="add_employee.php" class="btn btn-primary">
            <i class="fas fa-user-plus me-2"></i>Add New Employee
        </a>
    </div>
    
    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $success_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo $error_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="employeesTable" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Appointments</th>
                            <th>Revenue</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['specialization'] ?? 'General'); ?></td>
                                    <td class="text-center"><?php echo $row['total_appointments']; ?></td>
                                    <td>৳<?php echo number_format($row['total_revenue'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Actions</button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="view_employee.php?id=<?php echo $row['id']; ?>"><i class="fas fa-eye fa-fw me-2"></i>View</a></li>
                                                <li><a class="dropdown-item" href="edit_employee.php?id=<?php echo $row['id']; ?>"><i class="fas fa-edit fa-fw me-2"></i>Edit</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteEmployeeModal" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>">
                                                        <i class="fas fa-trash fa-fw me-2"></i>Delete
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No employees found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DELETE EMPLOYEE MODAL -->
<div class="modal fade" id="deleteEmployeeModal" tabindex="-1" aria-labelledby="deleteEmployeeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteEmployeeModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete the employee: <strong id="employeeNameToDelete"></strong>?
        <p class="text-danger mt-2">This action is irreversible and will permanently remove the employee's record and their user account.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="confirmDeleteButton" class="btn btn-danger">Delete Employee</a>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#employeesTable').DataTable({
        "order": [[0, "desc"]], // Default sort by ID descending
        "pageLength": 10
    });

    // Handle modal popup for deletion
    var deleteEmployeeModal = document.getElementById('deleteEmployeeModal');
    deleteEmployeeModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var employeeId = button.getAttribute('data-id');
        var employeeName = button.getAttribute('data-name');
        
        var modalBodyName = deleteEmployeeModal.querySelector('#employeeNameToDelete');
        var confirmDeleteButton = deleteEmployeeModal.querySelector('#confirmDeleteButton');
        
        modalBodyName.textContent = employeeName;
        confirmDeleteButton.href = 'employees.php?delete=' + employeeId;
    });
});
</script>

<?php require_once 'include/footer.php'; ?>