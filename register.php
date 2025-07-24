<!-- register.php -->
<!DOCTYPE html>
<html>
<head>
    <title>User Registration - Parlor Management</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.5.5/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h2>User Registration</h2>
    <?php
    // Display error/success messages
    if (isset($_GET['msg'])) {
        echo '<div class="alert alert-info">'.htmlspecialchars($_GET['msg']).'</div>';
    }
    ?>
    <form action="register.php" method="post" class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-2 control-label">Name:</label>
            <div class="col-sm-6">
                <input type="text" name="name" class="form-control" required>
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
            <label class="col-sm-2 control-label">Role:</label>
            <div class="col-sm-6">
                <select name="role" class="form-control" required>
                    <option value="customer">Customer</option>
                    <option value="beautician">Beautician</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-6">
                <button type="submit" name="register" class="btn btn-primary">Register</button>
            </div>
        </div>
    </form>
</div>
</body>
</html>

<?php
// PHP handler: handle form submit
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) {
    require_once 'db_connect.php'; // database connection file

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Simple validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        header('Location: register.php?msg=Please fill all required fields.');
        exit;
    }

    // Email already exists check
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header('Location: register.php?msg=Email already registered.');
        exit;
    }
    $stmt->close();

    // Password hashing
    $hashed_pw = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $hashed_pw, $phone, $role);
    if ($stmt->execute()) {
        header('Location: register.php?msg=Registration successful! You can now log in.');
    } else {
        header('Location: register.php?msg=Registration failed. Try again.');
    }
    $stmt->close();
    $conn->close();
    exit;
}
?>
