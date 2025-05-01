<?php
session_start();

if (isset($_SESSION['apply_message'])) {
  echo '<div class="alert success">' . $_SESSION['apply_message'] . '</div>';
  unset($_SESSION['apply_message']);
}
if (isset($_SESSION['error_message'])) {
  echo '<div class="alert error">' . $_SESSION['error_message'] . '</div>';
  unset($_SESSION['error_message']);
}

require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

if (isset($_SESSION['apply_message'])) {
    echo '<div class="alert success">' . $_SESSION['apply_message'] . '</div>';
    unset($_SESSION['apply_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert error">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

// Only job seekers can access this page
if ($_SESSION['user_type'] !== 'jobseeker') {
    header("Location: unauthorized.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize search parameters from GET request
$search_query = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$job_type = $_GET['job_type'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build the SQL query with filters
$query = "SELECT j.*, ep.company_name 
          FROM jobs j
          JOIN users u ON j.employer_id = u.id
          JOIN employer_profiles ep ON u.id = ep.user_id
          WHERE j.status = 'published'";

$params = [];
$types = '';

if (!empty($search_query)) {
    $query .= " AND (j.title LIKE ? OR j.summary LIKE ? OR j.responsibilities LIKE ? OR e.company_name LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

if (!empty($location)) {
    $query .= " AND j.location = ?";
    $params[] = $location;
    $types .= 's';
}

if (!empty($job_type)) {
    $query .= " AND j.job_type = ?";
    $params[] = $job_type;
    $types .= 's';
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM jobs j
                JOIN users u ON j.employer_id = u.id
                JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE j.status = 'published'";

$count_params = [];
$count_types = '';

if (!empty($search_query)) {
    $count_query .= " AND (j.title LIKE ? OR j.summary LIKE ? OR j.responsibilities LIKE ? OR ep.company_name LIKE ?)";
    $search_param = "%$search_query%";
    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param]);
    $count_types .= 'ssss';
}

if (!empty($location)) {
    $count_query .= " AND j.location = ?";
    $count_params[] = $location;
    $count_types .= 's';
}

if (!empty($job_type)) {
    $count_query .= " AND j.job_type = ?";
    $count_params[] = $job_type;
    $count_types .= 's';
}

$stmt = $conn->prepare($count_query);
if (!empty($count_params)) {
    $stmt->bind_param($count_types, ...$count_params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_jobs = $total_result->fetch_assoc()['total'] ?? 0; // fallback to 0 just in case
$stmt->execute();
$total_result = $stmt->get_result();
$total_jobs = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_jobs / $per_page);

// Add pagination to the main query
$query .= " ORDER BY j.created_at DESC LIMIT ? OFFSET ?";
$params = array_merge($params, [$per_page, $offset]);
$types .= 'ii';

// Fetch jobs
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);

// Check which jobs are saved by the user
$saved_jobs = [];
if (!empty($jobs)) {
    $job_ids = array_column($jobs, 'id');
    $placeholders = implode(',', array_fill(0, count($job_ids), '?'));
    $types = str_repeat('i', count($job_ids));
    
    $stmt = $conn->prepare("SELECT job_id FROM saved_jobs WHERE user_id = ? AND job_id IN ($placeholders)");
    $stmt->bind_param('i' . $types, $user_id, ...$job_ids);
    $stmt->execute();
    $saved_result = $stmt->get_result();
    while ($row = $saved_result->fetch_assoc()) {
        $saved_jobs[] = $row['job_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Search - JobMatch</title>
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
            position: relative;
        }
        
        .navbar::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            z-index: 0;
        }
        
        .navbar::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            z-index: 0;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        
        .nav-links {
            display: flex;
            z-index: 1;
            position: relative;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 1.5rem;
            padding: 0.5rem 0;
            font-weight: 500;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            color: var(--success);
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--success);
            transition: width 0.3s ease;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background-color: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            position: relative;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--success));
            border-radius: 12px 12px 0 0;
        }
        
        h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            position: relative;
            color: var(--dark);
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
        
        .search-form {
            background-color: white;
            padding: 1.8rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 1rem;
        }
        
        .search-form input, .search-form select {
            padding: 0.8rem;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-secondary {
            background-color: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.6rem 1.3rem;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin-right: 0.8rem;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-secondary:hover {
            background-color: rgba(67, 97, 238, 0.05);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: white;
            color: var(--danger);
            border: 2px solid var(--danger);
            padding: 0.6rem 1.3rem;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-danger:hover {
            background-color: rgba(247, 37, 133, 0.05);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: relative;
            border-left: 4px solid;
        }
        
        .alert-success {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
            border-color: var(--success);
        }
        
        .alert-error {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border-color: var(--danger);
        }
        
        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-tag {
            background-color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            border: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }
        
        .filter-tag button {
            background: none;
            border: none;
            margin-left: 0.5rem;
            cursor: pointer;
            color: var(--gray);
            font-weight: bold;
        }
        
        .job-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 1.8rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .job-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
            border-radius: 0 12px 12px 0;
        }
        
        .job-card h2 {
            color: var(--dark);
            margin-bottom: 0.8rem;
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .job-company {
            color: var(--gray);
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            color: var(--gray);
        }
        
        .job-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.95rem;
        }
        
        .job-description {
            margin-bottom: 1.5rem;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .job-actions {
            display: flex;
            align-items: center;
            margin-top: 1.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .empty-state p {
            color: var(--gray);
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }
        
        .pagination a {
            background-color: white;
            color: var(--primary);
            border: 1px solid var(--light-gray);
            padding: 0.6rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .pagination a:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .pagination a.active {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }
            
            .logo {
                margin-bottom: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-links a {
                margin: 0.5rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .job-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .btn-secondary, .btn-danger {
                margin: 0.5rem 0;
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">JobMatch</div>
        <div class="nav-links">
            <a href="job_search.php">Dashboard</a>
            <a href="userprofile.php">Profile</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($_SESSION['apply_message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['apply_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1>Find Your Dream Job</h1>
        </div>
        
        <form class="search-form" method="GET" action="job_search.php">
            <select name="location">
                <option value="">Location</option>  
                <option value="colombo" <?php echo $location === 'colombo' ? 'selected' : ''; ?>>Colombo</option>
                <option value="kandy" <?php echo $location === 'kandy' ? 'selected' : ''; ?>>Kandy</option>
                <option value="galle" <?php echo $location === 'galle' ? 'selected' : ''; ?>>Galle</option>
                <option value="jaffna" <?php echo $location === 'jaffna' ? 'selected' : ''; ?>>Jaffna</option>
                <option value="remote" <?php echo $location === 'remote' ? 'selected' : ''; ?>>Remote</option>
            </select>
            <select name="job_type">
                <option value="">Job Type</option>
                <option value="fulltime" <?php echo $job_type === 'fulltime' ? 'selected' : ''; ?>>Full-time</option>
                <option value="parttime" <?php echo $job_type === 'parttime' ? 'selected' : ''; ?>>Part-time</option>
                <option value="contract" <?php echo $job_type === 'contract' ? 'selected' : ''; ?>>Contract</option>
                <option value="internship" <?php echo $job_type === 'internship' ? 'selected' : ''; ?>>Internship</option>
            </select>
            <button type="submit" class="btn-primary">Search Jobs</button>
        </form>
        
        <?php if (!empty($search_query) || !empty($location) || !empty($job_type)): ?>
        <div class="filter-section">
            <?php if (!empty($search_query)): ?>
                <div class="filter-tag">
                    Search: <?php echo htmlspecialchars($search_query); ?>
                    <button onclick="removeFilter('search')">×</button>
                </div>
            <?php endif; ?>
            <?php if (!empty($location)): ?>
                <div class="filter-tag">
                    Location: <?php echo ucfirst(htmlspecialchars($location)); ?>
                    <button onclick="removeFilter('location')">×</button>
                </div>
            <?php endif; ?>
            <?php if (!empty($job_type)): ?>
                <div class="filter-tag">
                    Job Type: <?php echo ucfirst(htmlspecialchars($job_type)); ?>
                    <button onclick="removeFilter('job_type')">×</button>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (empty($jobs)): ?>
            <div class="empty-state">
                <p>No jobs found matching your criteria. Try adjusting your search filters.</p>
                <a href="job_search.php" class="btn-primary">Clear All Filters</a>
            </div>
        <?php else: ?>
            <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                    <h2><?php echo htmlspecialchars($job['title']); ?></h2>
                    <div class="job-company"><?php echo htmlspecialchars($job['company_name']); ?></div>
                    <div class="job-meta">
                        <span>📍 <?php echo ucfirst(htmlspecialchars($job['location'])); ?></span>
                        <span>🕒 <?php echo ucfirst(htmlspecialchars($job['job_type'])); ?></span>
                        <span>💰 
                            <?php if ($job['salary_min'] && $job['salary_max']): ?>
                                $<?php echo number_format($job['salary_min']); ?> - $<?php echo number_format($job['salary_max']); ?> 
                                <?php echo htmlspecialchars($job['salary_period'] === 'yearly' ? '/year' : '/month'); ?>
                            <?php else: ?>
                                Salary not specified
                            <?php endif; ?>
                        </span>
                        <span>⏳ Posted <?php echo time_elapsed_string($job['created_at']); ?></span>
                    </div>
                    <div class="job-description"><?php echo htmlspecialchars($job['summary']); ?></div>
                    
                    <div class="job-actions">
                        
                        <a href="apply_job.php?id=<?php echo $job['id']; ?>" class="btn-primary">Apply Now</a>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?php echo build_query_string(['page' => $page - 1]); ?>">&laquo; Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?<?php echo build_query_string(['page' => $i]); ?>" <?php echo $i === $page ? 'class="active"' : ''; ?>>
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?<?php echo build_query_string(['page' => $page + 1]); ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function removeFilter(filterName) {
            const url = new URL(window.location.href);
            url.searchParams.delete(filterName);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>

<?php
// Helper functions
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function build_query_string($new_params = []) {
    $params = $_GET;
    foreach ($new_params as $key => $value) {
        $params[$key] = $value;
    }
    return http_build_query($params);
}
?>