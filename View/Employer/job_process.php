<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

// Only employers can post jobs
if ($_SESSION['user_type'] !== 'employer') {
    header("Location: unauthorized.php");
    exit();
}

$employer_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $title = trim($_POST['title']);
    $job_type = $_POST['job_type'];
    $location = $_POST['location'];
    $department = trim($_POST['department']);
    $experience_level = $_POST['experience_level'] ?? null;
    $salary_min = !empty($_POST['salary_min']) ? (float)$_POST['salary_min'] : null;
    $salary_max = !empty($_POST['salary_max']) ? (float)$_POST['salary_max'] : null;
    $salary_period = $_POST['salary_period'] ?? null;
    $summary = trim($_POST['summary']);
    $responsibilities = trim($_POST['responsibilities']);
    $qualifications = trim($_POST['qualifications']);
    $preferred_skills = trim($_POST['preferred_skills'] ?? '');
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    $require_cover_letter = isset($_POST['require_cover_letter']) ? 1 : 0;
    $require_portfolio = isset($_POST['require_portfolio']) ? 1 : 0;
    $require_references = isset($_POST['require_references']) ? 1 : 0;
    $custom_questions = trim($_POST['custom_questions'] ?? '');
    $benefits = $_POST['benefits'] ?? [];
    $status = isset($_POST['save_as_draft']) ? 'draft' : 'published';

    // Basic validation
    $errors = [];
    if (empty($title)) $errors[] = "Job title is required";
    if (empty($job_type)) $errors[] = "Job type is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($summary)) $errors[] = "Job summary is required";
    if (empty($responsibilities)) $errors[] = "Responsibilities are required";
    if (empty($qualifications)) $errors[] = "Qualifications are required";
    if ($salary_min !== null && $salary_max !== null && $salary_min > $salary_max) {
        $errors[] = "Minimum salary cannot be greater than maximum salary";
    }

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert job
            $stmt = $conn->prepare("INSERT INTO jobs (
                employer_id, title, job_type, location, department, experience_level,
                salary_min, salary_max, salary_period, summary, responsibilities,
                qualifications, preferred_skills, deadline, require_cover_letter,
                require_portfolio, require_references, custom_questions, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("isssssddssssssiiiss", 
                $employer_id, $title, $job_type, $location, $department, $experience_level,
                $salary_min, $salary_max, $salary_period, $summary, $responsibilities,
                $qualifications, $preferred_skills, $deadline, $require_cover_letter,
                $require_portfolio, $require_references, $custom_questions, $status);
            
            $stmt->execute();
            $job_id = $conn->insert_id;

            // Insert benefits
            if (!empty($benefits)) {
                $stmt = $conn->prepare("INSERT INTO job_benefits (job_id, benefit) VALUES (?, ?)");
                foreach ($benefits as $benefit) {
                    $stmt->bind_param("is", $job_id, $benefit);
                    $stmt->execute();
                }
            }

            // Commit transaction
            $conn->commit();

            // Set success message
            $_SESSION['success_message'] = $status === 'draft' 
                ? "Job saved as draft successfully!" 
                : "Job published successfully!";
                
            header("Location: dashboard.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error saving job: " . $e->getMessage();
            header("Location: createjob.php");
            exit();
        }
    } else {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header("Location: createjob.php");
        exit();
    }
} else {
    header("Location: createjob.php");
    exit();
}
?>