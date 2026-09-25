<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

// Get Report ID
if (!isset($_GET['report_id']) || empty($_GET['report_id'])) {
    header("Location: admin_pending_reports.php");
    exit();
}

$report_id = $_GET['report_id'];

// Function to dynamically detect name column
function getNameColumn($pdo, $table) {
    try {
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('name', $columns)) return "name";
        if (in_array('full_name', $columns)) return "full_name";
        if (in_array('first_name', $columns) && in_array('last_name', $columns)) return "CONCAT(first_name, ' ', last_name)";
        if (in_array('first_name', $columns)) return "first_name";
        if (in_array('username', $columns)) return "username";
    } catch (Exception $e) {}
    return null;
}

$patientCol = getNameColumn($pdo, 'patients');
$userCol = getNameColumn($pdo, 'users');
$doctorCol = getNameColumn($pdo, 'doctors');

// Build patient name query
$pSelect = "p.id";
if ($patientCol) {
    $pSelect = (strpos($patientCol, 'CONCAT') !== false) ? "CONCAT(p.first_name, ' ', p.last_name)" : "p.{$patientCol}";
}

// Build doctor name query (Check doctors table first, fallback to users table)
$dSelect = "d.id";
$doctorJoin = "LEFT JOIN doctors d ON mr.doctor_id = d.id";

if ($doctorCol && $doctorCol !== 'id') {
    $dSelect = (strpos($doctorCol, 'CONCAT') !== false) ? "CONCAT(d.first_name, ' ', d.last_name)" : "d.{$doctorCol}";
} else if ($userCol) {
    // Join users table via user_id or direct doctor_id
    $doctorJoin .= " LEFT JOIN users u ON d.user_id = u.id OR mr.doctor_id = u.id";
    $dSelect = (strpos($userCol, 'CONCAT') !== false) ? "CONCAT(u.first_name, ' ', u.last_name)" : "u.{$userCol}";
}

// Process Form Submission (Payment Completion)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_payment'])) {
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);

    if ($amount === false || $amount <= 0) {
        $error = "Please enter a valid billing amount.";
    } else {
        try {
            // Update medical report status and amount
            $update_sql = "UPDATE medical_reports 
                           SET status = 'paid', amount = :amount 
                           WHERE id = :id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                'amount' => $amount,
                'id' => $report_id
            ]);

            $message = "Payment processed successfully!";
            
            // Redirect to print page after 2 seconds
            header("refresh:2;url=print_report.php?report_id=" . $report_id);
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch Report Details
$query = "SELECT mr.*, 
                 {$pSelect} as patient_name, 
                 {$dSelect} as doctor_name
          FROM medical_reports mr
          LEFT JOIN patients p ON mr.patient_id = p.id
          {$doctorJoin}
          WHERE mr.id = :report_id";

$stmt = $pdo->prepare($query);
$stmt->execute(['report_id' => $report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    die("Medical Report not found!");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payment | Admin</title>
    
    <!-- Google Fonts & Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark-green: #0d2310;   /* Deep Dark Green Background */
            --accent-green: #1b431e;    /* Dark Emerald Accent */
            --bright-green: #2e7d32;    /* Medical Green Button */
            --bright-green-hover: #215924; /* Button Hover State */
            --card-white: #fcfdfe;      /* Light Off-White Container */
            --section-bg: #f2f7f2;      /* Soft Light Greenish Box */
            --text-dark: #122213;       /* Main Dark Text */
            --text-muted: #5e7a60;      /* Muted Labels Text */
            --border-color: #d8e5d8;    /* Border Accent */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Open Sans', sans-serif; 
            background-color: var(--bg-dark-green); 
            padding: 50px 20px; 
            color: var(--text-dark); 
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .card { 
            background: var(--card-white); 
            padding: 40px; 
            border-radius: 16px; 
            max-width: 520px; 
            width: 100%; 
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.35); 
            border: 1px solid var(--border-color);
        }

        .back-link { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px; 
            color: var(--text-muted); 
            text-decoration: none; 
            font-weight: 600;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--accent-green);
        }

        h2 { 
            color: var(--accent-green); 
            margin-top: 0; 
            font-family: 'Montserrat', sans-serif;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        hr {
            border: none;
            height: 2px;
            background: var(--border-color);
            margin: 15px 0 25px 0;
        }

        /* Information Details Container */
        .info-container {
            background: var(--section-bg);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
        }

        .info-group { 
            margin-bottom: 12px; 
            font-size: 14.5px; 
            color: var(--text-dark); 
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-group:last-child {
            margin-bottom: 0;
        }

        .info-group strong { 
            color: var(--accent-green); 
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
        }

        /* Alert Styling */
        .alert-success { 
            background: #e8f5e9; 
            color: #2e7d32; 
            padding: 12px 16px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            border: 1px solid #a5d6a7;
            font-size: 14px;
        }

        .alert-error { 
            background: #ffebee; 
            color: #c62828; 
            padding: 12px 16px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            border: 1px solid #ef9a9a;
            font-size: 14px;
        }

        /* Form Inputs */
        .form-group { 
            margin-top: 15px; 
        }

        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: var(--accent-green);
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        input[type="number"] { 
            width: 100%; 
            padding: 12px 15px; 
            border: 1.5px solid var(--border-color); 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 16px; 
            font-family: 'Open Sans', sans-serif;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background-color: var(--section-bg);
            color: var(--text-dark);
            font-weight: 600;
        }

        input[type="number"]:focus {
            border-color: var(--bright-green);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.15);
            background-color: #ffffff;
        }

        .btn-submit { 
            background: var(--bright-green); 
            color: white; 
            border: none; 
            padding: 14px 20px; 
            width: 100%; 
            border-radius: 8px; 
            font-size: 15px; 
            font-weight: 700; 
            font-family: 'Montserrat', sans-serif;
            cursor: pointer; 
            margin-top: 20px; 
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover { 
            background: var(--accent-green); 
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>

<div class="card">
    <a href="admin_pending_reports.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Pending Reports</a>
    <h2><i class="fas fa-cash-register"></i> Process Bill Payment</h2>
    <hr>

    <?php if ($message): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?><br>
            <small style="opacity: 0.85;">Redirecting to printable report/invoice...</small>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="info-container">
        <div class="info-group">
            <strong>Report ID:</strong>
            <span>#<?php echo htmlspecialchars($report['id']); ?></span>
        </div>
        <div class="info-group">
            <strong>Patient Name:</strong>
            <span><?php echo htmlspecialchars($report['patient_name'] ?? 'N/A'); ?></span>
        </div>
        <div class="info-group">
            <strong>Doctor Name:</strong>
            <span>Dr. <?php echo htmlspecialchars($report['doctor_name'] ?? 'N/A'); ?></span>
        </div>
        <div class="info-group">
            <strong>Current Status:</strong>
            <span style="color: #e65100; font-weight: bold; background: #fff3e0; padding: 2px 10px; border-radius: 12px; font-size: 12px; border: 1px solid #ffe0b2;">
                <?php echo strtoupper($report['status']); ?>
            </span>
        </div>
    </div>

    <form method="POST" action="">
        <div class="form-group">
            <label for="amount">Billing Amount (LKR):</label>
            <div class="input-wrapper">
                <input type="number" step="0.01" id="amount" name="amount" placeholder="e.g. 1500.00" value="<?php echo htmlspecialchars($report['amount'] ?? ''); ?>" required>
            </div>
        </div>
        <button type="submit" name="complete_payment" class="btn-submit">
            <i class="fas fa-check"></i> Complete & Mark Paid
        </button>
    </form>
</div>

</body>
</html>