<?php
session_start();
require_once '../config/db.php';

// Patient check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// Fetch appointments relevant to the patient along with the doctor's name
$sql = "SELECT a.id, u.full_name AS doctor_name, d.specialization, a.appointment_date, a.appointment_time, a.status 
        FROM appointments a
        JOIN users u ON a.doctor_id = u.id
        JOIN doctors d ON a.doctor_id = d.user_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$patient_id]);
$my_appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments | Southern Lanka HMS</title>
    
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
            color: white;
            font-size: 1.1rem;
            text-align: center;
            margin-bottom: 40px;
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

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-flex h2 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .btn-new {
            background: var(--primary-light);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s;
            display: inline-block;
        }

        .btn-new:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Table Styling */
        .table-container {
            background: var(--card-white);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #f9fbe7;
            color: var(--primary-dark);
            border-bottom: 2px solid #dce775;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            font-size: 0.9rem;
        }

        tr:hover {
            background-color: #f1f8e9;
        }

        /* Status Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-pending { background: #fff3e0; color: #ef6c00; border: 1px solid #ffb74d; }
        .status-confirmed { background: #e8f5e9; color: #2e7d32; border: 1px solid #4caf50; }
        .status-cancelled { background: #ffebee; color: #c62828; border: 1px solid #ef5350; }
        .status-completed { background: #e3f2fd; color: #1565c0; border: 1px solid #42a5f5; }

        .ref-id {
            color: #888;
            font-family: monospace;
            font-size: 0.8rem;
        }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h4><i class="fas fa-heartbeat"></i> PATIENT PORTAL</h4>
        <div style="margin-top: 20px;">
            <a href="patient_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="book_appointment.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
            <a href="my_appointments.php" class="active"><i class="fas fa-list-alt"></i> My Appointments</a>
            <a href="my_profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
            
            <a href="../logout.php" class="logout-link"><i class="fas fa-power-off"></i> Sign Out</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header-flex">
            <h2>My Appointment History</h2>
            <a href="book_appointment.php" class="btn-new">
                <i class="fas fa-plus"></i> New Booking
            </a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Ref ID</th>
                        <th>Doctor Name</th>
                        <th>Specialization</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($my_appointments) > 0): ?>
                        <?php foreach ($my_appointments as $row): ?>
                            <tr>
                                <td class="ref-id">#APT-0<?php echo $row['id']; ?></td>
                                <td>
                                    <span style="font-weight: 600; color: var(--primary-dark);">
                                         <?php echo htmlspecialchars($row['doctor_name']); ?>
                                    </span>
                                </td>
                                <td><span style="color: var(--text-muted);"><?php echo htmlspecialchars($row['specialization']); ?></span></td>
                                <td>
                                    <div><i class="far fa-calendar-alt" style="color: var(--primary-light);"></i> <?php echo $row['appointment_date']; ?></div>
                                    <div style="font-size: 0.8rem; color: #888;"><i class="far fa-clock"></i> <?php echo $row['appointment_time']; ?></div>
                                </td>
                                <td>
                                    <?php 
                                        $statusClass = 'status-pending';
                                        if($row['status'] == 'confirmed') $statusClass = 'status-confirmed';
                                        elseif($row['status'] == 'cancelled') $statusClass = 'status-cancelled';
                                        elseif($row['status'] == 'completed') $statusClass = 'status-completed';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times fa-3x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                                    <p>You haven't booked any appointments yet.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>