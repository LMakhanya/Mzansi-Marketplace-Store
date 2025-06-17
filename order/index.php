<?php


$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
include($ENV_IPATH . "env.php");

require $_SERVER["DOCUMENT_ROOT"] . '/api/classes.php';

$classOrder = new order();
$classPayements = new Payment();

session_start();

// Assuming $pdo is initialized elsewhere (e.g., in a config file)


if (!isset($_GET["customer"]) || !isset($_GET['id'])) {

    $customerID = $_SESSION["customerID"] ?? null;
    $checkoutID = $_SESSION["checkoutID"] ?? null;

    if (!$customerID || !$checkoutID) {
        die("Error: Missing customerID or checkoutID.");
    }
} else {
    $customerID = $_GET["customer"];
    $checkoutID = $_GET["id"];
}

/* echo "Customer ID:$customerID & CheckoutID:$checkoutID"; */

// Initialize the database connection
$pdo = initializeDatabase();

if ($classOrder->checkoutIdExists($checkoutID)) {
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
} else {
    header("Location: /index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | The Mzansi Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/styles/orderconfirm.css">
</head>

<body>

    <main class="container">
        <div class="header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your purchase. We've sent a confirmation to your email.</p>
        </div>

        <div class="card" style="animation-delay: 0.4s;">
            <div class="card-header">
                <h2 style="font-size: 1.25rem; font-weight: 600; color: #111827; display: flex; align-items: center;">
                    <i class="fas fa-receipt" style="color: #e78026; margin-right: 12px;"></i>
                    Order Summary
                </h2>
            </div>
            <div class="status-tracker">
                <div class="timeline">
                    <div class="milestone completed">
                        <span class="circle">
                            <p>✔</p>
                        </span>
                        <div class="label">Confirmed</div>
                        <div class="date">On <?php echo date('F j, Y', strtotime($orderDate)) ?? ''; ?></div>
                    </div>
                    <div class="milestone">
                        <span class="circle"></span>
                        <div class="label">Shipped</div>
                        <div class="date">-</div>
                    </div>
                    <div class="milestone">
                        <span class="circle"></span>
                        <div class="label">Out for Delivery</div>
                        <div class="date">-</div>
                    </div>
                    <div class="milestone">
                        <span class="circle"></span>
                        <div class="label">Delivered</div>
                        <div class="date">-</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="grid">
                    <div>
                        <h3 style="font-size: 0.875rem; color: #6b7280; margin-bottom: 8px;">Order Number</h3>
                        <p style="font-weight: 500; color: #111827;">#ORD-<?php echo htmlspecialchars($orderNo ?? ''); ?></p>
                    </div>
                    <div>
                        <h3 style="font-size: 0.875rem; color: #6b7280; margin-bottom: 8px;">Date</h3>
                        <p style="font-weight: 500; color: #111827;" id="current-date"><?php echo date('F j, Y', strtotime($orderDate)) ?? ''; ?></p>
                    </div>
                    <div>
                        <h3 style="font-size: 0.875rem; color: #6b7280; margin-bottom: 8px;">Total</h3>
                        <p style="font-weight: bold; color:rgb(226, 138, 61); font-size: 1.25rem;">R<?php echo number_format($totalWithShipping ?? 0, 2); ?></p>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 0.875rem; color: #6b7280; margin-bottom: 12px;">Item(s)</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($products as $product): ?>
                            <div class="product-item">
                                <div class="product-image">
                                    <img loading="lazy" src="/uploads/<?php echo htmlspecialchars($product['product_image']); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                                </div>
                                <div style="flex-grow: 1;">
                                    <h4 style="font-weight: 500; color: #111827;"><?php echo htmlspecialchars($product['ProductName']); ?></h4>
                                    <p style="font-size: 0.875rem; color: #6b7280;"><?php echo htmlspecialchars($product['quantity']); ?> × R<?php echo number_format($product['price']); ?></p>
                                </div>
                                <div style="font-weight: 500; color: #111827;">R<?php echo number_format($product['price'] * $product['quantity'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="border-top: 1px solid #f0f0f0; padding-top: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #6b7280;">Subtotal</span>
                        <span style="font-weight: 500;">R<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #6b7280;">Shipping</span>
                        <span style="font-weight: 500;">R<?php echo number_format($shippingCost, 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-top: 16px; border-top: 1px solid #f0f0f0; font-weight: bold; font-size: 1.125rem;">
                        <span>Total</span>
                        <span>R<?php echo number_format($totalWithShipping, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid bottom">
            <div class="card" style="animation-delay: 0.6s;">
                <div class="card-body">
                    <h2 style="font-size: 1.25rem; font-weight: 600; color: #111827; display: flex; align-items: center; margin-bottom: 16px;">
                        <i class="fas fa-truck" style="color: #e78026; margin-right: 12px;"></i>
                        Shipping Information
                    </h2>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <p style="font-weight: 500;">John Doe</p>
                        <p style="color: #6b7280;"><?php echo nl2br(htmlspecialchars($shippingAddress)); ?></p>
                    </div>
                </div>
            </div>
            <div class="card" style="animation-delay: 0.8s;">
                <div class="card-body">
                    <h2 style="font-size: 1.25rem; font-weight: 600; color: #111827; display: flex; align-items: center; margin-bottom: 16px;">
                        <i class="fas fa-credit-card" style="color: #e78026; margin-right: 12px;"></i>
                        Payment Method
                    </h2>
                    <?php if ($p_type === 'card') { ?>
                        <div style="display: flex; align-items: center;">
                            <div style="width: 40px; height: 24px; background-color: #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                                <i class="fab fa-cc-visa" style="color: #1e3a8a;"></i>
                            </div>
                            <div>
                                <p style="font-weight: 500;">**** **** **** <?php echo htmlspecialchars($cardDetails['last4Digits'] ?? 'XXXX'); ?></p>
                                <p style="color: #6b7280; font-size: 0.875rem;">Expires 05/2025</p>
                                <p style="color: #6b7280; font-size: 0.875rem;">Charged: R<?php echo number_format($totalWithShipping ?? 0, 2); ?></p>
                                <p style="color: #6b7280; font-size: 0.875rem;">Billed on: <?php echo date('F j, Y', strtotime($orderDate)) ?? ''; ?></p>
                            </div>
                        </div>
                    <?php } elseif ($p_type === 'instant_eft') { ?>
                        <div style="display: flex; align-items: center;">
                            <div style="width: 40px; height: 24px; background-color: #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                                <p style="color:#6b7280; font-size:.9rem">EFT</p>
                            </div>
                            <div>
                                <p style="color: #6b7280; font-size: 0.875rem;">Charged: R<?php echo number_format($totalWithShipping ?? 0, 2); ?></p>
                                <p style="color: #6b7280; font-size: 0.875rem;">Billed on: <?php echo date('F j, Y', strtotime($orderDate)) ?? ''; ?></p>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="buttons">
            <a href="/" class="btn btn-primary">
                <i class="fas fa-shopping-bag" style="margin-right: 8px;"></i>
                Continue Shopping
            </a>

        </div>
    </main>

    <footer>
        <p style="color: #6b7280; margin-bottom: 16px;">Need help? <a href="#" style="color: #2563eb; text-decoration: none;">Contact our support team</a></p>
        <div class="social-links">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
        </div>
        <p style="color: #9ca3af; font-size: 0.875rem;">© 2025 The Mzansi Marketplace. All rights reserved.</p>
    </footer>

    <script>
        // Create confetti effect
        function createConfetti() {
            const colors = ['#f0f', '#0ff', '#ff0', '#f00', '#0f0', '#00f'];
            const container = document.querySelector('body');

            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = -10 + 'px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.transform = `rotate(${Math.random() * 360}deg)`;

                container.appendChild(confetti);

                const animation = confetti.animate([{
                        top: '-10px',
                        opacity: 1
                    },
                    {
                        top: '100vh',
                        opacity: 0
                    }
                ], {
                    duration: 3000 + Math.random() * 3000,
                    easing: 'cubic-bezier(0.1, 0.8, 0.9, 1)'
                });

                animation.onfinish = () => confetti.remove();
            }
        }

        // Trigger confetti on page load
        window.addEventListener('load', () => {
            setTimeout(createConfetti, 500);
        });
    </script>
</body>

</html>