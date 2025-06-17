<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>

<?php
// Database connection setting
$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
include($ENV_IPATH . "conn.php");
include($ENV_IPATH . "env.php");

// Initialize the database connection
$pdo = initializeDatabase();

function logError1($message)
{
    global $logPath;
    $timestamp = (new DateTime())->modify('+2 hours')->format('Y-m-d H:i');
    $logMessage = "$timestamp - $message\n";
    file_put_contents($logPath . "CreateAccoun.log", $logMessage, FILE_APPEND);
}

function generate_customerID()
{
    $characters = '0123456789abcdelmABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    for ($i = 0; $i < 6; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }

    return $randomString;
}

// Check if the customerID exists in the database
function check_customerID_exists($newcustomerID, $conn)
{
    $stmt = $conn->prepare("SELECT customerID FROM customer WHERE customerID = ? LIMIT 1");
    $stmt->bind_param("s", $newcustomerID);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->num_rows > 0;
}

function generate_unique_customerID($conn)
{
    do {
        $customerID = generate_customerID();
    } while (check_customerID_exists($customerID, $conn));

    return $customerID;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $username = $_POST['email'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $currentPage = isset($_POST['currentPage']) ? $_POST['currentPage'] : null;

    // Check if required fields are not ""
    if ($name !== "" && $surname !== "" && $username !== "" && $phone !== "" && $email !== "" && $password !== "") {
        $customerID = generate_unique_customerID($conn);
        $year = date('Y');
        $newcustomerID = 'kv' . $year . '_' . $customerID;

        $createCustSQL = "INSERT INTO customer (customerID, FirstName, LastName, username, Phone, Email, Password, Created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $createCustSTMT = $conn->prepare($createCustSQL);
        $createCustSTMT->bind_param("sssssss", $newcustomerID, $name, $surname, $username, $phone, $email, $password);

        if ($createCustSTMT->execute()) {
            header("Location: /");
            logError1("Customer: $customerID added");
        } else {
            die("Execution failed: " . $createCustSTMT->error);
        }
        $createCustSTMT->close();
        exit();
    } else {
        logError1("Empty fields.");

        // Login failed, redirect back to the login page
        header("Location: $currentPage?error=1");
        exit();
    }
}
?>