<?php
// Session start
session_start();

// Database connection
require_once 'config/db.php';

$success = "";
$error = "";

if (isset($_POST['register'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];

    if (!empty($full_name) && !empty($email) && !empty($password)) {
        try {
            $sql1 = "INSERT INTO users (full_name, email, password, role, phone) VALUES (:name, :email, :pass, 'patient', :phone)";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                'name' => $full_name,
                'email' => $email,
                'pass' => $password, 
                'phone' => $phone
            ]);

            $user_id = $pdo->lastInsertId();

            $sql2 = "INSERT INTO patients (user_id, dob, gender, address) VALUES (:u_id, :dob, :gender, :address)";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                'u_id' => $user_id,
                'dob' => $dob,
                'gender' => $gender,
                'address' => $address
            ]);

            $success = "Registration Successful! You can now <a href='login.php' style='color: #4caf50; font-weight: bold;'>Login</a>";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "This email is already registered!";
            } else {
                $error = "Something went wrong: " . $e->getMessage();
            }
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
    <title>Patient Registration | Southern Lanka HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1b5e20;        /* Deep Forest Green */
            --secondary: #4caf50;      /* Medical Green */
            --white: #ffffff;
            --dark: #122913;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            /* Background image with overlay */
            background: linear-gradient(rgba(18, 41, 19, 0.75), rgba(18, 41, 19, 0.75)), 
                        url('image/b1.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reg-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 650px;
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        .reg-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .reg-header h2 {
            font-family: 'Montserrat', sans-serif;
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .reg-header p {
            color: #555;
            font-size: 0.9rem;
        }

        /* Form Layout */
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 250px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--dark);
            margin-bottom: 6px;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 0.9rem;
            outline: none;
            transition: 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary);
            background: #fff;
        }

        textarea.form-control {
            resize: none;
        }

        /* Alerts */
        .alert {
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            border-left: 5px solid;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-color: #c62828;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-color: #2e7d32;
        }

        /* Button */
        .btn-reg {
            width: 100%;
            padding: 14px;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .btn-reg:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        /* Footer */
        .reg-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 0.9rem;
        }

        .reg-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }

        .back-home {
            display: inline-block;
            margin-top: 15px;
            color: #777 !important;
            font-weight: 400 !important;
            font-size: 0.8rem;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>

<div class="reg-card">
    <div class="reg-header">
        <h2>Patient Registration</h2>
        <p>Southern Lanka Hospital Management System</p>
    </div>

    <!-- Status Messages -->
    <?php if ($error != ""): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success != ""): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" placeholder="Kamal Perera" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="kamal@example.com" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="********" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="dob" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label">Home Address</label>
            <textarea name="address" class="form-control" rows="2" placeholder="Your permanent address"></textarea>
        </div>

        <button type="submit" name="register" class="btn-reg">REGISTER NOW</button>
        
        <div class="reg-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
            <a href="index.php" class="back-home"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </form>
</div>

</body>
</html>