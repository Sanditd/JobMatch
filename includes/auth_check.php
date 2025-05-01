<?php
// includes/auth_check.php

// session_start();

if (!isset($_SESSION['user_id'])) {
    // User is not logged in
    $_SESSION['error'] = 'Please log in to access this page.';
    header('Location: ../login.php');
    exit;
}

// Optional: Additional checks based on user type
$allowed_types = ['admin', 'employer', 'jobseeker'];
if (!in_array($_SESSION['user_type'], $allowed_types)) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}
?>