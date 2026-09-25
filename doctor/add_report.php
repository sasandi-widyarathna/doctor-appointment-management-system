<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$msg = "";
$appointment_id = $_GET['apt_id'] ?? null;
$patient_id = $_GET['patient_id'] ?? null;

if (isset($_POST['submit_report'])) {
    $apt_id = !empty($_POST['appointment_id']) ? $_POST['appointment_id'] : null;
    $p_id = $_POST['patient_id'];
    $doc_id = $_SESSION['user_id'];
    $diagnosis = $_POST['diagnosis'];
    $prescription = $_POST['prescription'];
    $remarks = $_POST['remarks'];

    try {
        $pdo->beginTransaction();

        // Status is sent to the Admin Panel as 'pending_payment'
        $stmt = $pdo->prepare("INSERT INTO medical_reports (appointment_id, patient_id, doctor_id, diagnosis, prescription, remarks, status) 
                                VALUES (?, ?, ?, ?, ?, ?, 'pending_payment')");
        $stmt->execute([$apt_id, $p_id, $doc_id, $diagnosis, $prescription, $remarks]);

        if ($apt_id) {
            $stmt_apt = $pdo->prepare("UPDATE appointments SET status = 'pending_billing' WHERE id = ?");
            $stmt_apt->execute([$apt_id]);
        }

        $pdo->commit();

        $msg = "<div class='alert alert-success'>
                    <i class='fas fa-check-circle'></i> Medical Report successfully sent to Admin for billing and printing.
                </div>";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medical Report</title>
    
    <!-- Google Fonts & Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #0a260c;          /* Dark Green Background */
            --primary-accent: #4caf50;   /* Vibrant Medical Accent Green */
            --accent-hover: #66bb6a;
            --card-white: #ffffff;
            --text-dark: #0a260c;
            --text-muted: #4e6e50;
            --input-bg: #f8faf7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Open Sans', sans-serif; 
            background-color: var(--bg-dark); 
            background-image: radial-gradient(circle at 50% 0%, #133e16 0%, var(--bg-dark) 70%);
            padding: 50px 20px; 
            color: #ffffff; 
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .form-box { 
            background: var(--card-white); 
            padding: 35px 40px; 
            border-radius: 16px; 
            max-width: 650px; 
            width: 100%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35); 
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-dark);
        }

        h2 { 
            color: var(--text-dark); 
            margin-top: 0; 
            font-family: 'Montserrat', sans-serif;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h2 i {
            color: var(--primary-accent);
        }

        hr {
            border: none;
            height: 2px;
            background: #e8f0e8;
            margin: 15px 0 25px 0;
        }

        /* Form Controls */
        .form-group { 
            margin-bottom: 20px; 
        }

        label { 
            display: block; 
            font-weight: 600; 
            margin-bottom: 8px; 
            color: var(--text-dark);
            font-size: 14px;
        }

        input[type="number"],
        input[type="text"],
        textarea { 
            width: 100%; 
            padding: 12px 15px; 
            border: 1.5px solid #c8e6c9; 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 15px;
            font-family: 'Open Sans', sans-serif;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background-color: var(--input-bg);
            color: var(--text-dark);
        }

        input:focus, 
        textarea:focus {
            border-color: var(--primary-accent);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
            background-color: #ffffff;
        }

        textarea {
            resize: vertical;
        }

        /* Buttons & Navigation */
        .btn { 
            background: var(--bg-dark); 
            color: #ffffff; 
            padding: 14px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 700; 
            font-family: 'Montserrat', sans-serif;
            font-size: 15px;
            width: 100%; 
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(10, 38, 12, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn:hover { 
            background: var(--primary-accent); 
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(76, 175, 80, 0.35);
        }

        .back-link { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px; 
            color: var(--text-muted); 
            text-decoration: none; 
            font-weight: 600;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--bg-dark);
        }

        /* Alert Styling */
        .alert { 
            padding: 14px 16px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success { 
            background: #e8f5e9; 
            color: #2e7d32; 
            border: 1px solid #a5d6a7; 
        }

        .alert-danger { 
            background: #ffebee; 
            color: #c62828; 
            border: 1px solid #ef9a9a; 
        }
    </style>
</head>
<body>

<div class="form-box">
    <h2><i class="fas fa-notes-medical"></i> Create Medical Report</h2>
    <hr>
    
    <?php echo $msg; ?>

    <form method="POST">
        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment_id ?? ''); ?>">
        
        <?php if (!$patient_id): ?>
            <div class="form-group">
                <label>Patient ID</label>
                <input type="number" name="patient_id" required placeholder="Enter Patient ID">
            </div>
        <?php else: ?>
            <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patient_id); ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Diagnosis / Symptoms</label>
            <textarea name="diagnosis" rows="3" required placeholder="Enter diagnosis details..."></textarea>
        </div>

        <div class="form-group">
            <label>Prescription & Dosage</label>
            <textarea name="prescription" rows="4" required placeholder="Enter prescribed medicine..."></textarea>
        </div>

        <div class="form-group">
            <label>Remarks / Doctor Advice</label>
            <input type="text" name="remarks" placeholder="Special instructions...">
        </div>

        <button type="submit" name="submit_report" class="btn"><i class="fas fa-paper-plane"></i> Submit & Send to Admin</button>
        <a href="doctor_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </form>
</div>

</body>
</html>