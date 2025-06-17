<?php

session_start();

$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
include($ENV_IPATH . "conn.php");
include($ENV_IPATH . "env.php");


include $_SERVER["DOCUMENT_ROOT"] . '/api/classes.php';
$classOrder = new Order();
$classCustomer = new Customer();
$classPayment = new Payment();
$classSendEmail = new SendEmail();

function logResults($message) // Log an error message to the log file
{
    $logFile = __DIR__ . '/_webhook_error.log'; // Adjust path as needed
    $logDir = dirname($logFile);

    // Ensure the directory exists
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true); // Create the directory with full permissions
    }

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "$timestamp - $message\n";

    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}
function cardExists($pdo, $last_4digits, $expiryDate)
{
    try {
        $stmt = $pdo->prepare("SELECT EXISTS(SELECT 1 FROM yoco_cards WHERE last4Digits = :last4Digits AND expiry = :expiry) AS card_exists");
        $stmt->execute([':last4Digits' => $last_4digits, ':expiry' => $expiryDate]);
        return (bool) $stmt->fetchColumn(); // Explicitly cast to boolean
    } catch (Exception $e) {
        logResults("Error checking if card exists: " . $e->getMessage());
        return false; // Consistent failure case
    }
}

function insertCard($pdo, $data)
{
    // Extract card details
    $expMonth = str_pad($data['payload']['paymentMethodDetails']['card']['expiryMonth'], 2, '0', STR_PAD_LEFT);
    $expYear = $data['payload']['paymentMethodDetails']['card']['expiryYear'];
    $expiryDate = "20$expYear-$expMonth-01"; // Ensure valid date format (YYYY-MM-DD)

    $maskedCard = $data['payload']['paymentMethodDetails']['card']['maskedCard'];
    $last4Digits = substr($maskedCard, -4);

    // Check if card already exists
    if (!cardExists($pdo, $last4Digits, $expiryDate)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO yoco_cards (last4Digits, scheme, expiry) VALUES (:last4Digits, :scheme, :expiry)");
            $stmt->execute([
                ':last4Digits' => $last4Digits,
                ':scheme' => $data['payload']['paymentMethodDetails']['card']['scheme'],
                ':expiry' => $expiryDate
            ]);
            $lastInsertId = $pdo->lastInsertId();
            logResults("Card inserted successfully. Card ID: " . $lastInsertId);
            return $lastInsertId; // Return the new card ID
        } catch (Exception $e) {
            logResults("Error inserting card data: " . $e->getMessage());
            return null; // Indicate failure
        }
    } else {
        logResults("Card already exists");
        // Fetch existing card ID instead of returning null
        $stmt = $pdo->prepare("SELECT card_id  FROM yoco_cards WHERE last4Digits = :last4Digits AND expiry = :expiry");
        $stmt->execute([':last4Digits' => $last4Digits, ':expiry' => $expiryDate]);
        return $stmt->fetchColumn() ?: null; // Return existing ID or null if not found
    }
}

function eventExists($pdo, $event_id, $checkoutID)
{
    $stmt = $pdo->prepare("SELECT 1 FROM yoco_events WHERE event_id = :event_id OR p_meta_checkoutId = :checkoutId");
    $stmt->execute([':event_id' => $event_id, ':checkoutId' => $checkoutID]);
    return $stmt->fetchColumn();
}

function insertEvent($pdo, $data, $customerID, $cardID)
{
    $createdAt = DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $data['createdDate']);
    $formattedDate = $createdAt ? $createdAt->format('Y-m-d H:i:s') : null;
    $event_id = $data['id'];

    $checkoutID = $data['payload']['metadata']['checkoutId'];
    $amount = $data['payload']['amount'];
    $formattedAmount = intval($amount / 100);
    logResults("Calling Insert event Function");
    // Insert subscription details only if the event does not already exist
    if (!eventExists($pdo, $event_id, $checkoutID)) {

        try {
            $eventStmt = $pdo->prepare("INSERT INTO yoco_events (customerId, event_id, event_type, createdDate, payload_id, 
                payload_type, payload_createdDate, payload_amount, payload_currency, p_type, status, p_mode, p_meta_checkoutId, cardID)
                VALUES (:customerId, :event_id, :event_type, :createdDate, :payload_id, :payload_type, :payload_createdDate, 
                :payload_amount, :payload_currency, :p_type, :status, :p_mode, :p_meta_checkoutId, :cardID)");

            $eventStmt->execute([
                ':customerId' => $customerID,
                ':event_id' => $data['id'],
                ':event_type' => $data['type'],
                ':createdDate' => $formattedDate,
                ':payload_id' => $data['payload']['id'],
                ':payload_type' => $data['payload']['type'],
                ':payload_createdDate' => $data['payload']['createdDate'],
                ':payload_amount' => $formattedAmount,
                ':payload_currency' => $data['payload']['currency'],
                ':p_type' => $data['payload']['paymentMethodDetails']['type'],
                ':status' => $data['payload']['status'],
                ':p_mode' => $data['payload']['mode'],
                ':p_meta_checkoutId' => $data['payload']['metadata']['checkoutId'],
                ':cardID' => $cardID,
            ]);

            // Return true to indicate successful event insertion
            return true;
        } catch (Exception $e) {
            $error = "Error inserting event data: " . $e->getMessage();
            logResults("Received at " . date("Y-m-d H:i:s") . ":\n" . $error . PHP_EOL, FILE_APPEND);
            throw new Exception($error);
        }
    }

    // Return false to indicate no event was inserted (either due to existing event or error)
    return false;
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

function generateAndInsertOrder($pdo, $customerID, $fullname, $email, $phone, $sellerID, $bagID, $totalAmount, $shippingAddress, $checkoutID)
{
    global $conn;
    $orderNo = generate_unique_orderNo($conn);

    logResults("New customer - generated Order Number: $orderNo");

    $sqlOrders = "INSERT INTO orders (orderNo, customerID, fullname, email, phone, seller_id, bagID, total_amount, shipping_address, checkoutID) 
                  VALUES (:orderNo, :customerID, :fullname, :email, :phone, :sellerID, :bagID, :totalAmount, :shippingAddress, :checkoutID)";
    $stmtOrders = $pdo->prepare($sqlOrders);
    $stmtOrders->execute([
        ':orderNo' => $orderNo,
        ':customerID' => $customerID,
        ':fullname' => $fullname,
        ':email' => $email,
        ':phone' => $phone,
        ':sellerID' => $sellerID,
        ':bagID' => $bagID,
        ':totalAmount' => $totalAmount,
        ':shippingAddress' => $shippingAddress,
        ':checkoutID' => $checkoutID
    ]);
    logResults("Record inserted into 'orders' successfully.");

    processOrderProducts($pdo, $orderNo, $customerID, $bagID, $sellerID);
}

function processOrderProducts($pdo, $orderNo, $customerID, $bagID, $sellerID)
{
    $sqlSelect = "SELECT * FROM bagtbl WHERE bagID = :bagID AND customerID = :customerID AND sellerID = :sellerID AND purchased = 0";
    $stmtSelect = $pdo->prepare($sqlSelect);
    $stmtSelect->execute([':bagID' => $bagID, ':customerID' => $customerID, ':sellerID' => $sellerID]);
    $result = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result as $row) {
        insertOrderProduct($pdo, $orderNo, $bagID, $row);
        updateBagItem($pdo, $customerID, $row['product_id'], $bagID, $sellerID);
    }
}

function insertOrderProduct($pdo, $orderNo, $bagID, $row)
{
    $totalAmount = $row['totalAmount'];
    $quantity = $row['Quantity'];

    // The Actual Item Price
    $itemPrice = $totalAmount / $quantity;

    $sqlOrderProducts = "INSERT INTO orderproducts (product_id, orderNo, bagID, price, quantity, model, color) 
                         VALUES (:product_id, :orderNo, :bagID, :price, :quantity, :model, :color)";
    $stmtOrderProducts = $pdo->prepare($sqlOrderProducts);
    $stmtOrderProducts->execute([
        ':product_id' => $row['product_id'],
        ':orderNo' => $orderNo,
        ':bagID' => $bagID,
        ':price' => $itemPrice,
        ':quantity' => $quantity,
        ':model' => $row['model'],
        ':color' => $row['color']
    ]);
    logResults("Record inserted into 'orderproducts' for Order Number: $orderNo, Product ID: {$row['product_id']}, Bag ID: $bagID");
}

function updateBagItem($pdo, $customerID, $product_id, $bagID, $sellerID)
{
    $updateSQL = "UPDATE bagtbl SET purchased = 1 WHERE customerID = :customerID AND product_id = :product_id AND bagID = :bagID AND sellerID = :sellerID";
    $stmtUpdate = $pdo->prepare($updateSQL);
    $stmtUpdate->execute([
        ':customerID' => $customerID,
        ':product_id' => $product_id,
        ':bagID' => $bagID,
        ':sellerID' => $sellerID
    ]);
    logResults("Bag item marked as purchased for Order Number, Product ID: $product_id, Bag ID: $bagID");
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read the incoming request's body
    $payload = file_get_contents('php://input');
    $data = json_decode($payload, true);

    if (isset($data['payload']['metadata']['checkoutId'])) {
        $checkoutID = $data['payload']['metadata']['checkoutId'];

        // Initialize the database connection
        $pdo = initializeDatabase();

        if ($pdo) {
            logResults("Getting customerId for checkout ID: $checkoutID from yoco_payments");

            // get payment type
            $paymentType = $classPayment->getPaymentType($pdo, $checkoutID);
            $paymentResult = $classPayment->getPaymentDetails($pdo, $checkoutID);


            if ($paymentResult) {
                $fullname = $paymentResult['fullname'];
                $email = $paymentResult['email'];
                $totalAmount = $paymentResult['amount'];

                switch ($paymentType) {
                    case 'Normal':
                        if (!$classOrder->checkoutIdExists($checkoutID)) {
                            $shippingAddress = $classCustomer->getCustomerShippingAddress($pdo, $checkoutID);

                            if ($shippingAddress) {
                                $customerID = $paymentResult['tx_ref'];
                                $phone = $paymentResult['phone'];
                                $bagID = $paymentResult['bagID'];
                                $sellerID = $paymentResult['sellerID'];
                                // Process the transaction
                                $cardID = insertCard($pdo, $data);
                                $eventInserted = insertEvent($pdo, $data, $customerID, $cardID);

                                if ($eventInserted) {
                                    $logEntry = "Received at " . date("Y-m-d H:i:s") . " CustomerID: $customerID" . ":\n" . $payload . "\n\n";
                                    file_put_contents('_webhook.log', $logEntry, FILE_APPEND);
                                    logResults("Event successfully inserted for customerId: $customerID\n");

                                    $paymentStatus = $data['payload']['status'];

                                    if ($paymentStatus === "successful" || $paymentStatus === "success" || $paymentStatus === "succeeded") {

                                        // Create a new order
                                        generateAndInsertOrder($pdo, $customerID, $fullname, $email, $phone, $sellerID, $bagID, $totalAmount, $shippingAddress, $checkoutID);
                                        $classSendEmail->sendOrderEmail($customerID, $checkoutID, $classOrder, $classPayment);
                                        logResults("\n");
                                    }
                                }
                                // close pdo connection
                                $pdo = null;
                            } else {
                                logResults("No Payment or Shipping Address found for checkout ID: $checkoutID");
                            }
                        } else {
                            logResults("Duplicate event detected: Checkout ID $checkoutID already processed.\n");
                        }
                        break;
                    case 'Change Plan':
                        $changePSellerID = $paymentResult['tx_ref'];
                        $newPlan = $paymentResult['new_plan'];
                        $amount = $paymentResult['amount']; // Assuming amount is provided in Rands

                        logResults("Change Plan payment detected: Checkout ID $checkoutID");

                        // Define plan pricing for upgrade/downgrade logic
                        $plans = [
                            'Free Plan' => 0,
                            'Basic Plan' => 170,
                            'Pro Plan' => 350,
                        ];

                        // Fetch current plan from sellers table
                        $currentPlanSQL = "SELECT account_plan, subscription_date FROM sellers WHERE seller_id = :seller_id";
                        $stmt = $pdo->prepare($currentPlanSQL);
                        $stmt->execute([':seller_id' => $changePSellerID]);
                        $seller = $stmt->fetch(PDO::FETCH_ASSOC);
                        $currentPlan = $seller['account_plan'] ?? 'Free Plan';
                        $subscriptionDate = $seller['subscription_date'] ? new DateTime($seller['subscription_date']) : new DateTime();

                        // Determine if it's an upgrade or downgrade
                        $isUpgrade = $plans[$newPlan] > $plans[$currentPlan];

                        // Process the transaction
                        $cardID = insertCard($pdo, $data);
                        $eventInserted = insertEvent($pdo, $data, $changePSellerID, $cardID);

                        if ($eventInserted) {
                            $logEntry = "Received at " . date("Y-m-d H:i:s") . " changePSellerID: $changePSellerID" . ":\n" . $payload . "\n\n";
                            file_put_contents('_webhook.log', $logEntry, FILE_APPEND);
                            logResults("Event successfully inserted for changePSellerID: $changePSellerID\n");

                            $paymentStatus = $data['payload']['status'];

                            if ($paymentStatus === "successful" || $paymentStatus === "success" || $paymentStatus === "succeeded") {
                                // Send change plan email to customer
                                $classSendEmail->subscriptionConfirmationEmail($email, $fullname, $newPlan);

                                // Update session
                                $_SESSION['account_plan'] = $newPlan;

                                // Calculate subscription dates
                                $today = new DateTime();
                                if ($isUpgrade) {
                                    // Upgrades are immediate
                                    $subscriptionStartDate = $today->format('Y-m-d');
                                } else {
                                    // Downgrades apply next cycle (add one month to current subscription date)
                                    $subscriptionStartDate = (clone $subscriptionDate)->modify('+1 month')->format('Y-m-d');
                                }
                                $subscriptionEndDate = (new DateTime($subscriptionStartDate))->modify('+1 month')->format('Y-m-d');

                                // Update sellers table (immediate for upgrades, keep current for downgrades)
                                if ($isUpgrade) {
                                    $updateStatusSQL = "UPDATE sellers SET account_plan = :planName, subscription_date = :subscriptionDate, active = 1 WHERE seller_id = :seller_id";
                                    $stmt = $pdo->prepare($updateStatusSQL);
                                    $stmt->execute([
                                        ':seller_id' => $changePSellerID,
                                        ':planName' => $newPlan,
                                        ':subscriptionDate' => $subscriptionStartDate,
                                    ]);
                                }

                                // Insert into sellers_subscriptions to record the plan change
                                $insertSubscriptionSQL = "INSERT INTO sellers_subscriptions (id, seller_id, account_plan, amount, status, created_at, subscription_start_date, subscription_end_date) 
                                     VALUES (:id, :seller_id, :account_plan, :amount, :status, :created_at, :start_date, :end_date)";
                                $stmt = $pdo->prepare($insertSubscriptionSQL);
                                $stmt->execute([
                                    ':id' => mt_rand(100000, 999999), // Generate a random ID (replace with auto-increment or UUID logic if needed)
                                    ':seller_id' => $changePSellerID,
                                    ':account_plan' => $newPlan,
                                    ':amount' => $amount,
                                    ':status' => 'active',
                                    ':created_at' => $today->format('Y-m-d H:i:s'),
                                    ':start_date' => $subscriptionStartDate,
                                    ':end_date' => $subscriptionEndDate,
                                ]);

                                logResults("Plan change recorded: Seller ID $changePSellerID, New Plan: $newPlan, Start: $subscriptionStartDate, End: $subscriptionEndDate\n");
                            }
                        }

                        // Close PDO connection
                        $pdo = null;
                        break;
                    case 'Enhancements Payment':
                        $sellerID = $paymentResult['tx_ref'];
                        $enhancement_id = $paymentResult['enhancement_id']; // Assuming enhancement_type is part of paymentResult
                        $enhancementAmount = $paymentResult['amount'];
                        $checkout_id = $paymentResult['response_id'];

                        logResults("Enhancements Payment detected: Checkout ID $checkoutID, Enhancement Type: $enhancement_id");

                        // Insert card details
                        $cardID = insertCard($pdo, $data);
                        $eventInserted = insertEvent($pdo, $data, $sellerID, $cardID);

                        if ($eventInserted) {
                            $logEntry = "Received at " . date("Y-m-d H:i:s") . " sellerID: $sellerID, Enhancement: $enhancement_id" . ":\n" . $payload . "\n\n";
                            file_put_contents('_webhook.log', $logEntry, FILE_APPEND);
                            logResults("Event successfully inserted for sellerID: $sellerID, Enhancement: $enhancement_id\n");

                            $paymentStatus = $data['payload']['status'];

                            if ($paymentStatus === "successful" || $paymentStatus === "success" || $paymentStatus === "succeeded") {

                                // Update seller's enhancement payment status
                                $updateEnhancementStatusSQL = "UPDATE enhancement_payments SET payment_status = 'completed', payment_date = NOW() 
                                                          WHERE seller_id = :seller_id AND checkout_id = :checkout_id AND enhancement_id = :enhancement_id";
                                $stmt = $pdo->prepare($updateEnhancementStatusSQL);
                                $stmt->execute([
                                    ':seller_id' => $sellerID,
                                    ':checkout_id' => $checkout_id,
                                    ':enhancement_id' => $enhancement_id
                                ]);

                                logResults("Enhancement payment processed successfully-seller: $sellerID & enh-id: $enhancement_id \n");

                                // Update seller's enhancement status
                                $updateEnhancementStatusSQL = "UPDATE product_enhancements SET `status` = 'active' WHERE id = :enhancement_id";
                                $stmt = $pdo->prepare($updateEnhancementStatusSQL);
                                $stmt->execute([
                                    ':enhancement_id' => $enhancement_id
                                ]);

                                logResults("Product enhancement processed successfully\n");
                                // Send enhancement purchase confirmation email
                                $classSendEmail->enhancementPurchaseEmail($email, $fullname, $enhancement_id, $enhancementAmount);

                                logResults("Enhancement process was successfully***********************************************\n\n");
                            } else {
                                // Update seller's enhancement payment status
                                $updateEnhancementStatusSQL = "UPDATE enhancement_payments SET payment_status = 'failed', payment_date = NOW() 
                                                          WHERE seller_id = :seller_id AND enhancement_id = :enhancement_id";
                                $stmt = $pdo->prepare($updateEnhancementStatusSQL);
                                $stmt->execute([
                                    ':seller_id' => $sellerID,
                                    ':enhancement_id' => $enhancement_id
                                ]);
                            }
                        }
                        break;

                    default:
                        logResults("Unknown payment type for checkout ID: $checkoutID");
                        break;
                }
            }
        } else {
            logResults("Database connection failed.");
        }
    } else {
        logResults("Invalid payload: Missing checkoutId.");
    }

    // Acknowledge the receipt of the webhook
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    http_response_code(200);
} else {
    // If the request is not a POST, return a 405 error
    http_response_code(405); // Method Not Allowed
    echo 'Invalid request method';
}
