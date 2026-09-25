<?php
session_start();
require_once '../config/db.php';

// Admin check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Doctor delete logic
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    header("Location: manage_doctors.php?msg=Doctor Record Deleted Successfully");
    exit();
}

// Fetch all doctors
$sql = "SELECT users.id, users.full_name, users.email, users.phone, doctors.specialization, doctors.department 
        FROM users 
        JOIN doctors ON users.id = doctors.user_id 
        WHERE users.role = 'doctor'";
$stmt = $pdo->query($sql);
$doctors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors | Southern Lanka HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-dark: #0a260c;    /* Sidebar Deep Green */
            --bg-light: #f1f8e9;         /* Main BG Light Green */
            --card-light: #ffffff;      /* Pure White/Light Card */
            --primary-light: #4caf50;   /* Medical Green */
            --text-dark: #1b5e20;       /* Dark Green Text */
            --text-muted: #558b2f;      /* Muted Green Text */
            --danger: #d32f2f;
            --white: #ffffff;
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

        /* Sidebar Styling (Remains Deep Green) */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary-dark);
            position: fixed;
            padding: 25px 0;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar h4 {
            font-family: 'Montserrat', sans-serif;
            color: var(--white);
            font-size: 1.1rem;
            text-align: center;
            margin-bottom: 40px;
            padding: 0 15px;
            text-transform: uppercase;
        }

        .sidebar a {
            padding: 15px 25px;
            display: block;
            color: #a5d6a7; /* Light muted green for links */
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
        }

        /* Main Content (Light Theme) */
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

        /* Add Button */
        .btn-add {
            background: var(--primary-light);
            color: var(--white);
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }

        .btn-add:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
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
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Table Container (Light Mode) */
        .table-container {
            background: var(--card-light);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            overflow-x: auto;
            border: 1px solid #e0e0e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #f9fbe7; /* Very light lime/green */
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
            color: #333;
        }

        tr:hover {
            background: #f1f8e9;
        }

        .doc-name {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .badge-spec {
            background: #e8f5e9;
            color: var(--primary-light);
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid var(--primary-light);
        }

        /* Action Buttons */
        .btn-delete {
            background: #fff5f5;
            color: var(--danger);
            border: 1px solid #ffcdd2;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .btn-delete:hover {
            background: var(--danger);
            color: var(--white);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

    </style>
</head>
<body>

    <!-- Sidebar (Keep Dark Green) -->
    <div class="sidebar">
        <h4><i class="fas fa-hand-holding-medical"></i> HMS ADMIN</h4>
        <a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="manage_doctors.php" class="active"><i class="fas fa-user-md"></i> Manage Doctors</a>
        <a href="manage_patients.php"><i class="fas fa-user-injured"></i> Manage Patients</a>
        <a href="manage_appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
        
        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout System</a>
    </div>

    <!-- Main Content (Light Green Theme) -->
    <div class="main-content">
        <div class="header-flex">
            <h2>Manage Doctors</h2>
            <a href="add_doctor.php" class="btn-add">
                <i class="fas fa-plus"></i> Add New Doctor
            </a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="msg-box">
                <i class="fas fa-check-circle"></i> &nbsp; <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <!-- Search Bar Section -->
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="doctorSearch" onkeyup="filterDoctors()" placeholder="Search doctors by name, spec, or department...">
        </div>

        <div class="table-container">
            <table id="doctorsTable">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Specialization</th>
                        <th>Department</th>
                        <th>Phone</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($doctors) > 0): ?>
                        <?php foreach ($doctors as $row): ?>
                            <tr>
                                <td class="doc-name"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><span class="badge-spec"><?php echo htmlspecialchars($row['specialization']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td style="text-align: center;">
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this doctor record?')">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="noDataRow">
                            <td colspan="6" class="no-data">
                                <i class="fas fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                                No doctors registered in the system.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JavaScript for Live Search -->
    <script>
    function filterDoctors() {
        const input = document.getElementById('doctorSearch');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('doctorsTable');
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