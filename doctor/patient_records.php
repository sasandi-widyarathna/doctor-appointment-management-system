<?php
session_start();
require_once '../config/db.php';

// Doctor check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];

// Fetch the list of unique patients who have booked appointments with this doctor
$sql = "SELECT DISTINCT u.id, u.full_name, u.email, u.phone, p.gender, p.dob, p.address
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        JOIN patients p ON a.patient_id = p.user_id
        WHERE a.doctor_id = ?
        ORDER BY u.full_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$doctor_id]);
$patients = $stmt->fetchAll();

// Function to calculate age based on Date of Birth
function calculateAge($dob) {
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    return $birthDate->diff($today)->y;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Records | Southern Lanka HMS</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        }

        /* Patient Grid */
        .patient-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .patient-card {
            background: var(--card-white);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .patient-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.15);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f1f1;
        }

        .avatar-circle {
            width: 55px;
            height: 55px;
            background: var(--bg-light);
            color: var(--primary-dark);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            font-weight: 700;
            border: 2px solid var(--primary-light);
        }

        .patient-info h3 {
            font-size: 1rem;
            color: var(--primary-dark);
            margin-bottom: 2px;
        }

        .patient-id {
            font-size: 0.75rem;
            color: #888;
            font-weight: 600;
        }

        .details-list {
            list-style: none;
            margin-bottom: 20px;
        }

        .details-list li {
            font-size: 0.85rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #444;
        }

        .details-list i {
            color: var(--primary-light);
            width: 16px;
        }

        .btn-history {
            display: block;
            width: 100%;
            text-align: center;
            padding: 10px;
            background: var(--primary-dark);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-history:hover {
            background: var(--primary-light);
        }

        /* Empty State */
        .empty-container {
            text-align: center;
            padding: 60px;
            background: var(--card-white);
            border-radius: 20px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h4><i class="fas fa-user-md"></i> DOCTOR PORTAL</h4>
        <div style="margin-top: 20px;">
            <a href="doctor_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="my_appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
            <a href="patient_records.php" class="active"><i class="fas fa-user-injured"></i> Patient Records</a>
            <a href="my_profile.php" class="active"><i class="fas fa-user-md"></i> My Profile</a>
            
            <a href="../logout.php" class="logout-link"><i class="fas fa-power-off"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header-section">
            <h2>My Patients</h2>
            <p style="color: var(--text-muted);">List of all patients who have consulted you.</p>
        </div>

        <div class="patient-grid">
            <?php if (count($patients) > 0): ?>
                <?php foreach ($patients as $row): ?>
                    <div class="patient-card">
                        <div class="card-header">
                            <div class="avatar-circle">
                                <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                            </div>
                            <div class="patient-info">
                                <h3><?php echo htmlspecialchars($row['full_name']); ?></h3>
                                <span class="patient-id">ID: #P-<?php echo $row['id']; ?></span>
                            </div>
                        </div>

                        <ul class="details-list">
                            <li>
                                <i class="fas fa-venus-mars"></i> 
                                <?php echo ucfirst($row['gender']); ?> (<?php echo calculateAge($row['dob']); ?> Years Old)
                            </li>
                            <li>
                                <i class="fas fa-phone"></i> 
                                <?php echo htmlspecialchars($row['phone']); ?>
                            </li>
                            <li>
                                <i class="fas fa-map-marker-alt"></i> 
                                <span style="display: inline-block; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($row['address']); ?>
                                </span>
                            </li>
                        </ul>

                        <a href="view_history.php?patient_id=<?php echo $row['id']; ?>" class="btn-history">
                            <i class="fas fa-notes-medical"></i> View Medical History
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-container" style="grid-column: 1 / -1;">
                    <i class="fas fa-folder-open fa-3x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>No patient records found in your database.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>