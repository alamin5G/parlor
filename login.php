<?php

session_start();
require_once 'includes/db_connect.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Lock endpoints if no super admin
if ($conn) {
    $sql = "SELECT COUNT(*) FROM users WHERE role='admin'";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_row();
        if ($row[0] == 0) {
            if (file_exists('setup_super_admin.php')) {
                header('Location: setup_super_admin.php');
                exit;
            }
        }
    }
}

$msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($conn) {
        $stmt = $conn->prepare("SELECT id, name, password, role, is_verified FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $name, $hashed_pw, $role, $is_verified);
            $stmt->fetch();

            if (password_verify($password, $hashed_pw)) {
                if (($role === 'customer' || $role === 'beautician') && $is_verified == 0) {
                    $_SESSION['registration_email'] = $email;
                    $msg = "Your account is not verified. Please check your email or <a href='resend-verification.php'>resend the verification link</a>.";
                } else {
                    $_SESSION['user_id'] = $id;
                    $_SESSION['name'] = $name;
                    $_SESSION['role'] = $role;

                    if ($role == 'admin') {
                        header('Location: admin/dashboard.php');
                    } elseif ($role == 'beautician') {
                        header('Location: employee/dashboard.php');
                    } else {
                        header('Location: user/dashboard.php');
                    }
                    exit;
                }
            } else {
                $msg = "Invalid email or password.";
            }
        } else {
            $msg = "Invalid email or password.";
        }
        $stmt->close();
    } else {
        $msg = "Database connection error.";
    }
}
?>

<!-- ...your login HTML form goes here... -->


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - Labonno Glamour World</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f5ff; }
        .login-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 1000px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); overflow: hidden; display: flex; }
        .login-form-section { background: #ffffff; padding: 50px; width: 50%; }
        .login-branding-section { width: 50%; background-image: linear-gradient(45deg, #6a11cb, #2575fc); color: white; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 50px; }
        .login-branding-section .logo { font-size: 4rem; margin-bottom: 20px; text-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .login-branding-section h1 { font-weight: 700; font-size: 2.5rem; margin-bottom: 10px; }
        .login-branding-section p { font-weight: 300; font-size: 1.1rem; }
        .form-control { height: 50px; border-radius: 10px; border: 1px solid #ddd; padding-left: 45px; }
        .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(106, 17, 203, 0.25); border-color: #8c4be0; }
        .input-group-text { background: transparent; border: none; position: absolute; left: 15px; top: 50%; transform: translateY(-50%); z-index: 3; color: #aaa; }
        .btn-gradient { background-image: linear-gradient(to right, #6a11cb 0%, #2575fc 100%); color: white; border: none; border-radius: 10px; padding: 12px; font-weight: 600; width: 100%; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .form-title { font-weight: 700; color: #333; }
        .form-text { color: #777; }
        .alert-custom { border-radius: 10px; padding: 12px; font-size: 0.9rem; }
        @media (max-width: 991.98px) { .login-branding-section { display: none; } .login-form-section { width: 100%; } .login-card { flex-direction: column; } }
    </style>
</head>
<body>

<div class="container-fluid login-container">
    <div class="login-card">
        <div class="login-branding-section">
            <div class="logo"><i class="fa-solid fa-spa"></i></div>
            <h1 class="mb-3">Welcome Back</h1>
            <p>Sign in to access your personalized dashboard and manage your beauty journey with Labonno Glamour World.</p>
        </div>
        <div class="login-form-section">
            <a href="index.php" class="text-decoration-none text-muted mb-4 d-inline-block"><i class="fa-solid fa-arrow-left me-2"></i>Back to Home</a>
            <h2 class="form-title mb-2">Member Login</h2>
            <p class="form-text mb-4">Enter your credentials to continue.</p>
            <?php if ($msg): ?>
                <div class="alert alert-danger alert-custom" role="alert">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Email Address" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="rememberMe">
                        <label class="form-check-label form-text" for="rememberMe">Remember me</label>
                    </div>
                    <a href="#" class="text-decoration-none" style="color: #6a11cb;">Forgot Password?</a>
                </div>
                <button type="submit" class="btn btn-gradient">Login</button>
            </form>
            <p class="text-center mt-4 form-text">
                Don't have an account? <a href="register.php" class="text-decoration-none fw-bold" style="color: #6a11cb;">Sign Up</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>