<?php
// db.php
// Database connection file. Include this in other PHP files.

$host = 'localhost';  // Adjust if hosted elsewhere (e.g., Heroku: use $_ENV vars)
$dbname = 'dbwflrgqytndbx';
$username = 'uasxxqbztmxwm';
$password = 'wss863wqyhal';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
