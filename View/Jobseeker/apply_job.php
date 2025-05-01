<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

if ($_SESSION['user_type'] !== 'jobseeker') {
    header("Location: unauthorized.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = $_GET['id'] ?? 0;

// Check if job exists
$stmt = $conn->prepare("SELECT id FROM jobs WHERE id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$job_exists = $stmt->get_result()->num_rows > 0;

if ($job_exists) {
    // Check if already applied
    $stmt = $conn->prepare("SELECT id FROM job_applications WHERE job_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $job_id, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        // Insert new application
        $stmt = $conn->prepare("INSERT INTO job_applications (job_id, user_id, status) VALUES (?, ?, 'applied')");
        $stmt->bind_param("ii", $job_id, $user_id);
        $stmt->execute();
    }
    
    // Redirect back with success message
    $_SESSION['apply_message'] = "Your application has been successfully submitted!";
    header("Location: job_search.php");
    exit();
} else {
    // Job doesn't exist
    $_SESSION['error_message'] = "The job you're trying to apply for doesn't exist.";
    header("Location: job_search.php");
    exit();
}
?>