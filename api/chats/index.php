<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Database connection setting
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
require_once $ENV_IPATH . "env.php";

// Function to get or create a chat session
function getOrCreateSession($pdo, $sellerId, $customerId, $orderId)
{
    $query = "SELECT id FROM chat_sessions WHERE seller_id = :sellerId AND customerID = :customerId AND orderID = :orderId";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['sellerId' => $sellerId, 'customerId' => $customerId, 'orderId' => $orderId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
        return $session['id'];
    } else {
        $query = "INSERT INTO chat_sessions (seller_id, customerID, orderID, created_at) VALUES (:sellerId, :customerId, :orderId, NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['sellerId' => $sellerId, 'customerId' => $customerId, 'orderId' => $orderId]);
        return $pdo->lastInsertId();
    }
}

// Function to get messages for a session
function getMessages($pdo, $sessionId)
{
    $query = "SELECT sender, message, timestamp FROM chat_messages WHERE sessionId = :sessionId ORDER BY timestamp ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['sessionId' => $sessionId]);

    // update isRead to 1


    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to save a message
function saveMessage($pdo, $sessionId, $sender, $message)
{
    $query = "INSERT INTO chat_messages (sender, message, sessionId, timestamp) VALUES (:sender, :message, :sessionId, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['sender' => $sender, 'message' => $message, 'sessionId' => $sessionId]);
}
function markAsRead($pdo, $sessionId)
{
    $query = "UPDATE chat_messages SET isRead = 0 WHERE sessionId = :sessionId";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['sessionId' => $sessionId]);
}

// Handle requests
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {

    // Initialize the database connection
    $pdo = initializeDatabase();

    $sellerId = $data['sellerId'] ?? '';
    $customerId = $data['customerId'] ?? '';
    $orderId = $data['orderId'] ?? null;
    $message = $data['message'] ?? null;
    $action = $data['action'] ?? '';


    switch ($action) {
        case 'getSession':
            $sessionId = getOrCreateSession($pdo, $sellerId, $customerId, $orderId);
            $messages = getMessages($pdo, $sessionId);
            markAsRead($pdo, $sessionId);
            echo json_encode(['sessionId' => $sessionId, 'messages' => $messages]);
            break;

        case 'sendMessage':
            $sessionId = $data['sessionId'] ?? getOrCreateSession($pdo, $sellerId, $customerId, $orderId);
            saveMessage($pdo, $sessionId, $data['sender'], $message);
            echo json_encode(['success' => true, 'sessionId' => $sessionId]);
            break;
        case 'pollMessages':
            $sessionId = $data['sessionId'] ?? null;
            if ($sessionId) {
                $messages = getMessages($pdo, $sessionId);
                echo json_encode(['messages' => $messages]);
            } else {
                echo json_encode(['error' => 'No session ID provided']);
            }
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
