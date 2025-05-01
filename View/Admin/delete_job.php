<?php
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

if (!isset($_GET['id'])) {
    header("Location: joblisting.php");
    exit();
}

$job_id = $_GET['id'];

try {
    // First delete related records
    $stmt = $conn->prepare("DELETE FROM job_applications WHERE job_id = ?");
    $stmt->execute([$job_id]);
    
    $stmt = $conn->prepare("DELETE FROM job_benefits WHERE job_id = ?");
    $stmt->execute([$job_id]);
    
    $stmt = $conn->prepare("DELETE FROM saved_jobs WHERE job_id = ?");
    $stmt->execute([$job_id]);
    
    // Then delete the job
    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    
    $_SESSION['message'] = "Job deleted successfully";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting job: " . $e->getMessage();
}

header("Location: joblisting.php");
exit();
?>