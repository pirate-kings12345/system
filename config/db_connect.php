<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // change if you set a MySQL password
$dbname = 'course_system';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
