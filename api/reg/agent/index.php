<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/agent/";
require_once $ENV_IPATH . "env.php";
require $_SERVER["DOCUMENT_ROOT"] . '/api/classes.php';

$classSendEmail = new SendEmail;

// === Utilities ===

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone)
{
    return preg_match('/^(\+27|0)[6-8][0-9]{8}$/', $phone);
}

function sanitizeInput($input)
{
    return trim($input ?? '');
}

function generateAgentPassword($fullName)
{
    $currentYear = date('Y');
    $cleanName = preg_replace('/[^a-zA-Z]/', '', $fullName); // Remove non-letters
    $shortName = strtolower(substr($cleanName, 0, 6)); // First 6 letters
    return $shortName . $currentYear; // e.g., "johnsm2025"
}

// === Database Operations ===

function generateUniqueAgentId($pdo, $prefix = 'MM-AG-', $maxAttempts = 10)
{
    for ($i = 0; $i < $maxAttempts; $i++) {
        $randomNum = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $agentId = $prefix . $randomNum;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM agents WHERE agent_id = :agentId");
        $stmt->execute(['agentId' => $agentId]);

        if ($stmt->fetchColumn() == 0) {
            return $agentId;
        }
    }
    throw new Exception('Unable to generate a unique agent ID after multiple attempts');
}

function emailExists($pdo, $email)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM agents WHERE email = :email");
    $stmt->execute(['email' => $email]);
    return $stmt->fetchColumn() > 0;
}

function insertAgent($pdo, $agentId, $fullName, $email, $password)
{
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO agents (agent_id, name, email, profile_picture, password) 
        VALUES (:agent_id, :name, :email, NULL, :password)
    ");
    $stmt->execute([
        'agent_id' => $agentId,
        'name' => $fullName,
        'email' => $email,
        'password' => $hashedPassword,
    ]);
}

function insertAgentDetails($pdo, $agentId, $phone, $location, $experience, $network)
{
    $stmt = $pdo->prepare("
        INSERT INTO agent_details (agent_id, phone, location, experience, network_size) 
        VALUES (:agent_id, :phone, :location, :experience, :network_size)
    ");
    $stmt->execute([
        'agent_id' => $agentId,
        'phone' => $phone,
        'location' => $location,
        'experience' => $experience ?: null,
        'network_size' => $network,
    ]);
}

function insertReferralLink($pdo, $agentId, $referralUrl)
{
    $stmt = $pdo->prepare("
        INSERT INTO referral_links (agent_id, referral_url) 
        VALUES (:agent_id, :referral_url)
    ");
    $stmt->execute([
        'agent_id' => $agentId,
        'referral_url' => $referralUrl,
    ]);
}


// === Validation ===

function validateInputs($data, $pdo)
{
    $errors = [];

    if (empty($data['fullName'])) {
        $errors[] = 'Full name is required';
    }

    if (!validateEmail($data['email'])) {
        $errors[] = 'Invalid email address';
    }

    if (empty($data['phone'])) {
        $errors[] = 'Phone number is required';
    } elseif (!validatePhone($data['phone'])) {
        $errors[] = 'Invalid phone number (e.g., +27 or 0 followed by 9 digits)';
    }

    if (empty($data['location'])) {
        $errors[] = 'Location is required';
    }

    if (!empty($data['experience']) && !in_array($data['experience'], ['0', '1-2', '3-5', '5+'])) {
        $errors[] = 'Invalid experience selection';
    }

    if ($data['network'] < 0) {
        $errors[] = 'Network size cannot be negative';
    }

    if (emailExists($pdo, $data['email'])) {
        $errors[] = 'Email address is already registered';
    }

    return $errors;
}

// === Main Handler ===

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = initializeDatabase_AG();

        // Gather and sanitize input
        $input = [
            'fullName'   => sanitizeInput($_POST['full-name'] ?? ''),
            'email'      => sanitizeInput($_POST['email'] ?? ''),
            'phone'      => sanitizeInput($_POST['phone'] ?? ''),
            'location'   => sanitizeInput($_POST['location'] ?? ''),
            'experience' => sanitizeInput($_POST['experience'] ?? ''),
            'network'    => isset($_POST['network']) ? (int)$_POST['network'] : 0,
        ];

        // Validate inputs
        $validationErrors = validateInputs($input, $pdo);
        if (!empty($validationErrors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $validationErrors]);
            exit;
        }

        $pdo->beginTransaction();

        // Process agent creation
        $agentId = generateUniqueAgentId($pdo);
        $referralUrl = "https://seller.themzansimarketplace.co.za/registration/account/?ref=$agentId";
        $password = generateAgentPassword($input['fullName']);

        insertAgent($pdo, $agentId, $input['fullName'], $input['email'], $password);
        insertAgentDetails($pdo, $agentId, $input['phone'], $input['location'], $input['experience'], $input['network']);
        insertReferralLink($pdo, $agentId, $referralUrl);

        // Send email (don't fail on exception)
        try {
            $classSendEmail->agentAccountCreatedEmail($input['email'], $input['fullName'], $agentId, $referralUrl, $password);
            $classSendEmail->newAgentrNotification('agents@themzansimarketplace.co.za',  $input['fullName'], $input['email'], $agentId);
        } catch (Exception $e) {
            error_log("Email failed to send: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Agent registered successfully',
            'agent_id' => $agentId,
            'referral_url' => $referralUrl,
        ]);

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['email'])) {

    header('Content-Type: application/json');
    $pdo = initializeDatabase_AG();
    $pdo->beginTransaction();

    $email = $_GET['email'];

    if (emailExists($pdo, $email)) {
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
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
