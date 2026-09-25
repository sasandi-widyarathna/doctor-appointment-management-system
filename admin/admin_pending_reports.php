<?php
session_start();
require_once '../config/db.php';

// Check if admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Function to dynamically check name column in tables
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
    // If patient name resides in users table linked via user_id
    $patientJoin .= " LEFT JOIN users pu ON p.user_id = pu.id OR mr.patient_id = pu.id";
    $pSelect = (strpos($userCol, 'CONCAT') !== false) ? "CONCAT(pu.first_name, ' ', pu.last_name)" : "pu.{$userCol}";
} else {
    $pSelect = "p.id";
}

// Build doctor name query & Join
$dSelect = "d.id";
$doctorJoin = "LEFT JOIN doctors d ON mr.doctor_id = d.id";

if ($doctorCol && $doctorCol !== 'id') {
    $dSelect = (strpos($doctorCol, 'CONCAT') !== false) ? "CONCAT(d.first_name, ' ', d.last_name)" : "d.{$doctorCol}";
} else if ($userCol) {
    $doctorJoin .= " LEFT JOIN users u ON d.user_id = u.id OR mr.doctor_id = u.id";
    $dSelect = (strpos($userCol, 'CONCAT') !== false) ? "CONCAT(u.first_name, ' ', u.last_name)" : "u.{$userCol}";
}

// Query for Pending Reports
$pendingQuery = "SELECT mr.id as report_id, 
                        mr.created_at, 
                        {$pSelect} as patient_name, 
                        {$dSelect} as doctor_name, 
                        mr.status 
                 FROM medical_reports mr
                 {$patientJoin}
                 {$doctorJoin}
                 WHERE mr.status = 'pending_payment'
                 ORDER BY mr.created_at DESC";

$stmt = $pdo->prepare($pendingQuery);
$stmt->execute();
$pendingReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query for Paid / Completed Reports
$paidQuery = "SELECT mr.id as report_id, 
                     mr.created_at, 
                     {$pSelect} as patient_name, 
                     {$dSelect} as doctor_name, 
                     mr.status 
              FROM medical_reports mr
              {$patientJoin}
              {$doctorJoin}
              WHERE mr.status != 'pending_payment'
              ORDER BY mr.created_at DESC";

$stmtPaid = $pdo->prepare($paidQuery);
$stmtPaid->execute();
$paidReports = $stmtPaid->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Medical Reports & Billing</title>
    
    <!-- Google Fonts & Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #0a260c;           /* Main Deep Dark Green Canvas */
            --card-light: #f7faf7;         /* Soft Light Soft-Green/White Container */
            --accent-green: #2e7d32;       /* Primary Forest Green */
            --accent-hover: #1b5e20;
            --accent-blue: #0288d1;        /* Print Button Accent Blue */
            --blue-hover: #01579b;
            --text-dark: #1b331d;
            --text-muted: #536e55;
            --border-color: #e0ebd0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Open Sans', sans-serif; 
            background-color: var(--bg-dark); 
            background-image: radial-gradient(circle at 50% 0%, #123e15 0%, var(--bg-dark) 75%);
            padding: 40px 20px; 
            color: var(--text-dark); 
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .container { 
            background: var(--card-light); 
            padding: 35px 40px; 
            border-radius: 16px; 
            max-width: 1100px; 
            width: 100%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); 
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .back-link { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px; 
            color: var(--accent-green); 
            text-decoration: none; 
            font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .back-link:hover {
            color: var(--accent-hover);
            transform: translateX(-3px);
        }

        h2 { 
            color: var(--text-dark); 
            font-family: 'Montserrat', sans-serif;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-color);
        }

        .section-title-paid {
            margin-top: 40px;
        }

        /* Search Bar Styling */
        .search-container {
            position: relative;
            margin-bottom: 25px;
            max-width: 450px;
        }

        .search-container input {
            width: 100%;
            padding: 12px 16px 12px 42px;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
            background: #ffffff;
            color: var(--text-dark);
        }

        .search-container input:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 8px rgba(46, 125, 50, 0.3);
        }

        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Modern Table Styling */
        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td { 
            padding: 16px; 
            text-align: left; 
            font-size: 14px;
        }

        th { 
            background: #eaf2eb; 
            color: var(--text-dark); 
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }

        td {
            border-bottom: 1px solid #f0f5f0;
            color: #2c3e2e;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f4f8f4;
        }

        /* Action Buttons */
        .action-btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-pay, .btn-print, .btn-reprint { 
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-size: 13px; 
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .btn-pay { 
            background: var(--accent-green); 
            color: #ffffff; 
        }

        .btn-pay:hover { 
            background: var(--accent-hover); 
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(46, 125, 50, 0.25);
        }

        .btn-print { 
            background: var(--accent-blue); 
            color: #ffffff; 
        }

        .btn-print:hover { 
            background: var(--blue-hover); 
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(2, 136, 209, 0.25);
        }

        .btn-reprint {
            background: #455a64;
            color: #ffffff;
        }

        .btn-reprint:hover {
            background: #263238;
            transform: translateY(-1px);
        }

        .badge-paid {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--text-muted);
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="admin_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    
    <!-- Search Bar -->
    <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" id="reportSearch" onkeyup="filterReports()" placeholder="Search by Report ID, Patient Name, Doctor, Date...">
    </div>

    <!-- Section 1: Pending Billing Reports -->
    <h2><i class="fas fa-file-invoice-dollar" style="color: var(--accent-green);"></i> Doctor Reports Pending Billing</h2>
    
    <div class="table-responsive">
        <table id="pendingTable">
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Patient Name</th>
                    <th>Doctor Name</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pendingReports) > 0): ?>
                    <?php foreach ($pendingReports as $row): ?>
                    <tr>
                        <td><strong>#<?php echo htmlspecialchars($row['report_id']); ?></strong></td>
                        <td><?php echo htmlspecialchars(!empty($row['patient_name']) ? $row['patient_name'] : 'N/A'); ?></td>
                        <td>Dr. <?php echo htmlspecialchars(!empty($row['doctor_name']) ? $row['doctor_name'] : 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="process_payment.php?report_id=<?php echo $row['report_id']; ?>" class="btn-pay">
                                    <i class="fas fa-credit-card"></i> Pay Bill
                                </a>
                                <a href="print_report.php?report_id=<?php echo $row['report_id']; ?>" target="_blank" class="btn-print">
                                    <i class="fas fa-print"></i> Print Report
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-records-row"><td colspan="5" class="empty-state">No pending reports found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Section 2: Completed / Paid Reports History -->
    <h2 class="section-title-paid"><i class="fas fa-history" style="color: var(--accent-blue);"></i> Completed Payments & Printed Reports History</h2>
    
    <div class="table-responsive">
        <table id="paidTable">
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Patient Name</th>
                    <th>Doctor Name</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($paidReports) > 0): ?>
                    <?php foreach ($paidReports as $row): ?>
                    <tr>
                        <td><strong>#<?php echo htmlspecialchars($row['report_id']); ?></strong></td>
                        <td><?php echo htmlspecialchars(!empty($row['patient_name']) ? $row['patient_name'] : 'N/A'); ?></td>
                        <td>Dr. <?php echo htmlspecialchars(!empty($row['doctor_name']) ? $row['doctor_name'] : 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td><span class="badge-paid"><i class="fas fa-check-circle"></i> Paid / Done</span></td>
                        <td>
                            <div class="action-btns">
                                <a href="print_report.php?report_id=<?php echo $row['report_id']; ?>" target="_blank" class="btn-reprint">
                                    <i class="fas fa-print"></i> Re-Print Report
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-records-row"><td colspan="6" class="empty-state">No completed reports found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- JavaScript for Live Search Filter -->
<script>
function filterReports() {
    const input = document.getElementById('reportSearch');
    const filter = input.value.toLowerCase();
    
    const filterTable = (tableId) => {
        const table = document.getElementById(tableId);
        const trs = table.getElementsByTagName('tr');

        for (let i = 1; i < trs.length; i++) {
            const row = trs[i];
            
            // Skip no records placeholder
            if (row.classList.contains('no-records-row')) continue;

            const textContent = row.textContent || row.innerText;
            
            if (textContent.toLowerCase().indexOf(filter) > -1) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        }
    };

    filterTable('pendingTable');
    filterTable('paidTable');
}
</script>

</body>
</html>