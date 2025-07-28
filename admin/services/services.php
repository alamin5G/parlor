<?php
require_once '../include/header.php';

// Handle service deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $service_id = $_GET['delete'];
    
    // Check if service is used in any appointments before deleting
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE service_id = ?");
    $check_stmt->bind_param("i", $service_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $error_msg = "This service cannot be deleted because it has associated appointments.";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $delete_stmt->bind_param("i", $service_id);
        
        if ($delete_stmt->execute()) {
            $success_msg = "Service deleted successfully.";
        } else {
            $error_msg = "Error deleting service.";
        }
    }
}

// Fetch all services
$query = "SELECT * FROM services ORDER BY name ASC";
$result = $conn->query($query);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Manage Services</h1>
        <a href="service_add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Add New Service
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
                            <th>Service Name</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td>৳<?php echo number_format($row['price'], 2); ?></td>
                                    <td><?php echo $row['duration_min']; ?> min</td>
                                    <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : ''); ?></td>
                                    <td>
                                        <a href="service_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="services.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this service?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No services found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../include/footer.php'; ?>