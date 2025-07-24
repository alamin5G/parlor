<?php
session_start();
require_once 'db_connect.php';

// Lock endpoints if no super admin
$sql = "SELECT COUNT(*) FROM users WHERE role='admin'";
$result = $conn->query($sql);
$row = $result->fetch_row();
if ($row[0] == 0) {
    header('Location: setup_super_admin.php');
    exit;
}

$msg = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $name, $hashed_pw, $role);
        $stmt->fetch();
        if (password_verify($password, $hashed_pw)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['name'] = $name;
            $_SESSION['role'] = $role;
            // Redirect based on role
            if ($role == 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($role == 'beautician') {
                header('Location: employee/dashboard.php');
            } else {
                header('Location: user/dashboard.php');
            }
            exit;
        } else {
            $msg = "Invalid email or password.";
        }
    } else {
        $msg = "Invalid email or password.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Parlor Management</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.5.5/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h2>Login</h2>
    <?php if ($msg) echo "<div class='alert alert-danger'>$msg</div>"; ?>
    <form method="post" class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-2 control-label">Email:</label>
            <div class="col-sm-6">
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">Password:</label>
            <div class="col-sm-6">
                <input type="password" name="password" class="form-control" required>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-6">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </div>
    </form>
</div>
</body>
</html>
