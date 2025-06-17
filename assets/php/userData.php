<?php
if (headers_sent($file, $line)) {
    die("Headers already sent in $file on line $line");
}

// Function to log messages to a file
function log_to_file($message)
{
    global $logPath;
    $timestamp = (new DateTime())->modify('+2 hours')->format('Y-m-d H:i');
    $logMessage = "$timestamp - $message\n";
    file_put_contents($logPath . "userdata.log", $logMessage, FILE_APPEND);
}

// Generate a random 6-character customer ID
function generate_customerID()
{
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    for ($i = 0; $i < 6; $i++) {
        $index = random_int(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }

    return $randomString;
}

// Check if the customerID exists in the database
function check_customerID_exists($newcustomerID, $conn)
{
    $stmt = $conn->prepare("SELECT 1 FROM customer WHERE customerID = ? LIMIT 1");
    $stmt->bind_param("s", $newcustomerID);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Generate a unique customer ID
function generate_unique_customerID($conn)
{
    do {
        $customerID = generate_customerID();
    } while (check_customerID_exists($customerID, $conn));

    return 'MSZA' . date('y') . '_' . $customerID;
}

// Function to check if a customer exists
function customerExists($conn, $customerID)
{
    $stmt = $conn->prepare("SELECT 1 FROM customer WHERE CustomerID = ? LIMIT 1");
    $stmt->bind_param("s", $customerID);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Function to check if a customer exists
function customerType($conn, $customerID)
{
    $stmt = $conn->prepare("SELECT cust_type FROM customer WHERE CustomerID = ? LIMIT 1");
    $stmt->bind_param("s", $customerID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['cust_type']; // Return cust_type value
    } else {
        return null; // Return null if customer not found
    }

    $stmt->close(); // Close the statement (unreachable in this case)
}

// Create a new customer
function createCustomer($conn, $logPath)
{
    $visitorIP = getUserIP();
    $visitorHostName = getUserHostName($visitorIP);
    $device = getDeviceInfo();
    $customerID = generate_unique_customerID($conn);

    $insertSQL = "INSERT INTO customer (CustomerID, created_at, cust_type, device_name, ip_address, hostname, device_type) 
                  VALUES (?, NOW(), 'guest', ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSQL);
    $stmt->bind_param("sssss", $customerID, $device['name'], $visitorIP, $visitorHostName, $device['type']);

    if ($stmt->execute()) {
        log_to_file("New visitor [$customerID] inserted successfully.", $logPath);
        return $customerID;
    } else {
        log_to_file("Error inserting visitor: " . $stmt->error, $logPath);
        return null;
    }
}


$_SESSION["customerID"] = "";
$customerID  = '';

// Log visitor information to a file
log_to_file("Session CustomerID " . $_SESSION['customerID'] . "inserted successfully.", $logPath);

// Manage customer session and cookies
if (!isset($_SESSION['customerID']) || !customerExists($conn, $_SESSION['customerID'])) {
    // Get the visitor's IP
    $visitorIP = getUserIP();

    $sql = "SELECT * FROM customer WHERE ip_address = ? and cust_type = 'guest'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $visitorIP);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $_SESSION['customerID'] = createCustomer($conn, $logPath);
    } else {
        $existingVisitor = $result->fetch_assoc();

        // Store the existing customer ID in the session
        $_SESSION["customerID"]  = $existingVisitor['CustomerID'];
    }
}

if (isset($_SESSION['customerID'])) {

    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {

        $sql = "SELECT * FROM customer WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $_SESSION['userEmail']);
        $stmt->execute();
        $result = $stmt->get_result();

        $existingVisitor = $result->fetch_assoc();

        // Store the existing customer ID in the session
        $_SESSION["customerID"]  = $existingVisitor['CustomerID'];
    }
}

if (!isset($_COOKIE['customerID']) || !customerExists($conn, $_COOKIE['customerID'])) {
    setcookie('customerID', $_SESSION['customerID'], time() + (30 * 24 * 60 * 60), "/"); // 30-day cookie
}

$customerID = $_SESSION['customerID'];
