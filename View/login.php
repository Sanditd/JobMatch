<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - JobMatch Recruitment System</title>
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
      display: flex;
      min-height: 100vh;
    }
    
    .left-panel {
      flex: 1;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 4rem;
      color: white;
      position: relative;
      overflow: hidden;
    }
    
    .left-panel::before {
      content: '';
      position: absolute;
      top: -50px;
      right: -50px;
      width: 200px;
      height: 200px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
    }
    
    .left-panel::after {
      content: '';
      position: absolute;
      bottom: -80px;
      left: -80px;
      width: 300px;
      height: 300px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
    }
    
    .right-panel {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
      background-color: white;
    }
    
    .login-form {
      width: 100%;
      max-width: 450px;
      background-color: white;
      padding: 3rem;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      position: relative;
      z-index: 1;
    }
    
    .login-form::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 10px;
      background: linear-gradient(90deg, var(--primary), var(--success));
      border-radius: 16px 16px 0 0;
    }
    
    h1 {
      font-size: 2.8rem;
      margin-bottom: 1.5rem;
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
      margin-bottom: 2rem;
      color: var(--dark);
      font-size: 1.8rem;
      font-weight: 600;
    }
    
    p {
      margin-bottom: 1.5rem;
      font-size: 1.1rem;
      line-height: 1.6;
      opacity: 0.9;
    }
    
    .highlight {
      font-weight: 500;
      color: var(--success);
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
    
    input {
      width: 100%;
      padding: 1rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background-color: var(--light);
    }
    
    input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    input::placeholder {
      color: var(--gray);
      opacity: 0.6;
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
      width: 100%;
      margin-top: 0.5rem;
      transition: all 0.3s ease;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }
    
    button:hover {
      background: linear-gradient(90deg, var(--primary-dark), var(--secondary));
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    button:active {
      transform: translateY(0);
    }
    
    .links {
      margin-top: 1.5rem;
      text-align: center;
      font-size: 0.9rem;
    }
    
    .links a {
      color: var(--primary);
      text-decoration: none;
      margin: 0 0.5rem;
      font-weight: 500;
      transition: all 0.2s ease;
    }
    
    .links a:hover {
      color: var(--secondary);
      text-decoration: underline;
    }
    
    .divider {
      display: flex;
      align-items: center;
      margin: 1.5rem 0;
      color: var(--gray);
      font-size: 0.9rem;
    }
    
    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background-color: var(--light-gray);
      margin: 0 0.5rem;
    }
    
    .user-types {
      display: flex;
      justify-content: space-between;
      margin-top: 2rem;
      gap: 1rem;
    }
    
    .user-type {
      text-align: center;
      padding: 1.5rem 1rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      width: 48%;
      transition: all 0.3s ease;
    }
    
    .user-type:hover {
      border-color: var(--primary);
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .user-type h3 {
      margin-bottom: 0.5rem;
      font-size: 1rem;
      color: var(--dark);
    }
    
    .user-type a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
      font-size: 0.9rem;
    }
    
    .user-type a:hover {
      text-decoration: underline;
    }
    
    .error-message {
      color: var(--danger);
      margin-bottom: 1.5rem;
      padding: 1rem;
      background-color: rgba(247, 37, 133, 0.1);
      border-radius: 8px;
      text-align: center;
      border-left: 4px solid var(--danger);
      font-weight: 500;
    }
    
    .success-message {
      color: #28a745;
      margin-bottom: 1.5rem;
      padding: 1rem;
      background-color: rgba(40, 167, 69, 0.1);
      border-radius: 8px;
      text-align: center;
      border-left: 4px solid #28a745;
      font-weight: 500;
    }
    
    /* Responsive design */
    @media (max-width: 992px) {
      .container {
        flex-direction: column;
      }
      
      .left-panel {
        padding: 2rem;
        text-align: center;
      }
      
      .left-panel::before,
      .left-panel::after {
        display: none;
      }
      
      h1::after {
        left: 50%;
        transform: translateX(-50%);
      }
      
      .right-panel {
        padding: 2rem 1rem;
      }
      
      .login-form {
        padding: 2rem;
      }
    }
    
    @media (max-width: 576px) {
      .user-types {
        flex-direction: column;
      }
      
      .user-type {
        width: 100%;
        margin-bottom: 1rem;
      }
      
      .user-type:last-child {
        margin-bottom: 0;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="left-panel">
      <h1>JobMatch</h1>
      <p>Welcome to the <span class="highlight">most innovative</span> job recruitment platform connecting talented professionals with outstanding opportunities.</p>
      <p>Our advanced matching algorithm helps you find your <span class="highlight">dream job</span> or the <span class="highlight">perfect candidate</span> faster than ever before.</p>
      <p><span class="highlight">10,000+</span> active jobs from <span class="highlight">5,000+</span> registered companies worldwide.</p>
    </div>
    <div class="right-panel">
      <div class="login-form">
        <h2>Welcome Back</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
          <div class="error-message">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
          </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
          <div class="success-message">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
          </div>
        <?php endif; ?>
        
        <form action="login_process.php" method="POST">
          <div class="form-group">
            <label for="username">Username or Email</label>
            <input type="text" id="username" name="username" placeholder="Enter your username or email" required
                   value="<?php echo isset($_SESSION['login_attempt']) ? htmlspecialchars($_SESSION['login_attempt']['username']) : ''; ?>">
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
          </div>
          <button type="submit">Sign In</button>
        </form>
        
        <div class="links">
          <a href="forgot_password.php">Forgot Password?</a>
        </div>
        
        <div class="divider">or continue with</div>
        
        <div class="user-types">
          <div class="user-type">
            <h3>Job Seeker</h3>
            <a href="register_jobseeker.php">Create Account</a>
          </div>
          <div class="user-type">
            <h3>Employer</h3>
            <a href="register_employer.php">Register Company</a>
          </div>
        </div>  
      </div>
    </div>
  </div>
</body>
</html>