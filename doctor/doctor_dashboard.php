<?php
session_start();
require_once '../config/db.php';

// Doctor check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// 1. Fetch statistics
// Total Appointments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ?");
$stmt->execute([$doctor_id]);
$total_apt = $stmt->fetchColumn();

// Pending Appointments for today
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'pending'");
$stmt->execute([$doctor_id, $today]);
$today_pending = $stmt->fetchColumn();

// 2. Fetch today's appointment list (Added patient_id for passing to add_report.php)
$sql = "SELECT a.id, a.patient_id, u.full_name AS patient_name, a.appointment_time, a.status, p.gender
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        JOIN patients p ON a.patient_id = p.user_id
        WHERE a.doctor_id = ? AND a.appointment_date = ?
        ORDER BY a.appointment_time ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$doctor_id, $today]);
$today_list = $stmt->fetchAll();

// 3. Fetch All/Search Appointments (For bottom table)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_date = isset($_GET['search_date']) ? trim($_GET['search_date']) : '';

$history_sql = "SELECT a.id, a.patient_id, u.full_name AS patient_name, a.appointment_date, a.appointment_time, a.status, p.gender
                FROM appointments a
                JOIN users u ON a.patient_id = u.id
                JOIN patients p ON a.patient_id = p.user_id
                WHERE a.doctor_id = ?";

$params = [$doctor_id];

if (!empty($search)) {
    $history_sql .= " AND (u.full_name LIKE ? OR a.status LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($search_date)) {
    $history_sql .= " AND a.appointment_date = ?";
    $params[] = $search_date;
}

$history_sql .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC LIMIT 50";
$stmt_history = $pdo->prepare($history_sql);
$stmt_history->execute($params);
$history_list = $stmt_history->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | Southern Lanka HMS</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-dark: #0a260c;    /* Sidebar Deep Green */
            --primary-light: #4caf50;   /* Medical Green */
            --bg-light: #f1f8e9;        /* Main BG Light Green */
            --card-white: #ffffff;
            --text-dark: #122913;
            --text-muted: #558b2f;
            --accent-blue: #1a5276;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Open Sans', sans-serif;
            color: var(--text-dark);
            display: flex;
        }

        /* Sidebar Styling (Dark Green) */
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
            color: white;
            font-size: 1.1rem;
            text-align: center;
            margin-bottom: 40px;
            padding: 0 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
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
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left: 5px solid var(--primary-light);
        }

        .logout-link {
            margin-top: auto;
            color: #ff8a65 !important;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 40px;
            min-height: 100vh;
        }

        /* Welcome Section */
        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-box h2 {
            font-family: 'Montserrat', sans-serif;
            color: var(--primary-dark);
        }

        .date-badge {
            background: var(--primary-dark);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--card-white);
            padding: 25px;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 6px solid var(--primary-light);
        }

        .stat-card.pending { border-left-color: #fbc02d; }

        .stat-info h3 { font-size: 1.8rem; color: var(--primary-dark); }
        .stat-info p { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 700; }
        .stat-icon { font-size: 2rem; opacity: 0.2; color: var(--primary-dark); }

        /* Table Section */
        .table-container {
            background: var(--card-white);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .table-container h5 {
            font-family: 'Montserrat', sans-serif;
            margin-bottom: 20px;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #f9fbe7;
            color: var(--primary-dark);
            border-bottom: 2px solid #dce775;
            font-size: 0.9rem;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            font-size: 0.95rem;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .bg-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #4caf50; }
        .bg-warning { background: #fff3e0; color: #ef6c00; border: 1px solid #ffb74d; }
        .bg-danger { background: #ffebee; color: #c62828; border: 1px solid #ef5350; }

        .btn-view {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: 0.3s;
            margin-right: 12px;
        }

        .btn-view:hover { color: var(--primary-dark); }

        /* Add Report Button Style */
        .btn-add-report {
            color: #ffffff;
            background-color: var(--primary-light);
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-add-report:hover {
            background-color: var(--primary-dark);
            color: #ffffff;
        }

        /* Search Form Styling */
        .search-form {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-form input[type="text"],
        .search-form input[type="date"] {
            padding: 10px 15px;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: 0.3s;
        }

        .search-form input[type="text"]:focus,
        .search-form input[type="date"]:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }

        .btn-search {
            background-color: var(--primary-dark);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-search:hover {
            background-color: var(--primary-light);
        }

        .btn-reset {
            background-color: #78909c;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: 0.3s;
        }

        .btn-reset:hover {
            background-color: #455a64;
        }

    </style>
</head>
<body>

    <div class="sidebar">
        <h4><i class="fas fa-user-md"></i> DOCTOR PORTAL</h4>
        <div style="margin-top: 20px;">
            <a href="doctor_dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="my_appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
            <a href="patient_records.php"><i class="fas fa-user-injured"></i> Patient Records</a>
            <a href="my_profile.php"><i class="fas fa-user-injured"></i> My Profile</a>

            <a href="../logout.php" class="logout-link"><i class="fas fa-power-off"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        
        <div class="header-box">
            <div>
                <h2>Welcome, <?php 
                    $doctor_name = $_SESSION['user_name'];
                    // Check the name and automatically format the "Dr." prefix
                    if (stripos($doctor_name, 'Dr') === 0) {
                        echo htmlspecialchars($doctor_name);
                    } else {
                        echo "Dr. " . htmlspecialchars($doctor_name);
                    }
                ?>!</h2>
                <p style="color: var(--text-muted);">Manage your patients and daily schedule efficiently.</p>
            </div>
            <div class="date-badge">
                <i class="far fa-calendar-alt"></i> <?php echo date('l, d M Y'); ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <p>Total Appointments</p>
                    <h3><?php echo $total_apt; ?></h3>
                </div>
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
            </div>
            <div class="stat-card pending">
                <div class="stat-info">
                    <p>Pending Today</p>
                    <h3><?php echo $today_pending; ?></h3>
                </div>
                <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
            </div>
        </div>

        <!-- Today's Schedule (Original Table Unchanged) -->
        <div class="table-container">
            <h5><i class="fas fa-hospital-user"></i> Today's Appointment Schedule</h5>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Patient Name</th>
                            <th>Gender</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($today_list) > 0): ?>
                            <?php foreach ($today_list as $row): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--primary-dark);"><?php echo $row['appointment_time']; ?></td>
                                    <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                    <td style="text-transform: capitalize;"><?php echo $row['gender']; ?></td>
                                    <td>
                                        <span class="badge <?php echo ($row['status'] == 'confirmed') ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_patient.php?id=<?php echo $row['id']; ?>" class="btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>

                                        <!-- Added Add Report Button -->
                                        <a href="add_report.php?apt_id=<?php echo $row['id']; ?>&patient_id=<?php echo $row['patient_id']; ?>" class="btn-add-report">
                                            <i class="fas fa-plus-circle"></i> Add Report
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fas fa-coffee fa-2x" style="display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                                    No appointments scheduled for today.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- NEW: All / Past Appointments Search & Table -->
        <div class="table-container">
            <h5><i class="fas fa-history"></i> Search All / Past Appointments</h5>
            
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Patient name or status..." value="<?php echo htmlspecialchars($search); ?>">
                <input type="date" name="search_date" value="<?php echo htmlspecialchars($search_date); ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
                <?php if (!empty($search) || !empty($search_date)): ?>
                    <a href="doctor_dashboard.php" class="btn-reset"><i class="fas fa-undo"></i> Reset</a>
                <?php endif; ?>
            </form>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient Name</th>
                            <th>Gender</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($history_list) > 0): ?>
                            <?php foreach ($history_list as $h_row): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?php echo date('Y-m-d', strtotime($h_row['appointment_date'])); ?></td>
                                    <td style="font-weight: 700; color: var(--primary-dark);"><?php echo $h_row['appointment_time']; ?></td>
                                    <td><?php echo htmlspecialchars($h_row['patient_name']); ?></td>
                                    <td style="text-transform: capitalize;"><?php echo $h_row['gender']; ?></td>
                                    <td>
                                        <?php 
                                            $badge_class = 'bg-warning';
                                            if ($h_row['status'] == 'confirmed') $badge_class = 'bg-success';
                                            if ($h_row['status'] == 'cancelled') $badge_class = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $h_row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_patient.php?id=<?php echo $h_row['id']; ?>" class="btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="add_report.php?apt_id=<?php echo $h_row['id']; ?>&patient_id=<?php echo $h_row['patient_id']; ?>" class="btn-add-report">
                                            <i class="fas fa-plus-circle"></i> Add Report
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    No records found matching your search.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>