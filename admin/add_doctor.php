<?php
session_start();
require_once '../config/db.php';

// Admin check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$success = "";
$error = "";

if (isset($_POST['add_doctor'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password']; // Password stored directly as requested
    $phone = $_POST['phone'];
    $specialization = $_POST['specialization'];
    $department = $_POST['department'];
    $experience = $_POST['experience'];

    if (!empty($full_name) && !empty($email) && !empty($password)) {
        try {
            // Start a transaction (Since both tables must be correctly updated)
            $pdo->beginTransaction();

            // 1. Insert into users table
            $sql1 = "INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, 'doctor', ?)";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([$full_name, $email, $password, $phone]);

            $user_id = $pdo->lastInsertId();

            // 2. Insert into doctors table
            $sql2 = "INSERT INTO doctors (user_id, specialization, department, experience_years) VALUES (?, ?, ?, ?)";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([$user_id, $specialization, $department, $experience]);

            $pdo->commit();
            $success = "Doctor added successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill all required fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Doctor | Southern Lanka HMS</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-dark: #0a260c;    /* Deep Admin Green */
            --primary-light: #4caf50;   /* Medical Green */
            --bg-light: #f1f8e9;        /* Light Background Green */
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

        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 40px;
            min-height: 100vh;
        }

        .header-section {
            margin-bottom: 30px;
        }

        .header-section h2 {
            font-family: 'Montserrat', sans-serif;
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .breadcrumb {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .breadcrumb a {
            color: var(--primary-light);
            text-decoration: none;
        }

        /* Form Container */
        .form-container {
            background: var(--card-white);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            max-width: 900px;
        }

        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.1rem;
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Custom Form Grid */
        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-family: 'Open Sans', sans-serif;
            font-size: 0.95rem;
            color: var(--text-dark);
            background-color: #fff;
            transition: border-color 0.3s;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-light);
        }

        hr {
            border: 0;
            height: 1px;
            background: #e0e0e0;
            margin: 30px 0;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        /* Buttons */
        .action-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-submit {
            background: var(--primary-light);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 0.95rem;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
        }

        .btn-cancel {
            background: transparent;
            color: #757575;
            border: 1px solid #ccc;
            padding: 11px 25px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-cancel:hover {
            background: #f5f5f5;
            color: #333;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-grid-2, .form-grid-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h4><i class="fas fa-clinic-medical"></i> HMS ADMIN</h4>
        <div style="margin-top: 20px;">
            <a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="manage_doctors.php" class="active"><i class="fas fa-user-md"></i> Manage Doctors</a>
            <a href="manage_patients.php"><i class="fas fa-user-injured"></i> Manage Patients</a>
            <a href="manage_appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
            
            <a href="../logout.php" class="logout-link"><i class="fas fa-power-off"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header-section">
            <h2>Add New Doctor</h2>
            <div class="breadcrumb">
                <a href="admin_dashboard.php">Dashboard</a> / Add Doctor
            </div>
        </div>

        <div class="form-container">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="add_doctor.php" method="POST">
                
                <h5 class="section-title"><i class="fas fa-info-circle"></i> Personal & Login Information</h5>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="Dr. Kamal Perera" required>
                    </div>
                    <div class="form-group">
                        <label>Email (Username)</label>
                        <input type="email" name="email" class="form-control" placeholder="kamal@hms.com" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Set a password" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX">
                    </div>
                </div>

                <hr>
                
                <h5 class="section-title"><i class="fas fa-stethoscope"></i> Professional Details</h5>
                
                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Specialization</label>
                        <input type="text" name="specialization" class="form-control" placeholder="Cardiologist" required>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department" class="form-select" required>
                            <option value="">Select Department</option>
                            <option value="OPD">OPD</option>
                            <option value="Cardiology">Cardiology</option>
                            <option value="Neurology">Neurology</option>
                            <option value="Pediatrics">Pediatrics</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Experience (Years)</label>
                        <input type="number" name="experience" class="form-control" placeholder="5">
                    </div>
                </div>

                <div class="action-container">
                    <button type="submit" name="add_doctor" class="btn-submit">
                        <i class="fas fa-plus"></i> Register Doctor
                    </button>
                    <a href="manage_doctors.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>