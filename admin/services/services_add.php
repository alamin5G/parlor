<?php
require_once '../include/header.php';

$success_msg = $error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $duration_min = $_POST['duration_min'];
    
    // Validation
    if (empty($name) || empty($price) || empty($duration_min)) {
        $error_msg = "Name, price and duration are required fields.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_msg = "Price must be a positive number.";
    } elseif (!is_numeric($duration_min) || $duration_min <= 0) {
        $error_msg = "Duration must be a positive number.";
    } else {
        // Insert new service
        $stmt = $conn->prepare("INSERT INTO services (name, description, price, duration_min) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $name, $description, $price, $duration_min);
        
        if ($stmt->execute()) {
            $success_msg = "Service added successfully.";
            // Clear form after successful submission
            $name = $description = $price = $duration_min = '';
        } else {
            $error_msg = "Error adding service: " . $conn->error;
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Add New Service</h1>
        <a href="services.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Services
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
                        <label for="name" class="form-label">Service Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="price" class="form-label">Price (৳)</label>
                        <input type="number" class="form-control" id="price" name="price" min="1" step="0.01" value="<?php echo isset($price) ? $price : ''; ?>" required>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="duration_min" class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" id="duration_min" name="duration_min" min="5" step="5" value="<?php echo isset($duration_min) ? $duration_min : '30'; ?>" required>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Add Service
                    </button>
                    <a href="services.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../include/footer.php'; ?>