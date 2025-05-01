<?php
// login_process.php

session_start();
require_once '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Please enter both username and password.';
        header('Location: login.html');
        exit;
    }

    // Prepare and bind
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Update last login time
            $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();
            $updateStmt->close();

            // Redirect based on user type
            switch ($user['user_type']) {
                case 'admin':
                    header('Location:  Admin/admindashboard.php');
                    break;
                case 'employer':
                    header('Location: Employer/dashboard.php');
                    break;
                case 'jobseeker':
                    header('Location: Jobseeker/job_search.php');
                    break;
                default:
                    header('Location: login.html');
            }
            exit;
        }
    }
    
    // If we get here, login failed
    $_SESSION['error'] = 'Invalid username or password.';
    header('Location: login.php');
    exit;
} else {
    header('Location: login.php');
    exit;
}
?>