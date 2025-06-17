<?php
error_reporting(E_ALL);
header('Content-Type: application/json');
ini_set('display_errors', 1);

// Database connection setting
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
require_once $ENV_IPATH . "env.php";
require_once $ENV_IPATH . "conn.php";

require $_SERVER["DOCUMENT_ROOT"] . '/api/classes.php';

$classOrder = new order();
$classPayements = new Payment();

session_start();

// Assuming $pdo is initialized elsewhere (e.g., in a config file)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $customerID = $_GET["customer"];
    $checkoutID = $_GET["id"];

    /* echo "Customer ID:$customerID & CheckoutID:$checkoutID"; */

    // Initialize the database connection
    $pdo = initializeDatabase();

    if ($classOrder->checkoutIdExists($checkoutID)) {
        $orderResults = $classOrder->getcustomerOrder($customerID, $checkoutID);
        $paymentResults = $classPayements->getPaymentDetails($pdo, $checkoutID);
        $orderNo = $orderResults['orderNo'] ?? null;
        $trackUrl = $orderResults['trackUrl'] ?? '';
        $trackNo = $orderResults['trackNo'] ?? '';
        $orderStatus = $orderResults['status'] ?? null;
        $custEmail = $orderResults['email'] ?? null;
        $custFname = $orderResults['fullname'] ?? null;
        $totalAmount = $orderResults['total_amount'] ?? 0;
        $orderDate = $orderResults['order_date'] ?? date('Y-m-d');
        $formattedOrderDate = date('Y-m-d', strtotime($orderDate));

        $shippingAddress = $orderResults['shipping_address'] ?? 'Not provided';

        $shippingType = $paymentResults['shipping'] ?? 'Unkown - Collect InStore';
        $shippingCost = $paymentResults['shipping_amnt'] ?? 0;


        $paymentEvent = $classOrder->getPaymentEvent($customerID, $checkoutID);
        $cardID = $paymentEvent['cardID'] ?? 'Unknown';
        $p_type = $paymentEvent['p_type'] ?? 'Unknown';
        $cardDetails = $classOrder->getCarddetails($cardID);
        $createdDate = $paymentEvent['createdDate'] ?? date('Y-m-d');

        $products = $orderNo ? $classOrder->getProducts($orderNo) : [];

        $subtotal = array_sum(array_map(function ($product) {
            $productDiscount = $product['price'] * ($product['discount'] / 100);
            $priceAfterDiscount = $product['price'] - $productDiscount;
            return $priceAfterDiscount * $product['quantity'];
        }, $products));

        $totalWithShipping = $subtotal + $shippingCost;

        echo json_encode([
            'status' => 'success',
            'orderNo' => $orderNo,
            'trackUrl' => $trackUrl,
            'trackNo' => $trackNo,
            'orderStatus' => $orderStatus,
            'custEmail' => $custEmail,
            'custFname' => $custFname,
            'totalAmount' => $totalAmount,
            'orderDate' => $formattedOrderDate,
            'shippingAddress' => $shippingAddress,
            'products' => $products,
            'shipping' => $shippingType,
            'shippingCost' => $shippingCost,
            'paymentEvent' => [
                'cardID' => $cardID,
                'p_type' => $p_type,
                'cardDetails' => $cardDetails,
                'createdDate' => $createdDate,
            ],
            'totalWithShipping' => $totalWithShipping,
            'taxRate' => 0.08,
            'totalWithTax' => $totalWithShipping * 1.08,
            'taxAmount' => $totalWithShipping * 0.08
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Checkout ID does not exist'
        ], JSON_PRETTY_PRINT);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Check for JSON parsing errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit;
    }


    // Initialize the database connection
    $pdo = initializeDatabase();


    // Extract data with null coalescing
    $orderNumber = $data['orderNumber'] ?? null;
    $orderStatus = $data['orderStatus'] ?? null;
    $trackOrderNumber = $data['trackOrderNumber'] ?? null;
    $trackPageUrl = $data['trackPageUrl'] ?? null;

    // Validation
    if (!$orderNumber || !$orderStatus) {
        http_response_code(400);
        echo json_encode(['error' => 'Order number and status are required']);
        exit;
    }

    // Prepare and execute the UPDATE query
    $query = "UPDATE orders SET 
                    `status` = :status, 
                    `trackNo` = :tracking_number, 
                    `trackUrl` = :tracking_url 
                  WHERE `orderNo` = :order_id";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':status' => $orderStatus,
        ':tracking_number' => $trackOrderNumber,
        ':tracking_url' => $trackPageUrl,
        ':order_id' => $orderNumber
    ]);

    // Check if any rows were affected
    if ($stmt->rowCount() > 0) {
        $response = [
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => [
                'orderNumber' => $orderNumber,
                'orderStatus' => $orderStatus,
                'trackingNumber' => $trackOrderNumber,
                'trackingUrl' => $trackPageUrl
            ]
        ];
        http_response_code(200);
    } else {
        $response = [
            'success' => false,
            'message' => 'No order found with the provided order number'
        ];
        http_response_code(404);
    }

    echo json_encode($response);
}
