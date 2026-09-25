<?php
$host = 'localhost';
$db_name = 'southern_lanka_hms';
$username = 'root';
$password = ''; // Default empty in XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>