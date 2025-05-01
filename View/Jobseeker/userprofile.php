<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'jobseeker') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user profile data
$profile = [];
$skills = [];
$experiences = [];
$educations = [];
$user_info = [];

// Get basic user info (email)
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user_info = $result->fetch_assoc();
}

// Get profile info (including phone)
$stmt = $conn->prepare("SELECT * FROM jobseeker_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $profile = $result->fetch_assoc();
}

// Get skills
$stmt = $conn->prepare("SELECT skill FROM jobseeker_skills WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $skills[] = $row['skill'];
}

// Get experiences
$stmt = $conn->prepare("SELECT * FROM experiences WHERE user_id = ? ORDER BY start_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $experiences[] = $row;
}

// Get educations
$stmt = $conn->prepare("SELECT * FROM educations WHERE user_id = ? ORDER BY start_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $educations[] = $row;
}

// Get initials for profile picture
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$initials = strtoupper(substr($user['username'], 0, 2));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - JobMatch</title>
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
            position: relative;
        }
        
        .navbar::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            z-index: 0;
        }
        
        .navbar::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            z-index: 0;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        
        .nav-links {
            display: flex;
            z-index: 1;
            position: relative;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 1.5rem;
            padding: 0.5rem 0;
            font-weight: 500;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            color: var(--success);
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--success);
            transition: width 0.3s ease;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background-color: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            position: relative;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--success));
            border-radius: 12px 12px 0 0;
        }
        
        h1, h2, h3 {
            color: var(--dark);
            font-weight: 600;
        }
        
        h1 {
            margin: 0;
            font-size: 1.8rem;
            position: relative;
        }
        
        h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background-color: var(--success);
            border-radius: 2px;
        }
        
        h2 {
            font-size: 1.4rem;
            margin-top: 0;
            margin-bottom: 1.2rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid var(--light-gray);
        }
        
        h3 {
            font-size: 1.2rem;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
        }
        
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        .profile-sidebar {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 1.8rem;
            position: relative;
            overflow: hidden;
        }
        
        .profile-sidebar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
            border-radius: 12px 0 0 12px;
        }
        
        .profile-content {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 1.8rem;
            position: relative;
            overflow: hidden;
        }
        
        .profile-content::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
            border-radius: 12px 0 0 12px;
        }
        
        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2.5rem;
            font-weight: 600;
            margin: 0 auto 1.5rem;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
        }
        
        .profile-name {
            text-align: center;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .profile-title {
            text-align: center;
            color: var(--gray);
            margin-bottom: 1.5rem;
        }
        
        .contact-info {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--light-gray);
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            color: var(--dark);
        }
        
        .contact-item i {
            width: 24px;
            height: 24px;
            color: var(--primary);
            margin-right: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .profile-stats {
            display: flex;
            justify-content: space-around;
            margin: 1.5rem 0;
            padding: 1rem 0;
            border-top: 1px solid var(--light-gray);
            border-bottom: 1px solid var(--light-gray);
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.8rem;
        }
        
        .skill-tag {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 0.4rem 0.8rem;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .section {
            margin-bottom: 2rem;
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .item-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
        }
        
        .item-date {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .item-company, .item-degree {
            color: var(--primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .item-description {
            color: var(--gray);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .experience-item, .education-item {
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .experience-item:last-child, .education-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .visibility-toggle {
            display: flex;
            align-items: center;
            margin-top: 1rem;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-right: 1rem;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--light-gray);
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .edit-btn {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
        }
        
        .edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }
            
            .logo {
                margin-bottom: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-links a {
                margin: 0.5rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .edit-btn {
                margin-top: 1rem;
                width: 100%;
                text-align: center;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="navbar">
        <div class="logo">JobMatch</div>
        <div class="nav-links">
            <a href="job_search.php">Dashboard</a>
            <a href="userprofile.php">Profile</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
  
    <div class="container">
        <div class="page-header">
            <h1>My Profile</h1>
            <a href="edit_profile.php" class="edit-btn">Edit Profile</a>
        </div>
    
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-picture">
                    <span><?php echo htmlspecialchars($initials); ?></span>
                </div>
                <div class="profile-name">
                    <?php 
                    $fullName = trim($profile['first_name'] ?? '') . ' ' . trim($profile['last_name'] ?? '');
                    echo htmlspecialchars(trim($fullName) ?: 'Your Name');
                    ?>
                </div>
                <div class="profile-title">
                    <?php echo htmlspecialchars($profile['professional_title'] ?? 'Professional Title'); ?>
                </div>
                
                
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
                    
                    <?php if (!empty($profile['address'])): ?>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($profile['address']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($skills)): ?>
                <div class="profile-skills">
                    <h3>Skills</h3>
                    <div class="skills-list">
                        <?php foreach ($skills as $skill): ?>
                        <div class="skill-tag"><?php echo htmlspecialchars($skill); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
      
            <div class="profile-content">
                <?php if (!empty($profile['summary'])): ?>
                <div class="section">
                    <h2>Professional Summary</h2>
                    <div class="section-content">
                        <p><?php echo htmlspecialchars($profile['summary']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($experiences)): ?>
                <div class="section">
                    <h2>Work Experience</h2>
                    <div class="section-content">
                        <?php foreach ($experiences as $exp): ?>
                        <div class="experience-item">
                            <div class="item-header">
                                <div class="item-title"><?php echo htmlspecialchars($exp['title']); ?></div>
                                <div class="item-date">
                                    <?php echo date('M Y', strtotime($exp['start_date'])); ?>
                                    - 
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
                
                <?php if (!empty($educations)): ?>
                <div class="section">
                    <h2>Education</h2>
                    <div class="section-content">
                        <?php foreach ($educations as $edu): ?>
                        <div class="education-item">
                            <div class="item-header">
                                <div class="item-title"><?php echo htmlspecialchars($edu['degree']); ?></div>
                                <div class="item-date">
                                    <?php echo date('M Y', strtotime($edu['start_date'])); ?>
                                    - 
                                    <?php echo $edu['end_date'] ? date('M Y', strtotime($edu['end_date'])) : 'Present'; ?>
                                </div>
                            </div>
                            <div class="item-degree"><?php echo htmlspecialchars($edu['institution']); ?></div>
                            <?php if (!empty($edu['field_of_study'])): ?>
                            <div class="item-description">
                                <p><?php echo htmlspecialchars($edu['field_of_study']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="section resume-section">
                    <h2>Profile Visibility</h2>
                    <div class="section-content">
                        <div class="visibility-toggle">
                            <label class="switch">
                                <input type="checkbox" <?php echo ($profile['profile_visible'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span>Make my profile visible to employers</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>