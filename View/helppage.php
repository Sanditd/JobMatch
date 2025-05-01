<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Help - JobMatch Recruitment System</title>
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
    .help-header {
      text-align: center;
      margin-bottom: 3rem;
    }
    .help-header h1 {
      margin-top: 0;
      color: #2c3e50;
    }
    .help-header p {
      color: #7f8c8d;
      max-width: 700px;
      margin: 0 auto;
    }
    .help-sections {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
    }
    .help-card {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      padding: 1.5rem;
      transition: transform 0.3s ease;
    }
    .help-card:hover {
      transform: translateY(-5px);
    }
    .help-card h2 {
      color: #3498db;
      margin-top: 0;
    }
    .help-card ul {
      padding-left: 1.5rem;
    }
    .help-card li {
      margin-bottom: 0.5rem;
    }
    .faq-section {
      margin-top: 3rem;
    }
    .faq-section h2 {
      color: #2c3e50;
      text-align: center;
      margin-bottom: 2rem;
    }
    .faq-item {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      padding: 1.5rem;
      margin-bottom: 1rem;
    }
    .faq-question {
      font-weight: 500;
      color: #2c3e50;
      margin-top: 0;
      margin-bottom: 1rem;
    }
    .faq-answer {
      color: #7f8c8d;
      line-height: 1.6;
    }
    .contact-section {
      margin-top: 3rem;
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      padding: 2rem;
      text-align: center;
    }
    .contact-section h2 {
      color: #2c3e50;
      margin-top: 0;
    }
    .contact-info {
      display: flex;
      justify-content: center;
      gap: 2rem;
      margin-top: 1.5rem;
    }
    .contact-method {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .contact-icon {
      width: 60px;
      height: 60px;
      background-color: #ecf0f1;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 1rem;
      color: #3498db;
      font-size: 1.5rem;
    }
  </style>
</head>
<body>
  <div class="navbar">
    <div class="logo">JobMatch</div>
    <div class="nav-links">
      <a href="Jobseeker/jobseekerhome.php">Home</a>
      <a href="Jobseeker/jobsearch.php">Search Jobs</a>
      <a href="Jobseeker/myapplications.php">My Applications</a>
      <a href="Jobseeker/userprofile.php">Profile</a>
      <a href="helppage.php">Help</a>
      <a href="lo">Logout</a>
    </div>
  </div>
  
  <div class="container">
    <div class="help-header">
      <h1>How can we help you?</h1>
      <p>Find answers to common questions, learn how to use JobMatch effectively, or get in touch with our support team.</p>
    </div>
    
    <div class="help-sections">
      <div class="help-card">
        <h2>Getting Started</h2>
        <ul>
          <li><a href="#">Creating an account</a></li>
          <li><a href="#">Setting up your profile</a></li>
          <li><a href="#">Uploading your resume</a></li>
          <li><a href="#">Privacy settings</a></li>
        </ul>
      </div>
      
      <div class="help-card">
        <h2>Job Search</h2>
        <ul>
          <li><a href="#">Finding jobs</a></li>
          <li><a href="#">Using search filters</a></li>
          <li><a href="#">Saving job searches</a></li>
          <li><a href="#">Setting up job alerts</a></li>
        </ul>
      </div>
      
      <div class="help-card">
        <h2>Applications</h2>
        <ul>
          <li><a href="#">Applying for jobs</a></li>
          <li><a href="#">Tracking your applications</a></li>
          <li><a href="#">Withdrawing applications</a></li>
          <li><a href="#">Interview preparation</a></li>
        </ul>
      </div>
      
      <div class="help-card">
        <h2>Video Tutorials</h2>
        <ul>
          <li><a href="#">Profile setup walkthrough</a></li>
          <li><a href="#">Job search tutorial</a></li>
          <li><a href="#">Application process</a></li>
          <li><a href="#">Using the dashboard</a></li>
        </ul>
      </div>
      
      <div class="help-card">
        <h2>Account Management</h2>
        <ul>
          <li><a href="#">Updating your information</a></li>
          <li><a href="#">Changing your password</a></li>
          <li><a href="#">Deactivating your account</a></li>
          <li><a href="#">Privacy settings</a></li>
        </ul>
      </div>
      
      <div class="help-card">
        <h2>Troubleshooting</h2>
        <ul>
          <li><a href="#">Login issues</a></li>
          <li><a href="#">Application errors</a></li>
          <li><a href="#">Browser compatibility</a></li>
          <li><a href="#">Report a problem</a></li>
        </ul>
      </div>
    </div>
    
    <div class="faq-section">
      <h2>Frequently Asked Questions</h2>
      
      <div class="faq-item">
        <h3 class="faq-question">How do I apply for a job?</h3>
        <div class="faq-answer">
          <p>To apply for a job, first make sure you're logged in to your JobMatch account. Navigate to the job listing you're interested in and click the "Apply Now" button. You'll be able to review your application before submitting it, and you can track its status in your "My Applications" section.</p>
        </div>
      </div>
      
      <div class="faq-item">
        <h3 class="faq-question">Can I edit my application after submitting it?</h3>
        <div class="faq-answer">
          <p>Once an application is submitted, you cannot edit it directly. However, you can withdraw your application and submit a new one if the job posting is still open. To withdraw an application, go to your "My Applications" page and click the "Withdraw" button next to the relevant application.</p>
        </div>
      </div>
      
      <div class="faq-item">
        <h3 class="faq-question">How do employers contact me?</h3>
        <div class="faq-answer">
          <p>Employers can contact you through the JobMatch messaging system if they're interested in your application. You'll receive notifications when you receive new messages. Make sure your contact information in your profile is up to date so employers can reach you if needed.</p>
        </div>
      </div>
      
      <div class="faq-item">
        <h3 class="faq-question">Is my personal information secure?</h3>
        <div class="faq-answer">
          <p>Yes, we take your privacy and security very seriously. Your personal information is protected by industry-standard security measures. You can control what information is visible to employers through your privacy settings in your profile.</p>
        </div>
      </div>
    </div>
    
    <div class="contact-section">
      <h2>Still need help?</h2>
      <p>Our support team is here to assist you with any questions or issues you may have.</p>
      
      <div class="contact-info">
        <div class="contact-method">
          <div class="contact-icon">📧</div>
          <strong>Email Us</strong>
          <div>support@jobmatch.com</div>
        </div>
        
        <div class="contact-method">
          <div class="contact-icon">📞</div>
          <strong>Call Us</strong>
          <div>+94 11 123 4567</div>
        </div>
        
        <div class="contact-method">
          <div class="contact-icon">💬</div>
          <strong>Live Chat</strong>
          <div>Available 9AM-5PM</div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>