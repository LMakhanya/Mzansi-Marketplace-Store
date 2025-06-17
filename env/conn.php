<?php
include $_SERVER["DOCUMENT_ROOT"] . '/vendor/autoload.php';

$projectRoot = realpath($_SERVER["DOCUMENT_ROOT"] . '/../'); // one level up
$dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? throw new Exception('DB_HOST is not set');
$db = $_ENV['DB_NAME'] ?? throw new Exception('DB_NAME is not set');
$user = $_ENV['DB_USER'] ?? throw new Exception('DB_USER is not set');
$password = $_ENV['DB_PASSWORD'] ?? throw new Exception('DB_PASSWORD is not set');

$logPath = $_SERVER["DOCUMENT_ROOT"] . "/logs/";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
   /*  die("Connection failed: " . $conn->connect_error); */
    logError('Connection Failed: ' . $conn->connect_error);
    exit;
}

function logError($message)
{
    global $logPath;
    $timestamp = (new DateTime())->modify('+2 hours')->format('Y-m-d H:i');
    $logMessage = "$timestamp - $message\n";
    file_put_contents($logPath . "log_Connection.log", $logMessage, FILE_APPEND);
}
