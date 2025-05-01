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
$user_type = $_GET['user_type'] ?? '';

// Build query
$query = "SELECT u.id, u.username, u.email, u.user_type, u.created_at, u.last_login,
          CASE 
              WHEN u.user_type = 'jobseeker' THEN CONCAT(js.first_name, ' ', js.last_name)
              WHEN u.user_type = 'employer' THEN e.company_name
              ELSE 'Admin'
          END as full_name
          FROM users u
          LEFT JOIN jobseekers js ON u.id = js.user_id
          LEFT JOIN employers e ON u.id = e.user_id
          WHERE 1=1";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($user_type)) {
    $query .= " AND u.user_type = ?";
    $params[] = $user_type;
    $types .= 's';
}

// Count total users for pagination
$count_query = "SELECT COUNT(*) FROM (
    SELECT u.id
    FROM users u
    LEFT JOIN jobseekers js ON u.id = js.user_id
    LEFT JOIN employers e ON u.id = e.user_id
    WHERE 1=1";

if (!empty($search)) {
    $count_query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
}

if (!empty($user_type)) {
    $count_query .= " AND u.user_type = ?";
}

$count_query .= ") as total";

// Prepare and execute the count query
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params); // Bind parameters dynamically
}
$stmt->execute();
$result = $stmt->get_result();
$total_users = $result->fetch_row()[0]; // Get count from first column
$total_pages = ceil($total_users / $per_page);

// Add pagination to main query
$query .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Prepare and execute the main query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params) - 2) . 'ii', ...$params); // Bind parameters dynamically, excluding last two for integer values
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
?>
\end{code}

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management - JobMatch Recruitment System</title>
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
      width: 250px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      height: 100vh;
      position: fixed;
      padding-top: 2rem;
      box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
    }
    
    .sidebar .logo {
      font-size: 1.8rem;
      font-weight: 700;
      padding: 0 1.5rem 2rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 1.5rem;
      position: relative;
    }
    
    .sidebar .logo::after {
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
      padding: 0;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .sidebar-menu li a {
      color: white;
      text-decoration: none;
      display: block;
      padding: 0.85rem 1.5rem;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .sidebar-menu li:hover, .sidebar-menu li.active {
      background-color: rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-menu li.active {
      border-left: 4px solid var(--success);
    }
    
    .main-content {
      margin-left: 250px;
      width: calc(100% - 250px);
      padding: 2rem;
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--light-gray);
    }
    
    .header h1 {
      margin: 0;
      color: var(--dark);
      font-weight: 600;
      position: relative;
      display: inline-block;
    }
    
    .header h1::after {
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
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 1rem;
      font-weight: 600;
    }
    
    .section {
      background-color: white;
      padding: 2rem;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
    }
    
    .section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--success));
      border-radius: 16px 16px 0 0;
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }
    
    .section-title {
      margin: 0;
      color: var(--dark);
      font-weight: 600;
      font-size: 1.5rem;
    }
    
    .filter-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      gap: 1rem;
      flex-wrap: wrap;
    }
    
    .search-box {
      flex-grow: 1;
      min-width: 200px;
    }
    
    .search-box form {
      display: flex;
    }
    
    .search-box input {
      width: 100%;
      padding: 1rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background-color: var(--light);
      font-family: 'Poppins', sans-serif;
    }
    
    .search-box input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    .search-box input::placeholder {
      color: var(--gray);
      opacity: 0.6;
    }
    
    .filter-dropdown select {
      padding: 1rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      background-color: var(--light);
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      min-width: 180px;
      transition: all 0.3s ease;
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
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      font-weight: 500;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
      letter-spacing: 0.5px;
    }
    
    .btn:hover {
      background: linear-gradient(90deg, var(--primary-dark), var(--secondary));
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .btn:active {
      transform: translateY(0);
    }
    
    .btn-secondary {
      background: white;
      color: var(--primary);
      border: 2px solid var(--primary);
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      font-weight: 500;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }
    
    .btn-secondary:hover {
      background-color: rgba(67, 97, 238, 0.1);
      transform: translateY(-2px);
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
      font-size: 0.9rem;
    }
    
    table tr:last-child td {
      border-bottom: none;
    }
    
    table tr:hover td {
      background-color: rgba(76, 201, 240, 0.05);
    }
    
    .status {
      display: inline-block;
      padding: 0.35rem 0.75rem;
      border-radius: 50px;
      font-size: 0.8rem;
      font-weight: 500;
    }
    
    .status.active {
      background-color: rgba(46, 213, 115, 0.15);
      color: #2ecc71;
    }
    
    .status.inactive {
      background-color: rgba(247, 37, 133, 0.15);
      color: var(--danger);
    }
    
    .status.pending {
      background-color: rgba(248, 150, 30, 0.15);
      color: var(--warning);
    }
    
    .action-btn {
      background: none;
      border: none;
      color: var(--primary);
      cursor: pointer;
      margin-right: 0.5rem;
      font-weight: 500;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s ease;
    }
    
    .action-btn.delete {
      color: var(--danger);
    }
    
    .action-btn:hover {
      opacity: 0.8;
    }
    
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-top: 2rem;
      gap: 0.5rem;
    }
    
    .pagination a {
      padding: 0.6rem 1rem;
      border: 2px solid var(--light-gray);
      border-radius: 8px;
      text-decoration: none;
      color: var(--dark);
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .pagination a:hover {
      border-color: var(--primary);
      background-color: rgba(67, 97, 238, 0.1);
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
        padding-top: 1.5rem;
      }
      
      .sidebar .logo {
        font-size: 1.5rem;
        padding: 0 0.5rem 1.5rem;
        text-align: center;
      }
      
      .sidebar .logo::after {
        left: 50%;
        transform: translateX(-50%);
        width: 40px;
      }
      
      .sidebar-menu li a {
        padding: 0.85rem 0.5rem;
        text-align: center;
      }
      
      .sidebar-menu li a span {
        display: none;
      }
      
      .main-content {
        margin-left: 80px;
        width: calc(100% - 80px);
      }
      
      .filter-bar {
        flex-direction: column;
        align-items: stretch;
      }
      
      .search-box, .filter-dropdown {
        margin-bottom: 1rem;
      }
    }
    
    @media (max-width: 768px) {
      .section {
        padding: 1.5rem;
      }
      
      table th, table td {
        padding: 0.75rem;
      }
      
      .filter-bar {
        flex-direction: column;
      }
      
      .pagination {
        flex-wrap: wrap;
      }
    }
    
    @media (max-width: 576px) {
      .header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .header .user-info {
        margin-top: 1rem;
      }
      
      .section-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
      }
    }
  </style>
</head>
<body>
  
  <div class="sidebar">
    <div class="logo">JobMatch</div>
    <ul class="sidebar-menu">
      <li><a href="admindashboard.php"><span>Dashboard</span></a></li>
      <li class="active"><a href="usermanagement.php"><span>User Management</span></a></li>
      <li><a href="joblisting.php"><span>Job Listings</span></a></li>
      <li><a href="../logout.php"><span>Logout</span></a></li>
    </ul>
  </div>
  
  <div class="main-content">
    <div class="header">
      <h1>User Management</h1>
      <div class="user-info">
        <div class="avatar">A</div>
        <div>Admin User</div>
      </div>
    </div>
    
    <div class="section">
      <div class="section-header">
        <h2 class="section-title">All Users</h2>
      </div>
      
      <div class="filter-bar">
        <div class="search-box">
          <form method="GET" action="usermanagement.php">
            <input type="text" name="search" placeholder="Search users by username or email..." value="<?php echo htmlspecialchars($search); ?>">
          </form>
        </div>
        <div class="filter-dropdown">
          <select onchange="window.location.href='usermanagement.php?user_type='+this.value+'&search=<?php echo urlencode($search); ?>'">
            <option value="">All User Types</option>
            <option value="admin" <?php echo $user_type === 'admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="employer" <?php echo $user_type === 'employer' ? 'selected' : ''; ?>>Employer</option>
            <option value="jobseeker" <?php echo $user_type === 'jobseeker' ? 'selected' : ''; ?>>Job Seeker</option>
          </select>
        </div>
      </div>
      
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>User Type</th>
            <th>Last Active</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
          <tr>
            <td><?php echo $user['id']; ?></td>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo ucfirst(htmlspecialchars($user['user_type'])); ?></td>
            <td><?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?></td>
            <td>
              <button class="action-btn delete" onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      
      <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?<?php echo http_build_query(['page' => $page - 1, 'search' => $search, 'user_type' => $user_type]); ?>">&laquo; Previous</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?<?php echo http_build_query(['page' => $i, 'search' => $search, 'user_type' => $user_type]); ?>" <?php echo $i === $page ? 'class="active"' : ''; ?>>
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
          <a href="?<?php echo http_build_query(['page' => $page + 1, 'search' => $search, 'user_type' => $user_type]); ?>">Next &raquo;</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function deleteUser(userId) {
      if (confirm('Are you sure you want to delete this user?')) {
        window.location.href = 'delete_user.php?id=' + userId;
      }
    }
  </script>
</body>
</html>