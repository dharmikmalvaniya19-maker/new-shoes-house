<?php
$host = 'localhost';
$username = 'root'; // or your custom user
$password = 'new_password'; // or your password
$dbname = 'shoes_house_db';

$conn = mysqli_connect($host, $username, $password, $dbname);

if ($conn) {
    echo "Connected successfully!";
} else {
    die("Connection failed: " . mysqli_connect_error());
}
?>