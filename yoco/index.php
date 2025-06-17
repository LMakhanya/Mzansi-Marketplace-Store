<?php


session_start();

$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
include($ENV_IPATH . "env.php");
include($ENV_IPATH . "conn.php");

// Initialize the database connection
$pdo = initializeDatabase();

// Check if the necessary parameters are present in the URL

function logPaymentError($message) // Log an error message to the log file
{
    global $logPath;

    $logFile = $logPath . '/yoco_pay_results.log'; // Adjust path as needed
    $logDir = dirname($logFile);

    // Ensure the directory exists
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true); // Create the directory with full permissions
    }

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "$timestamp - $message\n";

    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

function addCustomerAddress($conn, $tx_ref, $response_id, $houseNumber, $addressl1, $addressl2, $city, $state, $pcode)
{
    $insertSQL = "INSERT INTO customer_shipping_address (CustomerID, checkoutID, houseNumber, addressl1, addressl2, city, province, postalCode, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insertSQL);
    $stmt->bind_param("ssissssi", $tx_ref, $response_id, $houseNumber, $addressl1, $addressl2, $city, $state, $pcode);

    if ($stmt->execute()) {
        logPaymentError("Address for customer [$tx_ref] inserted successfully.\n");
        return true;
    } else {
        logPaymentError("Error inserting address: " . $stmt->error . "\n");
        return false;
    }
}

// check if method is post

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    logPaymentError("Invalid request method. Expected POST, got: " . $_SERVER["REQUEST_METHOD"]);
    http_response_code(405);
    exit;
}
if (isset($_POST['amount'])) {
    // Fetch values from the URL or set to default values if not set.
    //$tx_ref =  $_SESSION['customerID'] ? $_SESSION['customerID'] : 'default_tx_ref';
    $tx_ref = filter_input(INPUT_POST, 'customerID', FILTER_SANITIZE_EMAIL);
    $customer_email = filter_input(INPUT_POST, 'customer_email', FILTER_SANITIZE_EMAIL);
    $customer_name = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_type = filter_input(INPUT_POST, 'payment_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $bagid = filter_input(INPUT_POST, 'bagid', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $sellerid = filter_input(INPUT_POST, 'sellerid', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Set default values if null (in case of filtering issues)
    $tx_ref = $tx_ref ?? 'default_tx_ref';
    $customer_email = $customer_email ?? '';
    $customer_name = $customer_name ?? '';
    $amount = $amount !== false ? $amount : ''; // Keep numeric validation strict
    $payment_type = $payment_type ?? '';
    $bagid = $bagid ?? '';
    $sellerid = $sellerid ?? '';

    // Get Shipping Details from submission
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $addressl1 = filter_input(INPUT_POST, 'addressl1', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $addressl1 = filter_input(INPUT_POST, 'addressl1', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $addressl2 = filter_input(INPUT_POST, 'addressl2', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $pcode = filter_input(INPUT_POST, 'pcode', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $houseNumber = filter_input(INPUT_POST, 'houseno', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $shippingType = filter_input(INPUT_POST, 'shippingType', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $shippingAmnt = filter_input(INPUT_POST, 'shippingAmnt', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Set default values if null (in case of filtering issues)
    $fullname = $fullname ?? '';
    $email = $email ?? '';
    $phone = $phone ?? '';
    $addressl1 = $addressl1 ?? '';
    $addressl2 = $addressl2 ?? '';
    $city = $city ?? '';
    $state = $state ?? '';
    $pcode = $pcode ?? '';
    $houseNumber = $houseNumber ?? '';

    $shippingType = $shippingType ?? '';
    $shippingAmnt = $shippingAmnt ?? '';


    $secretKey = $_ENV['YOCO_KEY'];

    // Yoco API URL
    $url = 'https://payments.yoco.com/api/checkouts';

    // multiply the amount by 100
    $amount2 = $amount * 100;

    //$amount2 = 10 * 100; 


    // Prepare the data to be sent
    $data = json_encode([
        'amount' => $amount2,
        'currency' => 'ZAR',
        "successUrl" => "https://themzansimarketplace.co.za/order/success",
        "cancelUrl" => "https://themzansimarketplace.co.za/order/cancelled",
        "failureUrl" => "https://themzansimarketplace.co.za/order/failed",

    ]);

    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $secretKey // Use the secret key from the URL
    ]);

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        // Handle error
        echo 'cURL error: ' . curl_error($ch);
    } else {
        // Decode the JSON response
        $response_data = json_decode($response, true);

        // Check if the response contains the necessary data
        if (isset($response_data['id']) && isset($response_data['redirectUrl'])) {

            // MySQL database credentials
            // Set parameters and execute
            $response_id = $response_data['id'];
            $response_amount = $amount;
            $response_tx_ref = $tx_ref;
            $response_created_at = date('Y-m-d H:i:s', strtotime('+2 hours'));
            $payment_type = 'Normal';

            $stmt = $conn->prepare("INSERT INTO yoco_payments (response_id, amount, payment_type, tx_ref, fullname, email, phone, bagID, sellerID, shipping, shipping_amnt, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssis", $response_id, $response_amount, $payment_type, $response_tx_ref, $fullname, $email, $phone, $bagid, $sellerid, $shippingType, $shippingAmnt, $response_created_at);

            if ($stmt->execute()) {
                // Insert the address
                addCustomerAddress($conn, $tx_ref, $response_id, $houseNumber, $addressl1, $addressl2, $city, $state, $pcode);
            }

            // Close statement and connection
            $stmt->close();
            $conn->close();

            logPaymentError("Pay File with amount $amount2");

            $_SESSION['checkoutID'] = $response_id;
            $_SESSION['customerID'] = $tx_ref;
            $_SESSION['checkoutEmail'] = $email;

            // Redirect to the provided URL or handle the response as needed
            header('Location: ' . $response_data['redirectUrl']);
            // You might want to use header('Location: ' . $response_data['redirectUrl']) for redirection
        } else {
            // Handle the case where the response doesn't contain the expected data
            logPaymentError('Unexpected response format.');
        }
    }

    // Close the cURL session
    curl_close($ch);
} else {
    // Display an error message if the necessary parameters are not provided
    logPaymentError('Error: Missing amount or secret key.');
}
