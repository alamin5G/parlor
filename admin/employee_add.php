<?php
require_once 'include/header.php';

$success_msg = $error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $specialization = trim($_POST['specialization']);
    $hire_date = $_POST['hire_date'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error_msg = "Name, email, and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $error_msg = "Email already exists. Please use a different email.";
        } else {
            // Begin transaction for user and employee creation
            $conn->begin_transaction();
            
            try {
                // Create user account
                $role = 'beautician';
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $is_verified = 1; // Auto-verify employees
                
                $user_stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
                $user_stmt->bind_param("sssssi", $name, $email, $phone, $hashed_password, $role, $is_verified);
                $user_stmt->execute();
                
                $user_id = $conn->insert_id;
                
                // Create employee record
                $status = 'active';
                $employee_stmt = $conn->prepare("INSERT INTO employees (user_id, specialization, hire_date, status) VALUES (?, ?, ?, ?)");
                $employee_stmt->bind_param("isss", $user_id, $specialization, $hire_date, $status);
                $employee_stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                $success_msg = "Employee added successfully.";
                
                // Clear form after successful submission
                $name = $email = $phone = $specialization = $hire_date = '';
                
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error_msg = "Error adding employee: " . $e->getMessage();
            }
        }
        $check_stmt->close();
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Add New Employee</h1>
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
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo isset($specialization) ? htmlspecialchars($specialization) : ''; ?>">
                        <div class="form-text">E.g., Hair, Makeup, Facial, Nails, etc.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="hire_date" class="form-label">Hire Date</label>
                        <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?php echo isset($hire_date) ? $hire_date : date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        <div class="form-text">Minimum 6 characters</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Add Employee
                    </button>
                    <a href="employees.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>