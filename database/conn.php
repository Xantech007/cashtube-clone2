<?php
$host = 'sql300.infinityfree.com'; // Database host
$dbname = 'if0_40492226_clone_tasktube2'; // Db name
$username = 'if0_40492226'; // Database username
$password = '2HvhwmvsxjJ';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
