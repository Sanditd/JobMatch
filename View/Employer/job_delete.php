<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

// Only employers can access this page
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user_type'] !== 'employer') {
    header("Location: unauthorized.php");
    exit();
}

// Validate job ID
if (!isset($_POST['job_id']) || !is_numeric($_POST['job_id'])) {
    $_SESSION['error_message'] = "Invalid job ID";
    header("Location: dashboard.php");
    exit();
}

$job_id = $_POST['job_id'];
$employer_id = $_SESSION['user_id'];

// Verify the job belongs to this employer
$stmt = $conn->prepare("SELECT id FROM jobs WHERE id = ? AND employer_id = ?");
$stmt->bind_param("ii", $job_id, $employer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Job not found or you don't have permission to delete it";
    header("Location: dashboard.php");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // First delete benefits associated with the job
    $stmt = $conn->prepare("DELETE FROM job_benefits WHERE job_id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();

    // Then delete the job
    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    $_SESSION['success_message'] = "Job deleted successfully";
    header("Location: dashboard.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error deleting job: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}