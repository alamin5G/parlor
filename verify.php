<?php
session_start();
require_once 'includes/db_connect.php';

$msg = '';
$msg_type = 'danger';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE verify_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Update user to verified
        $update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $update->bind_param("i", $user['id']);
        
        if ($update->execute()) {
            $msg = "Your email has been verified successfully! You can now log in.";
            $msg_type = 'success';
        } else {
            $msg = "Verification failed. Please try again or contact support.";
        }
        $update->close();
    } else {
        $msg = "Invalid verification token or account already verified.";
    }
    $stmt->close();
} else {
    $msg = "Missing verification token.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Email Verification - Aura Salon & Spa</title>
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
        .verification-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .verification-card {
            width: 100%;
            max-width: 600px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            overflow: hidden;
            background: white;
            padding: 40px;
            text-align: center;
        }
        .logo {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #6a11cb;
        }
        .btn-gradient {
            background-image: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container-fluid verification-container">
    <div class="verification-card">
        <div class="logo"><i class="fa-solid fa-spa"></i></div>
        <h2 class="mb-4">Email Verification</h2>
        
        <div class="alert alert-<?php echo $msg_type; ?>" role="alert">
            <?php echo htmlspecialchars($msg); ?>
        </div>
        
        <div class="mt-4">
            <?php if ($msg_type == 'success'): ?>
                <a href="login.php" class="btn btn-gradient">Proceed to Login</a>
            <?php else: ?>
                <a href="index.php" class="btn btn-gradient">Return to Homepage</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>