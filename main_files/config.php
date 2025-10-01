<?php
// Database connezction (using PDO)
$host     = "localhost";
$dbname   = "sungura_enterprises";
$username = "root";
$password = ""; 

    try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "";

    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
?>
