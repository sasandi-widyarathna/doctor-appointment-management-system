<?php

// Include database connection

require_once 'config/db.php';

?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Southern Lanka Hospital | Home</title>

    

    <!-- Font Awesome (For Icons) -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Google Fonts -->

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

    

    <style>

        /* CSS Variables - Colors are controlled from here */

        :root {

            --primary: #1b5e20;        /* Deep Forest Green */

            --secondary: #4caf50;      /* Medical Green */

            --accent: #a5d6a7;         /* Soft Mint Green */

            --dark: #122913;           /* Very Dark Green for Footer */

            --light-bg: #f1f8e9;       /* Light green background (For Nav & Body) */

            --white: #ffffff;

            --shadow: 0 4px 15px rgba(0,0,0,0.06);

        }



        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

        }



        body {

            font-family: 'Open Sans', sans-serif;

            color: #333;

            line-height: 1.6;

            background-color: var(--light-bg); /* Entire page becomes light green */

        }



        h1, h2, h3, h4 {

            font-family: 'Montserrat', sans-serif;

        }



        .container {

            max-width: 1200px;

            margin: 0 auto;

            padding: 0 20px;

        }



        /* Navigation - Changed the background here to light green */

        nav {

            background: var(--light-bg); 

            height: 80px;

            display: flex;

            align-items: center;

            position: sticky;

            top: 0;

            z-index: 1000;

            box-shadow: var(--shadow);

            border-bottom: 1px solid rgba(0,0,0,0.05);

        }



        nav .container {

            display: flex;

            justify-content: space-between;

            align-items: center;

            width: 100%;

        }



        .navbar-brand {

            font-weight: 800;

            font-size: 1.4rem;

            color: var(--primary);

            text-decoration: none;

            display: flex;

            align-items: center;

        }



        .nav-links {

            display: flex;

            list-style: none;

            align-items: center;

        }



        .nav-links li {

            margin-left: 25px;

        }



        .nav-links a {

            text-decoration: none;

            color: #2e442e; /* Dark greenish text */

            font-weight: 600;

            font-size: 0.95rem;

            transition: 0.3s;

        }



        .nav-links a:hover {

            color: var(--secondary);

        }



        .btn-nav-login {

            border: 2px solid var(--primary);

            padding: 8px 25px;

            border-radius: 50px;

            color: var(--primary) !important;

            transition: 0.3s;

        }



        .btn-nav-login:hover {

            background: var(--primary);

            color: var(--white) !important;

        }



        .btn-nav-reg {

            background: var(--primary);

            padding: 10px 25px;

            border-radius: 50px;

            color: var(--white) !important;

            box-shadow: 0 4px 10px rgba(27, 94, 32, 0.2);

        }



        /* Hero Section */

        .hero-section {

            background: linear-gradient(rgba(27, 94, 32, 0.85), rgba(76, 175, 80, 0.6)), url('assets/img/hospital-bg.jpg');

            background-size: cover;

            background-position: center;

            height: 80vh;

            color: var(--white);

            display: flex;

            align-items: center;

            text-align: left;

            border-radius: 0 0 40px 40px; /* Given a beautiful shape */

        }



        .hero-content h1 {

            font-size: 3.5rem;

            font-weight: 800;

            line-height: 1.2;

            margin-bottom: 20px;

        }



        .hero-content p {

            font-size: 1.1rem;

            margin-bottom: 40px;

            max-width: 600px;

            opacity: 0.95;

        }



        .hero-btns {

            display: flex;

            gap: 15px;

        }



        .btn-main {

            padding: 15px 35px;

            border-radius: 50px;

            font-weight: 700;

            text-decoration: none;

            text-transform: uppercase;

            font-size: 0.85rem;

            transition: 0.3s;

            display: inline-block;

        }



        .btn-green {

            background: var(--primary);

            color: var(--white);

        }



        .btn-outline-white {

            border: 2px solid var(--white);

            color: var(--white);

        }



        .btn-main:hover {

            transform: translateY(-3px);

            box-shadow: 0 5px 15px rgba(0,0,0,0.2);

        }



        /* Services Section */

        .services {

            padding: 80px 0;

            text-align: center;

        }



        .section-title {

            font-size: 2.5rem;

            color: var(--primary);

            margin-bottom: 50px;

        }



        .services-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));

            gap: 30px;

        }



        .feature-card {

            background: var(--white); /* Card stands out in white */

            padding: 40px;

            border-radius: 20px;

            transition: 0.4s;

            box-shadow: 0 2px 10px rgba(0,0,0,0.03);

        }



        .feature-card:hover {

            transform: translateY(-10px);

            box-shadow: var(--shadow);

            border-bottom: 4px solid var(--secondary);

        }



        .icon-box {

            width: 70px;

            height: 70px;

            background: linear-gradient(135deg, var(--primary), var(--secondary));

            color: white;

            border-radius: 15px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto 20px;

            font-size: 1.5rem;

        }



        .feature-card h4 {

            color: var(--primary);

            margin-bottom: 15px;

        }



        /* Footer */

        footer {

            background: var(--dark);

            color: #adb5ad;

            padding: 70px 0 30px;

            margin-top: 50px;

        }



        .footer-row {

            display: flex;

            flex-wrap: wrap;

            justify-content: space-between;

            margin-bottom: 40px;

        }



        .footer-col {

            flex: 1;

            min-width: 250px;

            margin-bottom: 30px;

        }



        .footer-col h5 {

            color: var(--white);

            margin-bottom: 20px;

            font-size: 1.1rem;

        }



        .footer-col ul {

            list-style: none;

        }



        .footer-col a {

            color: #889488;

            text-decoration: none;

            transition: 0.3s;

        }



        .footer-col a:hover {

            color: var(--accent);

        }



        .footer-bottom {

            border-top: 1px solid #2e442e;

            padding-top: 30px;

            text-align: center;

            font-size: 0.85rem;

        }



        /* Responsive */

        @media (max-width: 768px) {

            .nav-links { display: none; }

            .hero-content h1 { font-size: 2.3rem; }

        }

    </style>

</head>

<body>



    <!-- Navigation Bar -->

    <nav>

        <div class="container">

            <a class="navbar-brand" href="index.php">

                <i class="fas fa-hand-holding-medical" style="margin-right: 10px;"></i>SOUTHERN LANKA HMS

            </a>

            <ul class="nav-links">

                <li><a href="index.php">Home</a></li>

                <li><a href="#services">Services</a></li>

                <li><a href="#about">About Us</a></li>

                <li><a href="login.php" class="btn-nav-login">Login</a></li>

                <li><a href="register.php" class="btn-nav-reg">Register</a></li>

            </ul>

        </div>

    </nav>



    <!-- Hero Section -->

    <header class="hero-section">

        <div class="container">

            <div class="hero-content">

                <h1>Advanced Healthcare <br><span style="color: var(--accent);">Solution for Matara</span></h1>

                <p>Experience a seamless way to connect with the best doctors. Book appointments, manage health records, and get expert care at Southern Lanka Hospital.</p>

                <div class="hero-btns">

                    <a href="login.php" class="btn-main btn-green">Book Appointment</a>

                    <a href="#services" class="btn-main btn-outline-white">View Services</a>

                </div>

            </div>

        </div>

    </header>



    <!-- Medical Services -->

    <section id="services" class="services container">

        <h6 style="color: var(--secondary); letter-spacing: 2px; text-transform: uppercase; font-weight: 700; font-size: 0.8rem; margin-bottom: 10px;">Why Choose Us</h6>

        <h2 class="section-title">Our Medical Services</h2>

        

        <div class="services-grid">

            <div class="feature-card">

                <div class="icon-box"><i class="fas fa-calendar-check"></i></div>

                <h4>Easy Channeling</h4>

                <p>Book your favorite specialist in just a few clicks without waiting in queues.</p>

            </div>

            <div class="feature-card">

                <div class="icon-box"><i class="fas fa-user-md"></i></div>

                <h4>Expert Doctors</h4>

                <p>Over 50+ specialized doctors are ready to provide you the best medical care.</p>

            </div>

            <div class="feature-card">

                <div class="icon-box"><i class="fas fa-microscope"></i></div>

                <h4>Lab Reports</h4>

                <p>Access and download your laboratory reports directly from your patient dashboard.</p>

            </div>

        </div>

    </section>



    <!-- Footer -->

    <footer>

        <div class="container">

            <div class="footer-row">

                <div class="footer-col">

                    <h5>Southern Lanka Hospital (HMS)</h5>

                    <p>Providing world-class healthcare services to the people of Southern Province with compassion and excellence.</p>

                </div>

                <div class="footer-col" style="padding-left: 50px;">

                    <h5>Quick Links</h5>

                    <ul>

                        <li><a href="#">Home</a></li>

                        <li><a href="login.php">Login</a></li>

                        <li><a href="register.php">Register</a></li>

                    </ul>

                </div>

                <div class="footer-col">

                    <h5>Contact Info</h5>

                    <p><i class="fas fa-map-marker-alt" style="color: var(--secondary); margin-right: 10px;"></i> Matara, Sri Lanka</p>

                    <p style="margin-top: 10px;"><i class="fas fa-phone" style="color: var(--secondary); margin-right: 10px;"></i> +94 41 123 4567</p>

                </div>

            </div>

            <div class="footer-bottom">

                <p>&copy; 2026 Southern Lanka HMS. All Rights Reserved.</p>

            </div>

        </div>

    </footer>



</body>

</html>