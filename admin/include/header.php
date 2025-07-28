<?php
session_start();

// Security check: Ensure user is logged in and is an admin.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // If not logged in or not an admin, redirect to the login page
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';
$admin_name = htmlspecialchars($_SESSION['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Labonno Glamour World</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
            background-color: #343a40;
            color: white;
            padding-top: 20px;
        }
        .sidebar .nav-link {
            color: #adb5bd;
            font-weight: 500;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #ffffff;
            background-color: #495057;
        }
        .sidebar .nav-link .fa-fw {
            margin-right: 10px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .navbar-brand {
            color: #fff !important;
            font-weight: 600;
            padding-left: 15px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="dashboard.php" class="navbar-brand mb-4">Admin Panel</a>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php"><i class="fa-solid fa-tachometer-alt fa-fw"></i>Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="employees.php"><i class="fa-solid fa-users fa-fw"></i>Employees</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="fa-solid fa-cut fa-fw"></i>Services</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="fa-solid fa-calendar-check fa-fw"></i>Appointments</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="fa-solid fa-file-invoice-dollar fa-fw"></i>Billing</a>
            </li>
            <li class="nav-item mt-auto" style="position:absolute; bottom: 20px; width: 100%;">
                <a class="nav-link" href="../logout.php"><i class="fa-solid fa-sign-out-alt fa-fw"></i>Logout</a>
            </li>
        </ul>
    </div>
    <main class="main-content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 rounded">
            <div class="container-fluid">
                <span class="navbar-text">
                    Welcome, <strong><?php echo $admin_name; ?></strong>
                </span>
            </div>
        </nav>