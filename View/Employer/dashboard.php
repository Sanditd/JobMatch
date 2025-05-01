<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/auth_check.php';

// Only employers can access this page
if ($_SESSION['user_type'] !== 'employer') {
    header("Location: unauthorized.php");
    exit();
}

// Check for success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';  
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Get employer's jobs
$employer_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM jobs WHERE employer_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Job Postings - JobMatch</title>
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
            margin-bottom: 1rem;
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .job-status {
            display: inline-block;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-draft {
            background-color: rgba(248, 150, 30, 0.15);
            color: var(--warning);
        }
        
        .status-published {
            background-color: rgba(76, 201, 240, 0.15);
            color: var(--success);
        }
        
        .status-closed {
            background-color: rgba(247, 37, 133, 0.15);
            color: var(--danger);
        }
        
        .job-card p {
            margin-bottom: 0.8rem;
            color: var(--gray);
        }
        
        .job-card p strong {
            color: var(--dark);
            margin-right: 0.3rem;
        }
        
        .job-actions {
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
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
            
            .page-header .btn-primary {
                margin-top: 1.5rem;
            }
            
            .job-card h2 {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .job-status {
                margin: 0.5rem 0;
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
            <a href="dashboard.php">Dashboard</a>
            <a href="createjob.php">Create Job</a>
            <a href="application.php">Applications</a>
            <a href="companyprofilepage.php">Company Profile</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
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

        <div class="page-header">
            <h1>My Job Postings</h1>
            <a href="createjob.php" class="btn-primary">Create New Job</a>
        </div>
        
        <?php if (empty($jobs)): ?>
            <div class="empty-state">
                <p>You haven't posted any jobs yet.</p>
                <a href="createjob.php" class="btn-primary">Post Your First Job</a>
            </div>
        <?php else: ?>
            <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                    <h2>
                        <?php echo htmlspecialchars($job['title']); ?>
                        <span class="job-status status-<?php echo $job['status']; ?>">
                            <?php echo ucfirst($job['status']); ?>
                        </span>
                    </h2>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
                    <p><strong>Type:</strong> <?php echo ucfirst($job['job_type']); ?></p>
                    <p><strong>Posted:</strong> <?php echo date('M j, Y', strtotime($job['created_at'])); ?></p>
                    <p><?php echo htmlspecialchars($job['summary']); ?></p>
                    
                    <div class="job-actions">
                        <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="btn-secondary">Edit Job</a>
                        <form action="job_delete.php" method="POST" style="display: inline;">
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            <button type="submit" class="btn-danger" onclick="return confirm('Are you sure you want to delete this job? This action cannot be undone.')">Delete Job</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>