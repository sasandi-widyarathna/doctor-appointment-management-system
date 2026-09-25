<?php
session_start();
require_once '../config/db.php';

// Admin check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Patient delete logic
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    header("Location: manage_patients.php?msg=Patient Record Deleted Successfully");
    exit();
}

// Fetch all patients
$sql = "SELECT users.id, users.full_name, users.email, users.phone, patients.dob, patients.gender, patients.address 
        FROM users 
        JOIN patients ON users.id = patients.user_id 
        WHERE users.role = 'patient'";
$stmt = $pdo->query($sql);
$patients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients | Southern Lanka HMS</title>
    
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
            --danger: #d32f2f;
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

        .patient-name {
            font-weight: 700;
            color: var(--primary-dark);
        }

        .patient-id {
            display: block;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .contact-info div {
            font-size: 0.85rem;
            margin-bottom: 3px;
        }

        .badge-gender {
            background: #e8f5e9;
            color: var(--primary-light);
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid var(--primary-light);
            text-transform: capitalize;
        }

        /* Delete Button */
        .btn-delete {
            background: #fff5f5;
            color: var(--danger);
            border: 1px solid #ffcdd2;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            transition: 0.3s;
            display: inline-block;
        }

        .btn-delete:hover {
            background: var(--danger);
            color: white;
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
        <a href="manage_patients.php" class="active"><i class="fas fa-user-injured"></i> Manage Patients</a>
        <a href="manage_appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
        
        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout System</a>
    </div>

    <!-- Main Content (Light Green) -->
    <div class="main-content">
        <div class="header-flex">
            <h2>Registered Patients</h2>
            <p>List of all patients registered at Southern Lanka Hospital.</p>
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
            <input type="text" id="patientSearch" onkeyup="filterPatients()" placeholder="Search patients by name, ID, phone, email...">
        </div>

        <div class="table-container">
            <table id="patientsTable">
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Contact Info</th>
                        <th>Gender</th>
                        <th>DOB</th>
                        <th>Address</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($patients) > 0): ?>
                        <?php foreach ($patients as $row): ?>
                            <tr>
                                <td>
                                    <span class="patient-name"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                    <span class="patient-id">ID: #P-0<?php echo $row['id']; ?></span>
                                </td>
                                <td class="contact-info">
                                    <div><i class="fas fa-envelope me-1" style="font-size: 0.8rem;"></i> <?php echo htmlspecialchars($row['email']); ?></div>
                                    <div><i class="fas fa-phone me-1" style="font-size: 0.8rem;"></i> <?php echo htmlspecialchars($row['phone']); ?></div>
                                </td>
                                <td>
                                    <span class="badge-gender"><?php echo htmlspecialchars($row['gender']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['dob']); ?></td>
                                <td style="max-width: 200px;"><?php echo htmlspecialchars($row['address']); ?></td>
                                <td style="text-align: center;">
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Confirm delete patient?')">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="noDataRow">
                            <td colspan="6" class="no-data">
                                <i class="fas fa-folder-open" style="font-size: 2.5rem; display: block; margin-bottom: 15px; opacity: 0.3;"></i>
                                No patients registered yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JavaScript for Live Search -->
    <script>
    function filterPatients() {
        const input = document.getElementById('patientSearch');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('patientsTable');
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