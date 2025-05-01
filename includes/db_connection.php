<?php
// includes/db_connection.php

$host = 'localhost';
$dbname = 'jobmatch';
$username = 'root'; // Change as per your MySQL configuration
$password = '';     // Change as per your MySQL configuration

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");
?>