<?php require_once '../includes/db_connect.php'; ?>
<?php require_once 'include/header.php'; ?>

<div class="container-fluid">
    <h1 class="mt-4">Dashboard</h1>
    <p>Welcome to the Labonno Glamour World admin panel. From here you can manage employees, services, appointments, and more.</p>
    
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">Total Appointments</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">Total Employees</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="employees.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <!-- Add more summary cards as needed -->
    </div>
</div>

<?php require_once 'include/footer.php'; ?>