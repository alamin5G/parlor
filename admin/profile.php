<?php
// filepath: c:\xampp\htdocs\parlor\admin\profile.php
// --- LOGIC FIRST ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- ALL PROCESSING LOGIC BEFORE ANY HTML OUTPUT ---
require_once '../includes/db_connect.php';
require_once '../includes/email_functions.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$err = ''; $msg = '';
$pass_err = ''; $pass_msg = ''; // Separate messages for password form

// --- ACTION TO CANCEL PENDING UPDATE ---
if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
    unset($_SESSION['pending_update']);
    $stmt = $conn->prepare("UPDATE users SET profile_update_code=NULL, profile_update_code_expires_at=NULL WHERE id=?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: profile.php");
    exit;
}

// --- ACTION: CHANGE PASSWORD ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $pass_err = "All password fields are required.";
    } elseif (strlen($new_password) < 6) {
        $pass_err = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $pass_err = "New passwords do not match.";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($current_password, $hashed_password)) {
            // Current password is correct, update to new password
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param('si', $new_hashed_password, $user_id);
            if ($update_stmt->execute()) {
                $pass_msg = "Password updated successfully!";
            } else {
                $pass_err = "Failed to update password. Please try again.";
            }
            $update_stmt->close();
        } else {
            $pass_err = "Incorrect current password.";
        }
    }
}

// --- ACTION: SUBMIT NEW PROFILE INFO ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_profile'])) {
    // ... (existing profile update logic remains unchanged) ...
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $phone_regex = '/^(?:\+88|88)?01[3-9]\d{8}$/';
    if (empty($name) || strlen($name) < 3) {
        $err = "Name is required and must be at least 3 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Invalid email format.";
    } elseif (empty($phone) || !preg_match($phone_regex, $phone)) {
        $err = "A valid Bangladeshi phone number is required.";
    } else {
        $stmt_email = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt_email->bind_param('i', $user_id);
        $stmt_email->execute();
        $current_email = $stmt_email->get_result()->fetch_assoc()['email'];
        $stmt_email->close();
        $code = random_int(100000, 999999);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $stmt = $conn->prepare("UPDATE users SET profile_update_code=?, profile_update_code_expires_at=? WHERE id=?");
        $stmt->bind_param('ssi', $code, $expires, $user_id);
        $stmt->execute();
        $_SESSION['pending_update'] = ['name' => $name, 'email' => $email, 'phone' => $phone];
        $mail_result = send_email($current_email, $_SESSION['name'], "Profile Change Verification Code", "Your verification code is <b>$code</b>.");
        if ($mail_result['success']) {
            $msg = "A verification code has been sent to your email ($current_email).";
        } else {
            $err = "Could not send verification email. " . $mail_result['message'];
            unset($_SESSION['pending_update']);
        }
    }
}

// --- ACTION: VERIFY CODE AND UPDATE ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['verify_code'])) {
    // ... (existing verification logic remains unchanged) ...
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
        unset($_SESSION['pending_update']);
    } else {
        $update = $_SESSION['pending_update'];
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, profile_update_code=NULL, profile_update_code_expires_at=NULL WHERE id=?");
        $stmt->bind_param('sssi', $update['name'], $update['email'], $update['phone'], $user_id);
        $stmt->execute();
        $_SESSION['name'] = $update['name'];
        unset($_SESSION['pending_update']);
        $_SESSION['flash_message'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit;
    }
}

// --- START PAGE OUTPUT ---
$page_title = "My Profile";
require_once 'include/header.php';

// Check for flash messages
if (isset($_SESSION['flash_message'])) {
    $msg = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
if (isset($_SESSION['flash_error'])) {
    $err = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
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

    <?php if (isset($_SESSION['pending_update'])): ?>
        <!-- Code Verification Card -->
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
                <?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                <h5 class="card-title mb-4">Verify Your Identity</h5>
                <form method="post">
                    <div class="mb-3">
                        <label for="code" class="form-label">Verification Code</label>
                        <input type="text" id="code" name="code" class="form-control form-control-lg" maxlength="6" required autofocus>
                        <div class="form-text">A 6-digit code was sent to your email address.</div>
                    </div>
                    <button type="submit" name="verify_code" class="btn btn-success">Verify &amp; Update Profile</button>
                    <a href="profile.php?action=cancel" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Profile and Password Forms -->
        <div class="row">
            <!-- Profile Information Card -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Edit Information</h5>
                        <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
                        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                        <form method="post" id="editProfileForm" novalidate>
                            <input type="hidden" name="edit_profile" value="1">
                            <div class="mb-3"><label for="name" class="form-label">Name</label><input type="text" id="name" name="name" class="form-control" required minlength="3" value="<?= htmlspecialchars($name) ?>"><div class="invalid-feedback" id="name-error"></div></div>
                            <div class="mb-3"><label for="email" class="form-label">Email</label><input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($email) ?>"><div class="invalid-feedback" id="email-error"></div></div>
                            <div class="mb-3"><label for="phone" class="form-label">Phone</label><input type="text" id="phone" name="phone" class="form-control" required value="<?= htmlspecialchars($phone ?? '') ?>" placeholder="e.g., 01712345678"><div class="invalid-feedback" id="phone-error"></div></div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Change Password Card -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Change Password</h5>
                        <?php if ($pass_err): ?><div class="alert alert-danger"><?= htmlspecialchars($pass_err) ?></div><?php endif; ?>
                        <?php if ($pass_msg): ?><div class="alert alert-success"><?= htmlspecialchars($pass_msg) ?></div><?php endif; ?>
                        <form method="post" id="changePasswordForm" novalidate>
                            <input type="hidden" name="change_password" value="1">
                            <div class="mb-3"><label for="current_password" class="form-label">Current Password</label><input type="password" id="current_password" name="current_password" class="form-control" required><div class="invalid-feedback">Please enter your current password.</div></div>
                            <div class="mb-3"><label for="new_password" class="form-label">New Password</label><input type="password" id="new_password" name="new_password" class="form-control" required minlength="6"><div class="invalid-feedback" id="new-password-error"></div></div>
                            <div class="mb-3"><label for="confirm_password" class="form-label">Confirm New Password</label><input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6"><div class="invalid-feedback" id="confirm-password-error"></div></div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Profile Info Form Validation ---
    const profileForm = document.getElementById('editProfileForm');
    if (profileForm) {
        // ... (existing profile validation script remains unchanged) ...
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        const nameError = document.getElementById('name-error');
        const emailError = document.getElementById('email-error');
        const phoneError = document.getElementById('phone-error');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const phoneRegex = /^(?:\+88|88)?01[3-9]\d{8}$/;

        function showError(input, errorElement, message) { input.classList.add('is-invalid'); errorElement.textContent = message; }
        function clearError(input, errorElement) { input.classList.remove('is-invalid'); errorElement.textContent = ''; }

        profileForm.addEventListener('submit', function(event) {
            let isValid = true;
            clearError(nameInput, nameError); if (nameInput.value.trim().length < 3) { showError(nameInput, nameError, 'Name must be at least 3 characters.'); isValid = false; }
            clearError(emailInput, emailError); if (!emailRegex.test(emailInput.value)) { showError(emailInput, emailError, 'Please enter a valid email.'); isValid = false; }
            clearError(phoneInput, phoneError); if (!phoneRegex.test(phoneInput.value.trim())) { showError(phoneInput, phoneError, 'Please enter a valid Bangladeshi phone number.'); isValid = false; }
            if (!isValid) { event.preventDefault(); }
        });
    }

    // --- Change Password Form Validation ---
    const passwordForm = document.getElementById('changePasswordForm');
    if (passwordForm) {
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const newPasswordError = document.getElementById('new-password-error');
        const confirmPasswordError = document.getElementById('confirm-password-error');

        function showPassError(input, errorElement, message) { input.classList.add('is-invalid'); errorElement.textContent = message; }
        function clearPassError(input, errorElement) { input.classList.remove('is-invalid'); errorElement.textContent = ''; }

        passwordForm.addEventListener('submit', function(event) {
            let isPassValid = true;
            clearPassError(newPasswordInput, newPasswordError);
            clearPassError(confirmPasswordInput, confirmPasswordError);

            if (newPasswordInput.value.length < 6) {
                showPassError(newPasswordInput, newPasswordError, 'Password must be at least 6 characters long.');
                isPassValid = false;
            }
            if (newPasswordInput.value !== confirmPasswordInput.value) {
                showPassError(confirmPasswordInput, confirmPasswordError, 'Passwords do not match.');
                isPassValid = false;
            }
            if (!isPassValid) {
                event.preventDefault();
            }
        });
    }
});
</script>

<?php require_once 'include/footer.php'; ?>