<?php
$host = 'sql211.infinityfree.com'; // Database host
$dbname = 'if0_40625983_clone_tasktube'; // Database name
$username = 'if0_40625983'; // Database username
$password = 'UV88ZXjoJbf';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
