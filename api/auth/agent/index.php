<?php
// Start session
session_start();

unset($_SESSION['error']);
unset($_SESSION['resetsuccess']);

// Path configurations
$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/agent/";
require_once($ENV_IPATH . "env.php");

require $_SERVER["DOCUMENT_ROOT"] . '/api/classes.php';
$classSendEmail = new SendEmail();

// Initialize database connection
try {
    $pdo = initializeDatabase_AG();
} catch (PDOException $e) {
    // logAuthError("Database connection failed: " . $e->getMessage());
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
    file_put_contents($logPath . "agent_auth.log", $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Validates CSRF token
 * @param string $token Submitted CSRF token
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Authenticates an agent
 * @param string $email Agent's email
 * @param string $password Agent's password
 * @param PDO $pdo Database connection
 * @return array Authentication result
 */
function authenticateUser($email, $password, $pdo)
{
    if (empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'Email and password required'];
    }

    $stmt = $pdo->prepare("
        SELECT agent_id, name, email, password 
        FROM agents 
        WHERE email = :email
    ");
    $stmt->execute(['email' => $email]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($agent && password_verify($password, $agent['password'])) {
        return ['success' => true, 'agent' => $agent];
    }
    return ['success' => false, 'error' => 'Invalid email or password'];
}

/**
 * Sets session variables for authenticated agent
 * @param array $agent Agent data
 */
function setAgentSession($agent)
{
    $_SESSION['agent_id'] = $agent['agent_id'];
    $_SESSION['name'] = $agent['name'];
    $_SESSION['email'] = $agent['email'];
    $_SESSION['isAgentLoggedin'] = true;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    try {
        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($csrfToken)) {
            // logAuthError("CSRF token validation failed");
            $_SESSION['error'] = true;
            $_SESSION['message'] = 'An error occured while trying to login. Please try again later.';
            header("Location: /agent/login/");
            exit();
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = authenticateUser($email, $password, $pdo);

        if ($result['success']) {
            // logAuthError("Login successful for: $email");
            setAgentSession($result['agent']);
            header("Location: /agent/");
            exit();
        } else {
            // logAuthError("Login failed for: $email - " . $result['error']);
            $_SESSION['error'] = true;
            $_SESSION['message'] = 'Incorrect credentials.';
            $_SESSION['email'] = $email;
            $_SESSION['password'] = $password;
            header("Location: /agent/login/");
            exit();
        }
    } catch (Exception $e) {
        // logAuthError("Error processing login: " . $e->getMessage());
        $_SESSION['error'] = true;
        $_SESSION['message'] = 'An error occured while trying to login. Please try again later.';
        header("Location: /agent/login/");
        exit();
    }
} else if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reset-password'])) {

    $token = isset($_POST['token']) ? preg_replace('/[^a-zA-Z0-9]/', '', $_POST['token']) : '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $_SESSION['error'] = true;
        $_SESSION['message'] = "Invalid CSRF token.";
        header("Location: /agent/reset-password/?token=" . urlencode($token));
        exit;
    }

    // Validate passwords
    if (strlen($password) < 8) {
        $_SESSION['error'] = true;
        $_SESSION['message'] = "Password must be at least 8 characters long.";
        header("Location: /agent/reset-password/?token=" . urlencode($token));
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = true;
        $_SESSION['message'] = "Passwords do not match.";
        header("Location: /agent/reset-password/?token=" . urlencode($token));
        exit;
    }

    try {
        // Verify token
        $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset || strtotime($reset['expires_at']) < time()) {
            $_SESSION['error'] = true;
            $_SESSION['message'] = "Invalid or expired reset link.";
            header("Location: /agent/forgot-password/");
            exit;
        }

        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update user's password
        $stmt = $pdo->prepare("UPDATE agents SET password = :password WHERE email = :email");
        $stmt->execute([
            'password' => $hashed_password,
            'email' => $reset['email']
        ]);

        // Delete the used reset token
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :token");
        $stmt->execute(['token' => $token]);

        // Send reset email
        $_SESSION['resetsuccess'] = true;

        $stmt = $pdo->prepare("SELECT name FROM agents WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $reset['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $classSendEmail->sendPasswordChangeConfirmationEmail($reset['email'], $user['name']);
        }
        header("Location: /agent/reset-password/");
        exit;
    } catch (PDOException $e) {
        // logAuthError("Database error in reset password: " . $e->getMessage());
        $_SESSION['error'] = true;
        $_SESSION['message'] = "An error occurred. Please try again later.";
        header("Location: /agent/reset-password/?token=" . urlencode($token));
        exit;
    }
} else if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['forgort-pass'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 1;
        $_SESSION['message'] = true;
        $_SESSION['message'] = "Please enter a valid email address.";
        header("Location: /agent/forgot-password/");
        exit;
    }

    // Check if email exists in the database (assuming a users table)
    try {
        $stmt = $pdo->prepare("SELECT agent_id, name FROM agents WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            // logAuthError("Im here. $email > Token < $reset_token");

            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in database with expiry (1 hour)
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) 
            VALUES (:email, :token, :expires_at)
            ON DUPLICATE KEY UPDATE token = :token_update, expires_at = :expires_at_update");
            $stmt->execute([
                'email' => $email,
                'token' => $reset_token,
                'expires_at' => $expires_at,
                'token_update' => $reset_token,
                'expires_at_update' => $expires_at
            ]);

            // Send reset email
            if ($classSendEmail->sendResetPasswordEmail($email, $user['name'], $reset_token)) {
                $_SESSION['success'] = 1;
                $_SESSION['message'] = "A password reset link has been sent to your email.";
            } else {
                $_SESSION['error'] = 1;
                $_SESSION['message'] = "Failed to send reset email. Please try again later.";
            }
        } else {
            $_SESSION['error'] = 1;
            $_SESSION['message'] = "No account found with that email address.";
        }
    } catch (PDOException $e) {
        // logAuthError("Database error in forgot password: " . $e->getMessage());
        $_SESSION['error'] = 1;
        $_SESSION['message'] =  "An error occurred. Please try again later.";
    }

    header("Location: /agent/forgot-password/");
    exit;
} else {
    // logAuthError("Invalid request method or missing login parameter");
    $_SESSION['error'] = true;
    $_SESSION['message'] = 'An error occured while trying to login. Please try again later.';
    header("Location: /agent/login/");
    exit();
}
