<?php

session_start();

require_once '../config/db.php';



// Patient check

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {

    header("Location: ../login.php");

    exit();

}



$success = "";

$error = "";



// 1. Database eken doctorslawa ganna

$doc_sql = "SELECT d.user_id, u.full_name, d.specialization 

            FROM doctors d 

            JOIN users u ON d.user_id = u.id";

$doc_stmt = $pdo->query($doc_sql);

$doctors = $doc_stmt->fetchAll();



// 2. Form eka submit kalama logic එක

if (isset($_POST['book_now'])) {

    $patient_id = $_SESSION['user_id'];

    $doctor_id = $_POST['doctor_id'];

    $date = $_POST['appointment_date'];

    $time = $_POST['appointment_time'];

    $reason = $_POST['reason'];



    if (!empty($doctor_id) && !empty($date) && !empty($time)) {

        try {

            $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) 

                    VALUES (?, ?, ?, ?, ?, 'pending')";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([$patient_id, $doctor_id, $date, $time, $reason]);

            

            $success = "Appointment booked successfully! Please wait for admin confirmation.";

        } catch (PDOException $e) {

            $error = "Booking failed: " . $e->getMessage();

        }

    } else {

        $error = "Please fill in all required fields.";

    }

}

?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Book Appointment | Southern Lanka HMS</title>

    

    <!-- Font Awesome -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Google Fonts -->

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

            padding: 0 15px;

            text-transform: uppercase;

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



        /* Main Content */

        .main-content {

            margin-left: var(--sidebar-width);

            flex: 1;

            padding: 40px;

            display: flex;

            justify-content: center;

            align-items: flex-start;

        }



        .booking-container {

            width: 100%;

            max-width: 700px;

            background: var(--card-white);

            padding: 40px;

            border-radius: 20px;

            box-shadow: 0 10px 30px rgba(0,0,0,0.05);

        }



        .booking-container h2 {

            font-family: 'Montserrat', sans-serif;

            color: var(--primary-dark);

            margin-bottom: 10px;

            display: flex;

            align-items: center;

            gap: 10px;

        }



        .booking-container p {

            color: var(--text-muted);

            margin-bottom: 30px;

        }



        /* Form Styling */

        .form-group {

            margin-bottom: 20px;

        }



        label {

            display: block;

            font-weight: 600;

            margin-bottom: 8px;

            font-size: 0.9rem;

            color: var(--primary-dark);

        }



        input, select, textarea {

            width: 100%;

            padding: 12px 15px;

            border: 1px solid #ddd;

            border-radius: 10px;

            font-family: inherit;

            font-size: 0.95rem;

            outline: none;

            transition: 0.3s;

        }



        input:focus, select:focus, textarea:focus {

            border-color: var(--primary-light);

            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);

        }



        .row {

            display: flex;

            gap: 20px;

        }



        .row .form-group {

            flex: 1;

        }



        .btn-submit {

            width: 100%;

            background: var(--primary-dark);

            color: white;

            padding: 15px;

            border: none;

            border-radius: 10px;

            font-size: 1rem;

            font-weight: 700;

            cursor: pointer;

            transition: 0.3s;

            margin-top: 10px;

        }



        .btn-submit:hover {

            background: var(--primary-light);

            transform: translateY(-2px);

        }



        /* Alert Messages */

        .alert {

            padding: 15px;

            border-radius: 10px;

            margin-bottom: 25px;

            font-size: 0.9rem;

        }



        .alert-success {

            background: #e8f5e9;

            color: #2e7d32;

            border: 1px solid #4caf50;

        }



        .alert-danger {

            background: #ffebee;

            color: #c62828;

            border: 1px solid #ef5350;

        }



    </style>

</head>

<body>



    <!-- Sidebar (Dark Green) -->

    <div class="sidebar">

        <h4><i class="fas fa-heartbeat"></i> PATIENT PORTAL</h4>

        <div style="margin-top: 20px;">

            <a href="patient_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>

            <a href="book_appointment.php" class="active"><i class="fas fa-calendar-plus"></i> Book Appointment</a>

            <a href="my_appointments.php"><i class="fas fa-list-alt"></i> My Appointments</a>

            

            <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>

        </div>

    </div>



    <!-- Main Content (Light Green) -->

    <div class="main-content">

        <div class="booking-container">

            <h2><i class="fas fa-notes-medical" style="color: var(--primary-light);"></i> Book Appointment</h2>

            <p>Please fill the details below to schedule your visit.</p>



            <?php if ($success): ?>

                <div class="alert alert-success">

                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>

                </div>

            <?php endif; ?>



            <?php if ($error): ?>

                <div class="alert alert-danger">

                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>

                </div>

            <?php endif; ?>



            <form action="book_appointment.php" method="POST">

                <div class="form-group">

                    <label>Select Specialist (Doctor)</label>

                    <select name="doctor_id" required>

                        <option value="">-- Choose a Doctor --</option>

                        <?php foreach ($doctors as $doc): ?>

                            <option value="<?php echo $doc['user_id']; ?>">

                                <?php echo htmlspecialchars($doc['full_name']); ?> (<?php echo htmlspecialchars($doc['specialization']); ?>)

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <div class="row">

                    <div class="form-group">

                        <label>Appointment Date</label>

                        <input type="date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>

                    </div>

                    <div class="form-group">

                        <label>Preferred Time</label>

                        <input type="time" name="appointment_time" required>

                    </div>

                </div>



                <div class="form-group">

                    <label>Reason for Visit (Optional)</label>

                    <textarea name="reason" rows="3" placeholder="Briefly describe your concern..."></textarea>

                </div>



                <button type="submit" name="book_now" class="btn-submit">

                    <i class="fas fa-paper-plane"></i> Confirm Booking

                </button>

            </form>

        </div>

    </div>



</body>

</html>