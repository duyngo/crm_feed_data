<?php
echo 'hello Working';

$servername = "db";  // The name of the MySQL container
$username = "root";
$password = "root";
$dbname = "contra_testing";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
$sql = "SELECT * FROM users LIMIT 10";
$result = $conn->query($sql)->fetch_all();
print_r(json_encode($result));
die;