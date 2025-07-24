<?php
$host = "localhost";
$user = "root"; // use your XAMPP username
$pass = "";     // use your XAMPP password
$dbname = "parlor_management";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<?php
require_once 'db_connect.php';
$sql = "SELECT COUNT(*) FROM users WHERE role='admin'";
$result = $conn->query($sql);
$row = $result->fetch_row();
if ($row[0] == 0 && basename($_SERVER['PHP_SELF']) != 'setup_super_admin.php') {
    header('Location: setup_super_admin.php');
    exit;
}
?>

