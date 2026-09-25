<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$success = "";
$error = "";

if (isset($_POST['process_payment'])) {
    $appointment_id = $_POST['appointment_id'];
    $amount = $_POST['amount'];
    $patient_id = $_SESSION['user_id'];

    if (!empty($appointment_id) && !empty($amount)) {
        try {
            $sql = "INSERT INTO payments (appointment_id, patient_id, amount, payment_method, status) 
                    VALUES (:apt_id, :p_id, :amount, 'Card/Online', 'completed')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'apt_id' => $appointment_id,
                'p_id' => $patient_id,
                'amount' => $amount
            ]);

            // Update Appointment Status to 'confirmed'
            $updateSql = "UPDATE appointments SET status = 'confirmed' WHERE id = :apt_id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute(['apt_id' => $appointment_id]);

            $success = "Payment Successful! Your appointment is confirmed.";
        } catch (PDOException $e) {
            $error = "Payment failed: " . $e->getMessage();
        }
    } else {
        $error = "Please fill all details!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make Payment | Southern Lanka HMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Open Sans', sans-serif; background: #f1f8e9; padding: 40px; }
        .pay-card { background: white; max-width: 500px; margin: 0 auto; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .btn-pay { background: #4caf50; color: white; padding: 12px; border: none; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .btn-pay:hover { background: #1b5e20; }
        .form-control { width: 100%; padding: 10px; margin: 10px 0 20px 0; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
        .alert-error { background: #ffebee; color: #c62828; }
    </style>
</head>
<body>
    <div class="pay-card">
        <h2><i class="fas fa-credit-card"></i> Make Payment</h2>
        <hr><br>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        
        <form method="POST">
            <label>Appointment ID</label>
            <input type="number" name="appointment_id" class="form-control" placeholder="Enter Appointment ID" required>
            
            <label>Fee (LKR)</label>
            <input type="number" name="amount" class="form-control" value="2500.00" readonly>
            
            <label>Card Number (Demo)</label>
            <input type="text" class="form-control" placeholder="4532 XXXX XXXX 8900" required>
            
            <button type="submit" name="process_payment" class="btn-pay">Pay Now</button>
        </form>
    </div>
</body>
</html>