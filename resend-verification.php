<?php
session_start();
require_once 'db_connect.php';

// Load Composer's autoloader
require 'vendor/autoload.php';

// Include email functions
require_once 'includes/email_functions.php';

$msg = '';
$msg_type = 'danger';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please enter a valid email address.";
    } else {
        // Check if email exists and is not verified
        $stmt = $conn->prepare("SELECT id, name, verify_token, is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if ($user['is_verified'] == 1) {
                $msg = "This account is already verified. You can log in.";
                $msg_type = 'info';
            } else {
                // Generate new token
                $new_token = bin2hex(random_bytes(32));
                
                $update = $conn->prepare("UPDATE users SET verify_token = ? WHERE id = ?");
                $update->bind_param("si", $new_token, $user['id']);
                $update->execute();
                
                // Create verification link
                $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/parlor/verify.php?token=" . $new_token;
                
                // Prepare email content
                $email_subject = "Verify your Aura Salon & Spa account";
                $email_body = "
                <html>
                <head>
                <title>Verify Your Email</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    h2 { color: #6a11cb; }
                    .btn { display: inline-block; padding: 10px 20px; background: linear-gradient(to right, #6a11cb, #2575fc); 
                           color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
                    .footer { margin-top: 30px; font-size: 0.8em; color: #666; }
                </style>
                </head>
                <body>
                <div class='container'>
                    <h2>Verify Your Aura Salon & Spa Account</h2>
                    <p>Hello {$user['name']},</p>
                    <p>You requested a new verification link. Please click the button below to verify your email address:</p>
                    <p style='text-align: center;'><a href='$verification_link' class='btn'>Verify Your Email</a></p>
                    <p>Or copy and paste this URL into your browser:</p>
                    <p>$verification_link</p>
                    <p>This link will expire in 24 hours.</p>
                    <div class='footer'>
                        <p>Regards,<br>Aura Salon & Spa Team</p>
                        <p>If you didn't request this email, you can safely ignore it.</p>
                    </div>
                </div>
                </body>
                </html>
                ";
                
                // Send email using our custom function
                $email_result = send_email($email, $user['name'], $email_subject, $email_body);
                
                if ($email_result['success']) {
                    $msg = "Verification email has been resent. Please check your inbox.";
                    $msg_type = 'success';
                } else {
                    $msg = "Could not send verification email. Please try again later.";
                    
                    // Log the error (for admin/developer)
                    error_log("Email sending failed: " . $email_result['message']);
                }
                
                $update->close();
            }
        } else {
            $msg = "No account found with that email address.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Resend Verification - Aura Salon & Spa</title>
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
        .resend-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .resend-card {
            width: 100%;
            max-width: 500px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            overflow: hidden;
            background: white;
            padding: 40px;
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
        .logo {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #6a11cb;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container-fluid resend-container">
    <div class="resend-card">
        <a href="login.php" class="text-decoration-none text-muted mb-4 d-inline-block"><i class="fa-solid fa-arrow-left me-2"></i>Back to Login</a>
        <div class="logo"><i class="fa-solid fa-spa"></i></div>
        <h2 class="text-center mb-4">Resend Verification Email</h2>
        
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?>" role="alert">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>
        
        <p class="text-center mb-4">Enter your email address to receive a new verification link.</p>
        
        <form method="post" action="resend-verification.php">
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                </div>
            </div>
            <button type="submit" class="btn btn-gradient">Resend Verification Email</button>
        </form>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>