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
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteServiceModal" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($row['name']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
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


<!-- DELETE SERVICE MODAL -->
<div class="modal fade" id="deleteServiceModal" tabindex="-1" aria-labelledby="deleteServiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteServiceModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete the service: <strong id="serviceNameToDelete"></strong>?
        <p class="text-danger mt-2">This action cannot be undone. Please ensure this service is not associated with any appointments before proceeding.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="confirmDeleteButton" class="btn btn-danger">Delete Service</a>
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    var deleteServiceModal = document.getElementById('deleteServiceModal');
    deleteServiceModal.addEventListener('show.bs.modal', function (event) {
        // Button that triggered the modal
        var button = event.relatedTarget;
        
        // Extract info from data-* attributes
        var serviceId = button.getAttribute('data-id');
        var serviceName = button.getAttribute('data-name');
        
        // Update the modal's content
        var modalBodyName = deleteServiceModal.querySelector('#serviceNameToDelete');
        var confirmDeleteButton = deleteServiceModal.querySelector('#confirmDeleteButton');
        
        modalBodyName.textContent = serviceName;
        confirmDeleteButton.href = 'services.php?delete=' + serviceId;
    });
});
</script>
<?php require_once '../include/footer.php'; ?>