<?php
require_once 'include/header.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: employees.php');
    exit;
}

$employee_id = $_GET['id'];

// Fetch employee data
$query = "SELECT e.*, u.name, u.email, u.phone, u.created_at
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

// Fetch employee's appointments count
$query = "SELECT COUNT(*) as total_appointments, 
          SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments
          FROM appointments 
          WHERE employee_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$appointments_result = $stmt->get_result();
$appointments_stats = $appointments_result->fetch_assoc();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Employee Details</h1>
        <div>
            <a href="edit_employee.php?id=<?php echo $employee_id; ?>" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Edit
            </a>
            <a href="employees.php" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Employees
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Name:</th>
                            <td><?php echo htmlspecialchars($employee['name']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td><?php echo htmlspecialchars($employee['phone'] ?? 'Not provided'); ?></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge <?php echo $employee['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo ucfirst($employee['status']); ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Employment Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Employee ID:</th>
                            <td><?php echo $employee['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Specialization:</th>
                            <td><?php echo htmlspecialchars($employee['specialization'] ?? 'General'); ?></td>
                        </tr>
                        <tr>
                            <th>Hire Date:</th>
                            <td><?php echo $employee['hire_date'] ? date('F d, Y', strtotime($employee['hire_date'])) : 'Not set'; ?></td>
                        </tr>
                        <tr>
                            <th>Account Created:</th>
                            <td><?php echo date('F d, Y', strtotime($employee['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Performance Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h2><?php echo $appointments_stats['total_appointments'] ?? 0; ?></h2>
                            <p>Total Appointments</p>
                        </div>
                        <div class="col-6">
                            <h2><?php echo $appointments_stats['completed_appointments'] ?? 0; ?></h2>
                            <p>Completed Appointments</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Appointments Section can be added here in the future -->
    
</div>

<?php require_once 'include/footer.php'; ?>