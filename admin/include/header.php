<?php
// filepath: c:\xampp\htdocs\parlor\admin\include\header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure user is logged in and is an admin.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /parlor/login.php");
    exit;
}

// Use absolute server path for requires for better reliability
require_once $_SERVER['DOCUMENT_ROOT'] . '/parlor/includes/db_connect.php';
$admin_name = htmlspecialchars($_SESSION['name']);

// Get the current script name to set the active class on nav links
$current_page = basename($_SERVER['PHP_SELF']);

// Define which pages belong to which navigation section for active state
$nav_sections = [
    'dashboard' => ['dashboard.php'],
    'appointments' => ['appointments.php', 'appointment_add.php', 'appointment_edit.php'],
    'users' => ['employees.php', 'add_employee.php', 'edit_employee.php', 'view_employee.php', 'customers.php', 'view_user.php'],
    'management' => ['services.php', 'service_add.php', 'service_edit.php'],
    'financials' => ['bills.php', 'view_bill.php', 'reports.php'],
    'profile' => ['profile.php'] // Added profile page
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
    <title><?php echo $page_title ?? 'Admin Dashboard'; ?> - Labonno Glamour World</title>
    
    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Page-specific CSS -->
    <?php
    if (isset($page_specific_css) && !empty($page_specific_css)) {
        echo '<link rel="stylesheet" href="' . htmlspecialchars($page_specific_css) . '">';
    }
    ?>

    <!-- Main Stylesheet -->
    <style>
        :root {
            --sidebar-bg: #212529;
            --sidebar-link: #adb5bd;
            --sidebar-hover: #343a40;
            --sidebar-active: #6a11cb;
            --sidebar-dropdown-bg: #1a1d20;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 260px;
            background-color: var(--sidebar-bg);
            color: white;
            padding-top: 1rem;
            transition: all 0.3s;
        }
        .sidebar .navbar-brand {
            color: #fff !important;
            font-weight: 600;
            padding: 0.5rem 1rem;
            font-size: 1.25rem;
            text-align: center;
            display: block;
            margin-bottom: 1rem;
        }
        .sidebar .nav-link {
            color: var(--sidebar-link);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-left: 4px solid transparent;
            transition: all 0.2s ease-in-out;
        }
        .sidebar .nav-link:hover {
            color: #ffffff;
            background-color: var(--sidebar-hover);
            border-left-color: #fff;
        }
        .sidebar .nav-link.active {
            color: #ffffff;
            background-color: var(--sidebar-active);
            border-left-color: #fff;
        }
        .sidebar .nav-link .fa-fw {
            margin-right: 12px;
        }
        .sidebar .dropdown-toggle::after {
            margin-left: auto;
            transition: transform 0.3s ease;
        }
        .sidebar .dropdown-toggle[aria-expanded="true"]::after {
            transform: rotate(90deg);
        }
        .sidebar .dropdown-menu {
            background-color: var(--sidebar-dropdown-bg);
            border: none;
            padding: 0;
            margin: 0.5rem 0;
        }
        .sidebar .dropdown-item {
            color: var(--sidebar-link);
            padding: 0.6rem 1.5rem 0.6rem 3rem;
        }
        .sidebar .dropdown-item:hover, .sidebar .dropdown-item.active {
            background-color: var(--sidebar-active);
            color: #fff;
        }
        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .top-navbar {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .content-wrapper {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="/parlor/admin/dashboard.php" class="navbar-brand"><i class="fa-solid fa-spa"></i> Labonno Glamour</a>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php if(is_section_active('dashboard', $current_page, $nav_sections)) echo 'active'; ?>" href="/parlor/admin/dashboard.php">
                    <i class="fa-solid fa-tachometer-alt fa-fw"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if(is_section_active('appointments', $current_page, $nav_sections)) echo 'active'; ?>" href="/parlor/admin/appointment/appointments.php">
                    <i class="fa-solid fa-calendar-check fa-fw"></i>Appointments
                </a>
            </li>

            <!-- User Management Dropdown -->
            <li class="nav-item">
                <a class="nav-link dropdown-toggle d-flex align-items-center <?php if(is_section_active('users', $current_page, $nav_sections)) echo 'active'; ?>" href="#userSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo is_section_active('users', $current_page, $nav_sections) ? 'true' : 'false'; ?>">
                    <i class="fa-solid fa-users-cog fa-fw"></i>User Management
                </a>
                <ul class="collapse list-unstyled <?php if(is_section_active('users', $current_page, $nav_sections)) echo 'show'; ?>" id="userSubmenu">
                    <li><a class="dropdown-item <?php if($current_page == 'customers.php' || $current_page == 'view_user.php') echo 'active'; ?>" href="/parlor/admin/customers.php">Customers</a></li>
                    <li><a class="dropdown-item <?php if(in_array($current_page, ['employees.php', 'add_employee.php', 'edit_employee.php', 'view_employee.php'])) echo 'active'; ?>" href="/parlor/admin/employees.php">Employees</a></li>
                </ul>
            </li>

            <!-- Parlor Management Dropdown -->
            <li class="nav-item">
                <a class="nav-link dropdown-toggle d-flex align-items-center <?php if(is_section_active('management', $current_page, $nav_sections)) echo 'active'; ?>" href="#parlorSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo is_section_active('management', $current_page, $nav_sections) ? 'true' : 'false'; ?>">
                    <i class="fa-solid fa-store fa-fw"></i>Parlor Management
                </a>
                <ul class="collapse list-unstyled <?php if(is_section_active('management', $current_page, $nav_sections)) echo 'show'; ?>" id="parlorSubmenu">
                    <li><a class="dropdown-item <?php if(in_array($current_page, ['services.php', 'service_add.php', 'service_edit.php'])) echo 'active'; ?>" href="/parlor/admin/services/services.php">Services</a></li>
                </ul>
            </li>
            
            <!-- Financials Dropdown -->
            <li class="nav-item">
                <a class="nav-link dropdown-toggle d-flex align-items-center <?php if(is_section_active('financials', $current_page, $nav_sections)) echo 'active'; ?>" href="#financialsSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo is_section_active('financials', $current_page, $nav_sections) ? 'true' : 'false'; ?>">
                    <i class="fa-solid fa-file-invoice-dollar fa-fw"></i>Financials
                </a>
                <ul class="collapse list-unstyled <?php if(is_section_active('financials', $current_page, $nav_sections)) echo 'show'; ?>" id="financialsSubmenu">
                    <li><a class="dropdown-item <?php if($current_page == 'bills.php' || $current_page == 'view_bill.php') echo 'active'; ?>" href="/parlor/admin/bills.php">Bills</a></li>
                    <li><a class="dropdown-item <?php if($current_page == 'reports.php') echo 'active'; ?>" href="#">Reports</a></li>
                </ul>
            </li>
        </ul>
    </div>
    <div class="main-content">
        <nav class="navbar navbar-expand-lg top-navbar mb-4">
            <div class="container-fluid">
                <!-- This span is intentionally left empty for alignment -->
                <span class="navbar-text"></span>
                
                <div class="ms-auto d-flex align-items-center">
                    <a href="/parlor/index.php" class="btn btn-sm btn-outline-secondary me-3" target="_blank">View Main Site <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i></a>
                    
                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle <?php if(is_section_active('profile', $current_page, $nav_sections)) echo 'show'; ?>" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle fa-2x me-2 text-secondary"></i>
                            <strong><?php echo $admin_name; ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item <?php if($current_page == 'profile.php') echo 'active'; ?>" href="/parlor/admin/profile.php"><i class="fas fa-user-edit fa-fw me-2"></i>My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/parlor/logout.php"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        <div class="content-wrapper">