<?php
session_start();
require_once '../config/db.php';

// Doctor role verification
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// 1. Profile Update Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doctor'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $experience = trim($_POST['experience'] ?? '');

    try {
        $pdo->beginTransaction();

        // Update Users Table (Including Email)
        $stmt1 = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt1->execute([$full_name, $email, $phone, $user_id]);

        // Check if record exists in Doctors Table
        $checkStmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
        $checkStmt->execute([$user_id]);

        if ($checkStmt->rowCount() > 0) {
            // Update existing record
            $stmt2 = $pdo->prepare("UPDATE doctors SET specialization = ?, experience = ? WHERE user_id = ?");
            $stmt2->execute([$specialization, $experience, $user_id]);
        } else {
            // Insert new record if it does not exist
            $stmt2 = $pdo->prepare("INSERT INTO doctors (user_id, specialization, experience) VALUES (?, ?, ?)");
            $stmt2->execute([$user_id, $specialization, $experience]);
        }

        $pdo->commit();
        $_SESSION['user_name'] = $full_name;
        $success = "Profile updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// 2. Fetch current user data (using LEFT JOIN)
$sql = "SELECT u.full_name, u.email, u.phone, 
               COALESCE(d.specialization, 'General') AS specialization, 
               COALESCE(d.experience, '') AS experience 
        FROM users u 
        LEFT JOIN doctors d ON u.id = d.user_id 
        WHERE u.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    die("User record not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Doctor Portal</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-dark: #0a260c;
            --primary-light: #4caf50;
            --bg-light: #f1f8e9;
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
            height: 100vh;
            overflow: hidden; /* Prevent page scrollbar */
        }

        /* Sidebar Layout */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary-dark);
            position: fixed;
            padding: 25px 0;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .sidebar h4 {
            font-family: 'Montserrat', sans-serif;
            color: white;
            font-size: 1.05rem;
            text-align: center;
            margin-bottom: 30px;
            padding: 0 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar a {
            padding: 14px 25px;
            display: block;
            color: #a5d6a7;
            text-decoration: none;
            transition: 0.3s;
            font-size: 0.9rem;
            border-left: 4px solid transparent;
        }

        .sidebar a:hover, .sidebar a.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--primary-light);
        }

        .logout-link {
            margin-top: auto;
            color: #ff8a65 !important;
            font-weight: 600;
        }

        /* Main Center Layout */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 20px;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center; /* Vertically centered */
        }

        /* Card Layout - Balanced Size */
        .profile-container {
            width: 100%;
            max-width: 720px;
            background: var(--card-white);
            border-radius: 14px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        /* Profile Header */
        .profile-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1b4d22 100%);
            color: white;
            padding: 25px 30px;
            text-align: center;
        }

        .profile-header h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5rem;
            margin: 10px 0 4px 0;
        }

        .specialization-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Form Body */
        .profile-body {
            padding: 30px;
        }

        /* Alert Box */
        .alert {
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        /* Grid Setup */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .full-width {
            grid-column: span 2;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--primary-dark);
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-family: 'Open Sans', sans-serif;
            font-size: 0.9rem;
            color: var(--text-dark);
            background-color: #fff;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-light);
        }

        .btn-container {
            text-align: center;
            margin-top: 25px;
        }

        .btn-update {
            background: var(--primary-light);
            color: white;
            border: none;
            padding: 11px 30px;
            font-size: 0.92rem;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-update:hover {
            background: var(--primary-dark);
        }

        @media (max-height: 700px) {
            .profile-header { padding: 18px 20px; }
            .profile-body { padding: 20px; }
            .form-grid { gap: 12px; }
            .btn-container { margin-top: 15px; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h4><i class="fas fa-user-md"></i> DOCTOR PORTAL</h4>
        <div style="margin-top: 10px;">
            <a href="doctor_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="my_appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
            <a href="patient_records.php"><i class="fas fa-user-injured"></i> Patient Records</a>
            <a href="my_profile.php" class="active"><i class="fas fa-user-md"></i> My Profile</a>
            
            <a href="../logout.php" class="logout-link"><i class="fas fa-power-off"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="profile-container">
            
            <div class="profile-header">
                <div>
                    <i class="fas fa-user-md fa-3x"></i>
                </div>
                <h2> <?php echo htmlspecialchars($doctor['full_name'] ?? ''); ?></h2>
                <span class="specialization-badge"><?php echo htmlspecialchars($doctor['specialization'] ?? 'N/A'); ?></span>
            </div>

            <div class="profile-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="my_profile.php" method="POST">
                    <div class="form-grid">
                        
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($doctor['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($doctor['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Specialization</label>
                            <input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($doctor['specialization'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Experience / Professional Bio</label>
                            <textarea name="experience" class="form-control" rows="3"><?php echo htmlspecialchars($doctor['experience'] ?? ''); ?></textarea>
                        </div>

                    </div>

                    <div class="btn-container">
                        <button type="submit" name="update_doctor" class="btn-update">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>

</body>
</html>