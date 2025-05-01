<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

if ($_SESSION['user_type'] !== 'employer') {
    header("Location: unauthorized.php");
    exit();
}

$jobseeker_id = $_GET['user_id'] ?? 0;

// Get job seeker profile
$profile_query = "SELECT * FROM jobseeker_profiles WHERE user_id = ?";
$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $jobseeker_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    header("Location: application.php?error=profile_not_found");
    exit();
}

// Get education
$edu_query = "SELECT * FROM educations WHERE user_id = ? ORDER BY end_date DESC";
$stmt = $conn->prepare($edu_query);
$stmt->bind_param("i", $jobseeker_id);
$stmt->execute();
$education = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get experience
$exp_query = "SELECT * FROM experiences WHERE user_id = ? ORDER BY end_date DESC";
$stmt = $conn->prepare($exp_query);
$stmt->bind_param("i", $jobseeker_id);
$stmt->execute();
$experience = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get skills
$skills_query = "SELECT skill FROM jobseeker_skills WHERE user_id = ?";
$stmt = $conn->prepare($skills_query);
$stmt->bind_param("i", $jobseeker_id);
$stmt->execute();
$skills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user info (email)
$user_query = "SELECT email FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $jobseeker_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();

// Get initials for profile picture
$initials = strtoupper(substr($profile['first_name'], 0, 1)) . strtoupper(substr($profile['last_name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?> - JobMatch</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
      padding: 1.2rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .logo {
      font-size: 1.8rem;
      font-weight: 700;
      color: white;
      display: flex;
      align-items: center;
    }
    
    .logo i {
      margin-right: 0.5rem;
      color: var(--success);
    }
    
    .nav-links {
      display: flex;
      align-items: center;
    }
    
    .nav-links a {
      color: white;
      text-decoration: none;
      margin-left: 1.8rem;
      font-weight: 500;
      font-size: 1rem;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
    }
    
    .nav-links a:hover {
      color: var(--success);
    }
    
    .nav-links a i {
      margin-right: 0.5rem;
    }
    
    .container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }
    
    .profile-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2.5rem;
    }
    
    .profile-header h1 {
      margin: 0;
      font-size: 2.2rem;
      color: var(--dark);
      font-weight: 700;
      position: relative;
      display: inline-block;
    }
    
    .profile-header h1::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 60px;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--success));
      border-radius: 2px;
    }
    
    .btn-primary {
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      font-weight: 500;
      border-radius: 8px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .btn-primary:hover {
      background: linear-gradient(90deg, var(--primary-dark), var(--secondary));
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
      border-radius: 12px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
      padding: 2rem;
      position: relative;
      overflow: hidden;
      align-self: flex-start;
    }
    
    .profile-sidebar::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--primary), var(--success));
    }
    
    .profile-picture {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      background-color: var(--primary);
      margin: 0 auto 1.5rem;
      display: flex;
      justify-content: center;
      align-items: center;
      color: white;
      font-size: 3.5rem;
      font-weight: 600;
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .profile-name {
      text-align: center;
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: var(--dark);
    }
    
    .profile-title {
      text-align: center;
      color: var(--gray);
      margin-bottom: 1.5rem;
      font-size: 1.1rem;
    }
    
    .contact-info {
      margin-top: 2rem;
    }
    
    .contact-item {
      display: flex;
      align-items: flex-start;
      margin-bottom: 1rem;
      color: var(--dark);
    }
    
    .contact-item i {
      margin-right: 1rem;
      color: var(--primary);
      font-size: 1.1rem;
      margin-top: 0.2rem;
    }
    
    .contact-item span {
      flex: 1;
    }
    
    .profile-content {
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
      padding: 2rem;
      position: relative;
      overflow: hidden;
    }
    
    .profile-content::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--primary), var(--success));
    }
    
    .section {
      margin-bottom: 2.5rem;
    }
    
    .section h2 {
      margin-top: 0;
      font-size: 1.5rem;
      color: var(--dark);
      font-weight: 600;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--light-gray);
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
    }
    
    .section h2 i {
      margin-right: 0.75rem;
      color: var(--primary);
    }
    
    .section-content {
      color: var(--dark);
      line-height: 1.7;
    }
    
    .experience-item, .education-item {
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--light-gray);
    }
    
    .experience-item:last-child, .education-item:last-child {
      margin-bottom: 0;
      padding-bottom: 0;
      border-bottom: none;
    }
    
    .item-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.5rem;
    }
    
    .item-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--dark);
    }
    
    .item-date {
      color: var(--gray);
      font-size: 0.95rem;
    }
    
    .item-company, .item-degree {
      color: var(--gray);
      margin-bottom: 1rem;
      font-size: 1.1rem;
    }
    
    .item-description {
      color: var(--dark);
      line-height: 1.7;
    }
    
    .skills-list {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }
    
    .skill-tag {
      background-color: var(--light);
      color: var(--primary);
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 500;
      border: 1px solid rgba(67, 97, 238, 0.2);
    }
    
    .resume-section {
      background-color: var(--light);
      padding: 1.5rem;
      border-radius: 8px;
      text-align: center;
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
      }
      
      .profile-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }
    }
    
    @media (max-width: 576px) {
      .container {
        padding: 0 1rem;
      }
      
      .profile-sidebar, .profile-content {
        padding: 1.5rem;
      }
      
      .profile-picture {
        width: 120px;
        height: 120px;
        font-size: 2.5rem;
      }
      
      .item-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="navbar">
    <div class="logo"> JobMatch</div>
    <div class="nav-links">
      <a href="dashboard.php"> Dashboard</a>
      <a href="application.php"> Back to Applications</a>
      <a href="../logout.php"> Logout</a>
    </div>
  </div>
  
  <div class="container">
    <div class="profile-header">
      <h1>Candidate Profile</h1>
    </div>
    
    <div class="profile-container">
      <div class="profile-sidebar">
        <div class="profile-picture">
          <?php echo htmlspecialchars($initials); ?>
        </div>
        <div class="profile-name"><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></div>
        <?php if ($profile['professional_title']): ?>
          <div class="profile-title"><?php echo htmlspecialchars($profile['professional_title']); ?></div>
        <?php endif; ?>
        
        <div class="contact-info">
          <?php if (!empty($user_info['email'])): ?>
          <div class="contact-item">
            <i class="fas fa-envelope"></i>
            <span><?php echo htmlspecialchars($user_info['email']); ?></span>
          </div>
          <?php endif; ?>
          
          <?php if (!empty($profile['phone'])): ?>
          <div class="contact-item">
            <i class="fas fa-phone"></i>
            <span><?php echo htmlspecialchars($profile['phone']); ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="profile-content">
        <?php if ($profile['summary']): ?>
        <div class="section">
          <h2><i class="fas fa-user"></i> About</h2>
          <div class="section-content">
            <p><?php echo htmlspecialchars($profile['summary']); ?></p>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($experience)): ?>
        <div class="section">
          <h2><i class="fas fa-briefcase"></i> Work Experience</h2>
          <div class="section-content">
            <?php foreach ($experience as $exp): ?>
            <div class="experience-item">
              <div class="item-header">
                <div class="item-title"><?php echo htmlspecialchars($exp['title']); ?></div>
                <div class="item-date">
                  <?php echo date('M Y', strtotime($exp['start_date'])); ?> - 
                  <?php echo $exp['current_job'] ? 'Present' : date('M Y', strtotime($exp['end_date'])); ?>
                </div>
              </div>
              <div class="item-company"><?php echo htmlspecialchars($exp['company']); ?></div>
              <?php if (!empty($exp['description'])): ?>
              <div class="item-description">
                <p><?php echo htmlspecialchars($exp['description']); ?></p>
              </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($education)): ?>
        <div class="section">
          <h2><i class="fas fa-graduation-cap"></i> Education</h2>
          <div class="section-content">
            <?php foreach ($education as $edu): ?>
            <div class="education-item">
              <div class="item-header">
                <div class="item-title"><?php echo htmlspecialchars($edu['degree']); ?></div>
                <div class="item-date">
                  <?php echo date('M Y', strtotime($edu['start_date'])); ?> - 
                  <?php echo date('M Y', strtotime($edu['end_date'])); ?>
                </div>
              </div>
              <div class="item-degree"><?php echo htmlspecialchars($edu['institution']); ?></div>
              <?php if (!empty($edu['field_of_study'])): ?>
              <div class="item-description">
                <p><?php echo htmlspecialchars($edu['field_of_study']); ?></p>
              </div>
              <?php endif; ?>
              <?php if (!empty($edu['description'])): ?>
              <div class="item-description">
                <p><?php echo htmlspecialchars($edu['description']); ?></p>
              </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($skills)): ?>
        <div class="section">
          <h2><i class="fas fa-code"></i> Skills</h2>
          <div class="section-content">
            <div class="skills-list">
              <?php foreach ($skills as $skill): ?>
                <div class="skill-tag"><?php echo htmlspecialchars($skill['skill']); ?></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if ($profile['resume_path']): ?>
        <div class="section resume-section">
          <a href="<?php echo htmlspecialchars($profile['resume_path']); ?>" class="btn-primary" download>
            <i class="fas fa-download"></i> Download Full Resume
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>