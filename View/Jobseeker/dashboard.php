<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Search - JobMatch Recruitment System</title>
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
    .search-section {
      background-color: white;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    .search-header {
      margin-bottom: 1.5rem;
    }
    .search-header h1 {
      margin-top: 0;
      color: #2c3e50;
    }
    .search-form {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .search-form input, .search-form select {
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
    }
    .search-form button {
      background-color: #3498db;
      color: white;
      border: none;
      padding: 0.75rem;
      font-size: 1rem;
      border-radius: 4px;
      cursor: pointer;
    }
    .filter-section {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    .filter-tag {
      background-color: #ecf0f1;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
    }
    .filter-tag button {
      background: none;
      border: none;
      margin-left: 0.5rem;
      cursor: pointer;
      color: #7f8c8d;
    }
    .search-results {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }
    .job-card {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      padding: 1.5rem;
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 1.5rem;
    }
    .job-info h2 {
      margin-top: 0;
      margin-bottom: 0.5rem;
      color: #3498db;
    }
    .job-info .company {
      color: #7f8c8d;
      margin-bottom: 0.75rem;
      font-size: 1.1rem;
    }
    .job-info .description {
      margin-bottom: 1rem;
      line-height: 1.5;
    }
    .job-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 1rem;
      color: #7f8c8d;
    }
    .job-meta span {
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }
    .job-actions {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }
    .job-actions button {
      padding: 0.5rem 1rem;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.9rem;
      white-space: nowrap;
    }
    .apply-btn {
      background-color: #3498db;
      color: white;
      border: none;
    }
    .save-btn {
      background-color: white;
      color: #3498db;
      border: 1px solid #3498db;
    }
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 2rem;
      gap: 0.5rem;
    }
    .pagination button {
      padding: 0.5rem 1rem;
      border: 1px solid #ddd;
      background-color: white;
      border-radius: 4px;
      cursor: pointer;
    }
    .pagination button.active {
      background-color: #3498db;
      color: white;
      border-color: #3498db;
    }
    .map-view {
      margin-top: 2rem;
      background-color: #eee;
      height: 300px;
      border-radius: 8px;
      display: flex;
      justify-content: center;
      align-items: center;
      color: #7f8c8d;
    }
  </style>
</head>
<body>
  <div class="navbar">
    <div class="logo">JobMatch</div>
    <div class="nav-links">
    <a href="dashboard.php">Home</a>
      <a href="userprofile.php">Profile</a>
      <a href="../helppage.php">Help</a>
      <a href="../logout.php">Logout</a>
    </div>
  </div>
  
  <div class="container">
    <div class="search-section">
      <div class="search-header">
        <h1>Find Your Dream Job</h1>
        <p>Search through thousands of job listings to find the perfect match for your skills and experience.</p>
      </div>
      
      <form class="search-form">
        <input type="text" placeholder="Job title, keywords, or company">
        <select>
          <option value="">Location</option>
          <option value="colombo">Colombo</option>
          <option value="kandy">Kandy</option>
          <option value="galle">Galle</option>
          <option value="jaffna">Jaffna</option>
          <option value="remote">Remote</option>
        </select>
        <select>
          <option value="">Job Type</option>
          <option value="fulltime">Full-time</option>
          <option value="parttime">Part-time</option>
          <option value="contract">Contract</option>
          <option value="internship">Internship</option>
        </select>
        <button type="submit">Search</button>
      </form>
      
      
      <div class="search-results">
        <div class="job-card">
          <div class="job-info">
            <h2>Senior Software Engineer</h2>
            <div class="company">TechCorp International</div>
            <div class="description">We're looking for an experienced software engineer to lead our development team. You'll be responsible for architecting scalable solutions and mentoring junior developers.</div>
            <div class="job-meta">
              <span>📍 Colombo</span>
              <span>🕒 Full-time</span>
              <span>💰 $80k - $100k</span>
              <span>⏳ Posted 2 days ago</span>
            </div>
          </div>
          <div class="job-actions">
            <button class="apply-btn">Apply Now</button>
          </div>
        </div>
        
    </div>
  </div>
</body>
</html>
