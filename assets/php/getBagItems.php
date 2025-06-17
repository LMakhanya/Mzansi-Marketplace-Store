<?php

header('Content-Type: application/json');

$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
include($ENV_IPATH . "conn.php");
include($ENV_IPATH . "env.php");

// Initialize the database connection
$pdo = initializeDatabase();
session_start();

function log_to_bag_file($message)
{
    global $logPath;
    $timestamp = (new DateTime())->modify('+2 hours')->format('Y-m-d H:i');
    $logMessage = "$timestamp - $message\n";
    file_put_contents($logPath . "get_bag.log", "$logMessage", FILE_APPEND);
}

function getSellerInfo($sellerId)
{
    global $conn;
    $sql = "SELECT * FROM sellers WHERE seller_id = '$sellerId'";
    $stmt = $conn->query($sql);
    return $stmt->fetch_assoc();
}

// Grab the 'action' param and other query parameters
$action = $_GET['action'] ?? $_POST['action'] ?? null;

$customerID = $_SESSION["customerID"];
$customerID = 'SSSZA24_YI5KdF';

log_to_bag_file("Processing Bag quantity update attempt for Customer: $customerID");

switch ($action) {
    case 'fetch':
        $sql = "SELECT p.*, b.bagID,b.product_id,b.customerID,b.size,b.Quantity,b.totalAmount,b.created_at,b.purchased, ss.seller_id, sb.b_name 
                FROM product p
                JOIN bagtbl b ON p.product_id = b.product_id 
                JOIN sellers ss ON p.seller_id = ss.seller_id 
                JOIN sss_business sb ON p.seller_id = sb.seller_id 
                WHERE b.purchased = 0 AND b.customerID = '" . $_SESSION['customerID'] . "'";
        $stmt = $conn->query($sql);

        // Prepare an array to group data by seller
        $groupedData = [];
        if ($stmt && $stmt->num_rows > 0) {
            while ($row = $stmt->fetch_assoc()) {
                $seller_id = $row['seller_id'];

                // Get seller info using the getSellerInfo function
                if (!isset($groupedData[$seller_id])) {
                    $sellerInfo = getSellerInfo($seller_id);

                    $sellerInfo['bagID'] = $row['bagID'];
                    $sellerInfo['b_name'] = $row['b_name'];

                    $groupedData[$seller_id] = [
                        'seller_info' => $sellerInfo,
                        'products' => []
                    ];
                }

                // Append the product to the seller's products array
                $groupedData[$seller_id]['products'][] = $row;
            }
        }

        // Remove seller_id keys and group under 'eachSeller'
        $output = ["eachSeller" => array_values($groupedData)];

        // Echo JSON with the grouped data
        echo json_encode($output, JSON_PRETTY_PRINT);
        break;
}

// Close the prepared statement and connection
$stmt->close();
$conn->close();
