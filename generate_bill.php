<?php
session_start();
require_once 'includes/db_connect.php';

// --- Security Check ---
// 1. User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. A valid appointment ID must be provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect based on role
    $redirect_url = ($_SESSION['role'] === 'admin') ? 'admin/appointment/appointments.php' : 'user/dashboard.php';
    header("Location: $redirect_url");
    exit();
}

$appointment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// --- Build the Database Query ---
$sql = "
    SELECT 
        a.id as appointment_id, a.customer_id, a.scheduled_at, a.status,
        u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
        eu.name as employee_name,
        s.name as service_name, s.price as service_price
    FROM appointments a
    JOIN users u ON a.customer_id = u.id
    JOIN employees e ON a.employee_id = e.id
    JOIN users eu ON e.user_id = eu.id
    JOIN services s ON a.service_id = s.id
    WHERE a.id = ?
";

// 3. Add role-based access control to the query
if ($user_role === 'customer') {
    $sql .= " AND a.customer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $appointment_id, $user_id);
} else { // For admin or other future roles
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
}

$stmt->execute();
$result = $stmt->get_result();
$bill = $result->fetch_assoc();

// 4. If no bill is found (or user doesn't have permission), redirect
if (!$bill) {
    $redirect_url = ($user_role === 'admin') ? 'admin/appointment/appointments.php' : 'user/dashboard.php';
    $_SESSION['error_msg'] = "Invoice not found or you do not have permission to view it.";
    header("Location: $redirect_url");
    exit();
}

// --- Decide which header/footer to use ---
// This is a simplified approach. For a large app, a different structure might be better.
$header_path = '';
$footer_path = '';
$back_link = '#';

if ($user_role === 'admin') {
    $header_path = 'admin/include/header.php';
    $footer_path = 'admin/include/footer.php';
    $back_link = 'admin/appointment/appointments.php';
} else { // Assuming 'customer'
    // You would need to create a header/footer for the user dashboard
    // For now, we'll create a basic HTML structure.
    // To make this work, you'd create user/include/header.php etc.
}

// If a user-specific header exists, use it. Otherwise, print a generic one.
if (!empty($header_path) && file_exists($header_path)) {
    require_once $header_path;
} else {
    // Fallback for roles without a specific header (like customer)
    echo '<!DOCTYPE html><html lang="en"><head><title>Invoice</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
          <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
          </head><body><div class="container mt-4">';
}
?>

<style>
    /* (Paste the same CSS from the previous generate_bill.php here) */
    .invoice-container { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); font-size: 16px; line-height: 24px; font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; color: #555; background: #fff; }
    .invoice-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
    .invoice-header .title { font-size: 45px; line-height: 45px; color: #333; font-weight: bold; }
    .invoice-details { display: flex; justify-content: space-between; margin-bottom: 40px; }
    .invoice-table table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
    .invoice-table table td { padding: 8px; vertical-align: top; }
    .invoice-table table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
    .invoice-table table tr.item td { border-bottom: 1px solid #eee; }
    .invoice-table table tr.total td:last-child { border-top: 2px solid #eee; font-weight: bold; font-size: 1.2em; }
    .invoice-footer { text-align: center; margin-top: 40px; font-size: 14px; color: #777; }
    .no-print { text-align: center; margin-top: 20px; }
    @media print {
        body { background-color: #fff; }
        .no-print, .top-navbar, .sidebar, footer, .main-content > .container-fluid > .no-print { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; }
        .invoice-container { box-shadow: none; border: none; max-width: 100%; margin: 0; padding: 0; }
    }
</style>

<div class="invoice-container">
    <!-- The entire invoice HTML structure remains the same as before -->
    <div class="invoice-header">
        <div>
            <h1 class="navbar-brand" style="font-size: 2rem;"><i class="fa-solid fa-spa"></i> Aura Salon & Spa</h1>
            <p class="mb-0">123 Beauty Lane, Glamour City</p>
        </div>
        <div class="title">INVOICE</div>
    </div>
    <div class="invoice-details">
        <div>
            <strong>Billed To:</strong><br>
            <?php echo htmlspecialchars($bill['customer_name']); ?><br>
            <?php echo htmlspecialchars($bill['customer_email']); ?><br>
            <?php echo htmlspecialchars($bill['customer_phone']); ?>
        </div>
        <div>
            <strong>Invoice #:</strong> <?php echo $bill['appointment_id']; ?><br>
            <strong>Date Issued:</strong> <?php echo date('F j, Y'); ?><br>
            <strong>Appointment Date:</strong> <?php echo date('F j, Y, g:i A', strtotime($bill['scheduled_at'])); ?>
        </div>
    </div>
    <div class="invoice-table">
        <table>
            <tr class="heading"><td>Service</td><td>Beautician</td><td style="text-align:right;">Price</td></tr>
            <tr class="item"><td><?php echo htmlspecialchars($bill['service_name']); ?></td><td><?php echo htmlspecialchars($bill['employee_name']); ?></td><td style="text-align:right;">৳<?php echo number_format($bill['service_price'], 2); ?></td></tr>
            <tr class="total"><td></td><td style="text-align:right;"><strong>Total:</strong></td><td style="text-align:right;">৳<?php echo number_format($bill['service_price'], 2); ?></td></tr>
        </table>
    </div>
    <div class="invoice-footer"><p>Thank you for your business!</p></div>
</div>

<div class="no-print">
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Invoice</button>
    <a href="<?php echo $back_link; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<?php
// If a user-specific footer exists, use it.
if (!empty($footer_path) && file_exists($footer_path)) {
    require_once $footer_path;
} else {
    // Fallback for roles without a specific footer
    echo '</div></body></html>';
}
?>