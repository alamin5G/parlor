<?php
// --- LOGIC FIRST ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "My Profile";
require_once 'include/header.php';
require_once '../includes/db_connect.php';
require_once '../includes/email_functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$err = $msg = '';

// Check for flash messages from previous actions
if (isset($_SESSION['flash_message'])) {
    $msg = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
if (isset($_SESSION['flash_error'])) {
    $err = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// --- ACTION: SUBMIT NEW PROFILE INFO ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (empty($name) || empty($email)) {
        $err = "Name and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Invalid email format.";
    } else {
        $code = random_int(100000, 999999);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $stmt = $conn->prepare("UPDATE users SET profile_update_code=?, profile_update_code_expires_at=? WHERE id=?");
        $stmt->bind_param('ssi', $code, $expires, $user_id);
        $stmt->execute();

        $_SESSION['pending_update'] = ['name' => $name, 'email' => $email, 'phone' => $phone];

        // Send code to admin's CURRENT email for security
        $mail_result = send_email(
            $_SESSION['email'], // Corrected: Send to email, not name
            $_SESSION['name'],
            "Profile Change Verification Code",
            "Your verification code is <b>$code</b>. It expires in 10 minutes."
        );

        if ($mail_result['success']) {
            $msg = "A verification code has been sent to your email. Please enter it below to confirm the changes.";
        } else {
            $err = "Could not send verification email. Please check email settings. " . $mail_result['message'];
            unset($_SESSION['pending_update']); // Clear pending update if email fails
        }
    }
}

// --- ACTION: VERIFY CODE AND UPDATE ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['verify_code'])) {
    $code_input = trim($_POST['code']);
    
    $stmt = $conn->prepare("SELECT profile_update_code, profile_update_code_expires_at FROM users WHERE id=?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($db_code, $db_expires);
    $stmt->fetch();
    $stmt->close();

    if (!$db_code || $db_code !== $code_input) {
        $err = "Invalid verification code.";
    } elseif (date('Y-m-d H:i:s') > $db_expires) {
        $err = "Code expired. Please try again.";
        unset($_SESSION['pending_update']); // Clear expired pending update
    } else {
        // Success! Update profile.
        $update = $_SESSION['pending_update'];
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, profile_update_code=NULL, profile_update_code_expires_at=NULL WHERE id=?");
        $stmt->bind_param('sssi', $update['name'], $update['email'], $update['phone'], $user_id);
        $stmt->execute();

        // Update session with new details
        $_SESSION['name'] = $update['name'];
        $_SESSION['email'] = $update['email'];
        
        unset($_SESSION['pending_update']);
        $_SESSION['flash_message'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit;
    }
}

// Fetch current user info for display
$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id=?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $phone);
$stmt->fetch();
$stmt->close();
?>

<div class="container-fluid">
    <h1 class="mt-4">My Profile</h1>
    <p class="text-muted">Manage your personal information and account settings.</p>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if ($err): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
            <?php elseif ($msg): ?>
                <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['pending_update'])): ?>
                <!-- Code Verification Form -->
                <h5 class="card-title mb-4">Verify Your Identity</h5>
                <form method="post">
                    <div class="mb-3">
                        <label for="code" class="form-label">Verification Code</label>
                        <input type="text" id="code" name="code" class="form-control form-control-lg" maxlength="6" required autofocus>
                        <div class="form-text">A 6-digit code was sent to your email address.</div>
                    </div>
                    <button type="submit" name="verify_code" class="btn btn-success">Verify &amp; Update Profile</button>
                    <a href="profile.php" class="btn btn-secondary">Cancel</a>
                </form>
            <?php else: ?>
                <!-- Profile Edit Form -->
                <h5 class="card-title mb-4">Edit Information</h5>
                <form method="post">
                    <input type="hidden" name="edit_profile" value="1">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" id="name" name="name" class="form-control" required value="<?= htmlspecialchars($name) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($email) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'include/footer.php'; ?>