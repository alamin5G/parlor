<?php
session_start();
require_once 'includes/db_connect.php';
require 'vendor/autoload.php';
require_once 'includes/email_functions.php';

// --- LOCKDOWN ---
// If the user did not come from the registration page, redirect them.
if (!isset($_SESSION['registration_email'])) {
    header('Location: login.php');
    exit();
}

$email = $_SESSION['registration_email'];
$msg = '';
$msg_type = 'info'; // Default to info

// Check if the user is already verified (e.g., if they use the back button after verifying)
$check_stmt = $conn->prepare("SELECT is_verified FROM users WHERE email = ?");
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$result = $check_stmt->get_result();
$user_check = $result->fetch_assoc();
if ($user_check && $user_check['is_verified'] == 1) {
    // User is verified, clear the session and redirect to login.
    unset($_SESSION['registration_email']);
    header('Location: login.php?verified=1'); // Add a success message on login page
    exit();
}
$check_stmt->close();


// Handle the resend request from the button
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // The email is already known from the session
    $stmt = $conn->prepare("SELECT id, name, verify_token FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Prepare and send the email again
        $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/parlor/verify.php?token=" . $user['verify_token'];
        $email_subject = "Verify your Labonno Glamour World account";
        $email_body = "Hello {$user['name']},<br><br>Here is your verification link again:<br><a href='{$verification_link}'>Verify Account</a>";
        
        $email_result = send_email($email, $user['name'], $email_subject, $email_body);
        
        if ($email_result['success']) {
            $msg = "A new verification email has been sent to <strong>" . htmlspecialchars($email) . "</strong>. Please check your inbox.";
            $msg_type = 'success';
        } else {
            $msg = "Could not send verification email. Please try again later.";
            $msg_type = 'danger';
            error_log("Email resend failed for {$email}: " . $email_result['message']);
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Verify Your Account - Labonno Glamour World</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f5ff; }
        .notice-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .notice-card { max-width: 550px; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); text-align: center; }
        .notice-icon { font-size: 4rem; color: #6a11cb; margin-bottom: 20px; }
        .btn-gradient { background-image: linear-gradient(to right, #6a11cb 0%, #2575fc 100%); color: white; border: none; border-radius: 10px; padding: 12px 25px; font-weight: 600; }
    </style>
</head>
<body>

<div class="container notice-container">
    <div class="notice-card">
        <div class="notice-icon"><i class="fa-solid fa-envelope-circle-check"></i></div>
        <h2 class="mb-3">Almost there!</h2>
        
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?>" role="alert">
                <?php echo $msg; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">A verification link has been sent to your email address:<br><strong><?php echo htmlspecialchars($email); ?></strong></p>
            <p class="text-muted">Please check your inbox and click the link to activate your account.</p>
        <?php endif; ?>
        
        <hr class="my-4">
        
        <p class="text-muted small">Didn't receive the email?</p>
        <form method="post" action="resend-verification.php">
            <button type="submit" class="btn btn-gradient">Resend Verification Email</button>
        </form>
        <a href="login.php" class="d-block mt-3">Back to Login</a>
    </div>
</div>

</body>
</html>