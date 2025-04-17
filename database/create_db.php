<?php

$host = '127.0.0.1';
$port = '3306';
$username = 'root';
$password = '';

try {
  $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Create the database
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `Loan_Management_System`;");
  echo "Database created successfully!\n";
} catch (PDOException $e) {
  die("DB ERROR: " . $e->getMessage());
}
