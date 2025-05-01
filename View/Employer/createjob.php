<?php
// createjob.php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

// Check for success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Only employers can access this page
if ($_SESSION['user_type'] !== 'employer') {
    header("Location: unauthorized.php");
    exit();
}

// Get form data from session if available (for repopulating after validation errors)
$form_data = $_SESSION['form_data'] ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['errors']);

// Common benefits list
$common_benefits = [
    'Health Insurance', 'Dental Insurance', 'Retirement Plan',
    'Paid Vacation', 'Flexible Schedule', 'Remote Work Options'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Job - JobMatch Employer</title>
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
    
    .job-form {
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      padding: 2.5rem;
      position: relative;
    }
    
    .job-form::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 10px;
      background: linear-gradient(90deg, var(--primary), var(--success));
      border-radius: 16px 16px 0 0;
    }
    
    .form-section {
      margin-bottom: 2.5rem;
      padding-bottom: 2.5rem;
      border-bottom: 1px solid var(--light-gray);
    }
    
    .form-section:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }
    
    .form-section h2 {
      margin-top: 0;
      color: var(--dark);
      font-weight: 600;
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
    }
    
    .form-group {
      margin-bottom: 1.5rem;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: var(--dark);
    }
    
    .form-group input, .form-group textarea, .form-group select {
      width: 100%;
      padding: 1rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s ease;
      background-color: var(--light);
    }
    
    .form-group input:focus, 
    .form-group textarea:focus, 
    .form-group select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    .form-group input::placeholder,
    .form-group textarea::placeholder,
    .form-group select::placeholder {
      color: var(--gray);
      opacity: 0.6;
    }
    
    .form-group textarea {
      min-height: 120px;
    }
    
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }
    
    .form-actions {
      display: flex;
      justify-content: flex-end;
      margin-top: 2rem;
      gap: 1rem;
    }
    
    button {
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      padding: 1rem 1.5rem;
      font-size: 1rem;
      font-weight: 500;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      font-family: 'Poppins', sans-serif;
    }
    
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    button:active {
      transform: translateY(0);
    }
    
    .btn-secondary {
      background: transparent;
      color: var(--primary);
      border: 2px solid var(--primary);
    }
    
    .btn-draft {
      background: linear-gradient(90deg, var(--warning), #f9844a);
    }
    
    .salary-range {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .salary-range input {
      flex-grow: 1;
    }
    
    .checkbox-group {
      display: flex;
      align-items: center;
      margin-bottom: 0.5rem;
    }
    
    .checkbox-group input {
      width: auto;
      margin-right: 0.5rem;
    }
    
    .checkbox-group label {
      margin-bottom: 0;
      font-weight: normal;
    }
    
    .tab-nav {
      display: flex;
      border-bottom: 1px solid var(--light-gray);
      margin-bottom: 2rem;
    }
    
    .tab {
      padding: 0.75rem 1.5rem;
      cursor: pointer;
      border-bottom: 3px solid transparent;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .tab.active {
      border-bottom-color: var(--success);
      color: var(--primary);
    }
    
    .alert {
      padding: 1rem;
      margin-bottom: 1.5rem;
      border-radius: 8px;
      font-weight: 500;
    }
    
    .alert-success {
      background-color: rgba(76, 201, 240, 0.1);
      color: var(--success);
      border-left: 4px solid var(--success);
    }
    
    .alert-error {
      background-color: rgba(247, 37, 133, 0.1);
      color: var(--danger);
      border-left: 4px solid var(--danger);
    }
    
    /* Responsive design */
    @media (max-width: 992px) {
      .form-row {
        grid-template-columns: 1fr;
      }
      
      .job-form {
        padding: 2rem;
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
    }
    
    @media (max-width: 576px) {
      .container {
        padding: 1rem;
      }
      
      .job-form {
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
      <h1>Create New Job Posting</h1>
      <p>Fill out the form below to create a new job listing that will connect you with talented professionals</p>
      
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <h3 style="margin-top: 0;">Please fix the following errors:</h3>
          <ul style="margin-bottom: 0;">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>
    
    <?php if (!empty($success_message)): ?>
      <div class="alert alert-success">
        <?php echo htmlspecialchars($success_message); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
      <div class="alert alert-error">
        <?php echo htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>

    <form action="job_process.php" method="POST" class="job-form">
      <div class="form-section">
        <h2>Job Information</h2>
        <div class="form-group">
          <label>Job Title*</label>
          <input type="text" name="title" placeholder="e.g. Senior Software Engineer" 
                 value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Job Type*</label>
            <select name="job_type" required>
              <option value="">Select job type</option>
              <option value="fulltime" <?php echo ($form_data['job_type'] ?? '') === 'fulltime' ? 'selected' : ''; ?>>Full-time</option>
              <option value="parttime" <?php echo ($form_data['job_type'] ?? '') === 'parttime' ? 'selected' : ''; ?>>Part-time</option>
              <option value="contract" <?php echo ($form_data['job_type'] ?? '') === 'contract' ? 'selected' : ''; ?>>Contract</option>
              <option value="internship" <?php echo ($form_data['job_type'] ?? '') === 'internship' ? 'selected' : ''; ?>>Internship</option>
              <option value="temporary" <?php echo ($form_data['job_type'] ?? '') === 'temporary' ? 'selected' : ''; ?>>Temporary</option>
            </select>
          </div>
          <div class="form-group">
            <label>Location*</label>
            <select name="location" required>
              <option value="">Select location</option>
              <option value="colombo" <?php echo ($form_data['location'] ?? '') === 'colombo' ? 'selected' : ''; ?>>Colombo</option>
              <option value="kandy" <?php echo ($form_data['location'] ?? '') === 'kandy' ? 'selected' : ''; ?>>Kandy</option>
              <option value="galle" <?php echo ($form_data['location'] ?? '') === 'galle' ? 'selected' : ''; ?>>Galle</option>
              <option value="jaffna" <?php echo ($form_data['location'] ?? '') === 'jaffna' ? 'selected' : ''; ?>>Jaffna</option>
              <option value="remote" <?php echo ($form_data['location'] ?? '') === 'remote' ? 'selected' : ''; ?>>Remote</option>
              <option value="hybrid" <?php echo ($form_data['location'] ?? '') === 'hybrid' ? 'selected' : ''; ?>>Hybrid</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" placeholder="e.g. Engineering, Marketing"
                   value="<?php echo htmlspecialchars($form_data['department'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>Experience Level</label>
            <select name="experience_level">
              <option value="">Select experience level</option>
              <option value="intern" <?php echo ($form_data['experience_level'] ?? '') === 'intern' ? 'selected' : ''; ?>>Intern</option>
              <option value="entry" <?php echo ($form_data['experience_level'] ?? '') === 'entry' ? 'selected' : ''; ?>>Entry Level</option>
              <option value="mid" <?php echo ($form_data['experience_level'] ?? '') === 'mid' ? 'selected' : ''; ?>>Mid Level</option>
              <option value="senior" <?php echo ($form_data['experience_level'] ?? '') === 'senior' ? 'selected' : ''; ?>>Senior Level</option>
              <option value="director" <?php echo ($form_data['experience_level'] ?? '') === 'director' ? 'selected' : ''; ?>>Director</option>
              <option value="executive" <?php echo ($form_data['experience_level'] ?? '') === 'executive' ? 'selected' : ''; ?>>Executive</option>
            </select>
          </div>
        </div>
      </div>
      
      <div class="form-section">
        <h2>Salary & Benefits</h2>
        <div class="form-row">
          <div class="form-group">
            <label>Salary Range</label>
            <div class="salary-range">
              <input type="number" name="salary_min" placeholder="Min" step="0.01" min="0"
                     value="<?php echo htmlspecialchars($form_data['salary_min'] ?? ''); ?>">
              <span>to</span>
              <input type="number" name="salary_max" placeholder="Max" step="0.01" min="0"
                     value="<?php echo htmlspecialchars($form_data['salary_max'] ?? ''); ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Salary Period</label>
            <select name="salary_period">
              <option value="yearly" <?php echo ($form_data['salary_period'] ?? '') === 'yearly' ? 'selected' : ''; ?>>Per Year</option>
              <option value="monthly" <?php echo ($form_data['salary_period'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Per Month</option>
              <option value="weekly" <?php echo ($form_data['salary_period'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Per Week</option>
              <option value="hourly" <?php echo ($form_data['salary_period'] ?? '') === 'hourly' ? 'selected' : ''; ?>>Per Hour</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Benefits</label>
          <?php foreach ($common_benefits as $benefit): ?>
            <div class="checkbox-group">
              <input type="checkbox" name="benefits[]" id="<?php echo strtolower(str_replace(' ', '-', $benefit)); ?>" 
                     value="<?php echo htmlspecialchars($benefit); ?>"
                     <?php echo (isset($form_data['benefits']) && in_array($benefit, $form_data['benefits'])) ? 'checked' : ''; ?>>
              <label for="<?php echo strtolower(str_replace(' ', '-', $benefit)); ?>"><?php echo htmlspecialchars($benefit); ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <div class="form-section">
        <h2>Job Description</h2>
        <div class="form-group">
          <label>Job Summary*</label>
          <textarea name="summary" placeholder="Brief overview of the position" required><?php 
            echo htmlspecialchars($form_data['summary'] ?? ''); 
          ?></textarea>
        </div>
        <div class="form-group">
          <label>Responsibilities*</label>
          <textarea name="responsibilities" placeholder="List the key responsibilities of the position" required><?php 
            echo htmlspecialchars($form_data['responsibilities'] ?? ''); 
          ?></textarea>
        </div>
        <div class="form-group">
          <label>Qualifications*</label>
          <textarea name="qualifications" placeholder="List the required qualifications and skills" required><?php 
            echo htmlspecialchars($form_data['qualifications'] ?? ''); 
          ?></textarea>
        </div>
        <div class="form-group">
          <label>Nice-to-Have Skills</label>
          <textarea name="preferred_skills" placeholder="List any additional skills that would be beneficial"><?php 
            echo htmlspecialchars($form_data['preferred_skills'] ?? ''); 
          ?></textarea>
        </div>
      </div>
      
      <div class="form-section">
        <h2>Application Settings</h2>
        <div class="form-group">
          <label>Application Deadline</label>
          <input type="date" name="deadline" 
                 value="<?php echo htmlspecialchars($form_data['deadline'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>Application Questions</label>
          <div class="checkbox-group">
            <input type="checkbox" name="require_cover_letter" id="cover-letter" 
                   <?php echo isset($form_data['require_cover_letter']) ? 'checked' : 'checked'; ?>>
            <label for="cover-letter">Require Cover Letter</label>
          </div>
          <div class="checkbox-group">
            <input type="checkbox" name="require_portfolio" id="portfolio"
                   <?php echo isset($form_data['require_portfolio']) ? 'checked' : ''; ?>>
            <label for="portfolio">Require Portfolio/Work Samples</label>
          </div>
          <div class="checkbox-group">
            <input type="checkbox" name="require_references" id="references"
                   <?php echo isset($form_data['require_references']) ? 'checked' : ''; ?>>
            <label for="references">Require References</label>
          </div>
        </div>
        <div class="form-group">
          <label>Custom Questions</label>
          <textarea name="custom_questions" placeholder="Add any additional questions you want applicants to answer"><?php 
            echo htmlspecialchars($form_data['custom_questions'] ?? ''); 
          ?></textarea>
        </div>
      </div>
      
      <div class="form-actions">
        <button type="submit" class="btn-primary">Publish Job</button>
      </div>
    </form>
  </div>

  <script>
    // Simple tab navigation
    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', function() {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        // Here you would add logic to show/hide form sections
      });
    });
  </script>
</body>
</html>