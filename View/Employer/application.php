<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

if ($_SESSION['user_type'] !== 'employer') {
    header("Location: unauthorized.php");
    exit();
}

$employer_id = $_SESSION['user_id'];

// Get all applications for this employer's jobs
$query = "SELECT ja.*, j.title AS job_title, 
          js.first_name, js.last_name, js.professional_title, js.summary,
          js.phone, js.resume_path
          FROM job_applications ja
          JOIN jobs j ON ja.job_id = j.id
          JOIN jobseeker_profiles js ON ja.user_id = js.user_id
          WHERE j.employer_id = ?
          ORDER BY ja.application_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Applications - JobMatch Employer</title>
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
    
    .filter-bar {
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      padding: 1.5rem;
      margin-bottom: 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
      position: relative;
    }
    
    .filter-bar::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 10px;
      background: linear-gradient(90deg, var(--primary), var(--success));
      border-radius: 16px 16px 0 0;
    }
    
    .filter-group {
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }
    
    .filter-dropdown select {
      padding: 1rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      min-width: 200px;
      font-family: 'Poppins', sans-serif;
      background-color: var(--light);
      transition: all 0.3s ease;
    }
    
    .filter-dropdown select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    .search-box input {
      padding: 1rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      min-width: 300px;
      font-family: 'Poppins', sans-serif;
      background-color: var(--light);
      transition: all 0.3s ease;
    }
    
    .search-box input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    .application-list {
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      position: relative;
      overflow: hidden;
    }
    
    .application-list::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 10px;
      background: linear-gradient(90deg, var(--primary), var(--success));
      border-radius: 16px 16px 0 0;
    }
    
    .application-item {
      padding: 2rem;
      border-bottom: 1px solid var(--light-gray);
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 2rem;
    }
    
    .application-item:last-child {
      border-bottom: none;
    }
    
    .application-info h3 {
      margin: 0 0 0.5rem;
      font-weight: 600;
      color: var(--dark);
    }
    
    .application-job {
      color: var(--primary);
      margin-bottom: 0.75rem;
      font-weight: 500;
    }
    
    .application-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 1.5rem;
      color: var(--gray);
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }
    
    .application-summary {
      color: var(--dark);
      margin-top: 1rem;
    }
    
    .application-skills {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-top: 1rem;
    }
    
    .skill-tag {
      background-color: var(--light);
      padding: 0.3rem 0.8rem;
      border-radius: 12px;
      font-size: 0.8rem;
      color: var(--primary);
    }
    
    .application-actions {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      min-width: 200px;
    }
    
    .status {
      display: inline-block;
      padding: 0.3rem 0.8rem;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 500;
    }
    
    .status.applied {
      background-color: var(--primary);
      color: white;
    }
    
    .status.reviewed {
      background-color: var(--secondary);
      color: white;
    }
    
    .status.interview {
      background-color: var(--warning);
      color: white;
    }
    
    .status.hired {
      background-color: var(--success);
      color: white;
    }
    
    .status.rejected {
      background-color: var(--danger);
      color: white;
    }
    
    button, .btn-primary, .btn-secondary {
      font-family: 'Poppins', sans-serif;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      letter-spacing: 0.5px;
      border-radius: 8px;
      text-transform: uppercase;
      font-size: 0.9rem;
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
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .btn-secondary {
      background: transparent;
      color: var(--primary);
      border: 2px solid var(--primary);
      padding: 0.75rem 1.25rem;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }
    
    .btn-secondary:hover {
      background-color: rgba(67, 97, 238, 0.1);
    }
    
    .btn-danger {
      background: transparent;
      color: var(--danger);
      border: 2px solid var(--danger);
      padding: 0.75rem 1.25rem;
      border-radius: 8px;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      font-weight: 500;
      text-transform: uppercase;
      font-size: 0.9rem;
    }
    
    .btn-danger:hover {
      background-color: rgba(247, 37, 133, 0.1);
    }
    
    .status-select {
      padding: 0.75rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 0.9rem;
      width: 100%;
      font-family: 'Poppins', sans-serif;
      background-color: var(--light);
      transition: all 0.3s ease;
      margin-bottom: 0.5rem;
    }
    
    .status-select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 2rem;
      gap: 0.5rem;
    }
    
    .pagination button {
      padding: 0.75rem 1rem;
      border: 2px solid var(--light-gray);
      background-color: white;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .pagination button.active {
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      color: white;
      border-color: var(--primary);
    }
    
    .pagination button:hover:not(.active) {
      border-color: var(--primary);
      color: var(--primary);
    }
    
    .empty-state {
      padding: 3rem;
      text-align: center;
      color: var(--gray);
    }
    
    .empty-state h3 {
      margin-bottom: 1rem;
      font-weight: 600;
    }
    
    /* Responsive design */
    @media (max-width: 992px) {
      .application-item {
        grid-template-columns: 1fr;
      }
      
      .application-actions {
        flex-direction: row;
        flex-wrap: wrap;
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
      
      .filter-bar {
        flex-direction: column;
        align-items: stretch;
      }
      
      .filter-group {
        width: 100%;
      }
      
      .search-box input,
      .filter-dropdown select {
        width: 100%;
        min-width: 0;
      }
    }
    
    @media (max-width: 576px) {
      .container {
        padding: 1rem;
      }
      
      .application-item,
      .filter-bar {
        padding: 1.5rem;
      }
      
      .nav-links {
        flex-wrap: wrap;
      }
      
      .nav-links a {
        margin-bottom: 0.5rem;
      }
      
      .application-meta {
        flex-direction: column;
        gap: 0.5rem;
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
      <h1>Applications</h1>
      <p>Review and manage all applications for your job postings</p>
    </div>
    
    <div class="application-list">
      <?php if (empty($applications)): ?>
        <div class="empty-state">
          <h3>No Applications Yet</h3>
          <p>When candidates apply to your job postings, they'll appear here.</p>
        </div>
      <?php else: ?>
        <?php foreach ($applications as $app): ?>
          <div class="application-item">
            <div class="application-info">
              <h3><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h3>
              <div class="application-job"><?php echo htmlspecialchars($app['job_title']); ?></div>
              <div class="application-meta">
                <span>Applied: <?php echo date('M j, Y', strtotime($app['application_date'])); ?></span>
                <span>Status: <span class="status <?php echo htmlspecialchars($app['status']); ?>"><?php echo ucfirst(htmlspecialchars($app['status'])); ?></span></span>
                <?php if ($app['phone']): ?>
                  <span>Phone: <?php echo htmlspecialchars($app['phone']); ?></span>
                <?php endif; ?>
              </div>
              <?php if ($app['professional_title']): ?>
                <p><strong><?php echo htmlspecialchars($app['professional_title']); ?></strong></p>
              <?php endif; ?>
              <?php if ($app['summary']): ?>
                <p class="application-summary"><?php echo htmlspecialchars($app['summary']); ?></p>
              <?php endif; ?>
            </div>

            <div class="application-actions">
              <a href="view_profile.php?user_id=<?php echo $app['user_id']; ?>" class="btn-primary">View Profile</a>
              <form action="update_status.php" method="POST" style="width: 100%;">
                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                <select name="status" class="status-select" onchange="this.form.submit()">
                  <option value="applied" <?php echo $app['status'] === 'applied' ? 'selected' : ''; ?>>Applied</option>
                  <option value="reviewed" <?php echo $app['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                  <option value="interview" <?php echo $app['status'] === 'interview' ? 'selected' : ''; ?>>Interview</option>
                  <option value="hired" <?php echo $app['status'] === 'hired' ? 'selected' : ''; ?>>Hired</option>
                  <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
              </form>
              <?php if ($app['resume_path']): ?>
                <a href="<?php echo htmlspecialchars($app['resume_path']); ?>" class="btn-secondary" download>Download Resume</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    
    <!-- Pagination would be added here if needed -->
    <!-- <div class="pagination">
      <button>Previous</button>
      <button class="active">1</button>
      <button>2</button>
      <button>3</button>
      <button>Next</button>
    </div> -->
  </div>

  <script>
    // Simple filter functionality
    document.getElementById('job-filter').addEventListener('change', function() {
      // Filter logic would go here
      console.log('Job filter changed:', this.value);
    });
    
    document.getElementById('status-filter').addEventListener('change', function() {
      // Filter logic would go here
      console.log('Status filter changed:', this.value);
    });
    
    document.querySelector('.search-box input').addEventListener('input', function() {
      // Search logic would go here
      console.log('Search input:', this.value);
    });
  </script>
</body>
</html>