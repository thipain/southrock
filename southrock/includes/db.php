<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'southrock';

$conn = new mysqli($host, $user, $pass, $dbname);

// Verifica conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Define charset para UTF-8
$conn->set_charset("utf8");
?>