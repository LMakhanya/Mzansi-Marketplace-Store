<?php
include $_SERVER["DOCUMENT_ROOT"] . '/vendor/phpmailer/phpmailer/src/Exception.php';
include $_SERVER["DOCUMENT_ROOT"] . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
include $_SERVER["DOCUMENT_ROOT"] . '/vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Order
{
    function getCarddetails($card_id)
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("SELECT * FROM yoco_cards WHERE card_id = :card_id");
            $stmt->execute([':card_id' => $card_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logResults("Error checking if card exists: " . $e->getMessage());
            return false;
        }
    }

    function checkoutIdExists($checkoutID)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT 1 FROM orders WHERE checkoutID = :checkoutId");
        $stmt->execute([':checkoutId' => $checkoutID]);
        return $stmt->fetchColumn();
    }

    function getPaymentEvent($customerID, $checkoutID)
    {
        global $pdo;
        $sql = "SELECT createdDate, p_type, cardID FROM yoco_events WHERE customerId = :customerId AND p_meta_checkoutId = :checkoutID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':customerId', $customerID, PDO::PARAM_STR);
        $stmt->bindParam(':checkoutID', $checkoutID, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function getcustomerOrder($customerID, $checkoutID)
    {
        global $pdo;
        $sql = "SELECT orderNo, email, fullname, order_date, total_amount, status, shipping_address, trackUrl, trackNo FROM orders WHERE customerID = :customerID AND checkoutID = :checkoutID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':customerID', $customerID, PDO::PARAM_STR);
        $stmt->bindParam(':checkoutID', $checkoutID, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function getProducts($orderNo)
    {
        global $pdo;
        $sql = "SELECT p.ProductName, p.product_image, op.price, op.quantity, p.discount FROM orderproducts op 
                JOIN product p ON op.product_id = p.product_id WHERE op.orderNo = :orderNo";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':orderNo', $orderNo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class Customer
{
    function getCustomerShippingAddress($pdo, $checkoutID)
    {
        if (!$checkoutID) {
            return null;
        }

        try {
            $stmt = $pdo->prepare("SELECT houseNumber, addressl1, addressl2, city, province, postalCode 
                                  FROM customer_shipping_address 
                                  WHERE checkoutID = :checkoutID");
            $stmt->execute([':checkoutID' => $checkoutID]);
            $results = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$results) {
                return null;
            }

            $address = trim($results['houseNumber']);

            if (!empty($results['addressl1'])) {
                $address .= ", " . trim($results['addressl1']);
            }

            if (!empty($results['addressl2'])) {
                $address .= ", " . trim($results['addressl2']);
            }

            $address .= " " . trim($results['city']);
            $address .= " " . trim($results['province']);
            $address .= " " . trim($results['postalCode']);

            return trim($address);
        } catch (PDOException $e) {
            // Log error details in production
            return null;
        }
    }
}
class Payment
{
    function getPaymentDetails($pdo, $checkoutID)
    {
        if (!$checkoutID) {
            return null;
        }
        $stmt = $pdo->prepare("SELECT * FROM yoco_payments WHERE response_id = :checkoutId");
        $stmt->execute([':checkoutId' => $checkoutID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function getPaymentType($pdo, $checkoutID)
    {
        $stmt = $pdo->prepare("SELECT payment_type FROM yoco_payments WHERE response_id = :checkoutId");
        $stmt->execute([':checkoutId' => $checkoutID]);
        return $stmt->fetchColumn();
    }
}

class Registration
{
    function log_to_file($message)
    {
        global $logPath;
        $timestamp = (new DateTime())->modify('+2 hours')->format('Y-m-d H:i');
        $logMessage = "$timestamp - $message\n";

        // Ensure $logPath has a trailing slash
        $filePath = rtrim($logPath, '/') . "/seller_registration.log";

        file_put_contents($filePath, $logMessage, FILE_APPEND);
    }

    function sanitizeString($input)
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    function isEmailRegistered($pdo, $email)
    {
        $sql = "SELECT * FROM sellers WHERE email = :email AND is_verified != 'yes' ORDER BY created_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    function thumbNails($pdo, $sellerID)
    {
        $sql = 'SELECT * FROM seller_thumbnails WHERE seller_id = :seller_id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':seller_id' => $sellerID]);
        return $stmt->fetchAll();
    }

    // login.php
    function thumbnailsRedirect($pdo, $sellerID)
    {
        $thumbnails = $this->thumbNails($pdo, $sellerID);

        if (count($thumbnails) > 0) {
            header('Location: /registration/verification-wait/');
            exit;
        } else {
            header('Location: /registration/selfie-verification/');
            exit;
        }
    }
}

$smtpData = [
    'host' => 'themzansimarketplace.co.za',
    'SMTPAuth' => true,
    'username' => 'noreply@themzansimarketplace.co.za',
    'password' => 'KyyEDh^FJ%1pD-e1',
    'secure' => 'ssl',
    'port' => 465
];

class SendEmail
{
    function subscriptionConfirmationEmail($email, $fullname, $plan)
    {
        $dashboardLink = 'https://seller.themzansimarketplace.co.za/dashboard/';
        $mail = new PHPMailer(true);
        try {
            // Server settings
            global $smtpData;
            $mail->isSMTP();
            $mail->Host = $smtpData['host'];
            $mail->SMTPAuth = $smtpData['SMTPAuth'];
            $mail->Username = $smtpData['username'];
            $mail->Password = $smtpData['password'];
            $mail->SMTPSecure = $smtpData['secure'];
            $mail->Port = $smtpData['port'];

            // Sanitize inputs
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            $fullname = htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8');
            $plan = htmlspecialchars($plan, ENT_QUOTES, 'UTF-8');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid email address: $email");
                return false;
            }

            // Recipients
            $mail->setFrom('noreply@themzansimarketplace.co.za', 'The Mzansi Marketplace');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "Mzansi Marketplace - Subscription Confirmation";

            // HTML email body
            $mail->Body = "<!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
                    <title>Subscription Confirmation - Mzansi Marketplace</title>
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                            font-family: 'Poppins', Arial, sans-serif;
                        }
                    </style>
                </head>
                <body style='margin: 0; padding: 10px; background: linear-gradient(135deg, #f0f2f5, #e2e8f0); color: #1e293b; line-height: 1.6; text-align: center;'>
                    <div style='max-width: 600px; width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); margin: 10px auto; overflow: hidden;'>
                        <div style='background: linear-gradient(135deg, #ac6111, #e68835); padding: 40px 20px; text-align: center; color: white;'>
                            <h1 style='font-size: 28px; font-weight: 700; letter-spacing: 0.5px;'>Subscription Confirmation</h1>
                        </div>
                        <div style='padding: 20px;'>
                            <div style='text-align: left;'>
                                <p style='font-size: 10pt; color: #64748b; line-height: 1.8; margin-bottom: 20px;'>
                                    Dear {$fullname},
                                </p>
                                <p style='font-size: 10pt; color: #64748b; line-height: 1.8; margin-bottom: 20px;'>
                                    Welcome to The Mzansi Marketplace! We're thrilled to confirm your subscription to the <strong>{$plan}</strong> plan, effective as of today, " . date('F d, Y') . ".
                                </p>
                                <p style='font-size: 10pt; color: #64748b; line-height: 1.8; margin-bottom: 20px;'>
                                    Your subscription gives you access to powerful tools to grow your business. Get started by exploring your dashboard and setting up your store.
                                </p>
                                <p style='text-align: center; margin: 20px 0;'>
                                    <a href='{$dashboardLink}' style='display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #ac6111, #e68835); color: white; text-decoration: none; border-radius: 8px; font-size: 10pt; font-weight: 500;'>Explore Your Dashboard</a>
                                </p>
                                <p style='font-size: 10pt; color: #64748b; line-height: 1.8; margin-bottom: 20px;'>
                                    If you have any questions or need assistance, feel free to reach out to us at <a href='mailto:support@themzansimarketplace.co.za' style='color: #4a90e2; text-decoration: none;'>support@themzansimarketplace.co.za</a> or call us at (+27) 69 535 2229.
                                </p>
                                <p style='font-size: 10pt; color: #64748b; line-height: 1.8; margin-bottom: 20px;'>
                                    Thank you for choosing The Mzansi Marketplace!
                                </p>
                            </div>
                        </div>
                        <div style='background: linear-gradient(135deg, #f8fafc, #f1f5f9); padding: 25px; text-align: center; font-size: 8pt; color: #64748b; border-top: 1px solid #e2e8f0;'>
                            <p style='margin-bottom: 5px;'>© 2025 Mzansi Marketplace. All rights reserved.</p>
                            <p style='margin-bottom: 5px;'>
                                <a href='https://themzansimarketplace.co.za/wording/legal/' style='color: #e68835; text-decoration: none; font-weight: 500;'>Terms & Conditions</a> |
                                <a href='https://themzansimarketplace.co.za/wording/legal/' style='color: #e68835; text-decoration: none; font-weight: 500;'>Privacy Policy</a>
                            </p>
                            <p style='margin-top: 5px; font-size: 8pt;'>You received this email because you subscribed at themzansimarketplace.co.za</p>
                        </div>
                    </div>
                </body>
                </html>";

            // Plain text fallback
            $mail->AltBody = "Mzansi Marketplace - Subscription Confirmation\n\nDear {$fullname},\n\nWelcome to The Mzansi Marketplace! We're thrilled to confirm your subscription to the {$plan} plan, effective as of today, " . date('F d, Y') . ".\n\nYour subscription gives you access to powerful tools to grow your business. Get started by exploring your dashboard at {$dashboardLink}.\n\nIf you have any questions or need assistance, contact us at support@themzansimarketplace.co.za or call (+27) 69 535 2229.\n\nThank you for choosing The Mzansi Marketplace!\n\n© 2025 Mzansi Marketplace. All rights reserved.\nTerms & Conditions: https://themzansimarketplace.co.za/wording/legal/\nPrivacy Policy: https://themzansimarketplace.co.za/wording/legal/\n\nYou received this email because you subscribed at themzansimarketplace.co.za";

            if ($mail->send()) {
                error_log('Subscription confirmation email sent successfully to ' . $email);
                return true;
            } else {
                error_log('Subscription confirmation email sending failed: ' . $mail->ErrorInfo);
                return false;
            }
        } catch (Exception $e) {
            error_log('Exception occurred while sending subscription confirmation email: ' . $e->getMessage());
            return false;
        }
    }

    function sendOrderEmail($customerID, $checkoutID, $classOrder, $classPayements)
    {
        global $pdo;

        $orderResults = $classOrder->getcustomerOrder($customerID, $checkoutID);
        $paymentResults = $classPayements->getPaymentDetails($pdo, $checkoutID);
        $orderNo = $orderResults['orderNo'] ?? null;
        $trackUrl = $orderResults['trackUrl'] ?? '';
        $trackNo = $orderResults['trackNo'] ?? '';
        $orderStatus = $orderResults['status'] ?? null;
        $custEmail = $orderResults['email'] ?? null;
        $custFullname = $orderResults['fullname'] ?? null;
        $totalAmount = $orderResults['total_amount'] ?? 0;
        $orderDate = $orderResults['order_date'] ?? date('Y-m-d');
        $formattedOrderDate = date('Y-m-d', strtotime($orderDate));

        $shippingAddress = $orderResults['shipping_address'] ?? 'Not provided';

        $shippingType = $paymentResults['shipping'] ?? 'Unkown - Collect InStore';
        $shippingCost = $paymentResults['shipping_amnt'] ?? 0;


        $paymentEvent = $classOrder->getPaymentEvent($customerID, $checkoutID);
        $cardID = $paymentEvent['cardID'] ?? 'Unknown';
        $p_type = $paymentEvent['p_type'] ?? 'Unknown';
        $cardDetails = $classOrder->getCarddetails($cardID);
        $createdDate = $paymentEvent['createdDate'] ?? date('Y-m-d');

        $products = $orderNo ? $classOrder->getProducts($orderNo) : [];

        $subtotal = array_sum(array_map(function ($product) {
            $productDiscount = $product['price'] * ($product['discount'] / 100);
            $priceAfterDiscount = $product['price'] - $productDiscount;
            return $priceAfterDiscount * $product['quantity'];
        }, $products));

        $totalWithShipping = $subtotal + $shippingCost;

        $mail = new PHPMailer(true);

        try {
            // Server settings
            global $smtpData;

            $mail->isSMTP();
            $mail->Host = $smtpData['host'];
            $mail->SMTPAuth = $smtpData['SMTPAuth'];
            $mail->Username = $smtpData['username'];
            $mail->Password = $smtpData['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $smtpData['port'];

            // Recipients
            $mail->setFrom('noreply@themzansimarketplace.co.za', 'The Mzansi Marketplace');
            $mail->addReplyTo('support@mzansimarketplace.co.za', 'Support Team');
            $mail->addAddress($custEmail);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "Order Confirmation - Thank You for Shopping with Us!";

            // Sanitize and prepare variables
            $custFullname = htmlspecialchars($custFullname ?? 'Customer');
            $shippingAddress = nl2br(htmlspecialchars($shippingAddress ?? 'Not provided'));
            $viewOrderUrl = htmlspecialchars($viewOrderUrl ?? 'https://themzansimarketplace.co.za/order/?customer=' . $customerID . '&id=' . $checkoutID); // Example URL
            $receiptUrl = "https://themzansimarketplace.co.za/receipt.php?order=" . urlencode($orderNo); // Example URL

            // Build product rows with fallback
            $productRows = '';
            if (!empty($products)) {
                foreach ($products as $product) {

                    $productDiscount = $product['price'] * ($product['discount'] / 100);
                    $priceAfterDiscount = $product['price'] - $productDiscount;

                    $productRows .= "
                    <tr>
                        <td style='padding: 15px; text-align: left; border-bottom: 1px solid #eee;'>
                            <table style='width: 100%;'>
                                <tr>
                                    <td style='padding: 0;'><img src='https://themzansimarketplace.co.za/uploads/" . htmlspecialchars($product['product_image'] ?? 'default.jpg') . "' alt='" . htmlspecialchars($product['ProductName'] ?? 'Product') . "' style='width: 60px; height: auto; border-radius: 4px; background-color: #f5f5f5; vertical-align: middle;'></td>
                                    <td style='padding: 0 0 0 15px; vertical-align: middle;'>" . htmlspecialchars($product['ProductName'] ?? 'Unknown Product') . "</td>
                                </tr>
                            </table>
                        </td>
                        <td style='padding: 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle;'>" . htmlspecialchars($product['quantity'] ?? 0) . "</td>
                        <td style='padding: 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle;'>R" . number_format($priceAfterDiscount  * ($product['quantity'] ?? 0), 2) . "</td>
                    </tr>";
                }
            } else {
                $productRows = "<tr><td colspan='3' style='padding: 15px; text-align: center; border-bottom: 1px solid #eee;'>No items in this order</td></tr>";
            }

            // Payment method logic with fallback
            $paymentInfo = '';
            if ($p_type === 'card') {
                $paymentInfo = "
                <table style='width: 100%;'>
                    <tr>
                        <td style='padding: 0;'><img src='https://themzansimarketplace.co.za/assets/icons/visa.svg' alt='Visa Card' style='width: 70px; height: 30px; padding: 5px; border: 1px solid #d3d3d3; border-radius: 5px; background-color: #fff; vertical-align: middle;'></td>
                        <td style='padding: 0 0 0 10px; vertical-align: middle;'>**** **** **** " . htmlspecialchars($cardDetails['last4Digits'] ?? 'XXXX') . "</td>
                    </tr>
                </table>";
            } elseif ($p_type === 'instant_eft') {
                $paymentInfo = "<img src='https://themzansimarketplace.co.za/assets/icons/eft.svg' alt='Instant EFT' style='width: 70px; height: 30px; padding: 5px; border: 1px solid #d3d3d3; border-radius: 5px; background-color: #fff;'>";
            } else {
                $paymentInfo = "<p style='margin: 0; padding: 0;'>Payment method not specified</p>";
            }

            // HTML email body with improved compatibility
            $mail->Body = "
                <!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Order Confirmation</title>
                    <style>
                        body { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
                    </style>
                </head>
                <body style='font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; color: #333;'>
                    <table style='width: 100%; max-width: 600px; margin: 50px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td style='padding: 20px;'>
                                <!-- Header (optional logo) -->
                                <table style='width: 100%;' cellpadding='0' cellspacing='0'>
                                    <tr>
                                        <td style='text-align: center; padding-bottom: 30px;'>
                                            <!-- Uncomment and adjust logo if needed -->
                                            <!-- <img src='https://themzansimarketplace.co.za/logo.png' alt='The Mzansi Marketplace Logo' style='max-width: 150px; height: auto;'> -->
                                        </td>
                                    </tr>
                                </table>
            
                                <!-- Content -->
                                <table style='width: 100%;' cellpadding='0' cellspacing='0'>
                                    <tr>
                                        <td style='line-height: 1.6; padding-bottom: 20px;'>
                                            <p style='font-size: 14px; margin: 0 0 10px 0;'>Hi $custFullname,</p>
                                            <p style='font-size: 14px; margin: 0 0 10px 0;'>Your order is confirmed! We’re preparing it for shipping and will notify you when it’s on its way. If delivered after <strong>" . date('F j, Y', strtotime($orderDate . ' +5 days')) . "</strong>, you’ll receive a R20 credit within 48 hours.</p>
                                            <p style='font-size: 14px; margin: 0 0 10px 0;'>Please ensure the address is correct and complete (e.g., includes a house number), as it can’t be changed after shipping.</p>
                                            <h4 style='margin: 0; padding: 0; font-size: 16px;'>Ship to:</h4>
                                            <p style='font-size: 14px; margin: 5px 0 10px 0;'>$shippingAddress</p>
                                            <p style='margin: 0;'><a href='$viewOrderUrl' target='_blank' style='color: #2980b9; text-decoration: none; font-weight: 500; font-size: 14px;'>Change Address</a></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style='text-align: center; padding: 20px 0;'>
                                            <a href='$viewOrderUrl' style='display: inline-block; width: 60%; min-width: 200px; margin: 0 auto; text-align: center; background-color: #e78026; color: #ffffff; padding: 15px; border-radius: 10px; text-decoration: none; font-size: 14px;'>View Order</a>
                                        </td>
                                    </tr>
                                </table>
             
                                <!-- Order Details -->
                                <table style='width: 100%; border-collapse: collapse;' cellpadding='0' cellspacing='0'>
                                    <!-- Order Summary -->
                                    <tr>
                                        <td style='background: #fafafa; padding: 20px; border: 1px solid #eee; border-radius: 8px;'>
                                            <h2 style='color: #2c3e50; margin: 0 0 15px 0; font-weight: 600; font-size: 18px;'>Order Summary</h2>
                                            <table style='width: 100%; border-collapse: collapse; font-size: 14px;' cellpadding='0' cellspacing='0'>
                                                <tr style='background: #f5f5f5;'>
                                                    <th style='padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-weight: 500; color: #555;'>Item</th>
                                                    <th style='padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-weight: 500; color: #555;'>Quantity</th>
                                                    <th style='padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-weight: 500; color: #555;'>Price</th>
                                                </tr>
                                                $productRows
                                                <tr>
                                                    <td colspan='2' style='padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-weight: bold;'>Subtotal</td>
                                                    <td style='padding: 15px; text-align: left; border-bottom: 1px solid #eee;'>R" . number_format($subtotal ?? 0, 2) . "</td>
                                                </tr>
                                                <tr>
                                                    <td colspan='2' style='padding: 15px; text-align: left; border-bottom: 1px solid #eee;'>Shipping</td>
                                                    <td style='padding: 15px; text-align: left; border-bottom: 1px solid #eee;'>R" . number_format($shippingCost ?? 0, 2) . "</td>
                                                </tr>
                                                <tr>
                                                    <td colspan='2' style='padding: 15px; text-align: left; font-weight: bold;'>Total</td>
                                                    <td style='padding: 15px; text-align: left; font-weight: bold;'>R" . number_format($totalWithShipping ?? 0, 2) . "</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <!-- Payment Info -->
                                    <tr>
                                        <td style='background: #fafafa; padding: 20px; border: 1px solid #eee; border-radius: 8px;'>
                                            <h3 style='color: #2c3e50; margin: 0 0 15px 0; font-weight: 600; font-size: 16px;'>Payment Method</h3>
                                            $paymentInfo
                                            <p style='font-size: 14px; margin: 10px 0 0 0;'>Charged: R" . number_format($totalWithShipping ?? 0, 2) . "</p>
                                            <p style='font-size: 14px; margin: 10px 0 0 0;'>Billed on: " . date('M j, Y', strtotime($createdDate ?? 'now')) . "</p>
                                        </td>
                                    </tr>
                                    <!-- Receipt Link -->
                                    <tr>
                                        <td style='padding: 20px 0;'>
                                            <p style='font-size: 14px; margin: 0;'><a href='$receiptUrl' style='color: #2980b9; text-decoration: none; font-weight: 500;'>Download Receipt (PDF)</a></p>
                                        </td>
                                    </tr>
                                </table>
            
                                <!-- Footer -->
                                <table style='width: 100%;' cellpadding='0' cellspacing='0'>
                                    <tr>
                                        <td style='text-align: center; padding-top: 30px; font-size: 12px; color: #999;'>
                                            <p style='margin: 0;'>© 2024 The Mzansi Marketplace. All rights reserved.</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </body>
                </html>";

            if ($mail->send()) {
                error_log('Email sent successfully to ' . $custEmail);
            } else {
                error_log('Email sending failed: ' . $mail->ErrorInfo);
            }
        } catch (Exception $e) {
            error_log('Exception occurred while sending email: ' . $e->getMessage());
        }
    }

    function sendSubscribeEmail($email)
    {
        $redirectlink = 'https://themzansimarketplace.co.za/';

        $mail = new PHPMailer(true);

        try {
            // Server settings
            global $smtpData;

            $mail->isSMTP();
            $mail->Host = $smtpData['host'];
            $mail->SMTPAuth = $smtpData['SMTPAuth'];
            $mail->Username = $smtpData['username'];
            $mail->Password = $smtpData['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $smtpData['port'];

            // Recipients
            $mail->setFrom('noreply@themzansimarketplace.co.za', 'The Mzansi Marketplace');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "The Mzansi Marketplace - Subscription Confirmation";

            // HTML email body with improved compatibility
            $mail->Body = "
            <!DOCTYPE html>
                <html lang='en'>

                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Subscription Confirmation</title>
                    <style>
                        @import url(https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap);
                        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
                        @import url('https://fonts.googleapis.com/css2?family=Darumadrop+One&display=swap');

                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                            font-family: 'Poppins', sans-serif;
                        }
                    </style>
                </head>

                <body style='background-color: #f0f2f5; line-height: 1.6; color: #1e293b; padding: 20px; font-family: 'Poppins', sans-serif;'>
                    <div style='max-width: 650px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);'>
                        <div style='background: linear-gradient(135deg, #ac6111, #e68835); padding: 40px 20px; text-align: center; color: white; position: relative;'>
                            <h1 style='font-size: 28px; font-weight: 700; letter-spacing: 0.5px;'>Welcome to Your Subscription!</h1>
                            <p style='font-size: 16px; opacity: 0.9; margin-top: 8px;'>You’re now part of The Mzansi Marketplace</p>
                        </div>

                        <div style='padding: 40px; background: #ffffff;'>
                            <h2 style='font-size: 24px; font-weight: 600; color: #1e293b;'>Hello User,</h2>
                            <p style='font-size: 0.8rem; color: #64748b; margin-bottom: 25px; line-height: 1.8;'>
                                Thank you for subscribing to <strong>The Mzansi Marketplace</strong> with $email! 
                                You’ve successfully joined our community, and we’re excited to have you on board.
                            </p>

                            <div style='background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #ac6111; margin-bottom: 25px;'>
                                <h3 style='font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 15px;'>What’s Next?</h3>
                                <ol style='padding-left: 20px; color: #475569;'>
                                    <li style='font-size: 0.85rem; margin-bottom: 10px;'>Stay tuned for updates, tips, and special offers!</li>
                                </ol>
                            </div>

                            <p style='text-align: center;'>
                                <a href='$redirectlink/' style='display: inline-block; padding: 14px 30px; background: linear-gradient(135deg, #ac6111, #e68835); color: white !important; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px; transition: transform 0.3s ease, box-shadow 0.3s ease; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);'>Continue Shopping</a>
                            </p>

                            </br>

                            <p style='margin-top:30px; font-size: 0.8rem; color: #64748b;'>
                                Need help? Reach out to our support team at <a href='mailto:support@themzansimarketplace.co.za' style='color: #ac6111; text-decoration: none; font-weight: 500;'>support@themzansimarketplace.co.za</a>.
                                We’re here to ensure you have the best experience!
                            </p>
                        </div>

                        <div style='margin-top:30px; background: linear-gradient(135deg, #f8fafc, #f1f5f9); padding: 25px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0;'>
                            <p>© 2025 The Mzansi Marketplace. All rights reserved.</p>
                            <p>
                                <a href='$redirectlink/portal/legal/' style='color: #ac6111; text-decoration: none; font-weight: 500;'>Terms & Conditions</a> |
                                <a href='$redirectlink/portal/legal/' style='color: #ac6111; text-decoration: none; font-weight: 500;'>Privacy Policy</a>
                            </p>
                            <p>You’re receiving this email because you subscribed at themzansimarketplace.co.za</p>
                        </div>
                    </div>
                </body>

                </html>";

            if ($mail->send()) {
                error_log('Subscription email sent successfully to ' . $email);
                return true; // Return success
            } else {
                error_log('Subscription email sending failed: ' . $mail->ErrorInfo);
                return false;
            }
        } catch (Exception $e) {
            error_log('Exception occurred while sending subscription email: ' . $e->getMessage());
        }
    }

    // === Email Function ===
    function agentAccountCreatedEmail($email, $fullname, $agentId, $referralUrl, $password)
    {
        $redirectlink = 'https://themzansimarketplace.co.za/agent/login/';

        $mail = new PHPMailer(true);

        try {
            // Server settings
            global $smtpData;

            $mail->isSMTP();
            $mail->Host = $smtpData['host'];
            $mail->SMTPAuth = $smtpData['SMTPAuth'];
            $mail->Username = $smtpData['username'];
            $mail->Password = $smtpData['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $smtpData['port'];

            // Recipients
            $mail->setFrom('noreply@themzansimarketplace.co.za', 'The Mzansi Marketplace');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "The Mzansi Marketplace - Agent Account Created";

            // HTML Body
            $mail->Body = "
                <!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Agent Account Created</title>
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                            font-family: 'Poppins', sans-serif;
                        }
                    </style>
                </head>
                <body style='background-color: #f0f2f5; padding: 20px; color: #1e293b;'>
                    <div style='max-width: 650px; margin: auto; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);'>
                        <div style='background: linear-gradient(135deg, #ac6111, #e68835); padding: 40px 20px; text-align: center; color: white;'>
                            <h1 style='font-size: 26px; font-weight: 700;'>Agent Account Created!</h1>
                            <p style='font-size: 16px; margin-top: 8px;'>You're officially part of The Mzansi Marketplace team.</p>
                        </div>
                        <div style='padding: 30px;'>
                            <h2 style='font-size: 22px;'>Hi $fullname,</h2>
                            <p style='font-size: 0.9rem; color: #475569; margin: 15px 0 25px;'>
                                Your agent account has been successfully created using the email address <strong>$email</strong>. You now have access to our agent portal where you can begin referring sellers and tracking your progress.
                            </p>

                            <div style='background: #f8fafc; padding: 20px; border-left: 4px solid #ac6111; border-radius: 8px; margin-bottom: 25px;'>
                                <h3 style='font-size: 16px; font-weight: 600; margin-bottom: 10px;'>Your Login Details:</h3>
                                <p style='font-size: 0.95rem; color: #334155;'>
                                    <strong style='font-size:.95rem;'>Username:</strong> $email<br>
                                    <strong style='font-size:.95rem;'>Password:</strong> $password<br>
                                    </br>
                                    <em style='font-size:.8rem;'>Please change your password after logging in for security.</em>
                                </p>
                            </div>
                           
                            <p style='text-align: center;'>
                                <a href='$redirectlink' style='display: inline-block; padding: 12px 28px; background: linear-gradient(135deg, #ac6111, #e68835); color: white; text-decoration: none; border-radius: 30px; font-weight: 600;'>Login to Agent Portal</a>
                            </p>
                           
                            <div style='background: #f8fafc; padding: 20px; border-left: 4px solid #ac6111; border-radius: 8px; margin-bottom: 25px;'>
                                <h3 style='font-size: 16px; font-weight: 600; margin-bottom: 10px;'>Your Agent Details:</h3>
                                <p style='font-size: 0.95rem; color: #334155;'>
                                    <strong>Agent ID:</strong> $agentId<br>
                                    <strong>Referral Link:</strong> <a href='$referralUrl' style='color: #ac6111; text-decoration: none;'>$referralUrl</a><br>
                                </p>
                            </div>

                            <div style='background: #f8fafc; padding: 20px; border-left: 4px solid #ac6111; border-radius: 8px; margin-bottom: 25px;'>
                                <h3 style='font-size: 16px; font-weight: 600; margin-bottom: 10px;'>Next Steps:</h3>
                                <ul style='padding-left: 20px; font-size: 0.85rem; color: #334155;'>
                                    <li>Login to your agent dashboard</li>
                                    <li>View your unique referral link</li>
                                    <li>Start referring sellers and earning rewards</li>
                                    <li>Track your referrals and commissions</li>
                                </ul>
                            </div>

                            <p style='margin-top: 30px; font-size: 0.8rem; color: #64748b;'>
                                If you have any questions or need assistance, feel free to contact us at 
                                <a href='mailtoJacob@themzansimarketplace.co.za' style='color: #ac6111; font-weight: 500;'>support@themzansimarketplace.co.za</a>.
                            </p>
                        </div>

                        <div style='background: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;'>
                            <p>© 2025 The Mzansi Marketplace. All rights reserved.</p>
                            <p>
                                <a href='https://themzansimarketplace.co.za/portal/legal/' style='color: #ac6111; text-decoration: none;'>Terms & Conditions</a> |
                                <a href='https://themzansimarketplace.co.za/portal/legal/' style='color: #ac6111; text-decoration: none;'>Privacy Policy</a>
                            </p>
                            <p>You received this email because you registered as an agent with The Mzansi Marketplace.</p>
                        </div>
                    </div>
                </body>
                </html>";

            if ($mail->send()) {
                error_log('Agent email sent successfully to ' . $email);
            } else {
                error_log('Agent email sending failed: ' . $mail->ErrorInfo);
            }
        } catch (Exception $e) {
            error_log('Exception occurred while sending agent email: ' . $e->getMessage());
        }
    }

    function accountCreatedEmail($email, $fullname)
    {
        $redirectlink = 'http://localhost:3000/sellers/login/';
        $redirectlink = 'https://seller.themzansimarketplace.co.za/login/';

        $mail = new PHPMailer(true);

        try {
            // Server settings
            global $smtpData;

            $mail->isSMTP();
            $mail->Host = $smtpData['host'];
            $mail->SMTPAuth = $smtpData['SMTPAuth'];
            $mail->Username = $smtpData['username'];
            $mail->Password = $smtpData['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $smtpData['port'];

            // Recipients
            $mail->setFrom('noreply@themzansimarketplace.co.za', 'The Mzansi Marketplace');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "The Mzansi Marketplace - Seller Account Confirmation";

            // HTML email body with improved compatibility
            $mail->Body = "
                <!DOCTYPE html>
                <html lang='en'>

                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Complete Your Store Registration</title>
                    <style>
                        @import url(https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap);
                        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
                        @import url('https://fonts.googleapis.com/css2?family=Darumadrop+One&display=swap');

                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                            font-family: 'Poppins', sans-serif;
                        }
                    </style>
                </head>

                <body style='background-color: #f0f2f5; line-height: 1.6; color: #1e293b; padding: 20px; font-family: 'Poppins', sans-serif;'>
                    <div style='max-width: 650px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);'>
                        <div style='background: linear-gradient(135deg, #ac6111, #e68835); padding: 40px 20px; text-align: center; color: white; position: relative;'>
                            <h1 style='font-size: 28px; font-weight: 700; letter-spacing: 0.5px;'>Account Created Successfully!</h1>
                            <p style='font-size: 16px; opacity: 0.9; margin-top: 8px;'>One step closer to your store</p>
                        </div>

                        <div style='padding: 40px; background: #ffffff;'>
                            <h2 style='font-size: 24px; font-weight: 600; color: #1e293b;'>Hello $fullname,</h2>
                            <p style='font-size: 0.8rem; color: #64748b; margin-bottom: 25px; line-height: 1.8;'>
                                Congratulations! Your <strong>The Mzansi Marketplace Sellers</strong> account with $email has been successfully created.
                                You’re almost ready to start selling—now it’s time to complete your store registration to unlock the full experience.
                            </p>

                            <div style='background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #ac6111; margin-bottom: 25px;'>
                                <h3 style='font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 15px;'>Next Steps:</h3>
                                <ol style='padding-left: 20px; color: #475569;'>
                                    <li style='font-size: 0.85rem; margin-bottom: 10px;'>Click the button below to complete store registration.</li>
                                    <li style='font-size: 0.85rem; margin-bottom: 10px;'>Login - to your sellers account.</li>
                                    <li style='font-size: 0.85rem; margin-bottom: 10px;'>Confirm or choose your preferred plan.</li>
                                    <li style='font-size: 0.85rem; margin-bottom: 10px;'>Provide your store details.</li>
                                    <li style='font-size: 0.85rem; margin-bottom: 10px;'>Selfie Upload.</li>
                                    <li style='font-size: 0.85rem; margin-bottom: 10px;'>Submit for review—we’ll get back to you soon!</li>
                                </ol>
                            </div>

                            <p style='text-align: center;'>
                                <a href='$redirectlink' style='display: inline-block; padding: 14px 30px; background: linear-gradient(135deg, #ac6111, #e68835); color: white !important; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px; transition: transform 0.3s ease, box-shadow 0.3s ease; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);'>Complete Store Registration</a>
                            </p>

                            </br>

                            <p style='margin-top:30px; font-size: 0.8rem; color: #64748b;'>
                                Questions? Contact our support team at <a href='mailto:support@themzansimarketplace.co.za' style='color: #ac6111; text-decoration: none; font-weight: 500;'>support@themzansimarketplace.co.za</a>.
                                We’re here to help you every step of the way!
                            </p>
                        </div>

                        <div style='margin-top:30px; background: linear-gradient(135deg, #f8fafc, #f1f5f9); padding: 25px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0;'>
                            <p>© 2025 The Mzansi Marketplace. All rights reserved.</p>
                            <p>
                                <a href='[Terms URL]' style='color: #ac6111; text-decoration: none; font-weight: 500;'>Terms & Conditions</a> |
                                <a href='[Privacy URL]' style='color: #ac6111; text-decoration: none; font-weight: 500;'>Privacy Policy</a>
                            </p>
                            <p>Received this email because you signed up at themzansimarketplace.co.za</p>
                        </div>
                    </div>
                </body>

                </html>";

            if ($mail->send()) {
                error_log('Email sent successfully to ' . $email);
            } else {
                error_log('Email sending failed: ' . $mail->ErrorInfo);
            }
        } catch (Exception $e) {
            error_log('Exception occurred while sending email: ' . $e->getMessage());
        }
    }

    function contactFormNotification($admin_email, $sender_name, $sender_email, $message)
    {
        $redirectlink = 'http://localhost:3000/registration/';

        $mail = new PHPMailer(true);

        try {
            // Server settings
            global $smtpData;

            $mail->isSMTP();
            $mail->Host = $smtpData['host'];
            $mail->SMTPAuth = $smtpData['SMTPAuth'];
            $mail->Username = $smtpData['username'];
            $mail->Password = $smtpData['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $smtpData['port'];

            // Recipients
            $mail->setFrom('noreply@themzansimarketplace.co.za', 'The Mzansi Marketplace');
            $mail->addReplyTo('support@mzansimarketplace.co.za', 'Support Team');
            $mail->addAddress($admin_email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "Mzansi Marketplace - New Contact Form Message";

            // HTML email body
            $mail->Body = "<!DOCTYPE html>
            <html lang='en'>

            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <meta http-equiv='X-UA-Compatible' content='IE=edge'>
                <title>New Contact Form Message - Mzansi Marketplace</title>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                        font-family: 'Poppins', sans-serif;
                    }
                </style>
            </head>

            <body style='margin: 0; padding: 10px; background: linear-gradient(135deg, #f0f2f5, #e2e8f0) !important; color: #1e293b !important; font-family: 'Poppins', Arial, sans-serif !important; line-height: 1.6 !important; text-align: center !important;'>

                <div style='max-width: 600px; width: 100%; background: white !important; border-radius: 12px !important; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05) !important; margin: 10px auto !important; overflow: hidden !important;'>
                    <div style='background: linear-gradient(135deg, #ac6111, #e68835); padding: 40px 20px; text-align: center; color: white; position: relative;'>
                        <h1 style='font-size: 28px; font-weight: 700; letter-spacing: 0.5px;'>New Contact Form Message &#128229;</h1>
                    </div>
                    </br>
                    <div style='padding: 20px !important;'>
                        <div style='text-align: left !important;'>
                            <p style='font-size: 14px !important; color: #64748b !important; line-height: 1.8 !important; margin-bottom: 20px !important;'>
                                Dear Admin,
                            </p>
                            <p style='font-size: 14px !important; color: #64748b !important; line-height: 1.8 !important; margin-bottom: 20px !important;'>
                                You have received a new message through the contact form on Mzansi Marketplace:
                            </p>
                            <p style='font-size: 14px !important; color: #64748b !important; line-height: 1.8 !important; margin-bottom: 20px !important;'>
                                <strong>From:</strong> $sender_name<br>
                                <strong>Email:</strong> $sender_email<br>
                                <strong>Message:</strong><br>
                                " . htmlspecialchars($message) . "
                            </p>
                            <p style='font-size: 14px !important; color: #64748b !important; line-height: 1.8 !important; margin-bottom: 20px !important;'>
                                Please respond to the sender at your earliest convenience.
                            </p>
                        </div>

                        <p style='text-align: center;'>
                            <a href='mailto:$sender_email' style='display: inline-block; padding: 14px 30px; background: linear-gradient(135deg, #ac6111, #e68835); color: white !important; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px; transition: transform 0.3s ease, box-shadow 0.3s ease; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);'>Reply to Sender</a>
                        </p>

                    </div>

                    <div style='background: linear-gradient(135deg, #f8fafc, #f1f5f9) !important; padding: 25px !important; text-align: center !important; font-size: 13px !important; color: #64748b !important; border-top: 1px solid #e2e8f0 !important;'>
                        <p style='margin-bottom: 5px !important;'>© 2025 The Mzansi Marketplace. All rights reserved.</p>
                        <p style='margin-bottom: 5px !important;'>
                            <a href='https://themzansimarketplace.co.za/portal/legal/' style='color: #e68835 !important; text-decoration: none !important; font-weight: 500 !important;'>Terms & Conditions</a> |
                            <a href='https://themzansimarketplace.co.za/portal/legal/' style='color: #e68835 !important; text-decoration: none !important; font-weight: 500 !important;'>Privacy Policy</a>
                        </p>
                        <p style='margin-top: 5px !important;'>This is an automated notification from mzansimarketplace.com</p>
                    </div>
                </div>

            </body>

            </html>";

            if ($mail->send()) {
                error_log('Contact form notification email sent successfully to ' . $admin_email);
            } else {
                error_log('Email sending failed: ' . $mail->ErrorInfo);
            }
        } catch (Exception $e) {
            error_log('Exception occurred while sending contact form notification: ' . $e->getMessage());
        }
    }

    function newAgentrNotification($admin_email, $agent_name, $agent_email, $agentId, $admin_name = 'Admin')
    {
        $redirectlink = 'https://themzansimarketplace.co.za/agent/' . urlencode($agentId); // Updated to production URL
        $unsubscribe_link = 'https://themzansimarketplace.co.za/unsubscribe?email=' . urlencode($admin_email);

        $mail = new PHPMailer(true);

        try {
            // Server settings
            global $smtpData;

            $mail->isSMTP();
            $mail->Host = $smtpData['host'];
            $mail->SMTPAuth = $smtpData['SMTPAuth'];
            $mail->Username = $smtpData['username'];
            $mail->Password = $smtpData['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $smtpData['port'];

            // Recipients
            $mail->setFrom('noreply@themzansimarketplace.co.za', 'The Mzansi Marketplace');
            $mail->addReplyTo('support@themzansimarketplace.co.za', 'Support Team');
            $mail->addAddress($admin_email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Mzansi Marketplace - New Agent Account Created';

            // HTML email body
            $mail->Body = "<!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <meta http-equiv='X-UA-Compatible' content='IE=edge'>
                <title>New Agent Account - Mzansi Marketplace</title>
                <style>
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                    }
                    body {
                        background: #f0f2f5;
                        color: #1e293b;
                        line-height: 1.6;
                        text-align: center;
                    }
                    .container {
                        max-width: 600px;
                        margin: 20px auto;
                        background: #ffffff;
                        border-radius: 12px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                        overflow: hidden;
                    }
                    .header {
                        background: linear-gradient(135deg, #ac6111, #e68835);
                        padding: 30px 20px;
                        color: #ffffff;
                        text-align: center;
                    }
                    .header h1 {
                        font-size: 24px;
                        font-weight: 600;
                        letter-spacing: 0.5px;
                    }
                    .content {
                        padding: 20px;
                        text-align: left;
                    }
                    .content p {
                        font-size: 14px;
                        color: #475569;
                        margin-bottom: 15px;
                        line-height: 1.8;
                    }
                    .content strong {
                        color: #1e293b;
                    }
                    .button {
                        display: inline-block;
                        padding: 12px 24px;
                        background: linear-gradient(135deg, #ac6111, #e68835);
                        color: #ffffff;
                        text-decoration: none;
                        border-radius: 50px;
                        font-weight: 500;
                        font-size: 14px;
                        margin: 10px 0;
                        transition: transform 0.2s ease;
                    }
                    .button:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                    }
                    .footer {
                        background: #f8fafc;
                        padding: 20px;
                        text-align: center;
                        font-size: 12px;
                        color: #64748b;
                        border-top: 1px solid #e2e8f0;
                    }
                    .footer a {
                        color: #e68835;
                        text-decoration: none;
                        font-weight: 500;
                    }
                    .footer a:hover {
                        text-decoration: underline;
                    }
                    @media only screen and (max-width: 600px) {
                        .container {
                            margin: 10px;
                        }
                        .header h1 {
                            font-size: 20px;
                        }
                        .content {
                            padding: 15px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>New Agent Account Created</h1>
                    </div>
                    <div class='content'>
                        <p>Dear " . htmlspecialchars($admin_name) . ",</p>
                        <p>A new agent has registered on The Mzansi Marketplace:</p>
                        <p>
                            <strong>Agent ID:</strong> " . htmlspecialchars($agentId) . "<br>
                            <strong>Agent Name:</strong> " . htmlspecialchars($agent_name) . "<br>
                            <strong>Email:</strong> " . htmlspecialchars($agent_email) . "
                        </p>
                        <p>Please review the agent's details and take appropriate action.</p>
                        <p style='text-align: center;'>
                            <a href='" . htmlspecialchars($redirectlink) . "' class='button' style='color:white;'>View Agent Details</a>
                        </p>
                        <p style='text-align: center;'>
                            <a href='mailto:" . htmlspecialchars($agent_email) . "' class='button' style='color:white;'>Contact Agent</a>
                        </p>
                    </div>
                    <div class='footer'>
                        <p>© 2025 The Mzansi Marketplace. All rights reserved.</p>
                        <p>
                            <a href='https://themzansimarketplace.co.za/portal/legal/'>Terms & Conditions</a> |
                            <a href='https://themzansimarketplace.co.za/portal/legal/'>Privacy Policy</a> |
                            <a href='" . htmlspecialchars($unsubscribe_link) . "'>Unsubscribe</a>
                        </p>
                        <p>The Mzansi Marketplace<br>123 Business Address, City, South Africa</p>
                        <p>This is an automated notification from themzansimarketplace.co.za</p>
                    </div>
                </div>
            </body>
            </html>";

            // Plain-text version
            $mail->AltBody = "Dear " . htmlspecialchars($admin_name) . ",\n\n" .
                "A new agent has registered on The Mzansi Marketplace:\n\n" .
                "Agent ID: " . htmlspecialchars($agentId) . "\n" .
                "Agent Name: " . htmlspecialchars($agent_name) . "\n" .
                "Email: " . htmlspecialchars($agent_email) . "\n\n" .
                "Please review the agent's details: " . htmlspecialchars($redirectlink) . "\n" .
                "Contact the agent: mailto:" . htmlspecialchars($agent_email) . "\n\n" .
                "© 2025 The Mzansi Marketplace. All rights reserved.\n" .
                "The Mzansi Marketplace, 123 Business Address, City, South Africa\n" .
                "Unsubscribe: " . htmlspecialchars($unsubscribe_link) . "\n" .
                "This is an automated notification from themzansimarketplace.co.za";

            if ($mail->send()) {
                error_log('New seller notification email sent successfully to ' . $admin_email);
                return true;
            } else {
                error_log('Email sending failed: ' . $mail->ErrorInfo);
                return false;
            }
        } catch (Exception $e) {
            error_log('Exception occurred while sending new seller notification: ' . $e->getMessage());
            return false;
        }
    }

    function sendResetPasswordEmail($user_email, $user_name, $reset_token)
    {
        $reset_link = 'https://themzansimarketplace.co.za/agent/reset-password/?token=' . urlencode($reset_token);
        $unsubscribe_link = 'https://themzansimarketplace.co.za/unsubscribe?email=' . urlencode($user_email);

        $mail = new PHPMailer(true);

        try {
            // Server settings
            global $smtpData;

            $mail->isSMTP();
            $mail->Host = $smtpData['host'];
            $mail->SMTPAuth = $smtpData['SMTPAuth'];
            $mail->Username = $smtpData['username'];
            $mail->Password = $smtpData['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $smtpData['port'];

            // Recipients
            $mail->setFrom('noreply@themzansimarketplace.co.za', 'The Mzansi Marketplace');
            $mail->addReplyTo('support@themzansimarketplace.co.za', 'Support Team');
            $mail->addAddress($user_email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Mzansi Marketplace - Reset Your Password';

            // HTML email body
            $mail->Body = "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <meta http-equiv='X-UA-Compatible' content='IE=edge'>
            <title>Reset Your Password - Mzansi Marketplace</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                }
                body {
                    background: #f0f2f5;
                    color: #1e293b;
                    line-height: 1.6;
                    text-align: center;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: #ffffff;
                    border-radius: 12px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                    overflow: hidden;
                }
                .header {
                    background: linear-gradient(135deg, #ac6111, #e68835);
                    padding: 30px 20px;
                    color: #ffffff;
                    text-align: center;
                }
                .header h1 {
                    font-size: 24px;
                    font-weight: 600;
                    letter-spacing: 0.5px;
                }
                .content {
                    padding: 20px;
                    text-align: left;
                }
                .content p {
                    font-size: 14px;
                    color: #475569;
                    margin-bottom: 15px;
                    line-height: 1.8;
                }
                .content strong {
                    color: #1e293b;
                }
                .button {
                    display: inline-block;
                    padding: 12px 24px;
                    background: linear-gradient(135deg, #ac6111, #e68835);
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 50px;
                    font-weight: 500;
                    font-size: 14px;
                    margin: 10px 0;
                    transition: transform 0.2s ease;
                }
                .button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                }
                .footer {
                    background: #f8fafc;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #64748b;
                    border-top: 1px solid #e2e8f0;
                }
                .footer a {
                    color: #e68835;
                    text-decoration: none;
                    font-weight: 500;
                }
                .footer a:hover {
                    text-decoration: underline;
                }
                @media only screen and (max-width: 600px) {
                    .container {
                        margin: 10px;
                    }
                    .header h1 {
                        font-size: 20px;
                    }
                    .content {
                        padding: 15px;
                    }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Reset Request 🔒</h1>
                </div>
                <div class='content'>
                    <p>Dear " . htmlspecialchars($user_name) . ",</p>
                    <p>We received a request to reset your password for your Mzansi Marketplace account.</p>
                    <p>Click the button below to reset your password. This link will expire in 1 hour for your security.</p>
                    <p style='text-align: center;'>
                        <a href='" . htmlspecialchars($reset_link) . "' class='button' style='color:white;'>Reset Password</a>
                    </p>
                    <p>If you did not request a password reset, please ignore this email or contact our support team.</p>
                </div>
                <div class='footer'>
                    <p>© 2025 The Mzansi Marketplace. All rights reserved.</p>
                    <p>
                        <a href='https://themzansimarketplace.co.za/portal/legal/'>Terms & Conditions</a> |
                        <a href='https://themzansimarketplace.co.za/portal/legal/'>Privacy Policy</a> |
                        <a href='" . htmlspecialchars($unsubscribe_link) . "'>Unsubscribe</a>
                    </p>
                    <p>The Mzansi Marketplace<br>123 Business Address, City, South Africa</p>
                    <p>This is an automated notification from themzansimarketplace.co.za</p>
                </div>
            </div>
        </body>
        </html>";

            // Plain-text version
            $mail->AltBody = "Dear " . htmlspecialchars($user_name) . ",\n\n" .
                "We received a request to reset your password for your Mzansi Marketplace account.\n\n" .
                "Click the link below to reset your password (expires in 1 hour):\n" .
                htmlspecialchars($reset_link) . "\n\n" .
                "If you did not request a password reset, please ignore this email or contact our support team.\n\n" .
                "© 2025 The Mzansi Marketplace. All rights reserved.\n" .
                "The Mzansi Marketplace, 123 Business Address, City, South Africa\n" .
                "Unsubscribe: " . htmlspecialchars($unsubscribe_link) . "\n" .
                "This is an automated notification from themzansimarketplace.co.za";

            if ($mail->send()) {
                error_log('Password reset email sent successfully to ' . $user_email);
                return true;
            } else {
                error_log('Email sending failed: ' . $mail->ErrorInfo);
                return false;
            }
        } catch (Exception $e) {
            error_log('Exception occurred while sending password reset email: ' . $e->getMessage());
            return false;
        }
    }

    function sendPasswordChangeConfirmationEmail($user_email, $user_name)
    {
        $unsubscribe_link = 'https://themzansimarketplace.co.za/unsubscribe?email=' . urlencode($user_email);

        $mail = new PHPMailer(true);

        try {
            // Server settings
            global $smtpData;

            $mail->isSMTP();
            $mail->Host = $smtpData['host'];
            $mail->SMTPAuth = $smtpData['SMTPAuth'];
            $mail->Username = $smtpData['username'];
            $mail->Password = $smtpData['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $smtpData['port'];

            // Recipients
            $mail->setFrom('noreply@themzansimarketplace.co.za', 'The Mzansi Marketplace');
            $mail->addReplyTo('support@themzansimarketplace.co.za', 'Support Team');
            $mail->addAddress($user_email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Mzansi Marketplace - Password Changed Successfully';

            // HTML email body
            $mail->Body = "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <meta http-equiv='X-UA-Compatible' content='IE=edge'>
            <title>Password Changed - Mzansi Marketplace</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                }
                body {
                    background: #f0f2f5;
                    color: #1e293b;
                    line-height: 1.6;
                    text-align: center;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: #ffffff;
                    border-radius: 12px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                    overflow: hidden;
                }
                .header {
                    background: linear-gradient(135deg, #ac6111, #e68835);
                    padding: 30px 20px;
                    color: #ffffff;
                    text-align: center;
                }
                .header h1 {
                    font-size: 24px;
                    font-weight: 600;
                    letter-spacing: 0.5px;
                }
                .content {
                    padding: 20px;
                    text-align: left;
                }
                .content p {
                    font-size: 14px;
                    color: #475569;
                    margin-bottom: 15px;
                    line-height: 1.8;
                }
                .content strong {
                    color: #1e293b;
                }
                .button {
                    display: inline-block;
                    padding: 12px 24px;
                    background: linear-gradient(135deg, #ac6111, #e68835);
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 50px;
                    font-weight: 500;
                    font-size: 14px;
                    margin: 10px 0;
                    transition: transform 0.2s ease;
                }
                .button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                }
                .footer {
                    background: #f8fafc;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #64748b;
                    border-top: 1px solid #e2e8f0;
                }
                .footer a {
                    color: #e68835;
                    text-decoration: none;
                    font-weight: 500;
                }
                .footer a:hover {
                    text-decoration: underline;
                }
                @media only screen and (max-width: 600px) {
                    .container {
                        margin: 10px;
                    }
                    .header h1 {
                        font-size: 20px;
                    }
                    .content {
                        padding: 15px;
                    }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Changed Successfully ✅</h1>
                </div>
                <div class='content'>
                    <p>Dear " . htmlspecialchars($user_name) . ",</p>
                    <p>Your password for The Mzansi Marketplace account has been successfully changed.</p>
                    <p>You can now log in to your account using your new password.</p>
                    <p style='text-align: center;'>
                        <a href='https://themzansimarketplace.co.za/agent/login/' class='button' style='color:white;'>Log In Now</a>
                    </p>
                    <p>If you did not initiate this change, please contact our support team immediately at <a href='mailto:support@themzansimarketplace.co.za'>support@themzansimarketplace.co.za</a>.</p>
                </div>
                <div class='footer'>
                    <p>© 2025 The Mzansi Marketplace. All rights reserved.</p>
                    <p>
                        <a href='https://themzansimarketplace.co.za/portal/legal/'>Terms & Conditions</a> |
                        <a href='https://themzansimarketplace.co.za/portal/legal/'>Privacy Policy</a> |
                        <a href='" . htmlspecialchars($unsubscribe_link) . "'>Unsubscribe</a>
                    </p>
                    <p>The Mzansi Marketplace<br>123 Business Address, City, South Africa</p>
                    <p>This is an automated notification from themzansimarketplace.co.za</p>
                </div>
            </div>
        </body>
        </html>";

            // Plain-text version
            $mail->AltBody = "Dear " . htmlspecialchars($user_name) . ",\n\n" .
                "Your password for The Mzansi Marketplace account has been successfully changed.\n\n" .
                "You can now log in to your account using your new password: https://themzansimarketplace.co.za/agent/login/\n\n" .
                "If you did not initiate this change, please contact our support team immediately at support@themzansimarketplace.co.za.\n\n" .
                "© 2025 The Mzansi Marketplace. All rights reserved.\n" .
                "The Mzansi Marketplace, 123 Business Address, City, South Africa\n" .
                "Unsubscribe: " . htmlspecialchars($unsubscribe_link) . "\n" .
                "This is an automated notification from themzansimarketplace.co.za";

            if ($mail->send()) {
                error_log('Password change confirmation email sent successfully to ' . $user_email);
                return true;
            } else {
                error_log('Email sending failed: ' . $mail->ErrorInfo);
                return false;
            }
        } catch (Exception $e) {
            error_log('Exception occurred while sending password change confirmation email: ' . $e->getMessage());
            return false;
        }
    }

    function enhancementPurchaseEmail($email, $fullname, $enhancementType, $enhancementAmount)
    {
        $redirectlink = 'https://seller.themzansimarketplace.co.za/registration/';

        $mail = new PHPMailer(true);
        try {
            // Server settings
            global $smtpData;

            $mail->isSMTP();
            $mail->Host = $smtpData['host'];
            $mail->SMTPAuth = $smtpData['SMTPAuth'];
            $mail->Username = $smtpData['username'];
            $mail->Password = $smtpData['password'];
            $mail->SMTPSecure = $smtpData['secure'];
            $mail->Port = $smtpData['port'];

            // Recipients
            $mail->setFrom('noreply@themzansimarketplace.co.za', 'The Mzansi Marketplace');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "Mzansi Marketplace - Enhancement Purchase Confirmed";

            // HTML email body
            $mail->Body = "
                <!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
                    <title>Enhancement Purchase Confirmed - Mzansi Marketplace</title>
                    <style>
                        body {
                            margin: 0;
                            padding: 0;
                            font-family: 'Arial', 'Helvetica', sans-serif;
                            color: #333333;
                            line-height: 1.6;
                            background-color: #f4f4f4;
                        }
                        .container {
                            max-width: 600px;
                            margin: 20px auto;
                            background: #ffffff;
                            border: 1px solid #e0e0e0;
                            border-radius: 8px;
                            overflow: hidden;
                        }
                        .header {
                            background-color: #ac6111;
                            padding: 20px;
                            text-align: center;
                            color: #ffffff;
                        }
                        .header h1 {
                            font-size: 24px;
                            margin: 0;
                            font-weight: normal;
                        }
                        .content {
                            padding: 20px;
                        }
                        .content p {
                            font-size: 14px;
                            color: #555555;
                            margin-bottom: 15px;
                        }
                        .content strong {
                            color: #333333;
                        }
                        .button {
                            display: inline-block;
                            padding: 12px 24px;
                            background-color: #ac6111;
                            color: #ffffff;
                            text-decoration: none;
                            border-radius: 5px;
                            font-size: 16px;
                            margin: 15px 0;
                        }
                        .footer {
                            background-color: #f4f4f4;
                            padding: 15px;
                            text-align: center;
                            font-size: 12px;
                            color: #777777;
                            border-top: 1px solid #e0e0e0;
                        }
                        .footer a {
                            color: #ac6111;
                            text-decoration: none;
                        }
                        @media only screen and (max-width: 600px) {
                            .container {
                                width: 100%;
                                margin: 10px;
                            }
                            .header h1 {
                                font-size: 20px;
                            }
                            .content {
                                padding: 15px;
                            }
                            .button {
                                display: block;
                                text-align: center;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Enhancement Purchase Confirmed</h1>
                        </div>
                        <div class='content'>
                            <p>Hello $fullname,</p>
                            <p>Thank you for your purchase! Your <strong>$enhancementType</strong> enhancement has been successfully activated for your Mzansi Marketplace account.</p>
                            <p>
                                <strong>Purchase Details:</strong><br>
                                Enhancement Type: $enhancementType<br>
                                Amount: R $enhancementAmount<br>
                                Email: $email
                            </p>
                            <p>You can now enjoy the benefits of your new enhancement. If you have any questions, contact our support team.</p>
                            <p style='text-align: center;'>
                                <a href='$redirectlink#support' class='button'>Contact Support</a>
                            </p>
                        </div>
                        <div class='footer'>
                            <p>© 2025 Mzansi Marketplace. All rights reserved.</p>
                            <p>
                                <a href='https://themzansimarketplace.co.za/wording/legal/'>Terms & Conditions</a> |
                                <a href='https://themzansimarketplace.co.za/wording/legal/'>Privacy Policy</a>
                            </p>
                            <p>You received this email because you purchased an enhancement at themzansimarketplace.co.za</p>
                        </div>
                    </div>
                </body>
                </html>";

            // Plain text fallback
            $mail->AltBody = "Mzansi Marketplace - Enhancement Purchase Confirmed\n\nHello $fullname,\n\nThank you for your purchase! Your $enhancementType enhancement has been successfully activated for your Mzansi Marketplace account.\n\nPurchase Details:\nEnhancement Type: $enhancementType\nAmount: R $enhancementAmount\nEmail: $email\n\nYou can now enjoy the benefits of your new enhancement. If you have any questions, contact our support team at $redirectlink#support.\n\n© 2025 Mzansi Marketplace. All rights reserved.\nTerms & Conditions: https://themzansimarketplace.co.za/wording/legal/\nPrivacy Policy: https://themzansimarketplace.co.za/wording/legal/\n\nYou received this email because you purchased an enhancement at themzansimarketplace.co.za";

            if ($mail->send()) {
                error_log('Enhancement purchase confirmation email sent successfully to ' . $email);
            } else {
                error_log('Email sending failed: ' . $mail->ErrorInfo);
            }
        } catch (Exception $e) {
            error_log('Exception occurred while sending enhancement purchase confirmation email: ' . $e->getMessage());
        }
    }
}
