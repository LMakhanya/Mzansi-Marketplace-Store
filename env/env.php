<?php
include $_SERVER["DOCUMENT_ROOT"] . '/vendor/autoload.php';

$projectRoot = realpath($_SERVER["DOCUMENT_ROOT"] . '/../'); // one level up
$dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
$dotenv->load();

$logPath = $_SERVER["DOCUMENT_ROOT"] . "/logs/";

function initializeDatabase()
{
    $host = $_ENV['DB_HOST'] ?? throw new Exception('DB_HOST is not set');
    $db = $_ENV['DB_NAME'] ?? throw new Exception('DB_NAME is not set');
    $user = $_ENV['DB_USER'] ?? throw new Exception('DB_USER is not set');
    $pass = $_ENV['DB_PASSWORD'] ?? throw new Exception('DB_PASSWORD is not set');
    $charset = $_ENV['DB_CHARSET'] ?? throw new Exception('DB_CHARSET is not set');

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }
}
function initializeDatabase_AG()
{
    $host = $_ENV['DB_HOST_AG'] ?? throw new Exception('DB_HOST is not set');
    $db = $_ENV['DB_NAME_AG'] ?? throw new Exception('DB_NAME is not set');
    $user = $_ENV['DB_USER_AG'] ?? throw new Exception('DB_USER is not set');
    $pass = $_ENV['DB_PASSWORD_AG'] ?? throw new Exception('DB_PASSWORD is not set');
    $charset = $_ENV['DB_CHARSET_AG'] ?? throw new Exception('DB_CHARSET is not set');

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }
}
function getUserIP()
{
    // Check for shared internet/ISP IP
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for IPs passing through proxies
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    // Get the remote IP address
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

// Function to get the hostname (optional)
function getUserHostName($ip)
{
    return gethostbyaddr($ip) ?: 'Unknown';
}

// Function to detect device type and name
function getDeviceInfo()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $deviceInfo = ['type' => 'Unknown', 'name' => 'Unknown'];

    // Common mobile device patterns
    $mobilePatterns = [
        '/iPhone/i' => 'iPhone',
        '/iPad/i' => 'iPad',
        '/Android.*Mobile/i' => 'Android Phone',
        '/Android/i' => 'Android Device', // Fallback for Android tablets
        '/Windows Phone/i' => 'Windows Phone',
        '/BlackBerry/i' => 'BlackBerry',
        '/SM-[A-Za-z0-9]+/i' => 'Samsung Device', // Samsung model codes (e.g., SM-G950)
        '/Pixel/i' => 'Google Pixel',
    ];

    // Check device type and name
    if (preg_match('/mobile/i', $userAgent)) {
        $deviceInfo['type'] = 'Mobile';
    } elseif (preg_match('/tablet/i', $userAgent) || preg_match('/iPad/i', $userAgent)) {
        $deviceInfo['type'] = 'Tablet';
    } else {
        $deviceInfo['type'] = 'Desktop';
    }

    // Attempt to extract device name
    foreach ($mobilePatterns as $pattern => $name) {
        if (preg_match($pattern, $userAgent, $matches)) {
            $deviceInfo['name'] = $name;
            // If a specific model code is matched (e.g., SM-G950), append it
            if (isset($matches[0]) && strpos($matches[0], 'SM-') === 0) {
                $deviceInfo['name'] = "Samsung {$matches[0]}";
            }
            break;
        }
    }

    // Desktop-specific OS detection (not exact device name, but useful context)
    if ($deviceInfo['type'] === 'Desktop') {
        if (preg_match('/Windows/i', $userAgent)) {
            $deviceInfo['name'] = 'Windows PC';
        } elseif (preg_match('/Macintosh|Mac OS/i', $userAgent)) {
            $deviceInfo['name'] = 'Mac';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $deviceInfo['name'] = 'Linux PC';
        }
    }

    // Browser info as a fallback or additional context
    if ($deviceInfo['name'] === 'Unknown') {
        if (preg_match('/Chrome/i', $userAgent)) {
            $deviceInfo['name'] = 'Chrome Device';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $deviceInfo['name'] = 'Safari Device';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $deviceInfo['name'] = 'Firefox Device';
        }
    }

    return $deviceInfo;
}

function getSellerWallet($seller_id)
{
    global $conn;

    // Get total sales
    $sqlGetTotalSales = "SELECT COALESCE(SUM(total_amount), 0) AS totalSales FROM orders WHERE seller_id = '" . $seller_id . "'";
    $resultGetTotalSales = mysqli_query($conn, $sqlGetTotalSales);

    // Get total withdrawals
    $sqlGetTotalWithdrawals = "SELECT COALESCE(SUM(amount), 0) AS totalWithdrawals FROM seller_withdrawal_requests WHERE seller_id = '" . $seller_id . "'";
    $resultGetTotalWithdrawals = mysqli_query($conn, $sqlGetTotalWithdrawals);

    if ($resultGetTotalSales && $resultGetTotalWithdrawals) {
        $rowTotalSales = mysqli_fetch_assoc($resultGetTotalSales);
        $rowTotalWithdrawals = mysqli_fetch_assoc($resultGetTotalWithdrawals);

        // Calculate seller wallet balance
        $seller_wallet_balance = $rowTotalSales['totalSales'] - $rowTotalWithdrawals['totalWithdrawals'] - ($rowTotalSales['totalSales'] * 0.10);

        // Store formatted values in session
        $_SESSION["totalSales"] = number_format($rowTotalSales['totalSales'], 2, '.', '');
        $_SESSION["totalWithdrawals"] = number_format($rowTotalWithdrawals['totalWithdrawals'], 2, '.', '');
        $_SESSION["sellerWallet"] = number_format($seller_wallet_balance, 2, '.', '');

        return $seller_wallet_balance;
    } else {
        // Handle errors
        echo "Error: " . mysqli_error($conn);
        return null;
    }
}
