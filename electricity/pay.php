<?php
session_start();

if (!isset($_SESSION['customer_logged_in']) || !$_SESSION['customer_logged_in']) {
    header("Location: customer_login_simple.php");
    exit();
}

$servername = "localhost";
$user = "root";
$password = "";
$dbname = "user";

$conn = new mysqli($servername, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$scno = $_SESSION['customer_scno'];

$sql = "UPDATE bill SET status = 'Paid' WHERE scno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $scno);
$stmt->execute();
$stmt->close();

$conn->close();

$_SESSION['payment_success'] = true;

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>