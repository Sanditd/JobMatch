<?php
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

if (!isset($_GET['id'])) {
    header("Location: usermanagement.php");
    exit();
}

$user_id = $_GET['id'];

try {
    // First delete related records in other tables
    $tables = ['jobseekers', 'employers', 'admins', 'job_applications', 'saved_jobs'];
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    
    // Then delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    $_SESSION['message'] = "User deleted successfully";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
}

header("Location: usermanagement.php");
exit();
?>