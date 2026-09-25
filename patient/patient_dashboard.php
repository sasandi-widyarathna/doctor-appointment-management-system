<?php
session_start();
require_once '../config/db.php';

// Patient check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// 1. Patient summary data
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$total_apt = $stmt->fetchColumn();

// Next appointment
$stmt = $pdo->prepare("SELECT a.*, u.full_name AS doctor_name 
                       FROM appointments a 
                       JOIN users u ON a.doctor_id = u.id 
                       WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() 
                       ORDER BY a.appointment_date ASC LIMIT 1");
$stmt->execute([$patient_id]);
$next_apt = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard | Southern Lanka HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
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
            --accent: #2e7d32;
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

        /* Welcome Banner */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-dark), #1b431e);
            color: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .welcome-text h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .btn-new {
            background: var(--primary-light);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            transition: 0.3s;
            display: inline-block;
        }

        .btn-new:hover {
            background: white;
            color: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Dashboard Stats */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .card-custom {
            background: var(--card-white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--primary-light);
            margin-bottom: 15px;
        }

        .apt-box {
            background: #f9fbe7;
            border-left: 5px solid var(--primary-light);
            padding: 20px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-pill {
            background: var(--primary-light);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        /* Health Tip */
        .tip-card {
            background: #e8f5e9;
            border: 2px dashed var(--primary-light);
            border-radius: 15px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .tip-icon {
            font-size: 2rem;
            color: #fbc02d;
        }

        @media (max-width: 992px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Sidebar (Dark Green) -->
    <div class="sidebar">
        <h4><i class="fas fa-heartbeat"></i> PATIENT PORTAL</h4>
        <div style="margin-top: 20px;">
            <a href="patient_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="book_appointment.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
            <a href="my_appointments.php"><i class="fas fa-notes-medical"></i> My Records</a>
            <a href="my_profile.php"><i class="fas fa-user-circle"></i> Profile Settings</a>
            
            <a href="../logout.php" class="logout-link"><i class="fas fa-power-off"></i> Sign Out</a>
        </div>
    </div>

    <!-- Main Content (Light Green) -->
    <div class="main-content">
        <!-- Welcome Banner -->
        <div class="welcome-card">
            <div class="welcome-text">
                <h2>Ayubowan, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                <p>How are you feeling today? You can manage your medical visits here.</p>
            </div>
            <a href="book_appointment.php" class="btn-new">
                <i class="fas fa-plus"></i> New Appointment
            </a>
        </div>

        <div class="dashboard-grid">
            <!-- Total Visits -->
            <div class="card-custom">
                <div class="stat-icon"><i class="fas fa-book-medical"></i></div>
                <h2 style="font-size: 2.5rem; color: var(--primary-dark);"><?php echo $total_apt; ?></h2>
                <p style="color: var(--text-muted); font-weight: 600;">Total Appointments</p>
            </div>

            <!-- Next Appointment -->
            <div class="card-custom">
                <h5 style="margin-bottom: 20px; font-family: 'Montserrat';"><i class="fas fa-calendar-check" style="color: var(--primary-light);"></i> Upcoming Visit</h5>
                <?php if ($next_apt): ?>
                    <div class="apt-box">
                        <div>
                            <h4 style="color: var(--primary-dark); margin-bottom: 5px;"> <?php echo htmlspecialchars($next_apt['doctor_name']); ?></h4>
                            <p style="font-size: 0.9rem; color: var(--text-muted);">
                                <i class="far fa-calendar-alt"></i> <?php echo $next_apt['appointment_date']; ?> 
                                &nbsp;&nbsp;
                                <i class="far fa-clock"></i> <?php echo $next_apt['appointment_time']; ?>
                            </p>
                        </div>
                        <span class="status-pill"><?php echo $next_apt['status']; ?></span>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: var(--text-muted);">
                        <p>No upcoming appointments found. Stay healthy!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Health Tip Section -->
        <div class="tip-card">
            <div class="tip-icon">
                <i class="fas fa-lightbulb"></i>
            </div>
            <div>
                <h4 style="color: var(--primary-dark); margin-bottom: 5px; font-family: 'Montserrat';">Health Tip of the Day</h4>
                <p style="font-size: 0.95rem;">Drink at least 8 glasses of water today to keep your body energized and hydrated.</p>
            </div>
        </div>
    </div>

</body>
</html>