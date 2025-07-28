<?php
require_once 'include/header.php';

// Delete employee functionality
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $employee_id = $_GET['delete'];
    // Get the user_id from employee record first
    $stmt = $conn->prepare("SELECT user_id FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];
        
        // Start transaction to ensure both records are deleted
        $conn->begin_transaction();
        
        try {
            // Delete from employees table first (foreign key)
            $delete_employee = $conn->prepare("DELETE FROM employees WHERE id = ?");
            $delete_employee->bind_param("i", $employee_id);
            $delete_employee->execute();
            
            // Then delete from users table
            $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
            $delete_user->bind_param("i", $user_id);
            $delete_user->execute();
            
            // Commit the transaction
            $conn->commit();
            
            $success_msg = "Employee deleted successfully.";
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_msg = "Error deleting employee: " . $e->getMessage();
        }
    }
}

// Fetch all employees with user data
$query = "SELECT e.*, u.name, u.email, u.phone 
          FROM employees e 
          JOIN users u ON e.user_id = u.id 
          ORDER BY e.id DESC";
$result = $conn->query($query);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Manage Employees</h1>
        <a href="add_employee.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Add New Employee
        </a>
    </div>
    
    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Specialization</th>
                            <th>Hire Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['specialization'] ?? 'General'); ?></td>
                                    <td><?php echo $row['hire_date'] ? date('M d, Y', strtotime($row['hire_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_employee.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_employee.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="employees.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this employee?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No employees found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>