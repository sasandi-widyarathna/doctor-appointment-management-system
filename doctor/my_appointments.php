<?php
session_start();
require_once '../config/db.php';

// Doctor check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];

// SQL Query Correction: 
// 1. 'a.reason' was removed.
// 2. 'u.phone' is used instead of 'p.phone' (since the phone number is mostly stored in the users table).
$sql = "SELECT a.id, u.full_name AS patient_name, a.appointment_date, a.appointment_time, a.status, u.phone
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        JOIN patients p ON a.patient_id = p.user_id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$doctor_id]);
$appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments | Southern Lanka HMS</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-dark: #0a260c;    /* Dark Green Sidebar */
            --primary-light: #4caf50;   /* Medical Green */
            --bg-light: #f1f8e9;        /* Light Green Background */
            --card-white: #ffffff;
            --text-dark: #122913;
            --text-muted: #558b2f;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg-light);
            font-family: 'Open Sans', sans-serif;
            color: var(--text-dark);
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary-dark);
            position: fixed;
            padding: 25px 0;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px rgba(0,0,0,0.3);
        }

        .sidebar h4 {
            color: white;
            font-family: 'Montserrat';
            text-align: center;
            margin-bottom: 30px;
            text-transform: uppercase;
        }

        .sidebar a {
            padding: 15px 25px;
            display: block;
            color: #a5d6a7;
            text-decoration: none;
            transition: 0.3s;
            border-left: 5px solid transparent;
        }

        .sidebar a:hover, .sidebar a.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left: 5px solid var(--primary-light);
        }

        .logout { margin-top: auto; color: #ff8a65 !important; font-weight: bold; }

        /* Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 40px;
        }

        .table-container {
            background: var(--card-white);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        table { width: 100%; border-collapse: collapse; }
        th { 
            text-align: left; 
            padding: 15px; 
            background: #f9fbe7; 
            border-bottom: 2px solid #dce775;
            color: var(--primary-dark);
        }
        td { padding: 15px; border-bottom: 1px solid #f1f1f1; }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: capitalize;
        }
        .status-confirmed { background: #e8f5e9; color: #2e7d32; }
        .status-pending { background: #fff3e0; color: #ef6c00; }

        .call-link { color: var(--primary-light); text-decoration: none; font-size: 1.2rem; }

        /* Search Bar Styling */
        .search-box {
            position: relative;
            margin-bottom: 20px;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid #c8e6c9;
            border-radius: 30px;
            outline: none;
            font-size: 0.95rem;
            background: #ffffff;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.2);
        }

        .search-box i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h4>HMS DOCTOR</h4>
        <a href="doctor_dashboard.php"><i class="fas fa-th-large me-2"></i> Dashboard</a>
        <a href="my_appointments.php" class="active"><i class="fas fa-calendar-check me-2"></i> My Appointments</a>
        <a href="patient_records.php"><i class="fas fa-user-injured me-2"></i> Patient Records</a>
        <a href="my_profile.php"><i class="fas fa-user-md"></i> My Profile</a>
        <a href="../logout.php" class="logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>

    <div class="main-content">
        <h2 style="margin-bottom: 20px; color: var(--primary-dark);">Appointment History</h2>
        
        <!-- Search Bar -->
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" onkeyup="filterAppointments()" placeholder="Search patient name, date, ref ID...">
        </div>

        <div class="table-container">
            <table id="appointmentsTable">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Patient Name</th>
                        <th>Status</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($appointments) > 0): ?>
                        <?php foreach ($appointments as $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: bold;"><?php echo $row['appointment_date']; ?></div>
                                    <small style="color: #666;"><?php echo $row['appointment_time']; ?></small>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($row['patient_name']); ?></div>
                                    <small style="color: #999;">Ref: #APT-0<?php echo $row['id']; ?></small>
                                </td>
                                <td>
                                    <?php $s = $row['status']; ?>
                                    <span class="badge status-<?php echo $s; ?>"><?php echo $s; ?></span>
                                </td>
                                <td>
                                    <a href="tel:<?php echo $row['phone']; ?>" class="call-link">
                                        <i class="fas fa-phone-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 30px;">No appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Live Search JavaScript -->
    <script>
        function filterAppointments() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('appointmentsTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let row = tr[i];
                let text = row.textContent || row.innerText;
                
                if (text.toLowerCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    </script>

</body>
</html>