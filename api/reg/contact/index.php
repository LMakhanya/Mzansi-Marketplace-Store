
<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
require_once $ENV_IPATH . "conn.php";

require_once $ENV_IPATH . "env.php";


require $_SERVER["DOCUMENT_ROOT"] . '/api/classes.php';
$classSendEmail = new SendEmail;



if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST['valid']) && !empty($_POST['valid'])) {
        try {
            $data = [
                'fullname' => "{$_POST['first-name']} {$_POST['last-name']}",
                'email' => $_POST['email'],
                'message' => $_POST['message'],
            ];

            logError("Inserting to seller_contact_form table:  FNAME: {$_POST['first-name']}, LNAME: {$_POST['last-name']}, EMAIL: {$_POST['email']}, MESSAGE: {$_POST['message']}");
            $cmd = "INSERT INTO seller_contact_form (fullname, email, message) 
                    VALUES (?, ?, ?)";
            $stmt = $conn->prepare($cmd);
            $stmt->bind_param("sss", $data['fullname'], $data['email'], $data['message']);
            if ($stmt->execute()) {
                $stmt->close();

                // $admin_email = 'luvuyom@themzansimarketplace.co.za';
                $admin_email = 'loovoour@gmail.com';

                $classSendEmail->contactFormNotification($admin_email, $data['fullname'], $data['email'], $data['message']);

                echo '<script>window.location.href = "/portal/contact.php/?success=1";</script>';
                exit();
            } else {
                logError("Error executing query: " . $stmt->error);
                echo '<script>window.location.href = "/portal/contact.php/?error=1";</script>';
                exit();
            }
        } catch (Exception $e) {
            logError("Error inserting to seller_contact_form table: " . $e->getMessage());
            echo '<script>window.location.href = "/portal/contact.php/?error=1";</script>';
            exit();
        }
    } else {
        logError("failed to valid form field..");
        echo 'error';
    }
}
