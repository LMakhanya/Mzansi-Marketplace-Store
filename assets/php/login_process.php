<?php
// Database connection setting
$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
include($ENV_IPATH . "conn.php");
include($ENV_IPATH . "env.php");

// Initialize the database connection
$pdo = initializeDatabase();

session_start();

function log_to_file($message)
{
    global $logPath;
    $timestamp = (new DateTime())->modify('+2 hours')->format('Y-m-d H:i');
    $logMessage = "$timestamp - $message\n";
    file_put_contents($logPath . "logfile.log", "$logMessage", FILE_APPEND);
}

// Log the start of a login attempt
log_to_file("Login attempt started.");

$pdo = initializeDatabase();

// Get user input
$username = isset($_POST['username']) ? $_POST['username'] : null;
$password = isset($_POST['password']) ? $_POST['password'] : null;
$currentPage = isset($_POST['currentPage']) ? $_POST['currentPage'] : null;

// Log the received username (not the password for security reasons)
log_to_file("Received username: $username.");

// Validate the user's login credentials
$sql = "SELECT * FROM customer WHERE (username = :username OR Email = :email) AND Password = :password";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->bindParam(':email', $username, PDO::PARAM_STR);
$stmt->bindParam(':password', $password, PDO::PARAM_STR); // Consider hashing the password for security
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    //Login successful, set session variables
    $_SESSION["customerID"] = $result["CustomerID"];
    $customerID = $result["CustomerID"];
    $_SESSION["username"] = $result["username"];
    $_SESSION["FirstName"] = $result["FirstName"];
    $_SESSION["LastName"] = $result["LastName"];
    $_SESSION["Address"] = $result["Address"];
    $_SESSION["userEmail"] = $result["Email"];
    $_SESSION["Phone"] = $result["phone"];

 
    // Log the successful login
    log_to_file("Login successful for username: $username.");

    // Redirect to a protected page (e.g., dashboard.php)
    header("Location: /user/");
    exit();
} else {
    // Log the failed login attempt
    log_to_file("Login failed for username: $username.");

    // Login failed, redirect back to the login page
    header("Location: $currentPage?error=1");
    exit();
}

// Close the database connection
$getQuantitySTMT->close();
$conn->close();

// Log the end of the login attempt
log_to_file("Login attempt ended.");