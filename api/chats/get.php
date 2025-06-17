<?php
// Prevent any output before JSON
ob_start(); // Start output buffering

header('Content-Type: application/json');

// Database connection
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
require_once $ENV_IPATH . "conn.php";

session_start();

// Get seller_id from session
$seller_id = $_SESSION['seller_id'] ?? null;

if (!$seller_id) {
    echo json_encode(["error" => "Seller ID not found in session."]);
    ob_end_flush();
    exit;
}

// Fetch all active chat sessions for the seller
$sql = "SELECT id, customerID, orderID, created_at
        FROM chat_sessions
        WHERE seller_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $seller_id);
$stmt->execute();
$result = $stmt->get_result();

// If no sessions found, return empty response
if ($result->num_rows === 0) {
    echo json_encode([
        "sellerID" => $seller_id,
        "sessions" => []
    ]);
    ob_end_flush();
    exit;
}

// Fetch total unread count across all sessions (do this once)
$totalUnreadSql = "SELECT COUNT(*) as total
                   FROM chat_messages
                   WHERE isRead = 1";
$totalUnreadStmt = $conn->prepare($totalUnreadSql);
$totalUnreadStmt->execute();
$totalUnreadResult = $totalUnreadStmt->get_result();
$totalUnread = $totalUnreadResult->fetch_assoc();
$totalUnreadStmt->close();
$totalUnreadCount = $totalUnread ? intval($totalUnread['total']) : 0;

// Process all sessions
$sessions = [];
while ($session = $result->fetch_assoc()) {
    $sessionId = intval($session['id']);

    // Fetch last message for the session
    $messageSql = "SELECT sender, message, isRead
                   FROM chat_messages
                   WHERE sessionId = ?
                   ORDER BY id DESC
                   LIMIT 1";
    $messageStmt = $conn->prepare($messageSql);
    $messageStmt->bind_param("i", $sessionId);
    $messageStmt->execute();
    $messageResult = $messageStmt->get_result();
    $lastMessage = $messageResult->fetch_assoc();
    $messageStmt->close();

    // Fetch count of unread messages for the session
    $unreadMessageSql = "SELECT COUNT(*) as unread
                         FROM chat_messages
                         WHERE sessionId = ? AND isRead = 1";
    $unreadMessageStmt = $conn->prepare($unreadMessageSql);
    $unreadMessageStmt->bind_param("i", $sessionId);
    $unreadMessageStmt->execute();
    $unreadMessageResult = $unreadMessageStmt->get_result();
    $unreadMessage = $unreadMessageResult->fetch_assoc();
    $unreadMessageStmt->close();

    // Assign values to session array
    $session['totalUnread'] = $totalUnreadCount;
    $session['unreadMessages'] = $unreadMessage ? intval($unreadMessage['unread']) : 0;
    $session['lastMessage'] = $lastMessage ? $lastMessage['message'] : '';
    $session['isRead'] = $lastMessage ? intval($lastMessage['isRead']) : 0;

    // Handle sender formatting
    $sender = $lastMessage['sender'] ?? '';
    $session['sender'] = match ($sender) {
        'seller' => 'You: ',
        '' => '',
        default => $sender . ': '
    };

    // Add to sessions array
    $sessions[] = $session;
}

$stmt->close();

// Prepare and output JSON response
$response = [
    'sellerID' => $seller_id,
    'sessions' => $sessions // Array of sessions
];

// Clear any previous output and send JSON
ob_clean(); // Clear buffer
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Ensure no further output
ob_end_flush();
exit;
?>