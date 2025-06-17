<?php
$IPATH2 = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
include($IPATH2 . "env.php");
include($IPATH2 . "conn.php");

session_start();
// Function to log to a file
function log_to_check_Out_File($message)
{
    $date = date('Y-m-d H:i');
    file_put_contents('log_to_check_Out_File.log', "$date - $message\n", FILE_APPEND);
}

function generate_orderNo()
{
    $characters = '0123456789';
    $randomString = '';

    for ($i = 0; $i < 11; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }

    return $randomString;
}

function check_orderNo_exists($conn, $orderNo)
{
    $stmt = $conn->prepare("SELECT orderNo FROM orders WHERE orderNo = ? LIMIT 1");
    $stmt->bind_param("i", $orderNo);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result->num_rows > 0;
}

function generate_unique_orderNo($conn)
{
    do {
        $orderNo = generate_orderNo();
    } while (check_orderNo_exists($conn, $orderNo));

    return $orderNo;
}

// Initialize the database connection
$pdo = initializeDatabase();

$customerID = $_SESSION['customerID'];
log_to_check_Out_File("Customer ID: $customerID");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $totalAmount = $_POST["total"];
    $_SESSION['checkoutTotal'] = $totalAmount;
    $bagID = $_POST["bagID"];
    $sellerID = $_POST["sellerID"];
    $billingAddress = '123 Main St, City';
    $shippingAddress = '456 Secondary St, City';

    $orderNo = generate_unique_orderNo($conn);
    log_to_check_Out_File("New customer - generated Order Number: $orderNo");


    log_to_check_Out_File("Processing Cart quantity update attempt for Customer: $customerID with Bag ID: $bagID ");

    $sqlOrders = "INSERT INTO orders (orderNo, customerID, bagID, total_amount, billing_address, shipping_address) VALUES (:orderNo, :customerID, :bagID, :totalAmount, :billingAddress, :shippingAddress)";
    $stmtOrders = $pdo->prepare($sqlOrders);
    $stmtOrders->bindParam(':orderNo', $orderNo, PDO::PARAM_INT);
    $stmtOrders->bindParam(':customerID', $customerID, PDO::PARAM_STR);
    $stmtOrders->bindParam(':bagID', $bagID, PDO::PARAM_STR);
    $stmtOrders->bindParam(':totalAmount', $totalAmount, PDO::PARAM_STR);
    $stmtOrders->bindParam(':billingAddress', $billingAddress, PDO::PARAM_STR);
    $stmtOrders->bindParam(':shippingAddress', $shippingAddress, PDO::PARAM_STR);

    if ($stmtOrders->execute()) {
        log_to_check_Out_File("Record inserted into 'orders' successfully.");
    } else {
        log_to_check_Out_File("Error inserting record into 'orders': " . $stmtOrders->errorInfo()[2]);
    }

    log_to_check_Out_File("Selecting * from bag");
    $sqlSelect = "SELECT * FROM bagtbl WHERE bagID = :bagID AND customerID = :customerID AND purchased = 0";
    $stmtSelect = $pdo->prepare($sqlSelect);
    $stmtSelect->bindParam(':bagID', $bagID, PDO::PARAM_STR);
    $stmtSelect->bindParam(':customerID', $customerID, PDO::PARAM_STR);
    $stmtSelect->execute();

    $result = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

    // Use the $result array for further processing or displaying data
    log_to_check_Out_File("Starting for each");
    foreach ($result as $row) {
        $bagProductID = $row['product_id'];
        $itemQuantity = $row['Quantity'];
        $model = $row['model'];
        $color = $row['color'];

        log_to_check_Out_File("Processing Cart item for Order Number: $orderNo, Product ID: $bagProductID, Bag ID: $bagID");

        $sqlOrderProducts = "INSERT INTO orderproducts (product_id, orderNo, bagID, quantity, model, color) VALUES (:bagProductID, :orderNo, :bagID, :itemQuantity, :model, :color)";
        $stmtOrderProducts = $pdo->prepare($sqlOrderProducts);
        $stmtOrderProducts->bindParam(':bagProductID', $bagProductID, PDO::PARAM_INT);
        $stmtOrderProducts->bindParam(':orderNo', $orderNo, PDO::PARAM_STR);  // Change to PDO::PARAM_STR
        $stmtOrderProducts->bindParam(':bagID', $bagID, PDO::PARAM_STR);
        $stmtOrderProducts->bindParam(':itemQuantity', $itemQuantity, PDO::PARAM_INT);
        $stmtOrderProducts->bindParam(':model', $model, PDO::PARAM_STR);
        $stmtOrderProducts->bindParam(':color', $color, PDO::PARAM_STR);

        try {
            if ($stmtOrderProducts->execute()) {
                log_to_check_Out_File("Record inserted into 'orderproducts' for Order Number: $orderNo, Product ID: $bagProductID, Bag ID: $bagID");

                $updateSQL = "UPDATE bagtbl SET purchased = 1 WHERE customerID = :customerID AND product_id = :bagProductID AND bagID = :bagID AND sellerID = :sellerID";
                $updatestmt = $pdo->prepare($updateSQL);
                $updatestmt->bindParam(':customerID', $customerID, PDO::PARAM_INT);
                $updatestmt->bindParam(':bagProductID', $bagProductID, PDO::PARAM_INT);
                $updatestmt->bindParam(':bagID', $bagID, PDO::PARAM_STR);
                $updatestmt->bindParam(':sellerID', $sellerID, PDO::PARAM_STR);
                $updatestmt->execute();

                log_to_check_Out_File("Bag item marked as purchased for Order Number: $orderNo, Product ID: $bagProductID, Bag ID: $bagID\n");
            } else {
                log_to_check_Out_File("Error inserting record into 'orderproducts' for Order Number: $orderNo, Product ID: $bagProductID, Bag ID: $bagID - " . $stmtOrderProducts->errorInfo()[2]);
            }
        } catch (PDOException $e) {
            log_to_check_Out_File("Error executing query: " . $e->getMessage());
        }
    }
    echo json_encode([
        "status" => 'success',
        "orderno" => $orderNo
    ]);
}
