<?php
session_start();

// Store user information before destroying the session
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Set page title based on role
$role_title = '';
switch ($role) {
    case 'admin':
        $role_title = 'Admin';
        break;
    case 'beautician':
        $role_title = 'Beautician';
        break;
    case 'customer':
        $role_title = 'Customer';
        break;
    default:
        $role_title = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - Labonno Glamour World</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f5ff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logout-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            padding: 40px;
            max-width: 500px;
            width: 90%;
        }
        .logout-icon {
            font-size: 4rem;
            color: #6a11cb;
            margin-bottom: 20px;
        }
        .countdown {
            font-weight: bold;
            color: #6a11cb;
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="logout-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
        <h2>Goodbye, <?php echo htmlspecialchars($user_name); ?>!</h2>
        <p class="mb-4">You have been successfully logged out of your <?php echo $role_title; ?> account.</p>
        <p>You will be redirected to the login page in <span id="countdown" class="countdown">5</span> seconds.</p>
        <div class="mt-4">
            <a href="login.php" class="btn btn-primary">Login Again</a>
            <a href="index.php" class="btn btn-outline-secondary ms-2">Back to Home</a>
        </div>
    </div>

    <script>
        // Countdown timer
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');
        
        const countdownTimer = setInterval(function() {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdownTimer);
                window.location.href = 'login.php';
            }
        }, 1000);
    </script>
</body>
</html>