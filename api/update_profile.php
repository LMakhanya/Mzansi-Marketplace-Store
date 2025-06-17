
<?php
session_start();
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
require_once($ENV_IPATH . "conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['customerID'])) {
    $customerID = $_SESSION['customerID'];

    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';



    // Input validation should be added here

    $sql = "UPDATE customer SET 
            FirstName = ?, 
            LastName = ?, 
            Email = ?, 
            phone = ?, 
            dateOfBirth = ?, 
            gender = ?
            WHERE CustomerID = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $firstName, $lastName, $email, $phone, $dob, $gender, $customerID);

    if ($stmt->execute()) {
        // Update address separately if needed
        $_SESSION["FirstName"] = $firstName;
        $_SESSION["LastName"] = $lastName;
        header("Location: /user/?personalD=success");
    } else {
        header("Location: /user/?personalD=error");
    }
    exit();
}
?>