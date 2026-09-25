<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM doctors");
$total_doctors = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM patients");
$total_patients = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM appointments");
$total_appointments = $stmt->fetchColumn();

// Fetch latest 3 notifications for the dashboard preview
$sql_notif = "SELECT n.*, u.full_name 
              FROM notifications n
              JOIN users u ON n.user_id = u.id
              ORDER BY n.created_at DESC LIMIT 3";
$stmt_notif = $pdo->query($sql_notif);
$latest_logs = $stmt_notif->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Southern Lanka HMS</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-dark: #0a260c;    
            --primary-light: #4caf50;   
            --bg-light: #f1f8e9;        
            --accent: #a5d6a7;
            --white: #ffffff;
            --text-dark: #122913;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg-light);
            font-family: 'Open Sans', sans-serif;
            color: var(--text-dark);
            display: flex;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary-dark);
            position: fixed;
            padding: 25px 0;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px rgba(0,0,0,0.3);
            z-index: 1000;
        }

        .sidebar h4 {
            font-family: 'Montserrat', sans-serif;
            color: var(--white);
            font-size: 1.1rem;
            text-align: center;
            margin-bottom: 40px;
            padding: 0 15px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .sidebar a {
            padding: 15px 25px;
            display: block;
            color: #a5d6a7; 
            text-decoration: none;
            transition: 0.3s;
            font-size: 0.9rem;
            border-left: 5px solid transparent;
        }

        .sidebar a:hover, .sidebar a.active {
            color: var(--white);
            background: rgba(255, 255, 255, 0.1);
            border-left: 5px solid var(--primary-light);
        }

        .logout-link {
            margin-top: auto;
            color: #ff8a65 !important;
            font-weight: 600;
        }

        /* Main Content Styling */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 40px;
            min-height: 100vh;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .welcome-text h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.7rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .location-badge {
            background: var(--primary-dark);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card-custom {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: 0.3s;
            border-bottom: 4px solid var(--accent);
        }

        .card-custom:hover {
            transform: translateY(-5px);
            border-bottom: 4px solid var(--primary-light);
        }

        .icon-box {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; margin-bottom: 20px;
        }

        .card-custom h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.2rem; margin-bottom: 5px;
            color: var(--primary-dark);
        }

        .card-custom p {
            color: #555; font-weight: 600; font-size: 0.85rem;
            text-transform: uppercase;
        }

        /* Bottom Section Grid */
        .bottom-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        .content-area {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .content-area h5 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700; margin-bottom: 20px;
            color: var(--primary-dark);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert-box {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid var(--primary-light);
        }

        .small-text { font-size: 0.75rem; color: #777; margin-left: auto; }

        /* Quick Action Buttons */
        .btn-action {
            background: var(--primary-dark);
            color: var(--white);
            border: none;
            padding: 14px;
            border-radius: 10px;
            width: 100%;
            margin-bottom: 12px;
            cursor: pointer;
            text-align: left;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            transition: 0.3s;
            text-decoration: none;
            display: block;
        }

        .btn-action:hover {
            background: var(--primary-light);
            transform: translateX(5px);
            color: white;
        }

        .view-all { font-size: 0.7rem; text-decoration: none; color: var(--primary-light); }

        @media (max-width: 992px) {
            .bottom-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h4><i class="fas fa-hand-holding-medical"></i> HMS ADMIN</h4>
        <a href="admin_dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="manage_doctors.php"><i class="fas fa-user-md"></i> Manage Doctors</a>
        <a href="manage_patients.php"><i class="fas fa-user-injured"></i> Manage Patients</a>
        <a href="manage_appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
        <a href="admin_pending_reports.php"><i class="fas fa-file-invoice-dollar"></i> Pending Reports</a>
        <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>

        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout System</a>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header-section">
            <div class="welcome-text">
                <h2>Dashboard Overview</h2>
                <p>Welcome back, <strong style="color: var(--primary-light);"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong></p>
            </div>
            <div class="location-badge">
                <i class="fas fa-hospital" style="color: var(--accent);"></i> Southern Lanka - Matara
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="card-custom">
                <div class="icon-box" style="background: #e8f5e9; color: #2e7d32;"><i class="fas fa-user-md"></i></div>
                <h3><?php echo $total_doctors; ?></h3>
                <p>Total Doctors</p>
            </div>

            <div class="card-custom">
                <div class="icon-box" style="background: #f1f8e9; color: #388e3c;"><i class="fas fa-user-injured"></i></div>
                <h3><?php echo $total_patients; ?></h3>
                <p>Registered Patients</p>
            </div>

            <div class="card-custom">
                <div class="icon-box" style="background: #fff8e1; color: #f57f17;"><i class="fas fa-calendar-check"></i></div>
                <h3><?php echo $total_appointments; ?></h3>
                <p>Total Appointments</p>
            </div>
        </div>

        <div class="bottom-grid">
            <!-- Notifications Card -->
            <div class="content-area">
                <h5>
                    <span><i class="fas fa-bell"></i> Recent Logs</span>
                    <a href="notifications.php" class="view-all">View All</a>
                </h5>
                
                <?php if(count($latest_logs) > 0): ?>
                    <?php foreach($latest_logs as $log): ?>
                        <div class="alert-box">
                            <i class="fas <?php echo ($log['type'] == 'SMS') ? 'fa-sms text-info' : 'fa-envelope text-warning'; ?>"></i>
                            <span><strong><?php echo htmlspecialchars($log['full_name']); ?>:</strong> <?php echo htmlspecialchars(substr($log['message'], 0, 50)); ?>...</span>
                            <span class="small-text"><?php echo date('H:i', strtotime($log['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert-box"><span>No recent notifications found.</span></div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions Card -->
            <div class="content-area">
                <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                <a href="add_doctor.php" class="btn-action">
                    <i class="fas fa-plus-circle"></i> &nbsp; Add New Doctor
                </a>
                <a href="admin_pending_reports.php" class="btn-action" style="background: #2e7d32;">
                    <i class="fas fa-file-invoice-dollar"></i> &nbsp; Pending Reports (Billing)
                </a>
                <a href="notifications.php" class="btn-action" style="background: #2c3e50;">
                    <i class="fas fa-list"></i> &nbsp; View All Notifications
                </a>
                <a href="reports.php" class="btn-action">
                    <i class="fas fa-chart-line"></i> &nbsp; View Daily Report
                </a>
            </div>
        </div>
    </div>
</body>
</html>