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
    $_SESSION['error_message'] = "Job not found or you don't have permission to edit it";
    header("Location: dashboard.php");
    exit();
}

// Validate form data
$errors = [];

// Required fields
$required_fields = [
    'title', 'job_type', 'location', 
    'summary', 'responsibilities', 'qualifications'
];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
    }
}

// Validate salary range if provided
if (!empty($_POST['salary_min']) && !empty($_POST['salary_max'])) {
    if ($_POST['salary_min'] > $_POST['salary_max']) {
        $errors[] = "Minimum salary cannot be greater than maximum salary";
    }
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header("Location: edit_job.php?id=" . $job_id);
    exit();
}

// Prepare job data
$job_data = [
    'title' => $_POST['title'],
    'job_type' => $_POST['job_type'],
    'location' => $_POST['location'],
    'department' => $_POST['department'] ?? null,
    'experience_level' => $_POST['experience_level'] ?? null,
    'salary_min' => !empty($_POST['salary_min']) ? $_POST['salary_min'] : null,
    'salary_max' => !empty($_POST['salary_max']) ? $_POST['salary_max'] : null,
    'salary_period' => $_POST['salary_period'] ?? 'yearly',
    'summary' => $_POST['summary'],
    'responsibilities' => $_POST['responsibilities'],
    'qualifications' => $_POST['qualifications'],
    'preferred_skills' => $_POST['preferred_skills'] ?? null,
    'deadline' => $_POST['deadline'] ?? null,
    'require_cover_letter' => isset($_POST['require_cover_letter']) ? 1 : 0,
    'require_portfolio' => isset($_POST['require_portfolio']) ? 1 : 0,
    'require_references' => isset($_POST['require_references']) ? 1 : 0,
    'custom_questions' => $_POST['custom_questions'] ?? null,
    'updated_at' => date('Y-m-d H:i:s')
];

// Start transaction
$conn->begin_transaction();

try {
    // Update job in database
    $stmt = $conn->prepare("UPDATE jobs SET 
    title = ?, job_type = ?, location = ?, department = ?, experience_level = ?,
    salary_min = ?, salary_max = ?, salary_period = ?, summary = ?, responsibilities = ?,
    qualifications = ?, preferred_skills = ?, deadline = ?, require_cover_letter = ?,
    require_portfolio = ?, require_references = ?, custom_questions = ?, updated_at = ?
    WHERE id = ? AND employer_id = ?");
    
    $stmt->bind_param("ssssssdssssssiisssii", 
    $job_data['title'], 
    $job_data['job_type'], 
    $job_data['location'],
    $job_data['department'], 
    $job_data['experience_level'],
    $job_data['salary_min'], 
    $job_data['salary_max'], 
    $job_data['salary_period'],
    $job_data['summary'], 
    $job_data['responsibilities'],
    $job_data['qualifications'], 
    $job_data['preferred_skills'], 
    $job_data['deadline'],
    $job_data['require_cover_letter'], 
    $job_data['require_portfolio'],
    $job_data['require_references'], 
    $job_data['custom_questions'],
    $job_data['updated_at'], 
    $job_id, 
    $employer_id
);

$stmt->execute();

    // Update benefits
    // First delete existing benefits
    $stmt = $conn->prepare("DELETE FROM job_benefits WHERE job_id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();

    // Insert new benefits if any
    if (!empty($_POST['benefits'])) {
        $stmt = $conn->prepare("INSERT INTO job_benefits (job_id, benefit) VALUES (?, ?)");
        foreach ($_POST['benefits'] as $benefit) {
            $stmt->bind_param("is", $job_id, $benefit);
            $stmt->execute();
        }
    }

    // Commit transaction
    $conn->commit();

    $_SESSION['success_message'] = "Job updated successfully";
    header("Location: dashboard.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error updating job: " . $e->getMessage();
    header("Location: edit_job.php?id=" . $job_id);
    exit();
}