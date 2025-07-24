<?php
require_once 'db_connect.php';

// Check if super admin exists
$sql = "SELECT COUNT(*) FROM users WHERE role='admin'";
$result = $conn->query($sql);
$row = $result->fetch_row();

if ($row[0] > 0) {
    // Super Admin exists, redirect to login
    header("Location: login.php");
    exit;
}

// Handle super admin registration
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $msg = "All fields are required.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $msg = "Email already registered.";
        } else {
            $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'admin')");
            $stmt->bind_param("ssss", $name, $email, $hashed_pw, $phone);
            if ($stmt->execute()) {
                // Success: Lock endpoint by redirecting to login
                header("Location: login.php?msg=Super Admin created. Please log in.");
                exit;
            } else {
                $msg = "Error creating Super Admin.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Super Admin Setup</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.5.5/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h2>Super Admin Setup (First Time Only)</h2>
    <?php if (isset($msg)) echo "<div class='alert alert-danger'>$msg</div>"; ?>
    <form method="post" class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-2 control-label">Name:</label>
            <div class="col-sm-6">
                <input type="text" name="name" class="form-control" required autofocus>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">Email:</label>
            <div class="col-sm-6">
                <input type="email" name="email" class="form-control" required>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">Phone:</label>
            <div class="col-sm-6">
                <input type="text" name="phone" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">Password:</label>
            <div class="col-sm-6">
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-6">
                <button type="submit" class="btn btn-primary">Create Super Admin</button>
            </div>
        </div>
    </form>
</div>
</body>
</html>
