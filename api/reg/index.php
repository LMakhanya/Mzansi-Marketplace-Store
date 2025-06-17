<?php
// Start session
session_start();
// Database connection settings
$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
include($ENV_IPATH . "env.php");
include($ENV_IPATH . "conn.php");


require $_SERVER["DOCUMENT_ROOT"] . '/api/classes.php';


$classReg = new Registration;
$classSendEmail = new SendEmail;

function getDeviceType()
{
    // Basic device type detection based on User-Agent
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    if (stripos($userAgent, 'mobile') !== false) {
        return 'Mobile';
    } elseif (stripos($userAgent, 'tablet') !== false) {
        return 'Tablet';
    } else {
        return 'Desktop';
    }
}

function generateSellerId($pdo)
{
    $prefix = "SELLER_";
    do {
        $randomNumber = mt_rand(10000, 99999);
        $sellerId = $prefix . $randomNumber;
        $stmt = $pdo->prepare("SELECT seller_id FROM sellers WHERE seller_id = :seller_id");
        $stmt->execute([':seller_id' => $sellerId]);
    } while ($stmt->fetch());
    return $sellerId;
}

// Utility functions
function checkIfEmailExist($pdo, $email)
{
    $sql = "SELECT seller_id FROM sellers_auth WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':username' => $email]);
    return $stmt->fetchColumn();
}


function createUserAuth($pdo, $email, $password)
{
    $seller_id = generateSellerId($pdo);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO sellers_auth (seller_id, username, password, created_at) VALUES (:seller_id, :email, :password, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':seller_id' => $seller_id,
        ':email' => $email,
        ':password' => $hashed_password
    ]);
    return $seller_id;
}

function createAccount($pdo, $sellerID, $firstName, $lastName, $email, $phone)
{
    $sellerIP = getUserIP();
    $sellerHostName = getUserHostName($sellerIP);
    $deviceType = getDeviceType();

    $sql = "INSERT INTO sellers (seller_id, firstname, lastname, email, phone, ip_address, hostname, device_type, status, created_at) 
            VALUES (:seller_id, :firstname, :lastname, :email, :phone, :ip_address, :hostname, :device_type, 'not-registered', NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':seller_id' => $sellerID,
        ':firstname' => $firstName,
        ':lastname' => $lastName,
        ':email' => $email,
        ':phone' => $phone,
        ':ip_address' => $sellerIP,
        ':hostname' => $sellerHostName,
        ':device_type' => $deviceType
    ]);
    return $pdo->lastInsertId();
}



function validateFileUpload($file)
{
    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if ($file['size'] > $max_size) return false;
    if (!in_array($file['type'], $allowed_types)) return false;
    return true;
}


// Main processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = initializeDatabase();
        $pdo->beginTransaction();

        // CSRF protection
        /*  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid CSRF token");
            } */

        $action = filter_var($_POST['action'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo 'Email : ' . $email;
            throw new Exception("Invalid email format");
        }

        $sellerID = checkIfEmailExist($pdo, $email);

        if (!$sellerID) {

            $firstName = $classReg->sanitizeString($_POST['firstname'] ?? '');
            $lastName = $classReg->sanitizeString($_POST['lastname'] ?? '');

            $fullname = $firstName . ' ' . $lastName;

            $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
            $password = $_POST['cpassword'] ?? '';

            if ($action === 'create') {
                $classReg->log_to_file("-------------- Creating New user ------------------");
                if (strlen($password) < 8) {
                    throw new Exception("Password must be at least 8 characters");
                }

                $sellerID = createUserAuth($pdo, $email, $password);
                $seller = createAccount($pdo, $sellerID, $firstName, $lastName, $email, $phone);

                if ($seller) {
                    $pdo->commit();

                    $classSendEmail->accountCreatedEmail($email, $fullname);

                    $classReg->log_to_file("New seller created: $sellerID");
                    $_SESSION['reg_seller_id'] = $sellerID;
                    $_SESSION['reg_email'] = $email;
                    header('Location: /registration/onboarding/');
                    exit;
                }
                $classReg->log_to_file("-------------- End create user ------------------");
            }
        } else {
            if ($action === 'register') {
                $classReg->log_to_file("--------------------------------");

                // Business Details
                $planName = $classReg->sanitizeString($_POST['planName'] ?? '');
                $businessName = $classReg->sanitizeString($_POST['businessName'] ?? '');
                $businessOwner = $classReg->sanitizeString($_POST['businessOwner'] ?? '');
                $businessType = $classReg->sanitizeString($_POST['businessTypeOption'] ?? '');
                $registrationNumber = $classReg->sanitizeString($_POST['registrationNumber'] ?? '') ?: null;
                //$taxId = $classReg->sanitizeString($_POST['taxId'] ?? '') ?: null;
                $description = $classReg->sanitizeString($_POST['b-description'] ?? '');
                $slogan = $classReg->sanitizeString($_POST['slogan'] ?? '') ?: null;
                $addressl1 = $classReg->sanitizeString($_POST['addressl1'] ?? '');
                $addressl2 = $classReg->sanitizeString($_POST['addressl2'] ?? '') ?: null;
                $city = $classReg->sanitizeString($_POST['city'] ?? '');
                $postalCode = $classReg->sanitizeString($_POST['postalCode'] ?? '');
                $province = $classReg->sanitizeString($_POST['province'] ?? '');

                $businessId = 'BUS_' . bin2hex(random_bytes(4));

                $requiredFields = [
                    'planName' => $planName,
                    'businessName' => $businessName,
                    'businessOwner' => $businessOwner,
                    'businessType' => $businessType,
                    'description' => $description,
                    'addressl1' => $addressl1,
                    'city' => $city,
                    'postalCode' => $postalCode,
                    'province' => $province
                ];

                foreach ($requiredFields as $field => $value) {
                    if (empty($value)) {
                        throw new Exception("Required field '$field' is missing");
                    }
                }

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM sss_business WHERE seller_id = :seller_id");
                $stmt->bindParam(':seller_id', $sellerID);
                $stmt->execute();
                $exists = $stmt->fetchColumn();

                if ($exists) {
                    // update sql
                    $sql_business = "UPDATE sss_business SET b_name = :b_name, b_owner_name = :b_owner_name, b_type = :b_type, b_regno = :b_regno, description = :description, slogan = :slogan WHERE seller_id = :seller_id";
                    $stmt = $pdo->prepare($sql_business);
                    $stmt->execute([
                        ':b_name' => $businessName,
                        ':b_owner_name' => $businessOwner,
                        ':b_type' => $businessType,
                        ':b_regno' => $registrationNumber,
                        ':description' => $description,
                        ':slogan' => $slogan,
                        ':seller_id' => $sellerID
                    ]);
                } else {
                    // Insert business
                    $sql_business = "INSERT INTO sss_business (b_id, seller_id, b_name, b_owner_name, b_type, b_regno   , description, slogan, created_at) VALUES (:b_id, :seller_id, :b_name, :b_owner_name, :b_type, :b_regno, :description, :slogan, NOW())";
                    $stmt = $pdo->prepare($sql_business);
                    $stmt->execute([
                        ':b_id' => $businessId,
                        ':seller_id' => $sellerID,
                        ':b_name' => $businessName,
                        ':b_owner_name' => $businessOwner,
                        ':b_type' => $businessType,
                        ':b_regno' => $registrationNumber,
                        ':description' => $description,
                        ':slogan' => $slogan
                    ]);
                }


                // **Check if the INSERT was successful**
                if ($stmt->rowCount() > 0) {
                    $classReg->log_to_file("Business inserted/Updated successfully.");

                    // Update seller status to registered
                    $updateStatusSQL = "UPDATE sellers SET `status` = 'registered', account_plan = :planName WHERE seller_id = :seller_id";
                    $stmt = $pdo->prepare($updateStatusSQL);
                    $stmt->execute([
                        ':seller_id' => $sellerID,
                        ':planName' => $planName
                    ]);

                    $_SESSION['reg_status'] = 'registered';

                    // Check if the update was successful
                    if ($stmt->rowCount() > 0) {
                        $classReg->log_to_file("Seller status updated successfully.");

                        // Insert address
                        $sql_address = "INSERT INTO sss_b_address (seller_id, b_id, address_l1, address_l2, city, postal_code, province, created_at) VALUES (:seller_id, :b_id, :address_l1, :address_l2, :city, :postal_code, :province, NOW())";
                        $stmt = $pdo->prepare($sql_address);
                        $stmt->execute([
                            ':seller_id' => $sellerID,
                            ':b_id' => $businessId,
                            ':address_l1' => $addressl1,
                            ':address_l2' => $addressl2,
                            ':city' => $city,
                            ':postal_code' => $postalCode,
                            ':province' => $province
                        ]);
                        if ($stmt->rowCount() > 0) {
                            $classReg->log_to_file("Address inserted successfully.");
                        } else {
                            $classReg->log_to_file("Address insertion failed.");
                        }
                    } else {
                        $classReg->log_to_file("Seller status update failed.");
                    }
                } else {
                    $classReg->log_to_file("Business insertion failed.");
                }

                // Handle logo upload
                if (isset($_FILES['logoUpload']) && $_FILES['logoUpload']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = $_SERVER["DOCUMENT_ROOT"] . "/registration/uploads/logos/";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    if (!validateFileUpload($_FILES['logoUpload'])) {
                        throw new Exception("Invalid file upload");
                    }

                    $fileName = $_FILES['logoUpload']['name'];
                    $fileTmpPath = $_FILES['logoUpload']['tmp_name'];
                    $newFileName = $businessId . '-' . time() . '-' . basename($fileName);
                    $uploadFilePath = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $uploadFilePath)) {
                        $sql = "UPDATE sss_business SET logo = :logo_path WHERE b_id = :b_id";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([':logo_path' => $newFileName, ':b_id' => $businessId]);
                        $classReg->log_to_file("Logo uploaded successfully: $uploadFilePath");
                    }
                }

                $pdo->commit();
                $classReg->log_to_file("Registration completed successfully for seller: $sellerID");

                $classReg->log_to_file("--------------------------------");
                header('Location: /registration/selfie-verification/');
                exit;
            } else {
                $classReg->thumbnailsRedirect($pdo, $sellerID);
            }
        }
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        $classReg->log_to_file("Error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['email'])) {

    header('Content-Type: application/json');
    $pdo = initializeDatabase();
    $pdo->beginTransaction();
    $email = mysqli_real_escape_string($conn, $_GET['email']);

    $sellerID = checkIfEmailExist($pdo, $email);
    if ($sellerID) {
        $jsonResults = [
            'status' => 'success',
            'data' => [
                'isRegistered' => true,
                'email' => $email,
            ]
        ];
    } else {
        $jsonResults = [
            'status' => 'success',
            'data' => [
                'isRegistered' => false,
                'email' => $email,
            ]
        ];
    }
    echo json_encode($jsonResults, JSON_PRETTY_PRINT);
}
// Generate CSRF token for form
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
