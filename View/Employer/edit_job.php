<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

// Only employers can access this page
if ($_SESSION['user_type'] !== 'employer') {
    header("Location: unauthorized.php");
    exit();
}

// Check if job ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$job_id = $_GET['id'];
$employer_id = $_SESSION['user_id'];

// Fetch job data
$stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
$stmt->bind_param("ii", $job_id, $employer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$job = $result->fetch_assoc();

// Fetch job benefits
$stmt = $conn->prepare("SELECT benefit FROM job_benefits WHERE job_id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
$benefits = [];
while ($row = $result->fetch_assoc()) {
    $benefits[] = $row['benefit'];
}

// Common benefits list
$common_benefits = [
    'Health Insurance', 'Dental Insurance', 'Retirement Plan',
    'Paid Vacation', 'Flexible Schedule', 'Remote Work Options'
];

// Check for success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Get form data from session if available (for repopulating after validation errors)
$form_data = $_SESSION['form_data'] ?? $job;
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['errors']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Job - JobMatch Employer</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f5f7fa;
      color: #333;
    }
    .navbar {
      background-color: #2c3e50;
      color: white;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo {
      font-size: 1.5rem;
      font-weight: bold;
    }
    .nav-links {
      display: flex;
    }
    .nav-links a {
      color: white;
      text-decoration: none;
      margin-left: 1.5rem;
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
      color: #2c3e50;
    }
    .job-form {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      padding: 2rem;
    }
    .form-section {
      margin-bottom: 2rem;
      padding-bottom: 2rem;
      border-bottom: 1px solid #eee;
    }
    .form-section:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }
    .form-section h2 {
      margin-top: 0;
      color: #2c3e50;
    }
    .form-group {
      margin-bottom: 1.5rem;
    }
    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
    }
    .form-group input, .form-group textarea, .form-group select {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
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
    .btn-primary {
      background-color: #3498db;
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      border-radius: 4px;
      cursor: pointer;
    }
    .btn-secondary {
      background-color: white;
      color: #3498db;
      border: 1px solid #3498db;
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      border-radius: 4px;
      cursor: pointer;
    }
    .btn-draft {
      background-color: #f39c12;
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      border-radius: 4px;
      cursor: pointer;
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
      border-bottom: 1px solid #ddd;
      margin-bottom: 2rem;
    }
    .tab {
      padding: 0.75rem 1.5rem;
      cursor: pointer;
      border-bottom: 3px solid transparent;
    }
    .tab.active {
      border-bottom-color: #3498db;
      color: #3498db;
      font-weight: 500;
    }

    .alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
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
      <h1>Edit Job Posting</h1>
      <p>Update the job listing below</p>
      
      <?php if (!empty($errors)): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
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

    <form action="job_update.php" method="POST" class="job-form">
      <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
      
      <div class="form-section">
        <h2>Job Information</h2>
        <div class="form-group">
          <label>Job Title*</label>
          <input type="text" name="title" placeholder="e.g. Senior Software Engineer" 
                 value="<?php echo htmlspecialchars($form_data['title']); ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Job Type*</label>
            <select name="job_type" required>
              <option value="">Select job type</option>
              <option value="fulltime" <?php echo $form_data['job_type'] === 'fulltime' ? 'selected' : ''; ?>>Full-time</option>
              <option value="parttime" <?php echo $form_data['job_type'] === 'parttime' ? 'selected' : ''; ?>>Part-time</option>
              <option value="contract" <?php echo $form_data['job_type'] === 'contract' ? 'selected' : ''; ?>>Contract</option>
              <option value="internship" <?php echo $form_data['job_type'] === 'internship' ? 'selected' : ''; ?>>Internship</option>
              <option value="temporary" <?php echo $form_data['job_type'] === 'temporary' ? 'selected' : ''; ?>>Temporary</option>
            </select>
          </div>
          <div class="form-group">
            <label>Location*</label>
            <select name="location" required>
              <option value="">Select location</option>
              <option value="colombo" <?php echo $form_data['location'] === 'colombo' ? 'selected' : ''; ?>>Colombo</option>
              <option value="kandy" <?php echo $form_data['location'] === 'kandy' ? 'selected' : ''; ?>>Kandy</option>
              <option value="galle" <?php echo $form_data['location'] === 'galle' ? 'selected' : ''; ?>>Galle</option>
              <option value="jaffna" <?php echo $form_data['location'] === 'jaffna' ? 'selected' : ''; ?>>Jaffna</option>
              <option value="remote" <?php echo $form_data['location'] === 'remote' ? 'selected' : ''; ?>>Remote</option>
              <option value="hybrid" <?php echo $form_data['location'] === 'hybrid' ? 'selected' : ''; ?>>Hybrid</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" placeholder="e.g. Engineering, Marketing"
                   value="<?php echo htmlspecialchars($form_data['department']); ?>">
          </div>
          <div class="form-group">
            <label>Experience Level</label>
            <select name="experience_level">
              <option value="">Select experience level</option>
              <option value="intern" <?php echo $form_data['experience_level'] === 'intern' ? 'selected' : ''; ?>>Intern</option>
              <option value="entry" <?php echo $form_data['experience_level'] === 'entry' ? 'selected' : ''; ?>>Entry Level</option>
              <option value="mid" <?php echo $form_data['experience_level'] === 'mid' ? 'selected' : ''; ?>>Mid Level</option>
              <option value="senior" <?php echo $form_data['experience_level'] === 'senior' ? 'selected' : ''; ?>>Senior Level</option>
              <option value="director" <?php echo $form_data['experience_level'] === 'director' ? 'selected' : ''; ?>>Director</option>
              <option value="executive" <?php echo $form_data['experience_level'] === 'executive' ? 'selected' : ''; ?>>Executive</option>
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
                     value="<?php echo htmlspecialchars($form_data['salary_min']); ?>">
              <span>to</span>
              <input type="number" name="salary_max" placeholder="Max" step="0.01" min="0"
                     value="<?php echo htmlspecialchars($form_data['salary_max']); ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Salary Period</label>
            <select name="salary_period">
              <option value="yearly" <?php echo $form_data['salary_period'] === 'yearly' ? 'selected' : ''; ?>>Per Year</option>
              <option value="monthly" <?php echo $form_data['salary_period'] === 'monthly' ? 'selected' : ''; ?>>Per Month</option>
              <option value="weekly" <?php echo $form_data['salary_period'] === 'weekly' ? 'selected' : ''; ?>>Per Week</option>
              <option value="hourly" <?php echo $form_data['salary_period'] === 'hourly' ? 'selected' : ''; ?>>Per Hour</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Benefits</label>
          <?php foreach ($common_benefits as $benefit): ?>
            <div class="checkbox-group">
              <input type="checkbox" name="benefits[]" id="<?php echo strtolower(str_replace(' ', '-', $benefit)); ?>" 
                     value="<?php echo htmlspecialchars($benefit); ?>"
                     <?php echo in_array($benefit, $benefits) ? 'checked' : ''; ?>>
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
            echo htmlspecialchars($form_data['summary']); 
          ?></textarea>
        </div>
        <div class="form-group">
          <label>Responsibilities*</label>
          <textarea name="responsibilities" placeholder="List the key responsibilities of the position" required><?php 
            echo htmlspecialchars($form_data['responsibilities']); 
          ?></textarea>
        </div>
        <div class="form-group">
          <label>Qualifications*</label>
          <textarea name="qualifications" placeholder="List the required qualifications and skills" required><?php 
            echo htmlspecialchars($form_data['qualifications']); 
          ?></textarea>
        </div>
        <div class="form-group">
          <label>Nice-to-Have Skills</label>
          <textarea name="preferred_skills" placeholder="List any additional skills that would be beneficial"><?php 
            echo htmlspecialchars($form_data['preferred_skills']); 
          ?></textarea>
        </div>
      </div>
      
      <div class="form-section">
        <h2>Application Settings</h2>
        <div class="form-group">
          <label>Application Deadline</label>
          <input type="date" name="deadline" 
                 value="<?php echo htmlspecialchars($form_data['deadline']); ?>">
        </div>
        <div class="form-group">
          <label>Application Questions</label>
          <div class="checkbox-group">
            <input type="checkbox" name="require_cover_letter" id="cover-letter" 
                   <?php echo $form_data['require_cover_letter'] ? 'checked' : ''; ?>>
            <label for="cover-letter">Require Cover Letter</label>
          </div>
          <div class="checkbox-group">
            <input type="checkbox" name="require_portfolio" id="portfolio"
                   <?php echo $form_data['require_portfolio'] ? 'checked' : ''; ?>>
            <label for="portfolio">Require Portfolio/Work Samples</label>
          </div>
          <div class="checkbox-group">
            <input type="checkbox" name="require_references" id="references"
                   <?php echo $form_data['require_references'] ? 'checked' : ''; ?>>
            <label for="references">Require References</label>
          </div>
        </div>
        <div class="form-group">
          <label>Custom Questions</label>
          <textarea name="custom_questions" placeholder="Add any additional questions you want applicants to answer"><?php 
            echo htmlspecialchars($form_data['custom_questions']); 
          ?></textarea>
        </div>
      </div>
      
      <div class="form-actions">
        <button type="submit" class="btn-primary">Update Job</button>
        <a href="dashboard.php" class="btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>