<?php
// filepath: c:\xampp\htdocs\parlor\employee\include\header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'beautician') {
    header("Location: /parlor/login.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/parlor/includes/db_connect.php';

// Fetch user details including profile photo
$stmt_user = $conn->prepare("SELECT name, profile_photo FROM users WHERE id = ?");
$stmt_user->bind_param("i", $_SESSION['user_id']);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$employee_name = htmlspecialchars($user_data['name']);
$profile_photo_path = $user_data['profile_photo'] ? htmlspecialchars($user_data['profile_photo']) : '/parlor/assets/images/default-avatar.png';
$employee_user_id = $_SESSION['user_id'];
$stmt_user->close();

// Get employee ID
$stmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt->bind_param("i", $employee_user_id);
$stmt->execute();
$employee_id = $stmt->get_result()->fetch_assoc()['id'] ?? 0;
$stmt->close();

// Get notification count for header bell
$sql_notif_count = "SELECT COUNT(*) as count FROM appointments 
                    WHERE employee_id = ? 
                    AND (status = 'booked' OR status = 'confirmed') 
                    AND is_seen_by_employee = 0";
$stmt_count = $conn->prepare($sql_notif_count);
$stmt_count->bind_param("i", $employee_id);
$stmt_count->execute();
$notification_count = $stmt_count->get_result()->fetch_assoc()['count'] ?? 0;
$stmt_count->close();

$current_page = basename($_SERVER['PHP_SELF']);
$nav_sections = [
    'dashboard' => ['dashboard.php'],
    'appointments' => ['appointments.php', 'appointment_view.php', 'calendar.php'],
    'profile' => ['profile.php'],
    'history' => ['history.php', 'bill_view.php'],
    'reviews' => ['reviews.php']
];

function is_section_active($section, $current_page, $sections) {
    return in_array($current_page, $sections[$section]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Employee Dashboard'; ?> - Labonno Glamour World</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --sidebar-bg: #2c3e50; --sidebar-link: #adb5bd; --sidebar-hover: #34495e; --sidebar-active: #1abc9c; --sidebar-dropdown-bg: #233140; }
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .sidebar { position: fixed; top: 0; left: 0; height: 100%; width: 260px; background-color: var(--sidebar-bg); color: white; padding-top: 1rem; }
        .sidebar .navbar-brand { color: #fff !important; font-weight: 600; padding: 0.5rem 1rem; font-size: 1.25rem; text-align: center; display: block; margin-bottom: 1rem; }
        .sidebar .nav-link { color: var(--sidebar-link); font-weight: 500; padding: 0.75rem 1.5rem; border-left: 4px solid transparent; transition: all 0.2s ease-in-out; }
        .sidebar .nav-link:hover { color: #ffffff; background-color: var(--sidebar-hover); }
        .sidebar .nav-link.active { color: #ffffff; background-color: var(--sidebar-active); border-left-color: #fff; }
        .sidebar .nav-link .fa-fw { margin-right: 12px; }
        .sidebar .dropdown-toggle::after { margin-left: auto; transition: transform 0.3s ease; }
        .sidebar .dropdown-toggle[aria-expanded="true"]::after { transform: rotate(90deg); }
        .sidebar .dropdown-menu { background-color: var(--sidebar-dropdown-bg); border: none; padding: 0; margin: 0.5rem 0; }
        .sidebar .dropdown-item { color: var(--sidebar-link); padding: 0.6rem 1.5rem 0.6rem 3rem; }
        .sidebar .dropdown-item:hover, .sidebar .dropdown-item.active { background-color: var(--sidebar-active); color: #fff; }
        .main-content { margin-left: 260px; padding: 20px; min-height: 100vh; display: flex; flex-direction: column; }
        .top-navbar { background-color: #fff; border-radius: 0.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .content-wrapper { flex: 1; }
        .profile-pic-nav { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-left: 15px; }
        /* Enhanced Notification Styles */
        .notification-bell { font-size: 1.2rem; color: #6c757d; transition: color 0.2s ease; }
        .notification-bell:hover { color: #0d6efd; }
        .notification-bell.has-notifications { color: #dc3545; }
        .notification-badge { position: absolute; top: -5px; right: -10px; padding: 0.25em 0.5em; font-size: 0.7rem; }
        .notification-dropdown { width: 350px; max-height: 400px; overflow-y: auto; padding: 0; }
        .notification-header { display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; background-color: #f8f9fa; border-bottom: 1px solid #e9ecef; }
        .notification-item { padding: 10px 15px; border-bottom: 1px solid #e9ecef; transition: background-color 0.2s ease; }
        .notification-item:last-child { border-bottom: none; }
        .notification-item:hover { background-color: #f8f9fa; }
        .notification-item .btn-sm { padding: 0.2rem 0.5rem; font-size: 0.75rem; }
        .notification-footer { padding: 8px 15px; background-color: #f8f9fa; border-top: 1px solid #e9ecef; text-align: center; }
        .notification-footer a { font-size: 0.875rem; color: #007bff; text-decoration: none; }
        .notification-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="dashboard.php" class="navbar-brand"><i class="fa-solid fa-user-clock"></i> Employee Panel</a>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link <?php if(is_section_active('dashboard', $current_page, $nav_sections)) echo 'active'; ?>" href="dashboard.php"><i class="fa-solid fa-tachometer-alt fa-fw"></i>Dashboard</a></li>
            
            <li class="nav-item">
                <a class="nav-link dropdown-toggle d-flex align-items-center <?php if(is_section_active('appointments', $current_page, $nav_sections)) echo 'active'; ?>" href="#apptSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo is_section_active('appointments', $current_page, $nav_sections) ? 'true' : 'false'; ?>">
                    <i class="fa-solid fa-calendar-check fa-fw"></i>Appointments
                </a>
                <ul class="collapse list-unstyled <?php if(is_section_active('appointments', $current_page, $nav_sections)) echo 'show'; ?>" id="apptSubmenu">
                    <li><a class="dropdown-item <?php if($current_page == 'appointments.php' || $current_page == 'appointment_view.php') echo 'active'; ?>" href="appointments.php">List View</a></li>
                    <li><a class="dropdown-item <?php if($current_page == 'calendar.php') echo 'active'; ?>" href="calendar.php">Calendar View</a></li>
                </ul>
            </li>

            <li class="nav-item"><a class="nav-link <?php if(is_section_active('history', $current_page, $nav_sections)) echo 'active'; ?>" href="history.php"><i class="fa-solid fa-history fa-fw"></i>Service History</a></li>
            <li class="nav-item"><a class="nav-link <?php if(is_section_active('reviews', $current_page, $nav_sections)) echo 'active'; ?>" href="reviews.php"><i class="fa-solid fa-star fa-fw"></i>My Reviews</a></li>
            <li class="nav-item"><a class="nav-link <?php if(is_section_active('profile', $current_page, $nav_sections)) echo 'active'; ?>" href="profile.php"><i class="fa-solid fa-user-edit fa-fw"></i>My Profile</a></li>
        </ul>
    </div>
    <div class="main-content">
        <nav class="navbar navbar-expand-lg top-navbar mb-4">
            <div class="container-fluid">
                <div class="ms-auto d-flex align-items-center">
                    <!-- Enhanced Notification Bell -->
                    <div class="dropdown notification-dropdown-container">
                        <a href="#" class="text-decoration-none position-relative me-3" 
                           id="notificationBellLink" 
                           data-bs-toggle="dropdown" 
                           data-bs-auto-close="outside" 
                           aria-expanded="false">
                            <i class="fas fa-bell notification-bell <?php if($notification_count > 0) echo 'has-notifications'; ?>"></i>
                            <?php if($notification_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notification-badge">
                                    <?= $notification_count <= 99 ? $notification_count : '99+' ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationBellLink" id="notification-dropdown-menu">
                            <div class="notification-header">
                                <strong>Notifications</strong>
                                <?php if($notification_count > 0): ?>
                                    <button class="btn btn-sm btn-outline-secondary" id="mark-all-read-header">
                                        <i class="fas fa-check-double"></i> Mark all read
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div id="notification-list">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 mb-0 text-muted">Loading notifications...</p>
                                </div>
                            </div>
                            <div class="notification-footer">
                                <a href="dashboard.php">View all on dashboard</a>
                            </div>
                        </div>
                    </div>
                    <span class="navbar-text">Welcome, <?php echo $employee_name; ?>!</span>
                    <img src="<?php echo $profile_photo_path; ?>" alt="Profile Picture" class="profile-pic-nav">
                    <a class="btn btn-light ms-3" href="/parlor/logout.php"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout</a>
                </div>
            </div>
        </nav>
        <div class="content-wrapper">