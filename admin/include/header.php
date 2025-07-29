<?php
session_start();

// Security check: Ensure user is logged in and is an admin.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to the login page using an absolute path
    header("Location: /parlor/login.php");
    exit;
}

// Use absolute server path for requires for better reliability
require_once $_SERVER['DOCUMENT_ROOT'] . '/parlor/includes/db_connect.php';
$admin_name = htmlspecialchars($_SESSION['name']);

// Get the current script and folder name to set the active class on nav links
$current_page = basename($_SERVER['PHP_SELF']);
$current_folder = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Aura Salon & Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #212529;
            --sidebar-link: #adb5bd;
            --sidebar-hover: #343a40;
            --sidebar-active: #6a11cb; /* Changed active color for better branding */
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
            width: 250px;
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
            border-left: 3px solid transparent;
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
            margin-right: 10px;
        }
        .main-content {
            margin-left: 250px;
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
        <a href="/parlor/admin/dashboard.php" class="navbar-brand"><i class="fa-solid fa-spa"></i> Aura Salon</a>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="/parlor/admin/dashboard.php"><i class="fa-solid fa-tachometer-alt fa-fw"></i>Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($current_page, ['employees.php', 'add_employee.php', 'edit_employee.php', 'view_employee.php']) ? 'active' : ''; ?>" href="/parlor/admin/employees.php"><i class="fa-solid fa-users fa-fw"></i>Employees</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_folder == 'services') ? 'active' : ''; ?>" href="/parlor/admin/services/services.php"><i class="fa-solid fa-cut fa-fw"></i>Services</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_folder == 'appointment') ? 'active' : ''; ?>" href="/parlor/admin/appointment/appointments.php"><i class="fa-solid fa-calendar-check fa-fw"></i>Appointments</a>
            </li>
            <li class="nav-item mt-auto" style="position:absolute; bottom: 20px; width: 100%;">
                <a class="nav-link" href="/parlor/logout.php"><i class="fa-solid fa-sign-out-alt fa-fw"></i>Logout</a>
            </li>
        </ul>
    </div>
    <div class="main-content">
        <nav class="navbar navbar-expand-lg top-navbar mb-4">
            <div class="container-fluid">
                <span class="navbar-text">
                    Welcome, <strong><?php echo $admin_name; ?></strong>
                </span>
                <a href="/parlor/index.php" class="btn btn-sm btn-outline-secondary ms-auto">View Main Site <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i></a>
            </div>
        </nav>
        <div class="content-wrapper>