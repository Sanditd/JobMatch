<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

// Check if user is logged in as employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch company profile data
$profile = [];
$benefits = [];
$photos = [];

// Get basic profile info
$stmt = $conn->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $profile = $result->fetch_assoc();
}

// Get benefits
$stmt = $conn->prepare("SELECT benefit FROM employer_benefits WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $benefits[] = $row['benefit'];
}

// Get photos
$stmt = $conn->prepare("SELECT photo_path, caption FROM employer_photos WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $photos[] = $row;
}

// Get company name for logo initial
$company_name = $profile['company_name'] ?? '';
$initials = strtoupper(substr($company_name, 0, 2));

// Count jobs posted
$stmt = $conn->prepare("SELECT COUNT(*) as job_count FROM jobs WHERE employer_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$job_count = $result->fetch_assoc()['job_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Company Profile - JobMatch</title>
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
    }
    
    .navbar {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .logo {
      font-size: 1.5rem;
      font-weight: 700;
      position: relative;
    }
    
    .logo::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 30px;
      height: 3px;
      background-color: var(--success);
      border-radius: 2px;
    }
    
    .nav-links {
      display: flex;
    }
    
    .nav-links a {
      color: white;
      text-decoration: none;
      margin-left: 1.5rem;
      font-weight: 500;
      transition: all 0.3s ease;
      padding: 0.5rem 0;
    }
    
    .nav-links a:hover {
      color: var(--success);
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem;
    }
    
    .page-header {
      margin-bottom: 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .page-header h1 {
      margin: 0 0 0.5rem;
      color: var(--dark);
      font-weight: 700;
      position: relative;
      display: inline-block;
    }
    
    .page-header h1::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 60px;
      height: 4px;
      background-color: var(--success);
      border-radius: 2px;
    }
    
    .btn-primary {
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      padding: 1rem 1.5rem;
      border-radius: 8px;
      text-decoration: none;
      display: inline-block;
      text-align: center;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      font-size: 0.9rem;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .profile-container {
      display: grid;
      grid-template-columns: 1fr 2fr;
      gap: 2rem;
    }
    
    .profile-sidebar {
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      padding: 1.5rem;
      position: relative;
    }
    
    .profile-sidebar::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 10px;
      background: linear-gradient(90deg, var(--primary), var(--success));
      border-radius: 16px 16px 0 0;
    }
    
    .company-initials {
      width: 150px;
      height: 150px;
      border-radius: 16px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      margin: 1.5rem auto;
      display: flex;
      justify-content: center;
      align-items: center;
      color: white;
      font-size: 3rem;
      font-weight: 700;
    }
    
    .company-stats {
      margin-top: 1.5rem;
    }
    
    .stat-item {
      display: flex;
      justify-content: space-between;
      padding: 0.75rem 0;
      border-bottom: 1px solid var(--light-gray);
    }
    
    .stat-label {
      color: var(--gray);
    }
    
    .stat-value {
      font-weight: 500;
      color: var(--primary);
    }
    
    .profile-content {
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      position: relative;
    }
    
    .profile-content::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 10px;
      background: linear-gradient(90deg, var(--primary), var(--success));
      border-radius: 16px 16px 0 0;
    }
    
    .section {
      padding: 2rem;
      border-bottom: 1px solid var(--light-gray);
    }
    
    .section:last-child {
      border-bottom: none;
    }
    
    .section h2 {
      margin-top: 0;
      margin-bottom: 1.5rem;
      color: var(--dark);
      font-weight: 600;
      position: relative;
      display: inline-block;
    }
    
    .section h2::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 40px;
      height: 3px;
      background-color: var(--success);
      border-radius: 2px;
    }
    
    .info-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
      margin-bottom: 1.5rem;
    }
    
    .info-item {
      margin-bottom: 1.5rem;
    }
    
    .info-label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: var(--gray);
      font-size: 0.9rem;
    }
    
    .info-value {
      padding: 1rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      background-color: var(--light);
      min-height: 3rem;
      transition: all 0.3s ease;
    }
    
    .info-text {
      padding: 1rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      background-color: var(--light);
      min-height: 6rem;
      transition: all 0.3s ease;
    }
    
    .benefits-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1rem;
      margin-top: 1rem;
    }
    
    .benefit-tag {
      background-color: var(--light);
      padding: 0.5rem 1rem;
      border-radius: 12px;
      font-size: 0.9rem;
      color: var(--primary);
      display: inline-flex;
      align-items: center;
    }
    
    .benefit-tag:before {
      content: '✓';
      margin-right: 0.5rem;
      color: var(--success);
      font-weight: 700;
    }
    
    /* Responsive design */
    @media (max-width: 992px) {
      .profile-container {
        grid-template-columns: 1fr;
      }
    }
    
    @media (max-width: 768px) {
      .navbar {
        flex-direction: column;
        padding: 1rem;
      }
      
      .logo {
        margin-bottom: 1rem;
      }
      
      .nav-links {
        width: 100%;
        justify-content: space-around;
      }
      
      .nav-links a {
        margin: 0;
        font-size: 0.9rem;
      }
      
      .info-row {
        grid-template-columns: 1fr;
      }
    }
    
    @media (max-width: 576px) {
      .container {
        padding: 1rem;
      }
      
      .section {
        padding: 1.5rem;
      }
      
      .nav-links {
        flex-wrap: wrap;
      }
      
      .nav-links a {
        margin-bottom: 0.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="navbar">
    <div class="logo">JobMatch</div>
    <div class="nav-links">
      <a href="dashboard.php">Dashboard</a>
      <a href="createjob.php">Create Job</a>
      <a href="application.php">Applications</a>
      <a href="companyprofilepage.php">Company Profile</a>
      <a href="../logout.php">Logout</a>
    </div>
  </div>
  
  <div class="container">
    <div class="page-header">
      <h1>Company Profile</h1>
      <a href="edit_company_profile.php" class="btn-primary">Edit Profile</a>
    </div>
    
    <div class="profile-container">
      <div class="profile-sidebar">
        <div class="company-initials">
          <?php echo strtoupper(substr($profile['company_name'] ?? '', 0, 2)); ?>
        </div>
        
        <div class="company-stats">
          <div class="stat-item">
            <span class="stat-label">Jobs Posted</span>
            <span class="stat-value"><?php echo htmlspecialchars($job_count); ?></span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Member Since</span>
            <span class="stat-value">
              <?php 
                $stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                echo date('M Y', strtotime($user['created_at']));
              ?>
            </span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Industry</span>
            <span class="stat-value"><?php echo htmlspecialchars($profile['industry'] ?? 'Not specified'); ?></span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Company Size</span>
            <span class="stat-value"><?php echo htmlspecialchars($profile['company_size'] ?? 'Not specified'); ?></span>
          </div>
        </div>
      </div>
      
      <div class="profile-content">
        <div class="section">
          <h2>Basic Information</h2>
          <div class="info-row">
            <div class="info-item">
              <span class="info-label">Company Name</span>
              <div class="info-value"><?php echo htmlspecialchars($profile['company_name'] ?? 'Not specified'); ?></div>
            </div>
            <div class="info-item">
              <span class="info-label">Website</span>
              <div class="info-value"><?php echo htmlspecialchars($profile['website'] ?? 'Not specified'); ?></div>
            </div>
          </div>
          <div class="info-item">
            <span class="info-label">Company Description</span>
            <div class="info-text"><?php echo htmlspecialchars($profile['description'] ?? 'No description provided.'); ?></div>
          </div>
        </div>
        
        <?php if (!empty($profile['culture_description']) || !empty($benefits)): ?>
        <div class="section">
          <h2>Company Culture</h2>
          <?php if (!empty($profile['culture_description'])): ?>
          <div class="info-item">
            <span class="info-label">What makes your company unique?</span>
            <div class="info-text"><?php echo htmlspecialchars($profile['culture_description']); ?></div>
          </div>
          <?php endif; ?>
          
          <?php if (!empty($benefits)): ?>
          <div class="info-item">
            <span class="info-label">Employee Benefits</span>
            <div class="benefits-list">
              <?php foreach ($benefits as $benefit): ?>
                <div class="benefit-tag"><?php echo htmlspecialchars($benefit); ?></div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="section">
          <h2>Contact Information</h2>
          <div class="info-row">
            <div class="info-item">
              <span class="info-label">Email</span>
              <div class="info-value"><?php echo htmlspecialchars($profile['contact_email'] ?? 'Not specified'); ?></div>
            </div>
            <div class="info-item">
              <span class="info-label">Phone</span>
              <div class="info-value"><?php echo htmlspecialchars($profile['contact_phone'] ?? 'Not specified'); ?></div>
            </div>
          </div>
          <div class="info-item">
            <span class="info-label">Address</span>
            <div class="info-value"><?php echo htmlspecialchars($profile['address'] ?? 'Not specified'); ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>