<?php
session_start();
require_once 'includes/db_connect.php';

// Load Composer's autoloader
require 'vendor/autoload.php';

// Include email functions
require_once 'includes/email_functions.php';

$msg = '';
$msg_type = 'danger'; // Default to error type

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'customer'; // Hardcode role to 'customer' for public registration

    // --- Validation ---
    if (empty($name) || empty($email) || empty($password)) {
        $msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $msg = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $msg = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $msg = "An account with this email already exists.";
            $stmt->close(); // Close the statement here
        } else {
            // All checks passed, proceed with registration
            $stmt->close(); // Close the first statement before creating a new one

            
            $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
            $verify_token = bin2hex(random_bytes(32));
            
            $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role, verify_token) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssss", $name, $email, $hashed_pw, $phone, $role, $verify_token);
            
            if ($insert_stmt->execute()) {
                // Prepare and send the first verification email
                $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/parlor/verify.php?token=" . $verify_token;
                $email_subject = "Verify your Aura Salon & Spa account";
               
                // Prepare email content
                $email_subject = "Verify your Labonno Glamour World account";
                $email_body = "<html>
                <head>
                <title>Verify Your Email</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    h2 { color: #6a11cb; }
                    .btn { display: inline-block; padding: 10px 20px; background: linear-gradient(to right, #6a11cb, #f2f7ffff); 
                           color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
                    .footer { margin-top: 30px; font-size: 0.8em; color: #666; }
                </style>
                </head>
                <body>
                <div class='container'>
                    <h2>Welcome to Labonno Glamour World!</h2>
                    <p>Hello $name,</p>
                    <p>Thank you for registering. To complete your registration and verify your email address, please click the button below:</p>
                    <p style='text-align: center;'><a href='$verification_link' class='btn'>Verify Your Email</a></p>
                    <p>Or copy and paste this URL into your browser:</p>
                    <p>$verification_link</p>
                    <p>This link will expire in 24 hours.</p>
                    <div class='footer'>
                        <p>Regards,<br>Labonno Glamour World Team</p>
                        <p>If you didn't create an account, you can safely ignore this email.</p>
                    </div>
                </div>
                </body>
                </html>
                ";
                
                 // Send email using our custom function
                 $email_result = send_email($email, $name, $email_subject, $email_body);

                if ($email_result['success']) {
                    // Store email in session and redirect to the verification notice page
                    $_SESSION['registration_email'] = $email;
                    header("Location: resend-verification.php");
                    exit();
                } else {
                    $msg = "Registration successful, but we could not send a verification email. Please try again later.";
                    // Optionally log the email error
                    error_log("Email sending failed for new user {$email}: " . $email_result['message']);
                }

            } else {
                $msg = "Registration failed. Please try again later.";
            }
            $insert_stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register - Labonno Glamour World</title>
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
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .register-card {
            width: 100%;
            max-width: 1000px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
        }
        .register-form-section {
            background: #ffffff;
            padding: 40px;
            width: 50%;
        }
        .register-branding-section {
            width: 50%;
            background-image: linear-gradient(45deg, #2575fc, #6a11cb);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 50px;
        }
        .register-branding-section .logo {
            font-size: 4rem;
            margin-bottom: 20px;
            text-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .register-branding-section h1 {
            font-weight: 700;
            font-size: 2.5rem;
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
        .alert-custom { border-radius: 10px; padding: 12px; font-size: 0.9rem; }

        @media (max-width: 991.98px) {
            .register-branding-section { display: none; }
            .register-form-section { width: 100%; }
            .register-card { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="container-fluid register-container">
    <div class="register-card">
        
        <!-- Form Section -->
        <div class="register-form-section">
            <a href="index.php" class="text-decoration-none text-muted mb-4 d-inline-block"><i class="fa-solid fa-arrow-left me-2"></i>Back to Home</a>
            <h2 class="form-title mb-2">Create Account</h2>
            <p class="form-text mb-4">Join our community to book and manage your appointments.</p>

            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-custom" role="alert">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php">
                <div class="mb-3">
                    <div class="input-group"><span class="input-group-text"><i class="fa-solid fa-user"></i></span><input type="text" name="name" class="form-control" placeholder="Full Name" required></div>
                </div>
                <div class="mb-3">
                    <div class="input-group"><span class="input-group-text"><i class="fa-solid fa-envelope"></i></span><input type="email" name="email" class="form-control" placeholder="Email Address" required></div>
                </div>
                <div class="mb-3">
                    <div class="input-group"><span class="input-group-text"><i class="fa-solid fa-phone"></i></span><input type="tel" name="phone" class="form-control" placeholder="Phone Number (Optional)"></div>
                </div>
                <div class="mb-3">
                    <div class="input-group"><span class="input-group-text"><i class="fa-solid fa-lock"></i></span><input type="password" name="password" class="form-control" placeholder="Password (min. 6 characters)" required></div>
                </div>
                <div class="mb-4">
                    <div class="input-group"><span class="input-group-text"><i class="fa-solid fa-lock"></i></span><input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required></div>
                </div>
                <button type="submit" class="btn btn-gradient">Create Account</button>
            </form>

            <p class="text-center mt-4 form-text">
                Already have an account? <a href="login.php" class="text-decoration-none fw-bold" style="color: #6a11cb;">Log In</a>
            </p>
        </div>

        <!-- Branding Section -->
        <div class="register-branding-section">
            <div class="logo"><i class="fa-solid fa-spa"></i></div>
            <h1 class="mb-3">Join Labonno Glamour World</h1>
            <p>Create your account in seconds to unlock exclusive access to our services and easy online booking.</p>
        </div>

    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>