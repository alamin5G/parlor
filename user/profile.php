<?php
// filepath: c:\xampp\htdocs\parlor\user\profile.php
$page_title = "My Profile";
require_once 'include/header.php';

$details_msg = ''; $details_err = '';
$password_msg = ''; $password_err = '';
$photo_msg = ''; $photo_err = '';

// --- Function to verify password ---
function verify_password($user_id, $password, $conn) {
    $hash = null; 
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hash);
    $stmt->fetch();
    $stmt->close();
    return password_verify($password, $hash);
}

// --- Handle Profile PHOTO update (NO password needed) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['photo_update'])) {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_photo']['type'];
        $file_size = $_FILES['profile_photo']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $photo_err = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } elseif ($file_size > 2 * 1024 * 1024) { // 2 MB limit
            $photo_err = "File is too large. Maximum size is 2MB.";
        } else {
            // Use a central, shared directory for all profile photos
            $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/parlor/assets/images/profile_photos/';
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $filename = 'user_' . $customer_user_id . '_' . uniqid() . '.' . pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $target_file = $target_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_file)) {
                $new_photo_path = '/parlor/assets/images/profile_photos/' . $filename;

                // Get old photo path to delete it
                $stmt_old = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
                $stmt_old->bind_param("i", $customer_user_id);
                $stmt_old->execute();
                $old_photo = $stmt_old->get_result()->fetch_assoc()['profile_photo'];
                $stmt_old->close();

                // Update database with new path
                $stmt_update = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                $stmt_update->bind_param("si", $new_photo_path, $customer_user_id);
                if ($stmt_update->execute()) {
                    // Delete old photo if it exists and is not the default avatar
                    if ($old_photo && file_exists($_SERVER['DOCUMENT_ROOT'] . $old_photo) && strpos($old_photo, 'default-avatar.png') === false) {
                        unlink($_SERVER['DOCUMENT_ROOT'] . $old_photo);
                    }
                    $photo_msg = "Profile photo updated successfully!";
                } else {
                    $photo_err = "Database error while updating photo.";
                }
                $stmt_update->close();
            } else {
                $photo_err = "Sorry, there was an error uploading your file.";
            }
        }
    } else {
        $photo_err = "Please select a file to upload.";
    }
}

// --- Handle Profile DETAILS update (Password REQUIRED) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['details_update'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password_details'];
    $phone_regex = '/^(?:\+88|88)?01[3-9]\d{8}$/';

    if (!verify_password($customer_user_id, $current_password, $conn)) {
        $details_err = "Incorrect password. Please try again.";
    } elseif (empty($name) || strlen($name) < 3) {
        $details_err = "Name must be at least 3 characters.";
    } elseif (empty($phone) || !preg_match($phone_regex, $phone)) {
        $details_err = "A valid Bangladeshi phone number is required.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $phone, $customer_user_id);
        if ($stmt->execute()) {
            $details_msg = "Profile details updated successfully!";
            $_SESSION['name'] = $name; // Update session name if needed
        } else {
            $details_err = "Error updating details.";
        }
        $stmt->close();
    }
}

// --- Handle PASSWORD change ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_update'])) {
    $current_password = $_POST['current_password_change'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!verify_password($customer_user_id, $current_password, $conn)) {
        $password_err = "Incorrect current password.";
    } elseif (strlen($new_password) < 6) {
        $password_err = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $password_err = "New passwords do not match.";
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hash, $customer_user_id);
        $stmt->execute();
        $stmt->close();
        $password_msg = "Password updated successfully!";
    }
}

// Fetch latest user info for display
$stmt = $conn->prepare("SELECT name, email, phone, profile_photo FROM users WHERE id = ?");
$stmt->bind_param("i", $customer_user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$current_photo = $user['profile_photo'] ? $user['profile_photo'] : '/parlor/assets/images/default-avatar.png';
?>
<style>
    .profile-pic-display { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #eee; }
</style>

<div class="container-fluid">
    <h1 class="mt-4">My Profile</h1>
    <p class="text-muted">Manage your personal information and account settings.</p>

    <div class="row">
        <!-- Combined Photo and Details Card -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <!-- Photo Upload Section -->
                <div class="card-body text-center border-bottom">
                    <h5 class="card-title mb-3">Profile Picture</h5>
                    <?php if ($photo_msg): ?><div class="alert alert-success"><?= $photo_msg ?></div><?php endif; ?>
                    <?php if ($photo_err): ?><div class="alert alert-danger"><?= $photo_err ?></div><?php endif; ?>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="photo_update" value="1">
                        <img src="<?= htmlspecialchars($current_photo) ?>" alt="Profile Picture" class="profile-pic-display mb-3" id="photo-preview">
                        <div class="mb-3"><input type="file" id="profile_photo" name="profile_photo" class="form-control" accept="image/png, image/jpeg, image/gif" required></div>
                        <button type="submit" class="btn btn-secondary">Upload Photo</button>
                    </form>
                </div>
                <!-- Details Update Section -->
                <div class="card-body">
                    <h5 class="card-title mb-4">Profile Details</h5>
                    <?php if ($details_msg): ?><div class="alert alert-success"><?= $details_msg ?></div><?php endif; ?>
                    <?php if ($details_err): ?><div class="alert alert-danger"><?= $details_err ?></div><?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="details_update" value="1">
                        <div class="mb-3"><label for="name" class="form-label">Name</label><input id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required></div>
                        <div class="mb-3"><label for="email" class="form-label">Email (Cannot be changed)</label><input id="email" type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly></div>
                        <div class="mb-3"><label for="phone" class="form-label">Phone</label><input id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required></div>
                        <hr>
                        <div class="mb-3"><label for="current_password_details" class="form-label fw-bold">Verify Current Password to Save</label><input type="password" id="current_password_details" name="current_password_details" class="form-control" required></div>
                        <button type="submit" class="btn btn-primary">Update Details</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">Change Password</h5>
                    <?php if ($password_msg): ?><div class="alert alert-success"><?= $password_msg ?></div><?php endif; ?>
                    <?php if ($password_err): ?><div class="alert alert-danger"><?= $password_err ?></div><?php endif; ?>
                    <form method="post" id="changePasswordForm" novalidate>
                        <input type="hidden" name="password_update" value="1">
                        <div class="mb-3"><label for="current_password_change" class="form-label">Current Password</label><input type="password" id="current_password_change" name="current_password_change" class="form-control" required></div>
                        <div class="mb-3"><label for="new_password" class="form-label">New Password</label><input type="password" id="new_password" name="new_password" class="form-control" required minlength="6"><div class="invalid-feedback" id="new-password-error"></div></div>
                        <div class="mb-3"><label for="confirm_password" class="form-label">Confirm New Password</label><input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6"><div class="invalid-feedback" id="confirm-password-error"></div></div>
                        <button type="submit" class="btn btn-warning">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image preview script
    const photoInput = document.getElementById('profile_photo');
    const photoPreview = document.getElementById('photo-preview');
    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Password validation script
    const passwordForm = document.getElementById('changePasswordForm');
    if (passwordForm) {
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const newPasswordError = document.getElementById('new-password-error');
        const confirmPasswordError = document.getElementById('confirm-password-error');

        function showError(input, errorElement, message) { input.classList.add('is-invalid'); errorElement.textContent = message; }
        function clearError(input, errorElement) { input.classList.remove('is-invalid'); errorElement.textContent = ''; }

        passwordForm.addEventListener('submit', function(event) {
            let isValid = true;
            clearError(newPasswordInput, newPasswordError);
            clearError(confirmPasswordInput, confirmPasswordError);

            if (newPasswordInput.value.length > 0 && newPasswordInput.value.length < 6) {
                showError(newPasswordInput, newPasswordError, 'Password must be at least 6 characters long.');
                isValid = false;
            }
            if (newPasswordInput.value !== confirmPasswordInput.value) {
                showError(confirmPasswordInput, confirmPasswordError, 'Passwords do not match.');
                isValid = false;
            }
            if (!isValid) {
                event.preventDefault();
            }
        });
    }
});
</script>

<?php require_once 'include/footer.php'; ?>