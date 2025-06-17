<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>

<?php
// Start session
session_start();

// Path configurations
$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
require_once($ENV_IPATH . "conn.php");
require_once($ENV_IPATH . "env.php");

require $_SERVER["DOCUMENT_ROOT"] . '/api/classes.php';
$classSendEmail = new SendEmail();

// Initialize database connection
try {
    $pdo = initializeDatabase();
    $conn = $pdo;
} catch (PDOException $e) {
    logAuthError("Database connection failed: " . $e->getMessage());
    die("Database connection error");
}

/**
 * Logs error messages with timestamp
 * @param string $message The error message to log
 */
function logAuthError($message)
{
    global $logPath;
    $timestamp = (new DateTime())->modify('+2 hours')->format('Y-m-d H:i');
    $logMessage = "$timestamp - $message\n";
    file_put_contents($logPath . "auth.log", $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Generates a random customer ID
 * @return string Random 6-character ID
 */
function generateCustomerID()
{
    $characters = '0123456789abcdelmABCDEFGHIJKLMNOPQRSTUVWXYZ';
    return substr(str_shuffle($characters), 0, 6);
}

/**
 * Checks if customer ID exists in database
 * @param string $customerID ID to check
 * @param PDO $conn Database connection
 * @return bool True if exists, false otherwise
 */
function checkCustomerIDExists($customerID, $conn)
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM customer WHERE customerID = ?");
    $stmt->execute([$customerID]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Generates a unique customer ID
 * @param PDO $conn Database connection
 * @return string Unique customer ID
 */
function generateUniqueCustomerID($conn)
{
    do {
        $customerID = 'kv' . date('Y') . '_' . generateCustomerID();
    } while (checkCustomerIDExists($customerID, $conn));
    return $customerID;
}

/**
 * Checks if email already exists in database
 * @param string $email Email to check
 * @param PDO $conn Database connection
 * @return bool True if exists, false otherwise
 */
function checkEmailExists($email, $conn)
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM customer WHERE Email = ?");
    $stmt->execute([$email]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Validates registration form data
 * @param array $fields Form fields
 * @return array Array containing validation status and errors
 */
function validateRegistrationData($fields)
{
    $errors = [];

    if (empty($fields['name'])) $errors[] = 'First name is required';
    if (empty($fields['surname'])) $errors[] = 'Last name is required';
    if (empty($fields['email']) || !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    if (empty($fields['phone'])) $errors[] = 'Phone number is required';
    if (empty($fields['password']) || strlen($fields['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }

    return [
        'isValid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Registers a new customer
 * @param array $fields Form fields
 * @param PDO $conn Database connection
 * @return array Registration result
 */
function registerCustomer($fields, $conn)
{
    $validation = validateRegistrationData($fields);

    if (!$validation['isValid']) {
        return ['success' => false, 'errors' => $validation['errors']];
    }

    if (checkEmailExists($fields['email'], $conn)) {
        return ['success' => false, 'errors' => ['Email already registered']];
    }

    $hashedPassword = password_hash($fields['password'], PASSWORD_ARGON2I);
    $customerID = $_SESSION['customerID'];

    $sql = "UPDATE customer SET FirstName= :firstName, LastName= :lastName, username= :username, Phone= :phone, Email= :email, Password= :password, cust_type = 'customer' , Created_at=NOW() WHERE customerID = :customerID";

    $stmt = $conn->prepare($sql);

    $success = $stmt->execute([
        ':firstName' => $fields['name'],
        ':lastName' => $fields['surname'],
        ':username' => $fields['name'],
        ':phone' => $fields['phone'],
        ':email' => $fields['email'],
        ':password' => $hashedPassword,
        ':customerID' => $customerID
    ]);

    if ($success) {
        return ['success' => true, 'customerID' => $customerID];
    }
    return ['success' => false, 'errors' => ['Registration failed']];
}

/**
 * Authenticates a user
 * @param string $login Login (username or email)
 * @param string $password Password
 * @param PDO $conn Database connection
 * @return array Authentication result
 */
function authenticateUser($login, $password, $conn)
{
    if (empty($login) || empty($password)) {
        return ['success' => false, 'error' => 'Login and password required'];
    }

    // Fixed query - using different parameter names for username and email
    $sql = "SELECT * FROM customer WHERE Email = :email";
    $stmt = $conn->prepare($sql);

    // Provide both parameters explicitly
    $stmt->execute([
        ':email' => $login
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['Password'])) {
        return ['success' => true, 'user' => $user];
    }
    return ['success' => false, 'error' => 'Invalid credentials'];
}

/**
 * Sets session variables for authenticated user
 * @param array $user User data
 */
function setUserSession($user)
{
    $_SESSION["customerID"] = $user["CustomerID"];
    $_SESSION["username"] = $user["username"];
    $_SESSION["FirstName"] = $user["firstname"];
    $_SESSION["LastName"] = $user["lastname"];
    $_SESSION["Address"] = $user["addressID"] ?? '';
    $_SESSION["userEmail"] = $user["Email"];
    $_SESSION["Phone"] = $user["phone"];
    $_SESSION["loggedin"] = true;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $currentPage = $_POST['currentPage'] ?? '/';

    try {
        if (isset($_POST['create-account'])) {
            $fields = [
                'name' => $_POST['name'] ?? '',
                'surname' => $_POST['surname'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'password' => $_POST['password'] ?? ''
            ];

            $result = registerCustomer($fields, $conn);

            $email = $fields['email'];
            $fullname = $fields['name'] . ' ' . $fields['surname'];

            if ($result['success']) {
                $sql = "SELECT * FROM customer WHERE customerID = :customerID";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':customerID' => $result['customerID']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                setUserSession($user);
                logAuthError("Customer created: " . $result['customerID']);
                $classSendEmail->accountCreatedEmail($email, $fullname);
                header("Location: /");
                exit();
            } else {
                logAuthError("Account creation failed: " . implode(', ', $result['errors']));
                header("Location: $currentPage?error=" . urlencode(implode(',', $result['errors'])));
                exit();
            }
        } elseif (isset($_POST['login'])) {

            $login = $_POST['username'] ?? '';  // Changed variable name for clarity
            $password = $_POST['password'] ?? '';

            $result = authenticateUser($login, $password, $conn);

            if ($result['success']) {

                logAuthError("Login successful for: " . json_encode($result, JSON_PRETTY_PRINT));

                setUserSession($result['user']);

                logAuthError("Login successful for: $login");
                header("Location: /user/");
                exit();
            } else {
                logAuthError("Login failed for: $login - " . $result['error']);
                header("Location: $currentPage?error=" . urlencode($result['error']));
                exit();
            }
        }
    } catch (Exception $e) {
        logAuthError("Error processing request: " . $e->getMessage());
        header("Location: $currentPage?error=An Error occured while logging In, Please try again later.");
        exit();
    }
}
?>