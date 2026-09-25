<?php
session_start();
require_once '../config/db.php';

// Check if the user is a patient
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// 1. Profile update logic
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = trim($_POST['gender']);
    $address = trim($_POST['address']);

    try {
        $pdo->beginTransaction();

        // Updating the users table (Full Name, Email & Phone)
        $sql1 = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([$full_name, $email, $phone, $user_id]);

        // Updating the patients table (Gender & Address)
        $sql2 = "UPDATE patients SET gender = ?, address = ? WHERE user_id = ?";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$gender, $address, $user_id]);

        $pdo->commit();
        
        $_SESSION['user_name'] = $full_name;
        $success = "Your profile has been updated successfully!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Something went wrong! " . $e->getMessage();
    }
}

// 2. Fetch patient data
$sql = "SELECT u.full_name, u.email, u.phone, p.dob, p.gender, p.address 
        FROM users u 
        JOIN patients p ON u.id = p.user_id 
        WHERE u.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Southern Lanka HMS</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-dark: #0a260c;    /* Sidebar Deep Green */
            --primary-light: #4caf50;   /* Medical Green */
            --bg-light: #f1f8e9;        /* Main BG Light Green */
            --card-white: #ffffff;
            --text-dark: #122913;
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
            overflow: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary-dark);
            position: fixed;
            padding: 22px 0;
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
            text-transform: uppercase;
        }

        .sidebar a {
            padding: 13px 22px;
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

        /* Main Content - Centered */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 25px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Medium Profile Card */
        .profile-card {
            width: 100%;
            max-width: 720px;
            background: var(--card-white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-dark), #1b431e);
            color: white;
            padding: 24px;
            text-align: center;
        }

        .user-avatar {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 2rem;
            border: 3px solid var(--primary-light);
        }

        .profile-header h2 {
            font-size: 1.45rem;
            font-family: 'Montserrat', sans-serif;
            margin-bottom: 3px;
        }

        .profile-header p {
            font-size: 0.85rem;
            opacity: 0.85;
        }

        /* Form Body */
        .form-body {
            padding: 28px 32px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--primary-dark);
        }

        input, select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
            transition: 0.3s;
            background-color: #fff;
        }

        input:focus, select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.15);
        }

        .read-only-bg {
            background-color: #f4f8f4;
            color: #666;
            cursor: not-allowed;
        }

        .btn-save {
            width: 100%;
            background: var(--primary-dark);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 20px;
        }

        .btn-save:hover {
            background: var(--primary-light);
        }

        /* Alerts */
        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #4caf50; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ef5350; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h4><i class="fas fa-heartbeat"></i> PATIENT PORTAL</h4>
        <div style="margin-top: 10px;">
            <a href="patient_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="book_appointment.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
            <a href="my_appointments.php"><i class="fas fa-list-alt"></i> My Appointments</a>
            <a href="my_profile.php" class="active"><i class="fas fa-user-circle"></i> My Profile</a>
            
            <a href="../logout.php" class="logout-link"><i class="fas fa-power-off"></i> Sign Out</a>
        </div>
    </div>

    <div class="main-content">
        <div class="profile-card">
            <div class="profile-header">
                <div class="user-avatar"><i class="fas fa-user"></i></div>
                <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <p>Patient ID: #P-<?php echo $user_id; ?></p>
            </div>

            <div class="form-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <form action="my_profile.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" required>
                                <option value="male" <?php echo (strtolower($user['gender']) == 'male') ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo (strtolower($user['gender']) == 'female') ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo (strtolower($user['gender']) == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth (Locked)</label>
                            <input type="text" class="read-only-bg" value="<?php echo $user['dob']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Residential Address</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="fas fa-save"></i> Update Profile Information
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>