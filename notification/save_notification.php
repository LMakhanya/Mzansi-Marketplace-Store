<?php
// Database connection setting
$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$CONTAINER_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/admin/containers";
// Database connection settings
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
require_once $ENV_IPATH . "conn.php";

require_once $ENV_IPATH . "env.php";

// Initialize the database connection
$pdo = initializeDatabase();

// Check if form data is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $conn->real_escape_string($_POST['title']);
    $message = $conn->real_escape_string($_POST['message']);

    // Prepare SQL query to insert a new notification
    $sql = "INSERT INTO sss_notifications (title, message, created_at) VALUES ('$title', '$message', CURRENT_TIMESTAMP)";

    // Execute query to insert notification
    if ($conn->query($sql) === TRUE) {
        $notification_id = $conn->insert_id; // Get the ID of the newly inserted notification

        // Update the notification_ids in sss_unread_notifications for each user
        $update_sql = "SELECT id, notification_ids FROM sss_unread_notifications";
        $result = $conn->query($update_sql);

        if ($result->num_rows > 0) {
            // Loop through each user and update their notification_ids
            while ($row = $result->fetch_assoc()) {
                $user_id = $row['id'];
                $current_ids = $row['notification_ids'];

                // If the user already has some notification IDs, append the new one, else just add the new ID
                $new_ids = ($current_ids) ? $current_ids . ',' . $notification_id : $notification_id;

                // Update the notification_ids for the user
                $update_user_sql = "UPDATE sss_unread_notifications SET notification_ids = '$new_ids' WHERE id = '$user_id'";
                $conn->query($update_user_sql);
            }
        }

        header('location: /admin/pages/notifications.php');
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
