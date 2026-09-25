<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in (Admin or Doctor)
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'doctor'])) {
    header("Location: ../login.php");
    exit();
}

// Patient ID check
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: patients.php");
    exit();
}

$patient_id = $_GET['id'];

try {
    // 1. Fetch Patient Personal Details
    $patient_sql = "SELECT p.*, u.full_name, u.email, u.phone, u.address, u.created_at 
                    FROM patients p 
                    JOIN users u ON p.user_id = u.id 
                    WHERE p.user_id = ?";
    $patient_stmt = $pdo->prepare($patient_sql);
    $patient_stmt->execute([$patient_id]);
    $patient = $patient_stmt->fetch();

    if (!$patient) {
        die("Patient not found.");
    }

    // 2. Fetch Patient Appointments History
    $app_sql = "SELECT a.*, u.full_name AS doctor_name, d.specialization 
                FROM appointments a 
                JOIN doctors d ON a.doctor_id = d.user_id 
                JOIN users u ON d.user_id = u.id 
                WHERE a.patient_id = ? 
                ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    $app_stmt = $pdo->prepare($app_sql);
    $app_stmt->execute([$patient_id]);
    $appointments = $app_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile | Southern Lanka HMS</title>
    
    <!-- Font Awesome & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-dark: #0a260c;
            --primary-light: #4caf50;
            --bg-light: #f1f8e9;
            --card-white: #ffffff;
            --text-dark: #122913;
            --text-muted: #558b2f;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg-light); font-family: 'Open Sans', sans-serif; color: var(--text-dark); display: flex; }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width); height: 100vh; background: var(--primary-dark);
            position: fixed; padding: 25px 0; display: flex; flex-direction: column;
            box-shadow: 4px 0 15px rgba(0,0,0,0.3); z-index: 1000;
        }
        .sidebar h4 { font-family: 'Montserrat', sans-serif; color: white; font-size: 1.1rem; text-align: center; margin-bottom: 40px; padding: 0 15px; }
        .sidebar a { padding: 15px 25px; display: block; color: #a5d6a7; text-decoration: none; transition: 0.3s; font-size: 0.9rem; border-left: 5px solid transparent; }
        .sidebar a:hover, .sidebar a.active { color: white; background: rgba(255, 255, 255, 0.1); border-left: 5px solid var(--primary-light); }
        .logout-link { margin-top: auto; color: #ff8a65 !important; font-weight: 600; }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 40px; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { background: var(--primary-dark); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.3s; }
        .btn-back:hover { background: var(--primary-light); }

        .profile-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }

        .card { background: var(--card-white); padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card h3 { font-family: 'Montserrat', sans-serif; color: var(--primary-dark); margin-bottom: 20px; font-size: 1.2rem; border-bottom: 2px solid var(--bg-light); padding-bottom: 10px; }

        .info-group { margin-bottom: 15px; }
        .info-group label { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; display: block; text-transform: uppercase; }
        .info-group p { font-size: 1rem; color: var(--text-dark); font-weight: 600; margin-top: 3px; }

        /* Table Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; font-size: 0.9rem; }
        th { background: var(--bg-light); color: var(--primary-dark); font-weight: 700; }
        tr:nth-child(even) { background-color: #fafafa; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; display: inline-block; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h4><i class="fas fa-hospital-user"></i> HMS PORTAL</h4>
        
        <a href="patients.php" class="active"><i class="fas fa-user-injured"></i> Patients</a>
        
        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header-actions">
            <h2><i class="fas fa-id-card" style="color: var(--primary-light);"></i> Patient Details</h2>
            <a href="javascript:history.back()" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        </div>

        <div class="profile-grid">
            <!-- Left Side: Basic Info -->
            <div class="card">
                <h3><i class="fas fa-user"></i> Personal Profile</h3>
                
                <div class="info-group">
                    <label>Full Name</label>
                    <p><?php echo htmlspecialchars($patient['full_name']); ?></p>
                </div>
                
                <div class="info-group">
                    <label>Email Address</label>
                    <p><?php echo htmlspecialchars($patient['email']); ?></p>
                </div>

                <div class="info-group">
                    <label>Phone Number</label>
                    <p><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></p>
                </div>

                <div class="info-group">
                    <label>Address</label>
                    <p><?php echo htmlspecialchars($patient['address'] ?? 'N/A'); ?></p>
                </div>

                <div class="info-group">
                    <label>Blood Group</label>
                    <p><?php echo htmlspecialchars($patient['blood_group'] ?? 'Not Specified'); ?></p>
                </div>

                <div class="info-group">
                    <label>Registered Date</label>
                    <p><?php echo date('M d, Y', strtotime($patient['created_at'])); ?></p>
                </div>
            </div>

            <!-- Right Side: Appointments History -->
            <div>
                <div class="card">
                    <h3><i class="fas fa-history"></i> Appointment History</h3>
                    
                    <?php if (count($appointments) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Date & Time</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $app): ?>
                                    <tr>
                                        <td>
                                             <?php echo htmlspecialchars($app['doctor_name']); ?><br>
                                            <small style="color: var(--text-muted);"><?php echo htmlspecialchars($app['specialization']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($app['appointment_date'])); ?><br>
                                            <small><?php echo date('h:i A', strtotime($app['appointment_time'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($app['reason'] ?: 'N/A'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($app['status']); ?>">
                                                <?php echo ucfirst($app['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: var(--text-muted);">No appointment records found for this patient.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>