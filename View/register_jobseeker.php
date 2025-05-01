<?php
session_start();
require_once '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic account info
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $user_type = 'jobseeker';

    // Profile info
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $professional_title = trim($_POST['professional_title']);
    $phone = trim($_POST['phone']);
    $summary = trim($_POST['summary']);
    $skills = explode(',', trim($_POST['skills']));
    
    // Experience (simplified for registration)
    $exp_title = trim($_POST['exp_title'] ?? '');
    $exp_company = trim($_POST['exp_company'] ?? '');
    $exp_start = $_POST['exp_start'] ?? '';
    $exp_end = $_POST['exp_end'] ?? '';
    $exp_current = isset($_POST['exp_current']) ? 1 : 0;
    $exp_desc = trim($_POST['exp_desc'] ?? '');
    
    // Education
    $edu_degree = trim($_POST['edu_degree'] ?? '');
    $edu_institution = trim($_POST['edu_institution'] ?? '');
    $edu_field = trim($_POST['edu_field'] ?? '');
    $edu_start = $_POST['edu_start'] ?? '';
    $edu_end = $_POST['edu_end'] ?? '';
    $edu_desc = trim($_POST['edu_desc'] ?? '');

    // Validate inputs
    $errors = [];

    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($professional_title)) $errors[] = "Professional title is required";

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Username or email already exists";
    }

    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert into users table
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, user_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hashed_password, $email, $user_type);
            $stmt->execute();
            $user_id = $conn->insert_id;

            // Insert into jobseeker_profiles table
            $stmt = $conn->prepare("INSERT INTO jobseeker_profiles 
                (user_id, first_name, last_name, professional_title, phone, summary) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $user_id, $first_name, $last_name, $professional_title, $phone, $summary);
            $stmt->execute();

            // Insert skills
            if (!empty($skills)) {
                $stmt = $conn->prepare("INSERT INTO jobseeker_skills (user_id, skill) VALUES (?, ?)");
                foreach ($skills as $skill) {
                    $skill = trim($skill);
                    if (!empty($skill)) {
                        $stmt->bind_param("is", $user_id, $skill);
                        $stmt->execute();
                    }
                }
            }

            // Insert experience if provided
            if (!empty($exp_title) && !empty($exp_company)) {
                $stmt = $conn->prepare("INSERT INTO experiences 
                    (user_id, title, company, start_date, end_date, current_job, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssis", $user_id, $exp_title, $exp_company, $exp_start, $exp_end, $exp_current, $exp_desc);
                $stmt->execute();
            }

            // Insert education if provided
            if (!empty($edu_degree) && !empty($edu_institution)) {
                $stmt = $conn->prepare("INSERT INTO educations 
                    (user_id, degree, institution, field_of_study, start_date, end_date, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssss", $user_id, $edu_degree, $edu_institution, $edu_field, $edu_start, $edu_end, $edu_desc);
                $stmt->execute();
            }

            // Commit transaction
            $conn->commit();

            // Set session and redirect
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = $user_type;
            
            header("Location: login.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header("Location: register_jobseeker.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Seeker Registration - JobMatch</title>
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
    
    .container {
      max-width: 1000px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }
    
    h1 {
      font-size: 2.5rem;
      margin-bottom: 1.5rem;
      color: var(--primary);
      font-weight: 700;
      position: relative;
      display: inline-block;
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
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
      color: var(--dark);
      font-weight: 600;
    }
    
    .form-section {
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
      padding: 2.5rem;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
    }
    
    .form-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--primary), var(--success));
    }
    
    .form-group {
      margin-bottom: 1.5rem;
      position: relative;
    }
    
    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: var(--dark);
    }
    
    .required::after {
      content: '*';
      color: var(--danger);
      margin-left: 4px;
    }
    
    input, textarea, select {
      width: 100%;
      padding: 1rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background-color: var(--light);
    }
    
    input:focus, textarea:focus, select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    input::placeholder, textarea::placeholder {
      color: var(--gray);
      opacity: 0.6;
    }
    
    textarea {
      min-height: 120px;
      resize: vertical;
    }
    
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }
    
    .btn-primary {
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      padding: 1rem 1.5rem;
      font-size: 1rem;
      font-weight: 500;
      border-radius: 8px;
      cursor: pointer;
      width: 100%;
      margin-top: 0.5rem;
      transition: all 0.3s ease;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }
    
    .btn-primary:hover {
      background: linear-gradient(90deg, var(--primary-dark), var(--secondary));
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .error {
      color: var(--danger);
      margin-bottom: 1.5rem;
      padding: 1rem;
      background-color: rgba(247, 37, 133, 0.1);
      border-radius: 8px;
      text-align: center;
      border-left: 4px solid var(--danger);
      font-weight: 500;
    }
    
    .checkbox-group {
      display: flex;
      align-items: center;
    }
    
    .checkbox-group input {
      width: auto;
      margin-right: 0.75rem;
      accent-color: var(--primary);
    }
    
    .progress-steps {
      display: flex;
      justify-content: space-between;
      margin-bottom: 2rem;
      position: relative;
    }
    
    .progress-steps::before {
      content: '';
      position: absolute;
      top: 15px;
      left: 0;
      right: 0;
      height: 2px;
      background-color: var(--light-gray);
      z-index: 1;
    }
    
    .step {
      text-align: center;
      position: relative;
      z-index: 2;
    }
    
    .step-number {
      width: 32px;
      height: 32px;
      background-color: var(--light-gray);
      color: var(--gray);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 0.5rem;
      font-weight: 600;
    }
    
    .step.active .step-number {
      background-color: var(--primary);
      color: white;
    }
    
    .step-text {
      font-size: 0.85rem;
      color: var(--gray);
      font-weight: 500;
    }
    
    .step.active .step-text {
      color: var(--primary);
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }
    }
    
    @media (max-width: 576px) {
      .container {
        padding: 0 1rem;
      }
      
      .form-section {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Job Seeker Registration</h1>
    
    <div class="progress-steps">
      <div class="step active">
        <div class="step-number">1</div>
        <div class="step-text">Account Info</div>
      </div>
      <div class="step">
        <div class="step-number">2</div>
        <div class="step-text">Personal Info</div>
      </div>
      <div class="step">
        <div class="step-number">3</div>
        <div class="step-text">Experience</div>
      </div>
      <div class="step">
        <div class="step-number">4</div>
        <div class="step-text">Education</div>
      </div>
    </div>
    
    <?php if (isset($_SESSION['errors'])): ?>
      <div class="error">
        <?php foreach ($_SESSION['errors'] as $error): ?>
          <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
        <?php unset($_SESSION['errors']); ?>
      </div>
    <?php endif; ?>

    <form action="register_jobseeker.php" method="POST">
      <div class="form-section">
        <h2>Account Information</h2>
        <div class="form-row">
          <div class="form-group">
            <label class="required">Username</label>
            <input type="text" name="username" required 
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['username'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="required">Email</label>
            <input type="email" name="email" required
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['email'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="required">Password</label>
            <input type="password" name="password" required>
          </div>
          <div class="form-group">
            <label class="required">Confirm Password</label>
            <input type="password" name="confirm_password" required>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h2>Personal Information</h2>
        <div class="form-row">
          <div class="form-group">
            <label class="required">First Name</label>
            <input type="text" name="first_name" required
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['first_name'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="required">Last Name</label>
            <input type="text" name="last_name" required
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['last_name'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="required">Professional Title</label>
          <input type="text" name="professional_title" placeholder="e.g. Software Engineer, Marketing Specialist" required
                value="<?php echo htmlspecialchars($_SESSION['form_data']['professional_title'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" placeholder="+1 (123) 456-7890"
                value="<?php echo htmlspecialchars($_SESSION['form_data']['phone'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label class="required">Professional Summary</label>
          <textarea name="summary" required placeholder="Briefly describe your professional background, skills, and career goals"><?php 
              echo htmlspecialchars($_SESSION['form_data']['summary'] ?? ''); 
          ?></textarea>
        </div>
        <div class="form-group">
          <label class="required">Skills</label>
          <input type="text" name="skills" placeholder="e.g. JavaScript, React, Project Management, Photoshop" required
                value="<?php echo htmlspecialchars($_SESSION['form_data']['skills'] ?? ''); ?>">
          <small style="color: var(--gray); display: block; margin-top: 0.5rem;">Separate skills with commas</small>
        </div>
      </div>

      <div class="form-section">
        <h2>Work Experience</h2>
        <p>Add your most recent or relevant work experience (optional)</p>
        <div class="form-row">
          <div class="form-group">
            <label>Job Title</label>
            <input type="text" name="exp_title"
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['exp_title'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>Company</label>
            <input type="text" name="exp_company"
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['exp_company'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Start Date</label>
            <input type="date" name="exp_start"
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['exp_start'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>End Date</label>
            <input type="date" name="exp_end"
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['exp_end'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-group">
          <div class="checkbox-group">
            <input type="checkbox" name="exp_current" id="current_job"
                  <?php echo isset($_SESSION['form_data']['exp_current']) ? 'checked' : ''; ?>> 
            <label for="current_job">I currently work here</label>
          </div>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="exp_desc" placeholder="Describe your responsibilities and achievements in this role"><?php 
              echo htmlspecialchars($_SESSION['form_data']['exp_desc'] ?? ''); 
          ?></textarea>
        </div>
      </div>

      <div class="form-section">
        <h2>Education</h2>
        <p>Add your highest level of education (optional)</p>
        <div class="form-row">
          <div class="form-group">
            <label>Degree</label>
            <input type="text" name="edu_degree" placeholder="e.g. Bachelor of Science"
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['edu_degree'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>Institution</label>
            <input type="text" name="edu_institution" placeholder="University Name"
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['edu_institution'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Field of Study</label>
          <input type="text" name="edu_field" placeholder="e.g. Computer Science, Business Administration"
                value="<?php echo htmlspecialchars($_SESSION['form_data']['edu_field'] ?? ''); ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Start Date</label>
            <input type="date" name="edu_start"
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['edu_start'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>End Date (or expected)</label>
            <input type="date" name="edu_end"
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['edu_end'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="edu_desc" placeholder="Notable achievements, honors, or relevant coursework"><?php 
              echo htmlspecialchars($_SESSION['form_data']['edu_desc'] ?? ''); 
          ?></textarea>
        </div>
      </div>

      <button type="submit" class="btn-primary">Complete Registration</button>
    </form>
  </div>
  <?php unset($_SESSION['form_data']); ?>
</body>
</html>