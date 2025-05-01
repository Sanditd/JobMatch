<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

// Verify admin access
if ($_SESSION['user_type'] !== 'admin') {
    header("Location: ../unauthorized.php");
    exit();
}

// Initialize statistics array
$stats = [
    'total_users' => 0,
    'total_jobs' => 0,
    'total_applications' => 0,
    'active_jobs' => 0
];

// Function to safely execute queries and handle errors
function executeQuery($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        die("Query execution failed: " . $stmt->error);
    }
    
    return $stmt;
}

// Get statistics data
try {
    // Total users
    $stmt = executeQuery($conn, "SELECT COUNT(*) FROM users");
    $stmt->bind_result($stats['total_users']);
    $stmt->fetch();
    $stmt->close();

    // Total jobs
    $stmt = executeQuery($conn, "SELECT COUNT(*) FROM jobs");
    $stmt->bind_result($stats['total_jobs']);
    $stmt->fetch();
    $stmt->close();

    // Total applications
    $stmt = executeQuery($conn, "SELECT COUNT(*) FROM job_applications");
    $stmt->bind_result($stats['total_applications']);
    $stmt->fetch();
    $stmt->close();

    // Active jobs
    $stmt = executeQuery($conn, "SELECT COUNT(*) FROM jobs WHERE status = 'published'");
    $stmt->bind_result($stats['active_jobs']);
    $stmt->fetch();
    $stmt->close();

    // Recent users (last 5)
    $stmt = executeQuery($conn, 
        "SELECT u.id, u.username, u.email, u.user_type, 
        CASE 
            WHEN u.user_type = 'jobseeker' THEN CONCAT(js.first_name, ' ', js.last_name)
            WHEN u.user_type = 'employer' THEN e.company_name
            ELSE 'Admin'
        END as full_name
        FROM users u
        LEFT JOIN jobseekers js ON u.id = js.user_id
        LEFT JOIN employers e ON u.id = e.user_id
        ORDER BY u.created_at DESC LIMIT 5");
    
    $result = $stmt->get_result();
    $recent_users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Recent jobs (last 5)
    $stmt = executeQuery($conn, 
        "SELECT j.id, j.title, ep.company_name, j.created_at, 
        (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as applications
        FROM jobs j
        JOIN employer_profiles ep ON j.employer_id = ep.user_id
        ORDER BY j.created_at DESC LIMIT 5");
    
    $result = $stmt->get_result();
    $recent_jobs = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while fetching data. Please try again later.");
}

// Handle delete actions if requested
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['delete_user'])) {
        $userId = intval($_GET['delete_user']);
        deleteUser($conn, $userId);
    } elseif (isset($_GET['delete_job'])) {
        $jobId = intval($_GET['delete_job']);
        deleteJob($conn, $jobId);
    }
}

// Function to delete a user
function deleteUser($conn, $userId) {
    // Verify the user exists and is not the main admin
    $stmt = executeQuery($conn, "SELECT id FROM users WHERE id = ? AND id != 1", [$userId]);
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        $_SESSION['message'] = "User not found or cannot delete main admin";
        $_SESSION['message_type'] = "error";
        $stmt->close();
        header("Location: admindashboard.php");
        exit();
    }
    $stmt->close();

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Delete user based on their type
        $stmt = executeQuery($conn, "SELECT user_type FROM users WHERE id = ?", [$userId]);
        $stmt->bind_result($userType);
        $stmt->fetch();
        $stmt->close();

        if ($userType === 'jobseeker') {
            // Delete jobseeker related data
            executeQuery($conn, "DELETE FROM jobseeker_skills WHERE user_id = ?", [$userId]);
            executeQuery($conn, "DELETE FROM educations WHERE user_id = ?", [$userId]);
            executeQuery($conn, "DELETE FROM experiences WHERE user_id = ?", [$userId]);
            executeQuery($conn, "DELETE FROM jobseekers WHERE user_id = ?", [$userId]);
            executeQuery($conn, "DELETE FROM jobseeker_profiles WHERE user_id = ?", [$userId]);
        } elseif ($userType === 'employer') {
            // Delete employer related data
            executeQuery($conn, "DELETE FROM employer_benefits WHERE user_id = ?", [$userId]);
            executeQuery($conn, "DELETE FROM employer_photos WHERE user_id = ?", [$userId]);
            executeQuery($conn, "DELETE FROM employer_profiles WHERE user_id = ?", [$userId]);
            executeQuery($conn, "DELETE FROM employers WHERE user_id = ?", [$userId]);
            
            // Get jobs by this employer to delete related data
            $stmt = executeQuery($conn, "SELECT id FROM jobs WHERE employer_id = ?", [$userId]);
            $result = $stmt->get_result();
            $jobIds = [];
            while ($row = $result->fetch_assoc()) {
                $jobIds[] = $row['id'];
            }
            $stmt->close();
            
            if (!empty($jobIds)) {
                // Delete job related data
                $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
                executeQuery($conn, "DELETE FROM job_benefits WHERE job_id IN ($placeholders)", $jobIds);
                executeQuery($conn, "DELETE FROM job_applications WHERE job_id IN ($placeholders)", $jobIds);
                executeQuery($conn, "DELETE FROM saved_jobs WHERE job_id IN ($placeholders)", $jobIds);
                executeQuery($conn, "DELETE FROM jobs WHERE id IN ($placeholders)", $jobIds);
            }
        }
        
        // Delete user applications and saved jobs
        executeQuery($conn, "DELETE FROM job_applications WHERE user_id = ?", [$userId]);
        executeQuery($conn, "DELETE FROM saved_jobs WHERE user_id = ?", [$userId]);
        
        // Finally delete the user
        executeQuery($conn, "DELETE FROM users WHERE id = ?", [$userId]);
        
        $conn->commit();
        
        $_SESSION['message'] = "User deleted successfully";
        $_SESSION['message_type'] = "success";
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete user error: " . $e->getMessage());
        $_SESSION['message'] = "Failed to delete user";
        $_SESSION['message_type'] = "error";
    }
    
    header("Location: admindashboard.php");
    exit();
}

// Function to delete a job
function deleteJob($conn, $jobId) {
    // Begin transaction
    $conn->begin_transaction();

    try {
        // Delete job related data
        executeQuery($conn, "DELETE FROM job_benefits WHERE job_id = ?", [$jobId]);
        executeQuery($conn, "DELETE FROM job_applications WHERE job_id = ?", [$jobId]);
        executeQuery($conn, "DELETE FROM saved_jobs WHERE job_id = ?", [$jobId]);
        
        // Delete the job
        executeQuery($conn, "DELETE FROM jobs WHERE id = ?", [$jobId]);
        
        $conn->commit();
        
        $_SESSION['message'] = "Job deleted successfully";
        $_SESSION['message_type'] = "success";
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete job error: " . $e->getMessage());
        $_SESSION['message'] = "Failed to delete job";
        $_SESSION['message_type'] = "error";
    }
    
    header("Location: admindashboard.php");
    exit();
}

// The HTML part remains the same as in your original file
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - JobMatch Recruitment System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #4361ee;
      --primary-dark: #3a56d4;
      --secondary: #3f37c9;
      --light: #f8f9fa;
      --dark: #212529;
      --gray: #6c757d;
      --light-gray: #e9ecef;
      --success: #4cc9f0;
      --danger: #f72585;
      --warning: #f8961e;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
      background-color: var(--light);
      color: var(--dark);
      line-height: 1.6;
      display: flex;
    }
    
    .sidebar {
      width: 250px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      height: 100vh;
      position: fixed;
      padding-top: 2rem;
      box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
    }
    
    .sidebar .logo {
      font-size: 1.8rem;
      font-weight: 700;
      padding: 0 1.5rem 2rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 1.5rem;
      position: relative;
    }
    
    .sidebar .logo::after {
      content: '';
      position: absolute;
      bottom: 1.5rem;
      left: 1.5rem;
      width: 60px;
      height: 4px;
      background-color: var(--success);
      border-radius: 2px;
    }
    
    .sidebar-menu {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .sidebar-menu li {
      padding: 0;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .sidebar-menu li a {
      color: white;
      text-decoration: none;
      display: block;
      padding: 0.85rem 1.5rem;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .sidebar-menu li:hover, .sidebar-menu li.active {
      background-color: rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-menu li.active {
      border-left: 4px solid var(--success);
    }
    
    .main-content {
      margin-left: 250px;
      width: calc(100% - 250px);
      padding: 2rem;
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--light-gray);
    }
    
    .header h1 {
      margin: 0;
      color: var(--dark);
      font-weight: 600;
      position: relative;
      display: inline-block;
    }
    
    .header h1::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 60px;
      height: 4px;
      background-color: var(--success);
      border-radius: 2px;
    }
    
    .header .user-info {
      display: flex;
      align-items: center;
    }
    
    .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 1rem;
      font-weight: 600;
    }
    
    .stats-container {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .stat-card {
      background-color: white;
      padding: 1.5rem;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--success));
      border-radius: 16px 16px 0 0;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }
    
    .stat-value {
      font-size: 2.5rem;
      font-weight: 700;
      margin: 0.5rem 0;
      color: var(--dark);
    }
    
    .stat-label {
      color: var(--gray);
      font-size: 1rem;
      font-weight: 500;
    }
    
    .section {
      background-color: white;
      padding: 2rem;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
    }
    
    .section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--success));
      border-radius: 16px 16px 0 0;
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }
    
    .section-title {
      margin: 0;
      color: var(--dark);
      font-weight: 600;
      font-size: 1.5rem;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    table th, table td {
      text-align: left;
      padding: 1rem;
      border-bottom: 1px solid var(--light-gray);
    }
    
    table th {
      color: var(--gray);
      font-weight: 500;
      font-size: 0.9rem;
    }
    
    table tr:last-child td {
      border-bottom: none;
    }
    
    table tr:hover td {
      background-color: rgba(76, 201, 240, 0.05);
    }
    
    .action-btn {
      background: none;
      border: none;
      color: var(--primary);
      cursor: pointer;
      margin-right: 0.5rem;
      font-weight: 500;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s ease;
    }
    
    .action-btn.delete {
      color: var(--danger);
    }
    
    .action-btn:hover {
      opacity: 0.8;
    }
    
    .search-box {
      display: flex;
      margin-bottom: 1rem;
    }
    
    .search-box input {
      flex-grow: 1;
      padding: 0.75rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s ease;
    }
    
    .search-box input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    .btn {
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      font-weight: 500;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
      letter-spacing: 0.5px;
    }
    
    .btn:hover {
      background: linear-gradient(90deg, var(--primary-dark), var(--secondary));
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .btn:active {
      transform: translateY(0);
    }

    /* Responsive design */
    @media (max-width: 992px) {
      .sidebar {
        width: 80px;
        padding-top: 1.5rem;
      }
      
      .sidebar .logo {
        font-size: 1.5rem;
        padding: 0 0.5rem 1.5rem;
        text-align: center;
      }
      
      .sidebar .logo::after {
        left: 50%;
        transform: translateX(-50%);
        width: 40px;
      }
      
      .sidebar-menu li a {
        padding: 0.85rem 0.5rem;
        text-align: center;
      }
      
      .sidebar-menu li a span {
        display: none;
      }
      
      .main-content {
        margin-left: 80px;
        width: calc(100% - 80px);
      }
      
      .stats-container {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    
    @media (max-width: 768px) {
      .stats-container {
        grid-template-columns: 1fr;
      }
      
      .section {
        padding: 1.5rem;
      }
      
      table th, table td {
        padding: 0.75rem;
      }
    }
    
    @media (max-width: 576px) {
      .header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .header .user-info {
        margin-top: 1rem;
      }
      
      .section-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .section-header .btn {
        margin-top: 1rem;
      }
    }
  </style>
</head>
<body>
  
  <div class="sidebar">
    <div class="logo">JobMatch</div>
    <ul class="sidebar-menu">
      <li class="active"><a href="admindashboard.php"><span>Dashboard</span></a></li>
      <li><a href="usermanagement.php"><span>User Management</span></a></li>
      <li><a href="joblisting.php"><span>Job Listings</span></a></li>
      <li><a href="../logout.php"><span>Logout</span></a></li>
    </ul>
  </div>
  
  <div class="main-content">
    <div class="header">
      <h1>Admin Dashboard</h1>
      <div class="user-info">
        <div class="avatar">A</div>
        <div>Admin User</div>
      </div>
    </div>
    
    <!-- Display success/error messages if any -->
    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
      <?php 
        echo $_SESSION['message']; 
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
      ?>
    </div>
    <?php endif; ?>
    
    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?php echo $stats['total_users']; ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Jobs</div>
        <div class="stat-value"><?php echo $stats['total_jobs']; ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Applications</div>
        <div class="stat-value"><?php echo $stats['total_applications']; ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active Jobs</div>
        <div class="stat-value"><?php echo $stats['active_jobs']; ?></div>
      </div>
    </div>
    
    <div class="section">
      <div class="section-header">
        <h2 class="section-title">Recent Users</h2>
        <a href="usermanagement.php">
          <button class="btn">View All</button>
        </a>
      </div>
      <table>
        <thead>
          <tr>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>User Type</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_users as $user): ?>
          <tr>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo ucfirst(htmlspecialchars($user['user_type'])); ?></td>
            <td>
              <button class="action-btn delete" onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    
    <div class="section">
      <div class="section-header">
        <h2 class="section-title">Recent Job Listings</h2>
        <a href="joblisting.php">
          <button class="btn">View All</button>
        </a>
      </div>
      <table>
        <thead>
          <tr>
            <th>Job Title</th>
            <th>Company</th>
            <th>Posted Date</th>
            <th>Applications</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_jobs as $job): ?>
          <tr>
            <td><?php echo htmlspecialchars($job['title']); ?></td>
            <td><?php echo htmlspecialchars($job['company_name']); ?></td>
            <td><?php echo date('M j, Y', strtotime($job['created_at'])); ?></td>
            <td><?php echo $job['applications']; ?></td>
            <td>
              <button class="action-btn delete" onclick="deleteJob(<?php echo $job['id']; ?>)">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    function deleteUser(userId) {
      if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        window.location.href = 'admindashboard.php?delete_user=' + userId;
      }
    }
    
    function deleteJob(jobId) {
      if (confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
        window.location.href = 'admindashboard.php?delete_job=' + jobId;
      }
    }
  </script>
</body>
</html>
</html>