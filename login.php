<?php
// Session start
session_start();

// Database connection
require_once 'config/db.php';

$error = "";

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // User search in database
        $sql = "SELECT * FROM users WHERE email = :email AND password = :password";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email, 'password' => $password]);
        $user = $stmt->fetch();

        if ($user) {
            // Save user to session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: admin/admin_dashboard.php");
            } elseif ($user['role'] == 'doctor') {
                header("Location: doctor/doctor_dashboard.php");
            } else {
                header("Location: patient/patient_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid Email or Password!";
        }
    } else {
        $error = "Please fill all fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Southern Lanka HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1b5e20;
            --secondary: #4caf50;
            --accent: #a5d6a7;
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
            /* Added background image with overlay */
            background: linear-gradient(rgba(18, 41, 19, 0.7), rgba(18, 41, 19, 0.7)), url('image/b1.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            /* Made background slightly transparent to show the image */
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(5px);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            text-align: center;
        }

        .login-header i {
            color: var(--secondary);
            margin-bottom: 15px;
        }

        .login-header h2 {
            font-family: 'Montserrat', sans-serif;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .login-header p {
            color: #555;
            font-size: 0.85rem;
            margin-bottom: 30px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-container i {
            position: absolute;
            left: 15px;
            color: var(--secondary);
        }

        .form-control {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 0.95rem;
            outline: none;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: var(--secondary);
        }

        .error-msg {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
            text-align: left;
        }

        .btn-login {
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
            margin-top: 10px;
        }

        .btn-login:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }

        .login-footer {
            margin-top: 25px;
            font-size: 0.9rem;
        }

        .login-footer a {
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
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <i class="fas fa-hand-holding-medical fa-3x"></i>
        <h2>Welcome Back</h2>
        <p>Southern Lanka Hospital Management</p>
    </div>

    <?php if ($error != ""): ?>
        <div class="error-msg">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label class="form-label">Email Address</label>
            <div class="input-container">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" class="form-control" placeholder="admin@gmail.com" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-container">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="form-control" placeholder="********" required>
            </div>
        </div>

        <button type="submit" name="login" class="btn-login">LOGIN</button>
        
        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Register Now</a></p>
            <a href="index.php" class="back-home"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </form>
</div>

</body>
</html>