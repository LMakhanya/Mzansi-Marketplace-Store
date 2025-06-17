<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', $_SERVER["DOCUMENT_ROOT"] . "/logs/php_errors.log");

header('Content-Type: application/json');

$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
$LOG_PATH = $_SERVER["DOCUMENT_ROOT"] . "/logs/quantity.log";

require_once $ENV_IPATH . "conn.php";
require_once $ENV_IPATH . "env.php";

session_start();

try {
    $pdo = initializeDatabase();
} catch (Exception $e) {
    logToFile("Database connection failed: " . $e->getMessage());
    sendErrorResponse(500, "Database connection failed");
    exit;
}

function logToFile(string $message): void
{
    $timestamp = (new DateTime())->modify('+2 hours')->format('Y-m-d H:i');
    file_put_contents($GLOBALS['LOG_PATH'], "$timestamp - $message\n", FILE_APPEND);
}

function sendErrorResponse(int $code, string $message, ?string $details = null): void
{
    ob_clean();
    http_response_code($code);
    $response = ['status' => 'error', 'message' => $message];
    if ($details) {
        $response['errorDetails'] = $details;
    }
    echo json_encode($response);
}

function getCartItems(PDO $pdo, string $customerID): array
{
    $sql = "
        SELECT b.bagID, b.product_id, b.Quantity, b.totalAmount, 
               p.ProductName, p.Price, p.product_image, p.brandName
        FROM bagtbl b
        JOIN product p ON b.product_id = p.product_id
        WHERE b.customerID = :customerID AND b.purchased = 0
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':customerID' => $customerID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCartSummary(PDO $pdo, string $customerID): array
{
    $sql = "
        SELECT SUM(Quantity) AS totalQuantity, SUM(totalAmount) AS totalAmount
        FROM bagtbl 
        WHERE customerID = :customerID AND purchased = 0
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':customerID' => $customerID]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return [
        'totalQuantity' => (int) ($result['totalQuantity'] ?? 0),
        'totalAmount' => (float) ($result['totalAmount'] ?? 0)
    ];
}

function updateCart(PDO $pdo, string $action, string $customerID)
{
    $bagID = $_POST['bagID'] ?? '';
    $product_id = $_POST['product_id'] ?? '';
    $price = $_POST['price'] ?? 0;
    $size = $_POST['size'] ?? 0;

    // Log values for debugging
    logToFile("Action: $action, Bag ID: $bagID, Product ID: $product_id, Price: $price");

    if (empty($bagID) || empty($product_id)) {
        return ['status' => 'error', 'message' => 'Missing bagID or product_id'];
    }

    try {
        // Define SQL and parameters based on action
        switch ($action) {
            case 'addQ':
                $sql = "
                    UPDATE bagtbl 
                    SET Quantity = Quantity + 1, totalAmount = :price
                    WHERE bagID = :bagID AND customerID = :customerID AND product_id = :product_id AND `size` = :size
                ";
                $params = [
                    ':bagID' => $bagID,
                    ':customerID' => $customerID,
                    ':product_id' => $product_id,
                    ':price' => $price,
                    ':size' => $size
                ];
                break;
            case 'minusQ':
                $sql = "
                    UPDATE bagtbl 
                    SET Quantity = GREATEST(Quantity - 1, 0), totalAmount = :price
                    WHERE bagID = :bagID AND customerID = :customerID AND product_id = :product_id AND `size` = :size
                ";
                $params = [
                    ':bagID' => $bagID,
                    ':customerID' => $customerID,
                    ':product_id' => $product_id,
                    ':price' => $price,
                    ':size' => $size
                ];
                break;
            case 'delete':
                $sql = "
                    DELETE FROM bagtbl 
                    WHERE bagID = :bagID AND customerID = :customerID AND product_id = :product_id AND `size` = :size
                ";
                $params = [
                    ':bagID' => $bagID,
                    ':customerID' => $customerID,
                    ':product_id' => $product_id,
                    ':size' => $size

                ]; // No :price needed
                break;
            default:
                return ['status' => 'error', 'message' => 'Invalid action'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $rowCount = $stmt->rowCount();

        if ($rowCount === 0) {
            logToFile("No rows affected for action '$action' on product_id $product_id, bagID $bagID.");
            return ['status' => 'error', 'message' => 'No cart item found to update' .  $size];
        }

        logToFile("$rowCount row(s) affected for action '$action' on product_id $product_id, bagID $bagID.");
        $items = getCartItems($pdo, $customerID);
        $summary = getCartSummary($pdo, $customerID);

        return [
            'status' => 'success',
            'message' => ucfirst($action) . ' completed successfully',
            'quantity' => $summary['totalQuantity'],
            'totalAmount' => $summary['totalAmount'],
            'items' => $items
        ];
    } catch (PDOException $e) {
        $errorDetails = "Database error during $action: " . $e->getMessage() . " (Code: " . $e->getCode() . ")";
        logToFile($errorDetails);
        return [
            'status' => 'error',
            'message' => 'Database error occurred',
            'errorDetails' => $errorDetails
        ];
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$customerID = $_SESSION['customerID'] ?? null;

if (!$customerID) {
    sendErrorResponse(401, "User not authenticated");
    exit;
}

logToFile("Processing action '$action' for customerID: $customerID");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    if ($action === 'fetch') {
        $items = getCartItems($pdo, $customerID);
        $summary = getCartSummary($pdo, $customerID);
        $response = empty($items)
            ? ['status' => 'success', 'quantity' => 0, 'items' => []]
            : [
                'status' => 'success',
                'bagID' => $items[0]['bagID'] ?? '',
                'quantity' => $summary['totalQuantity'],
                'totalAmount' => $summary['totalAmount'],
                'items' => $items
            ];
    } else {
        $response = updateCart($pdo, $action, $customerID); // Updated function used here
    }
} else {
    $response = ['status' => 'error', 'message' => 'Invalid request method or action'];
}

ob_end_clean();
echo json_encode($response);
exit;
