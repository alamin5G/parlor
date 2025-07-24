<?php
require_once 'db_connect.php';

// Check if super admin exists
if ($conn) {
    $sql = "SELECT COUNT(*) FROM users WHERE role='admin'";
    $result = $conn->query($sql);
    $row = $result->fetch_row();

    if ($row[0] > 0) {
        // Super Admin exists, this page is no longer needed. Redirect to login.
        header("Location: login.php");
        exit;
    }
} else {
    die("Database connection failed. Please check your db_connect.php file.");
}

$msg = '';
// Handle super admin registration form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password)) {
        $msg = "Name, Email, and Password are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $msg = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $msg = "Passwords do not match.";
    } else {
        $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'admin')");
        $stmt->bind_param("ssss", $name, $email, $hashed_pw, $phone);
        
        if ($stmt->execute()) {
            // Success: Redirect to login with a success message.
            // The check at the top of this file will now prevent access.
            header("Location: login.php?setup_success=1");
            exit;
        } else {
            $msg = "Error creating the Super Admin account. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>System Setup - Aura Salon & Spa</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts (Poppins) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f5ff;
        }
        .setup-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        .setup-card {
            width: 100%;
            max-width: 600px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }
        .setup-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #6a11cb;
        }
        .form-control {
            height: 50px;
            border-radius: 10px;
            border: 1px solid #ddd;
            padding-left: 45px;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(106, 17, 203, 0.25);
            border-color: #8c4be0;
        }
        .input-group-text {
            background: transparent;
            border: none;
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 3;
            color: #aaa;
        }
        .btn-gradient {
            background-image: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }
        .form-title { font-weight: 700; color: #333; }
        .form-text { color: #777; }
        .alert-custom { border-radius: 10px; padding: 12px; font-size: 0.9rem; text-align: left; }
    </style>
</head>
<body>

<div class="container-fluid setup-container">
    <div class="setup-card">
        <div class="setup-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <h2 class="form-title">System Setup</h2>
        <p class="form-text mb-4">Welcome! To get started, you must create the first <strong>Super Admin</strong> account. This page will be locked automatically after setup is complete.</p>

        <?php if ($msg): ?>
            <div class="alert alert-danger alert-custom" role="alert">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="text-start">
            <div class="mb-3">
                <label class="form-label fw-semibold">Full Name</label>
                <div class="input-group"><span class="input-group-text"><i class="fa-solid fa-user"></i></span><input type="text" name="name" class="form-control" required autofocus></div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <div class="input-group"><span class="input-group-text"><i class="fa-solid fa-envelope"></i></span><input type="email" name="email" class="form-control" required></div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Phone Number (Optional)</label>
                <div class="input-group"><span class="input-group-text"><i class="fa-solid fa-phone"></i></span><input type="tel" name="phone" class="form-control"></div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group"><span class="input-group-text"><i class="fa-solid fa-lock"></i></span><input type="password" name="password" class="form-control" required minlength="6"></div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Confirm Password</label>
                <div class="input-group"><span class="input-group-text"><i class="fa-solid fa-lock"></i></span><input type="password" name="confirm_password" class="form-control" required></div>
            </div>
            <button type="submit" class="btn btn-gradient">Create Admin & Launch System</button>
        </form>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>