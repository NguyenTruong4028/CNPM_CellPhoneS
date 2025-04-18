<?php
$host = "localhost";
$user = "root";
$password = ""; // Đặt mật khẩu của bạn ở đây nếu có
$dbname = "tbdt";

$conn = new mysqli($host, $user,$password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
