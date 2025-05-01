<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$query = "SELECT j.id, j.title, ep.company_name, j.created_at, j.status,
          (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as applications
          FROM jobs j
          JOIN employer_profiles ep ON j.employer_id = ep.user_id
          WHERE 1=1";

$params = [];

if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR ep.company_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status)) {
    $query .= " AND j.status = ?";
    $params[] = $status;
}

// Count total jobs for pagination
$count_query = "SELECT COUNT(*) FROM (
    SELECT j.id
    FROM jobs j
    JOIN employer_profiles ep ON j.employer_id = ep.user_id
    WHERE 1=1";

if (!empty($search)) {
    $count_query .= " AND (j.title LIKE ? OR ep.company_name LIKE ?)";
}

if (!empty($status)) {
    $count_query .= " AND j.status = ?";
}

$count_query .= ") as total";

// Prepare and execute the count query
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params); // Bind the parameters dynamically
}
$stmt->execute();
$result = $stmt->get_result();
$total_jobs = $result->fetch_row()[0]; // Get the count from the first column
$total_pages = ceil($total_jobs / $per_page);

// Add pagination to main query
$query .= " ORDER BY j.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Prepare and execute the main query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params) - 2) . 'ii', ...$params); // Bind the parameters dynamically, excluding the last two for integer values
}
$stmt->execute();
$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Listings - JobMatch Admin</title>
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
      display: flex;
    }
    
    .sidebar {
      width: 260px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      height: 100vh;
      position: fixed;
      padding-top: 2rem;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    
    .sidebar .logo {
      font-size: 1.8rem;
      font-weight: 700;
      padding: 0 1.5rem 2rem;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      margin-bottom: 1.5rem;
      position: relative;
    }
    
    .sidebar .logo:after {
      content: '';
      position: absolute;
      bottom: 1.5rem;
      left: 1.5rem;
      width: 60px;
      height: 4px;
      background-color: var(--success);
      border-radius: 2px;
    }
    
    .sidebar-menu {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .sidebar-menu li {
      padding: 0.85rem 1.5rem;
      cursor: pointer;
      transition: all 0.3s ease;
      border-left: 4px solid transparent;
    }
    
    .sidebar-menu li:hover {
      background-color: rgba(255,255,255,0.1);
      border-left: 4px solid var(--success);
    }
    
    .sidebar-menu li.active {
      background-color: rgba(255,255,255,0.15);
      border-left: 4px solid var(--success);
    }
    
    .sidebar-menu li a {
      color: white;
      text-decoration: none;
      display: block;
      font-weight: 500;
      transition: all 0.2s ease;
    }
    
    .main-content {
      margin-left: 260px;
      width: calc(100% - 260px);
      padding: 2rem;
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      background-color: white;
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .header h1 {
      margin: 0;
      color: var(--dark);
      font-size: 1.8rem;
      font-weight: 600;
      position: relative;
    }
    
    .header h1:after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 60px;
      height: 4px;
      background-color: var(--success);
      border-radius: 2px;
    }
    
    .header .user-info {
      display: flex;
      align-items: center;
    }
    
    .avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 1rem;
      font-weight: 600;
      font-size: 1.1rem;
      box-shadow: 0 3px 8px rgba(67, 97, 238, 0.3);
    }
    
    .section {
      background-color: white;
      padding: 1.8rem;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      margin-bottom: 2rem;
      position: relative;
    }
    
    .section:before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--primary), var(--success));
      border-radius: 12px 12px 0 0;
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.8rem;
    }
    
    .section-title {
      margin: 0;
      color: var(--dark);
      font-size: 1.4rem;
      font-weight: 600;
    }
    
    .filter-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.8rem;
      gap: 1rem;
    }
    
    .search-box {
      flex-grow: 1;
    }
    
    .search-box input {
      width: 100%;
      padding: 0.9rem 1.2rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s ease;
    }
    
    .search-box input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    .filter-dropdown select {
      padding: 0.9rem 1.2rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      font-family: 'Poppins', sans-serif;
      background-color: white;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    
    .filter-dropdown select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    .btn {
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      padding: 0.9rem 1.5rem;
      font-size: 1rem;
      font-weight: 500;
      border-radius: 8px;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(67, 97, 238, 0.25);
    }
    
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(67, 97, 238, 0.35);
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    table th, table td {
      text-align: left;
      padding: 1rem;
      border-bottom: 1px solid var(--light-gray);
    }
    
    table th {
      color: var(--gray);
      font-weight: 500;
      font-size: 0.95rem;
    }
    
    table tbody tr:hover {
      background-color: rgba(67, 97, 238, 0.03);
    }
    
    .status {
      display: inline-block;
      padding: 0.4rem 1rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
    }
    
    .status.published {
      background-color: rgba(76, 201, 240, 0.15);
      color: var(--success);
    }
    
    .status.closed {
      background-color: rgba(247, 37, 133, 0.15);
      color: var(--danger);
    }
    
    .status.draft {
      background-color: rgba(248, 150, 30, 0.15);
      color: var(--warning);
    }
    
    .action-btn {
      background: none;
      border: none;
      color: var(--primary);
      cursor: pointer;
      font-weight: 500;
      font-family: 'Poppins', sans-serif;
      transition: all 0.2s ease;
    }
    
    .action-btn:hover {
      color: var(--secondary);
      text-decoration: underline;
    }
    
    .action-btn.delete {
      color: var(--danger);
    }
    
    .action-btn.delete:hover {
      color: #d90368;
    }
    
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 2rem;
      gap: 0.5rem;
    }
    
    .pagination a {
      padding: 0.6rem 1rem;
      border: 2px solid var(--light-gray);
      background-color: white;
      border-radius: 8px;
      cursor: pointer;
      color: var(--dark);
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .pagination a:hover {
      border-color: var(--primary);
      color: var(--primary);
    }
    
    .pagination a.active {
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      color: white;
      border-color: var(--primary);
    }
    
    /* Responsive design */
    @media (max-width: 992px) {
      .sidebar {
        width: 80px;
        overflow: hidden;
      }
      
      .sidebar .logo {
        font-size: 1.3rem;
        padding: 1rem 0.5rem;
        text-align: center;
      }
      
      .sidebar .logo:after {
        display: none;
      }
      
      .sidebar-menu li {
        padding: 0.85rem 0;
        text-align: center;
      }
      
      .sidebar-menu li a {
        font-size: 0;
      }
      
      .sidebar-menu li a:before {
        content: '•';
        font-size: 1.5rem;
      }
      
      .main-content {
        margin-left: 80px;
        width: calc(100% - 80px);
      }
    }
    
    @media (max-width: 768px) {
      .filter-bar {
        flex-direction: column;
      }
      
      .search-box {
        width: 100%;
        margin-bottom: 1rem;
      }
      
      .filter-dropdown {
        width: 100%;
      }
      
      .header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .header .user-info {
        margin-top: 1rem;
      }
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="logo">JobMatch</div>
    <ul class="sidebar-menu">
      <li><a href="admindashboard.php">Dashboard</a></li>
      <li><a href="usermanagement.php">User Management</a></li>
      <li class="active"><a href="joblisting.php">Job Listings</a></li>
      <li><a href="../logout.php">Logout</a></li>
    </ul>
  </div>
  
  <div class="main-content">
    <div class="header">
      <h1>Job Listings</h1>
      <div class="user-info">
        <div class="avatar">A</div>
        <div>Admin User</div>
      </div>
    </div>
    
    <div class="section">
      <div class="section-header">
        <h2 class="section-title">All Job Listings</h2>
      </div>
      
      <div class="filter-bar">
        <div class="search-box">
          <form method="GET" action="joblisting.php">
            <input type="text" name="search" placeholder="Search jobs or companies..." value="<?php echo htmlspecialchars($search); ?>">
          </form>
        </div>
        <div class="filter-dropdown">
          <select onchange="window.location.href='joblisting.php?status='+this.value<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
            <option value="">All Statuses</option>
            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
            <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
            <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
          </select>
        </div>
      </div>
      
      <table>
        <thead>
          <tr>
            <th>Job ID</th>
            <th>Job Title</th>
            <th>Company</th>
            <th>Posted Date</th>
            <th>Status</th>
            <th>Applications</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(count($jobs) > 0): ?>
            <?php foreach ($jobs as $job): ?>
            <tr>
              <td>J-<?php echo $job['id']; ?></td>
              <td><?php echo htmlspecialchars($job['title']); ?></td>
              <td><?php echo htmlspecialchars($job['company_name']); ?></td>
              <td><?php echo date('M j, Y', strtotime($job['created_at'])); ?></td>
              <td>
                <span class="status <?php echo $job['status']; ?>">
                  <?php echo ucfirst(htmlspecialchars($job['status'])); ?>
                </span>
              </td>
              <td><?php echo $job['applications']; ?></td>
              <td>
                <a href="view_job.php?id=<?php echo $job['id']; ?>" class="action-btn">View</a>
                <button class="action-btn delete" onclick="deleteJob(<?php echo $job['id']; ?>)">Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 2rem;">No job listings found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
      
      <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?<?php echo http_build_query(['page' => $page - 1, 'search' => $search, 'status' => $status]); ?>">&laquo; Previous</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?<?php echo http_build_query(['page' => $i, 'search' => $search, 'status' => $status]); ?>" <?php echo $i === $page ? 'class="active"' : ''; ?>>
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
          <a href="?<?php echo http_build_query(['page' => $page + 1, 'search' => $search, 'status' => $status]); ?>">Next &raquo;</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function deleteJob(jobId) {
      if (confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
        window.location.href = 'delete_job.php?id=' + jobId;
      }
    }
  </script>
</body>
</html>