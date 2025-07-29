<?php

require_once 'include/header.php';

$success_msg = $error_msg = '';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: employees.php');
    exit;
}

$employee_id = $_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $hire_date = $_POST['hire_date'];
    $status = $_POST['status'];
    $password = $_POST['password'];
    
    // Get current user_id
    $stmt = $conn->prepare("SELECT user_id FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update user info
            if (!empty($password)) {
                // Update with new password
                if (strlen($password) < 6) {
                    throw new Exception("Password must be at least 6 characters.");
                }
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                $user_stmt->bind_param("ssssi", $name, $email, $phone, $hashed_password, $user_id);
            } else {
                // Update without changing password
                $user_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                $user_stmt->bind_param("sssi", $name, $email, $phone, $user_id);
            }
            $user_stmt->execute();
            
            // Update employee info
            $employee_stmt = $conn->prepare("UPDATE employees SET specialization = ?, hire_date = ?, status = ? WHERE id = ?");
            $employee_stmt->bind_param("sssi", $specialization, $hire_date, $status, $employee_id);
            $employee_stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            $success_msg = "Employee updated successfully.";
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_msg = "Error updating employee: " . $e->getMessage();
        }
    } else {
        $error_msg = "Employee not found.";
    }
}

// Fetch employee data
$query = "SELECT e.*, u.name, u.email, u.phone 
          FROM employees e 
          JOIN users u ON e.user_id = u.id 
          WHERE e.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: employees.php');
    exit;
}

$employee = $result->fetch_assoc();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Employee</h1>
        <a href="employees.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Employees
        </a>
    </div>
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($employee['name']); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo htmlspecialchars($employee['specialization'] ?? ''); ?>">
                        <div class="form-text">E.g., Hair, Makeup, Facial, Nails, etc.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="hire_date" class="form-label">Hire Date</label>
                        <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?php echo $employee['hire_date'] ?? date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo $employee['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $employee['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6">
                        <div class="form-text">Minimum 6 characters. Leave blank to keep current password.</div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Employee
                    </button>
                    <a href="employees.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>