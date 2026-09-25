<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get Report ID from URL
if (!isset($_GET['report_id']) || empty($_GET['report_id'])) {
    die("Report ID missing!");
}

$report_id = $_GET['report_id'];

// Dynamically check columns for names
function getNameColumn($pdo, $table) {
    try {
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('full_name', $columns)) return "full_name";
        if (in_array('name', $columns)) return "name";
        if (in_array('first_name', $columns) && in_array('last_name', $columns)) return "CONCAT(first_name, ' ', last_name)";
        if (in_array('first_name', $columns)) return "first_name";
        if (in_array('username', $columns)) return "username";
    } catch (Exception $e) {}
    return null;
}

$patientCol = getNameColumn($pdo, 'patients');
$userCol = getNameColumn($pdo, 'users');
$doctorCol = getNameColumn($pdo, 'doctors');

// Build patient name query & Join
$patientJoin = "LEFT JOIN patients p ON mr.patient_id = p.id";

if ($patientCol && $patientCol !== 'id') {
    $pSelect = (strpos($patientCol, 'CONCAT') !== false) ? "CONCAT(p.first_name, ' ', p.last_name)" : "p.{$patientCol}";
} else if ($userCol) {
    // Join users table via user_id or direct patient_id
    $patientJoin .= " LEFT JOIN users pu ON p.user_id = pu.id OR mr.patient_id = pu.id";
    $pSelect = (strpos($userCol, 'CONCAT') !== false) ? "CONCAT(pu.first_name, ' ', pu.last_name)" : "pu.{$userCol}";
} else {
    $pSelect = "p.id";
}

// Build doctor name selection (Check doctors table first, then fallback to users table)
$dSelect = "d.id";
$doctorJoin = "LEFT JOIN doctors d ON mr.doctor_id = d.id";

if ($doctorCol && $doctorCol !== 'id') {
    $dSelect = (strpos($doctorCol, 'CONCAT') !== false) ? "CONCAT(d.first_name, ' ', d.last_name)" : "d.{$doctorCol}";
} else if ($userCol) {
    // Join users table via user_id or direct doctor_id
    $doctorJoin .= " LEFT JOIN users u ON d.user_id = u.id OR mr.doctor_id = u.id";
    $dSelect = (strpos($userCol, 'CONCAT') !== false) ? "CONCAT(u.first_name, ' ', u.last_name)" : "u.{$userCol}";
}

// Fetch Report Details
$query = "SELECT mr.*, 
                 {$pSelect} as patient_name, 
                 {$dSelect} as doctor_name
          FROM medical_reports mr
          {$patientJoin}
          {$doctorJoin}
          WHERE mr.id = :report_id";

$stmt = $pdo->prepare($query);
$stmt->execute(['report_id' => $report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    die("Report not found!");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Medical Report - #<?php echo $report['id']; ?></title>
    
    <!-- Font Awesome & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark-green: #0d2310;   /* Deep Dark Green Background */
            --accent-green: #1b431e;    /* Dark Emerald Accent */
            --bright-green: #2e7d32;    /* Medical Green for Actions */
            --card-white: #fcfdfe;      /* Crisp Light Printable Card */
            --section-bg: #f2f7f2;      /* Light Subtle Green Box */
            --text-dark: #122213;       /* Dark Text for Readability */
            --text-muted: #4e6b50;      /* Muted Text */
            --border-color: #d8e5d8;    /* Soft Border */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Open Sans', sans-serif; 
            background-color: var(--bg-dark-green); 
            padding: 40px 20px; 
            color: var(--text-dark); 
            min-height: 100vh;
        }

        .invoice-box { 
            max-width: 850px; 
            margin: auto; 
            padding: 45px; 
            background: var(--card-white); 
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.35); 
            border-radius: 16px; 
            border: 1px solid var(--border-color);
        }

        /* Header Styling */
        .header { 
            text-align: center; 
            border-bottom: 2px dashed var(--accent-green); 
            padding-bottom: 20px; 
            margin-bottom: 30px; 
        }

        .header h1 { 
            margin: 0; 
            color: var(--accent-green); 
            font-family: 'Montserrat', sans-serif;
            font-size: 26px; 
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header p { 
            margin: 6px 0 0 0; 
            color: var(--text-muted); 
            font-size: 13px; 
            font-weight: 600;
        }

        .header .report-title {
            margin-top: 12px;
            display: inline-block;
            background: var(--accent-green);
            color: #ffffff;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        /* Details Table */
        .details-table { 
            width: 100%; 
            margin-bottom: 30px; 
            border-collapse: collapse; 
            background: var(--section-bg);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .details-table td { 
            padding: 14px 18px; 
            vertical-align: top; 
            font-size: 14px;
            line-height: 1.6;
        }

        .details-table strong {
            color: var(--accent-green);
            font-family: 'Montserrat', sans-serif;
        }

        /* Report Sections */
        .report-section { 
            background: var(--section-bg); 
            padding: 20px; 
            border-left: 4px solid var(--accent-green); 
            margin-bottom: 20px; 
            border-radius: 8px; 
            border-top: 1px solid #e2ebe2;
            border-right: 1px solid #e2ebe2;
            border-bottom: 1px solid #e2ebe2;
        }

        .report-section h4 { 
            margin-top: 0; 
            margin-bottom: 10px;
            color: var(--accent-green); 
            font-family: 'Montserrat', sans-serif;
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .report-section h4 i {
            color: var(--bright-green);
        }

        .report-section p {
            margin: 0;
            font-size: 14px;
            line-height: 1.6;
            color: #1a2e1c;
        }

        /* Status Tags */
        .status-tag { 
            display: inline-block; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-weight: 700; 
            font-size: 11px; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pending { 
            background: #fff3e0; 
            color: #e65100; 
            border: 1px solid #ffe0b2;
        }

        .paid { 
            background: #e8f5e9; 
            color: #2e7d32; 
            border: 1px solid #a5d6a7;
        }

        /* Bill Amount */
        .bill-amount-box {
            margin-top: 30px; 
            border-top: 2px solid var(--border-color); 
            padding-top: 18px; 
            text-align: right; 
        }

        .bill-amount-box h3 {
            margin: 0; 
            color: var(--accent-green);
            font-family: 'Montserrat', sans-serif;
            font-size: 20px;
            font-weight: 700;
        }

        /* Signatures */
        .signature-container {
            margin-top: 60px; 
            display: flex; 
            justify-content: space-between;
        }

        .signature-box {
            text-align: center; 
            width: 200px; 
            border-top: 1px solid var(--accent-green); 
            padding-top: 8px; 
        }

        .signature-box small {
            color: var(--text-muted);
            font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            text-transform: uppercase;
            font-size: 11px;
        }

        /* Button Styling */
        .btn-container { 
            text-align: center; 
            margin-top: 35px; 
        }

        .btn-print { 
            background: var(--bright-green); 
            color: #ffffff; 
            border: none; 
            padding: 12px 28px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: 700; 
            font-family: 'Montserrat', sans-serif;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print:hover { 
            background: var(--accent-green); 
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }

        /* Print Settings */
        @media print {
            body { 
                background: white; 
                padding: 0; 
            }
            .invoice-box { 
                box-shadow: none; 
                border: none; 
                padding: 0; 
                width: 100%; 
                max-width: 100%;
            }
            .btn-container { 
                display: none; 
            }
        }
    </style>
</head>
<body>

<div class="invoice-box">
    <div class="header">
        <h1>Southern Lanka Hospital</h1>
        <p>Medical Center & Diagnostic Laboratory | Contact: +94 91 222 3344</p>
        <div class="report-title">
            OFFICIAL MEDICAL REPORT & BILL
        </div>
    </div>

    <table class="details-table">
        <tr>
            <td width="50%">
                <strong>Patient Name:</strong> <?php echo htmlspecialchars(!empty($report['patient_name']) ? $report['patient_name'] : 'N/A'); ?><br>
                <strong>Doctor Name:</strong> Dr. <?php echo htmlspecialchars(!empty($report['doctor_name']) ? $report['doctor_name'] : 'N/A'); ?>
            </td>
            <td width="50%" style="text-align: right;">
                <strong>Report ID:</strong> #<?php echo htmlspecialchars($report['id']); ?><br>
                <strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?><br>
                <strong>Status:</strong> 
                <span class="status-tag <?php echo $report['status'] === 'paid' ? 'paid' : 'pending'; ?>">
                    <?php echo strtoupper(str_replace('_', ' ', $report['status'])); ?>
                </span>
            </td>
        </tr>
    </table>

    <div class="report-section">
        <h4><i class="fas fa-stethoscope"></i> Diagnosis / Medical Summary</h4>
        <p><?php echo nl2br(htmlspecialchars($report['diagnosis'] ?? $report['summary'] ?? 'No diagnosis details provided.')); ?></p>
    </div>

    <?php if (!empty($report['prescribed_tests'])): ?>
    <div class="report-section">
        <h4><i class="fas fa-flask"></i> Prescribed Tests / Laboratory Requests</h4>
        <p><?php echo nl2br(htmlspecialchars($report['prescribed_tests'])); ?></p>
    </div>
    <?php endif; ?>

    <?php if (isset($report['amount'])): ?>
    <div class="bill-amount-box">
        <h3>Total Bill Amount: Rs. <?php echo number_format($report['amount'], 2); ?></h3>
    </div>
    <?php endif; ?>

    <div class="signature-container">
        <div class="signature-box">
            <small>Doctor Signature</small>
        </div>
        <div class="signature-box">
            <small>Admin Signature / Stamp</small>
        </div>
    </div>

    <div class="btn-container">
        <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Print Medical Report</button>
    </div>
</div>

</body>
</html>