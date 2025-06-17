<?php
// Enable error reporting

header('Content-Type: application/json');

// Define required paths
$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
include($ENV_IPATH . "conn.php");
include($ENV_IPATH . "env.php");

// Initialize the database connection
$pdo = initializeDatabase();

session_start();

// Function to log messages to a file
function log_to_file($message)
{
    global $logPath;
    $timestamp = (new DateTime())->modify('+2 hours')->format('Y-m-d H:i');
    $logMessage = "$timestamp - $message\n";
    file_put_contents($logPath . "like_review.log", $logMessage, FILE_APPEND);
}

// function to update the rating on product_reviews table by adding 1 to wahttever value that was presint where review_id - id
function update_like_by_id($review_id)
{
    global $conn;
    $stmt = $conn->prepare("UPDATE product_reviews SET likes = likes + 1 WHERE review_id = ?");
    $stmt->bind_param("i", $review_id); // "i" denotes an integer parameter
    if ($stmt->execute()) {
        // Retrieve the updated likes count
        $stmt = $conn->prepare("SELECT likes FROM product_reviews WHERE review_id = ?");
        $stmt->bind_param("i", $review_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $newLikes = $row['likes'];

        return $newLikes;
    } else {
        return 0;
    }
}

$customerID = $_SESSION["customerID"];

if ($customerID == '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to like a review']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Parse incoming JSON request
    $input = json_decode(file_get_contents('php://input'), true);

    $action = $input['action'] ?? '';
    $reviewid = $input['reviewid'] ?? '';

    if (!$action) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No action specified']);
        exit;
    }

    if (!$reviewid) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No review ID specified']);
        exit;
    }

    if ($action == 'like') {

        $jsonResults = [
            'status' => 'success',
            'message' => 'Review liked successfully',
            'reviewid' => $reviewid,
            'newLikes' => update_like_by_id($reviewid)
        ];
    } else {
        $jsonResults = [
            'status' => 'error',
            'message' => 'Invalid action'
        ];
    }
    echo json_encode($jsonResults, JSON_PRETTY_PRINT);
}
