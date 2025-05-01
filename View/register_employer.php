  <?php
  session_start();
  require_once '../includes/db_connection.php';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // Basic account info
      $username = trim($_POST['username']);
      $email = trim($_POST['email']);
      $password = trim($_POST['password']);
      $confirm_password = trim($_POST['confirm_password']);
      $user_type = 'employer';

      // Company info
      $company_name = trim($_POST['company_name']);
      $website = trim($_POST['website']);
      $industry = trim($_POST['industry']);
      $company_size = trim($_POST['company_size']);
      $description = trim($_POST['description']);
      $culture_description = trim($_POST['culture_description']);
      $contact_email = trim($_POST['contact_email']);
      $contact_phone = trim($_POST['contact_phone']);
      $address = trim($_POST['address']);
      $benefits = $_POST['benefits'] ?? [];

      // Validate inputs
      $errors = [];

      if (empty($username)) $errors[] = "Username is required";
      if (empty($email)) $errors[] = "Email is required";
      if (empty($password)) $errors[] = "Password is required";
      if ($password !== $confirm_password) $errors[] = "Passwords do not match";
      if (empty($company_name)) $errors[] = "Company name is required";
      if (empty($contact_email)) $errors[] = "Contact email is required";

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

              // Insert into employer_profiles table
              $stmt = $conn->prepare("INSERT INTO employer_profiles 
                  (user_id, company_name, website, industry, company_size, description, 
                  culture_description, contact_email, contact_phone, address) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
              $stmt->bind_param("isssssssss", 
                  $user_id, $company_name, $website, $industry, $company_size, $description,
                  $culture_description, $contact_email, $contact_phone, $address);
              $stmt->execute();

              // Insert benefits
              if (!empty($benefits)) {
                  $stmt = $conn->prepare("INSERT INTO employer_benefits (user_id, benefit) VALUES (?, ?)");
                  foreach ($benefits as $benefit) {
                      $stmt->bind_param("is", $user_id, $benefit);
                      $stmt->execute();
                  }
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
          header("Location: register_employer.php");
          exit();
      }
  }
  ?>
  <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employer Registration - JobMatch</title>
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
    
    .benefits-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1rem;
    }
    
    .benefit-item {
      display: flex;
      align-items: center;
      padding: 0.5rem;
      border-radius: 6px;
      transition: all 0.2s ease;
    }
    
    .benefit-item:hover {
      background-color: rgba(67, 97, 238, 0.05);
    }
    
    .benefit-item input {
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
      
      .benefits-list {
        grid-template-columns: 1fr 1fr;
      }
    }
    
    @media (max-width: 576px) {
      .container {
        padding: 0 1rem;
      }
      
      .form-section {
        padding: 1.5rem;
      }
      
      .benefits-list {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Employer Registration</h1>
    
    <div class="progress-steps">
      <div class="step active">
        <div class="step-number">1</div>
        <div class="step-text">Account Info</div>
      </div>
      <div class="step">
        <div class="step-number">2</div>
        <div class="step-text">Company Info</div>
      </div>
      <div class="step">
        <div class="step-number">3</div>
        <div class="step-text">Benefits</div>
      </div>
      <div class="step">
        <div class="step-number">4</div>
        <div class="step-text">Contact</div>
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

    <form action="register_employer.php" method="POST">
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
        <h2>Company Information</h2>
        <div class="form-group">
          <label class="required">Company Name</label>
          <input type="text" name="company_name" required
                value="<?php echo htmlspecialchars($_SESSION['form_data']['company_name'] ?? ''); ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Website</label>
            <input type="text" name="website" placeholder="https://"
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['website'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>Industry</label>
            <select name="industry">
              <option value="">Select Industry</option>
              <option value="Information Technology" <?php echo ($_SESSION['form_data']['industry'] ?? '') === 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
              <option value="Finance" <?php echo ($_SESSION['form_data']['industry'] ?? '') === 'Finance' ? 'selected' : ''; ?>>Finance</option>
              <option value="Healthcare" <?php echo ($_SESSION['form_data']['industry'] ?? '') === 'Healthcare' ? 'selected' : ''; ?>>Healthcare</option>
              <option value="Education" <?php echo ($_SESSION['form_data']['industry'] ?? '') === 'Education' ? 'selected' : ''; ?>>Education</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Company Size</label>
          <select name="company_size">
            <option value="">Select Company Size</option>
            <option value="1-10 employees" <?php echo ($_SESSION['form_data']['company_size'] ?? '') === '1-10 employees' ? 'selected' : ''; ?>>1-10 employees</option>
            <option value="11-50 employees" <?php echo ($_SESSION['form_data']['company_size'] ?? '') === '11-50 employees' ? 'selected' : ''; ?>>11-50 employees</option>
            <option value="51-200 employees" <?php echo ($_SESSION['form_data']['company_size'] ?? '') === '51-200 employees' ? 'selected' : ''; ?>>51-200 employees</option>
            <option value="201-500 employees" <?php echo ($_SESSION['form_data']['company_size'] ?? '') === '201-500 employees' ? 'selected' : ''; ?>>201-500 employees</option>
            <option value="500+ employees" <?php echo ($_SESSION['form_data']['company_size'] ?? '') === '500+ employees' ? 'selected' : ''; ?>>500+ employees</option>
          </select>
        </div>
        <div class="form-group">
          <label class="required">Company Description</label>
          <textarea name="description" required><?php 
              echo htmlspecialchars($_SESSION['form_data']['description'] ?? ''); 
          ?></textarea>
        </div>
        <div class="form-group">
          <label>Company Culture</label>
          <textarea name="culture_description" placeholder="Describe your company culture, values, and work environment"><?php 
              echo htmlspecialchars($_SESSION['form_data']['culture_description'] ?? ''); 
          ?></textarea>
        </div>
      </div>

      <div class="form-section">
        <h2>Employee Benefits</h2>
        <p>Select the benefits your company offers to attract top talent</p>
        <div class="benefits-list">
          <div class="benefit-item">
            <input type="checkbox" name="benefits[]" value="Health Insurance" id="health" 
                  <?php echo isset($_SESSION['form_data']['benefits']) && in_array('Health Insurance', $_SESSION['form_data']['benefits']) ? 'checked' : ''; ?>>
            <label for="health">Health Insurance</label>
          </div>
          <div class="benefit-item">
            <input type="checkbox" name="benefits[]" value="Dental Insurance" id="dental"
                  <?php echo isset($_SESSION['form_data']['benefits']) && in_array('Dental Insurance', $_SESSION['form_data']['benefits']) ? 'checked' : ''; ?>>
            <label for="dental">Dental Insurance</label>
          </div>
          <div class="benefit-item">
            <input type="checkbox" name="benefits[]" value="Retirement Plan" id="retirement"
                  <?php echo isset($_SESSION['form_data']['benefits']) && in_array('Retirement Plan', $_SESSION['form_data']['benefits']) ? 'checked' : ''; ?>>
            <label for="retirement">Retirement Plan</label>
          </div>
          <div class="benefit-item">
            <input type="checkbox" name="benefits[]" value="Paid Vacation" id="vacation"
                  <?php echo isset($_SESSION['form_data']['benefits']) && in_array('Paid Vacation', $_SESSION['form_data']['benefits']) ? 'checked' : ''; ?>>
            <label for="vacation">Paid Vacation</label>
          </div>
          <div class="benefit-item">
            <input type="checkbox" name="benefits[]" value="Flexible Schedule" id="flexible"
                  <?php echo isset($_SESSION['form_data']['benefits']) && in_array('Flexible Schedule', $_SESSION['form_data']['benefits']) ? 'checked' : ''; ?>>
            <label for="flexible">Flexible Schedule</label>
          </div>
          <div class="benefit-item">
            <input type="checkbox" name="benefits[]" value="Remote Work Options" id="remote"
                  <?php echo isset($_SESSION['form_data']['benefits']) && in_array('Remote Work Options', $_SESSION['form_data']['benefits']) ? 'checked' : ''; ?>>
            <label for="remote">Remote Work Options</label>
          </div>
          <div class="benefit-item">
            <input type="checkbox" name="benefits[]" value="Training Budget" id="training"
                  <?php echo isset($_SESSION['form_data']['benefits']) && in_array('Training Budget', $_SESSION['form_data']['benefits']) ? 'checked' : ''; ?>>
            <label for="training">Training Budget</label>
          </div>
          <div class="benefit-item">
            <input type="checkbox" name="benefits[]" value="Performance Bonuses" id="bonuses"
                  <?php echo isset($_SESSION['form_data']['benefits']) && in_array('Performance Bonuses', $_SESSION['form_data']['benefits']) ? 'checked' : ''; ?>>
            <label for="bonuses">Performance Bonuses</label>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h2>Contact Information</h2>
        <div class="form-row">
          <div class="form-group">
            <label class="required">Contact Email</label>
            <input type="email" name="contact_email" required
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['contact_email'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>Contact Phone</label>
            <input type="text" name="contact_phone" placeholder="+1 (123) 456-7890"
                  value="<?php echo htmlspecialchars($_SESSION['form_data']['contact_phone'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Company Address</label>
          <input type="text" name="address" placeholder="Street, City, Country"
                value="<?php echo htmlspecialchars($_SESSION['form_data']['address'] ?? ''); ?>">
        </div>
      </div>

      <button type="submit" class="btn-primary">Complete Registration</button>
    </form>
  </div>
  <?php unset($_SESSION['form_data']); ?>
</body>
</html>