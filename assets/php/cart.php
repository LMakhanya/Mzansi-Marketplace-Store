<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
include($ENV_IPATH . "conn.php");
include($ENV_IPATH . "env.php");

// Initialize the database connection
$pdo = initializeDatabase();

header('Content-Type: application/json');

session_start();

$totalAmountBag = 0.0;

function generate_bagID()
{
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    for ($i = 0; $i < 10; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }

    return $randomString;
}

function check_bagID_exists($conn, $bagID)
{
    $stmt = $conn->prepare("SELECT bagID FROM bagtbl WHERE bagID = ? LIMIT 1");
    $stmt->bind_param("s", $bagID);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result->num_rows > 0;
}

function generate_unique_bagID($conn)
{
    do {
        $bagID = generate_bagID();
    } while (check_bagID_exists($conn, $bagID));

    return $bagID;
}

function check_customerID_exists($conn, $customerID)
{
    $stmt = $conn->prepare("SELECT customerID FROM bagtbl WHERE customerID = ?");
    $stmt->bind_param("s", $customerID);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result->num_rows > 0;
}
function check_sellerID_exists($conn, $sellerID)
{
    $stmt = $conn->prepare("SELECT sellerID FROM bagtbl WHERE sellerID = ?");
    $stmt->bind_param("s", $sellerID);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result->num_rows > 0;
}
function check_product_in_bagTbl($product_id, $customerID, $size)
{
    global $conn;
    $stmt = $conn->prepare("SELECT Quantity FROM bagtbl WHERE product_id = ? AND customerID = ? AND `size` = ? AND purchased = 0");
    $stmt->bind_param("isi", $product_id, $customerID, $size);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a row exists
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['Quantity']; // Return the quantity if found
    }

    $stmt->close();
    return false; // Return false if no matching row is found
}

function getbagID($conn, $customerID)
{
    $stmt = $conn->prepare("SELECT bagID FROM bagtbl WHERE customerID = ? AND purchased = 0");
    $stmt->bind_param("s", $customerID);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if (!$result) {
        die("Query failed: " . $conn->error);
    }

    $row = $result->fetch_assoc();

    if ($row === null) {
        // If no matching record is found, generate a new unique bagID
        $newBagID = generate_unique_bagID($conn);
        return $newBagID;
    }

    return $row['bagID'];
}

function getTotalQuantity($customerID)
{
    global $pdo;
    $getTotalQuantitySQL = "SELECT product_id, SUM(Quantity) AS totalQuantity, SUM(totalAmount) AS totalAmount  FROM bagtbl WHERE customerID = :customerID AND purchased = 0";
    $getTotalQuantitySTMT = $pdo->prepare($getTotalQuantitySQL);
    $getTotalQuantitySTMT->bindParam(':customerID', $customerID, PDO::PARAM_STR);
    $getTotalQuantitySTMT->execute();

    $result = $getTotalQuantitySTMT->fetch(PDO::FETCH_ASSOC);
    $totalQuantity = ($result['totalQuantity'] !== null) ? (int)$result['totalQuantity'] : 0;
    $bagTotalAmount = ($result['totalAmount'] !== null) ? $result['totalAmount'] : 0;

    $_SESSION["totalAmount"] = $bagTotalAmount;
    $_SESSION["totalQuantity"] = $totalQuantity;

    return $totalQuantity;
}
function logCartError($message)
{
    global $logPath;
    $timestamp = (new DateTime())->modify('+2 hours')->format('Y-m-d H:i');
    $logMessage = "$timestamp - $message\n";
    file_put_contents($logPath . "log_addToCart.log", $logMessage, FILE_APPEND);
}

function insertIntoBag($bagID, $pID, $customerID, $quantity, $totalAmountBag, $model, $sellerID, $size)
{
    global $conn;
    $addtobagSQL = "INSERT INTO bagtbl (bagID, product_id, customerID, `size`, Quantity, totalAmount, created_at, purchased, model, sellerID) VALUES (?, ?, ?, ?, ?, ?, NOW(), 0, ?,?)";
    $addtobagSTMT = $conn->prepare($addtobagSQL);
    $addtobagSTMT->bind_param("sisiidss", $bagID, $pID, $customerID, $size, $quantity, $totalAmountBag, $model, $sellerID);

    if ($addtobagSTMT->execute()) {
        logCartError("Record inserted successfully in bagtbl");
        $newQuantity = getTotalQuantity($customerID);
        echo json_encode([
            'status' => 'success',
            'newQuantity' => $newQuantity,
        ]);
        exit;
    }
}

function updateProductQuantity($bagID, $pID, $customerID, $newQuantity, $totalAmountBag, $sellerID, $size)
{
    global $conn;

    if (!$size) {
        $size = 0;
    }

    $updateQuantitySQL = "UPDATE bagtbl SET Quantity = ?, totalAmount = ? WHERE bagID = ? AND product_id = ? AND customerID = ? AND `size` = ? AND sellerID = ? AND purchased = 0";
    $updateQuantitySTMT = $conn->prepare($updateQuantitySQL);
    logCartError("Binding: newQuantity=$newQuantity, totalAmountBag=$totalAmountBag, bagID=$bagID, pID=$pID, customerID=$customerID, size=$size, sellerID=$sellerID");
    $updateQuantitySTMT->bind_param("idsisis", $newQuantity, $totalAmountBag, $bagID, $pID, $customerID, $size, $sellerID);

    if ($updateQuantitySTMT->execute()) {
        logCartError("Product quantity updated successfully in bagtbl");
        $newQuantity = getTotalQuantity($customerID);
        echo json_encode([
            'status' => 'success',
            'newQuantity' => $newQuantity,
        ]);
        exit;
    }
}

logCartError("Cart file found");

if (!isset($_SESSION["customerID"])) {
    header("Location: /signin.php");
    exit();
} else {
    $customerID = $_SESSION["customerID"];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $pID = isset($_POST['pID']) ? $_POST['pID'] : null;
        $pName = isset($_POST['pName']) ? $_POST['pName'] : null;
        $model = isset($_POST['model']) ? $_POST['model'] : null;
        $pPrice = isset($_POST['pPrice']) ? $_POST['pPrice'] : null;
        $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : null;
        $sellerID = isset($_POST['sellerID']) ? $_POST['sellerID'] : null;
        $size = isset($_POST['size']) ? $_POST['size'] : null;

        $totalAmountBag = $pPrice * $quantity;

        if (!check_customerID_exists($conn, $customerID)) {

            $bagID = generate_unique_bagID($conn);

            logCartError("New customer - generated bagID: $bagID");
            logCartError("New customer - generated quantity: $quantity");

            if (!check_product_in_bagTbl($pID, $customerID, $size)) {
                insertIntoBag($bagID, $pID, $customerID, $quantity, $totalAmountBag, $model, $sellerID, $size);
            } else {
                $currentQ = check_product_in_bagTbl($pID, $customerID, $size);

                $newQuantity = $quantity + $currentQ;
                $totalAmountBag = $pPrice * $newQuantity;

                updateProductQuantity($bagID, $pID, $customerID, $newQuantity, $totalAmountBag, $sellerID, $size);
            }
            exit;
        } else {
            logCartError("\nExisting customer - customerID: $customerID whith sellerID: $sellerID");

            if (!check_sellerID_exists($conn, $sellerID)) {
                $bagID = generate_unique_bagID($conn);
            } else {
                $bagID = getbagID($conn, $customerID);
            }
            logCartError("customer - generated bagID: $bagID");
            logCartError("customer - generated quantity: $quantity");

            if (!check_product_in_bagTbl($pID, $customerID, $size)) {
                insertIntoBag($bagID, $pID, $customerID, $quantity, $totalAmountBag, $model, $sellerID, $size);
            } else {
                $currentQ = check_product_in_bagTbl($pID, $customerID, $size);
                logCartError("customer - generated currentQ: $currentQ");

                $newQuantity = $quantity + $currentQ;
                $totalAmountBag = $pPrice * $newQuantity;
                updateProductQuantity($bagID, $pID, $customerID, $newQuantity, $totalAmountBag, $sellerID, $size);
            }
        }
    }
}
