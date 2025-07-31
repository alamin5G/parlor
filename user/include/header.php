<?php
// filepath: c:\xampp\htdocs\parlor\user\include\header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Ensure user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: /parlor/login.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/parlor/includes/db_connect.php';

// Fetch user details for display
$stmt_user = $conn->prepare("SELECT name, profile_photo FROM users WHERE id = ?");
$stmt_user->bind_param("i", $_SESSION['user_id']);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$customer_name = htmlspecialchars($user_data['name']);
$profile_photo_path = $user_data['profile_photo'] ? htmlspecialchars($user_data['profile_photo']) : '/parlor/assets/images/default-avatar.png';
$customer_user_id = $_SESSION['user_id'];
$stmt_user->close();

$current_page = basename($_SERVER['PHP_SELF']);

// Define navigation sections for active state highlighting
$nav_sections = [
    'dashboard' => ['dashboard.php'],
    'booking' => ['services.php', 'book.php'],
    'appointments' => ['my_appointments.php', 'appointment_view.php', 'edit_appointment.php', 'cancel_appointment.php', 'review.php'],
    'billing' => ['my_billing.php', 'payment_history.php'],
    'reviews' => ['my_reviews.php'],
    'profile' => ['profile.php']
];

function is_section_active($section, $current_page, $sections) {
    if (!isset($sections[$section])) return false;
    return in_array($current_page, $sections[$section]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Customer Dashboard'; ?> - Labonno Glamour World</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Corrected path to the shared stylesheet -->
    <link rel="stylesheet" href="/parlor/user/assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <a href="dashboard.php" class="navbar-brand"><i class="fa-solid fa-spa"></i> Customer Panel</a>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link <?php if(is_section_active('dashboard', $current_page, $nav_sections)) echo 'active'; ?>" href="dashboard.php"><i class="fa-solid fa-tachometer-alt fa-fw"></i>Dashboard</a></li>
            <li class="nav-item"><a class="nav-link <?php if(is_section_active('booking', $current_page, $nav_sections)) echo 'active'; ?>" href="book.php"><i class="fa-solid fa-calendar-plus fa-fw"></i>Book Appointment</a></li>
            <li class="nav-item"><a class="nav-link <?php if(is_section_active('appointments', $current_page, $nav_sections)) echo 'active'; ?>" href="my_appointments.php"><i class="fa-solid fa-calendar-check fa-fw"></i>My Appointments</a></li>
            <li class="nav-item"><a class="nav-link <?php if(isset($_GET['status']) && $_GET['status'] == 'completed') echo 'active'; ?>" href="my_appointments.php?status=completed"><i class="fa-solid fa-history fa-fw"></i>My History</a></li>
            <!-- New links for billing and reviews -->
            <li class="nav-item"><a class="nav-link <?php if(is_section_active('billing', $current_page, $nav_sections)) echo 'active'; ?>" href="my_billing.php"><i class="fa-solid fa-receipt fa-fw"></i>My Billing</a></li>
            <li class="nav-item"><a class="nav-link <?php if(is_section_active('reviews', $current_page, $nav_sections)) echo 'active'; ?>" href="my_reviews.php"><i class="fa-solid fa-star fa-fw"></i>My Reviews</a></li>
            <li class="nav-item"><a class="nav-link <?php if(is_section_active('profile', $current_page, $nav_sections)) echo 'active'; ?>" href="profile.php"><i class="fa-solid fa-user-edit fa-fw"></i>My Profile</a></li>
        </ul>
    </div>
    <div class="main-content">
        <nav class="navbar navbar-expand-lg top-navbar mb-4">
            <div class="container-fluid">
                <div class="ms-auto d-flex align-items-center">
                    <span class="navbar-text">Welcome, <?php echo $customer_name; ?>!</span>
                    <img src="<?php echo $profile_photo_path; ?>" alt="Profile Picture" class="profile-pic-nav">
                    <a class="btn btn-light ms-3" href="/parlor/logout.php"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout</a>
                </div>
            </div>
        </nav>
        <div class="content-wrapper">