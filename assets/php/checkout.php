<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Database connection setting
$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
include($ENV_IPATH . "conn.php");
include($ENV_IPATH . "env.php");

// Initialize the database connection
$pdo = initializeDatabase();


session_start();

function logCheckoutError($message)
{
    global $logPath;
    $timestamp = (new DateTime())->modify('+2 hours')->format('Y-m-d H:i');
    $logMessage = "$timestamp - $message\n";
    file_put_contents($logPath . "log_CheckOut.log", $logMessage, FILE_APPEND);
}

$pdo = initializeDatabase();

$getItemsSQL = "SELECT * FROM bagtbl WHERE  customerID =:customerID  AND purchased = 0";
$stmt = $pdo->prepare($getItemsSQL);
$stmt->bindParam(':customerID', $customerID, PDO::PARAM_STR); // Consider hashing the password for security
$stmt->execute();

while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Login successful, set session variables
    $_SESSION["product_id"] = $result["product_id"];
    $product_id = $result["product_id"];
    echo $product_id . "<br>";

    $updateSQL = "UPDATE bagtbl SET purchased = 1 WHERE customerID = '$customerID' AND product_id = '$product_id'";
    $updatestmt = $pdo->prepare($updateSQL);
    $updatestmt->execute();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customerID = $_POST['customerID'];
    $bagIG = $_POST['b-bagID'];
    $name = $_POST['name'];
    $descr = $_POST['descr'];
    $price = $_POST['price'];
    $quantity = $_POST['b-quantity'];

    // Check if required fields are not ""
    if ($name !== "" && $descr !== "" && $price !== "" && $quantity !== "") {

        echo $customerID . "<br>";
        echo $name . "<br>";
        echo $descr . "<br>";
        echo $price . "<br>";
        echo $quantity . "<br>";

        $createOrderSQL = "INSERT INTO ordertb (customerID, OrderDate, TotalAmount) VALUES (?, NOW(),?)";
        $createOrderSTMT = $conn->prepare($createOrderSQL);
        $createOrderSTMT->bind_param("si", $customerID, $price);

        if ($createOrderSTMT->execute()) {
            header("Location: /logged/home.php");
            logError1("Customer: $customerID added");
        } else {
            die("Execution failed: " . $createOrderSTMT->error);
        }
        $createOrderSTMT->close();
        exit();
    } else {
        logError1("Empty fields.");
    }
}
