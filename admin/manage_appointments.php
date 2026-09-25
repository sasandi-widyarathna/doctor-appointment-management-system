<?php
session_start();
require_once '../config/db.php';

// Admin check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Appointment Status update logic
if (isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['status'];

    $sql = "UPDATE appointments SET status = :status WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['status' => $new_status, 'id' => $appointment_id]);
    header("Location: manage_appointments.php?msg=Status Updated Successfully");
    exit();
}

// Fetch appointments with patient and doctor names
$sql = "SELECT 
            a.id, 
            p.full_name AS patient_name, 
            d.full_name AS doctor_name, 
            a.appointment_date, 
            a.appointment_time, 
            a.status 
        FROM appointments a
        JOIN users p ON a.patient_id = p.id
        JOIN users d ON a.doctor_id = d.id
        ORDER BY a.appointment_date DESC";
$stmt = $pdo->query($sql);
$appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments | Southern Lanka HMS</title>
    
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
            --warning: #fbc02d;
            --success: #2e7d32;
            --danger: #d32f2f;
            --info: #0288d1;
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

        /* Main Content Styling (Light Green) */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 40px;
            min-height: 100vh;
        }

        .header-flex {
            margin-bottom: 25px;
        }

        .header-flex h2 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 1.8rem;
        }

        .header-flex p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Search Bar Styling */
        .search-container {
            position: relative;
            margin-bottom: 25px;
            max-width: 400px;
        }

        .search-container input {
            width: 100%;
            padding: 12px 16px 12px 42px;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
            background: #ffffff;
            color: var(--primary-dark);
        }

        .search-container input:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.3);
        }

        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Message Box */
        .msg-box {
            background: #e8f5e9;
            border: 1px solid var(--primary-light);
            color: var(--text-dark);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        /* Table Container */
        .table-container {
            background: var(--card-white);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 18px 15px;
            background: #f9fbe7;
            color: var(--primary-dark);
            font-family: 'Montserrat', sans-serif;
            font-size: 0.85rem;
            text-transform: uppercase;
            border-bottom: 2px solid #dce775;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            font-size: 0.9rem;
        }

        tr:hover {
            background: #f1f8e9;
        }

        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: capitalize;
            display: inline-block;
        }

        .status-pending { background: #fff9c4; color: #f57f17; border: 1px solid #fbc02d; }
        .status-confirmed { background: #e8f5e9; color: #2e7d32; border: 1px solid #4caf50; }
        .status-cancelled { background: #ffebee; color: #c62828; border: 1px solid #ef5350; }
        .status-completed { background: #e3f2fd; color: #1565c0; border: 1px solid #2196f3; }

        /* Update Form */
        .update-form {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .select-sm {
            padding: 6px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 0.85rem;
            outline: none;
        }

        .btn-update {
            background: var(--primary-light);
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-update:hover {
            background: var(--primary-dark);
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: var(--text-muted);
        }

    </style>
</head>
<body>

    <!-- Sidebar (Dark Green) -->
    <div class="sidebar">
        <h4><i class="fas fa-hand-holding-medical"></i> HMS ADMIN</h4>
        <a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="manage_doctors.php"><i class="fas fa-user-md"></i> Manage Doctors</a>
        <a href="manage_patients.php"><i class="fas fa-user-injured"></i> Manage Patients</a>
        <a href="manage_appointments.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a>
        
        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout System</a>
    </div>

    <!-- Main Content (Light Green) -->
    <div class="main-content">
        <div class="header-flex">
            <h2>Manage Appointments</h2>
            <p>Review and update status of all hospital appointments.</p>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="msg-box">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($_GET['msg']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Search Bar Section -->
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="appointmentSearch" onkeyup="filterAppointments()" placeholder="Search by Patient, Doctor, ID, Date, ...">
        </div>

        <div class="table-container">
            <table id="appointmentsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th style="text-align: center;">Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($appointments) > 0): ?>
                        <?php foreach ($appointments as $row): ?>
                            <tr>
                                <td><span style="font-weight: 600; color: var(--text-muted);">#APT-<?php echo $row['id']; ?></span></td>
                                <td style="font-weight: 700; color: var(--primary-dark);"><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                                <td>
                                    <div><i class="far fa-calendar-alt" style="color: var(--primary-light);"></i> <?php echo $row['appointment_date']; ?></div>
                                    <small style="color: var(--text-muted);"><i class="far fa-clock"></i> <?php echo $row['appointment_time']; ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $statusLabel = $row['status'];
                                        $class = 'status-' . $statusLabel;
                                    ?>
                                    <span class="status-badge <?php echo $class; ?>">
                                        <?php echo $statusLabel; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <form action="manage_appointments.php" method="POST" class="update-form">
                                        <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                                        <select name="status" class="select-sm">
                                            <option value="pending" <?php if($row['status']=='pending') echo 'selected'; ?>>Pending</option>
                                            <option value="confirmed" <?php if($row['status']=='confirmed') echo 'selected'; ?>>Confirm</option>
                                            <option value="cancelled" <?php if($row['status']=='cancelled') echo 'selected'; ?>>Cancel</option>
                                            <option value="completed" <?php if($row['status']=='completed') echo 'selected'; ?>>Complete</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-update">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="noDataRow">
                            <td colspan="6" class="no-data">
                                <i class="fas fa-calendar-times" style="font-size: 2.5rem; display: block; margin-bottom: 15px; opacity: 0.3;"></i>
                                No appointments found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JavaScript for Live Search -->
    <script>
    function filterAppointments() {
        const input = document.getElementById('appointmentSearch');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('appointmentsTable');
        const trs = table.getElementsByTagName('tr');

        for (let i = 1; i < trs.length; i++) {
            const row = trs[i];
            
            // Skip checking the "No data" row if it exists
            if (row.id === "noDataRow") continue;

            const textContent = row.textContent || row.innerText;
            
            if (textContent.toLowerCase().indexOf(filter) > -1) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        }
    }
    </script>

</body>
</html>