<?php
ob_start();

$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";

require_once $ENV_IPATH . "conn.php";
require_once $ENV_IPATH . "env.php";
session_start();
// Check if the user is authenticated
if (!isset($_SESSION["username"])) {
    $_SESSION["username"] = "";
}
if (!isset($_SESSION["FirstName"])) {
    $_SESSION["FirstName"] = "";
}
if (!isset($_SESSION["LastName"])) {
    $_SESSION["LastName"] = "";
}
if (!isset($_SESSION["userEmail"])) {
    $_SESSION["userEmail"] = "";
}
if (!isset($_SESSION["Phone"])) {
    $_SESSION["Phone"] = "";
}
if (!isset($_SESSION["Address"])) {
    $_SESSION["Address"] = "";
}

$totalAmount = $_GET["total"] ?? 0;  // Default to 0 if not set
$bagID = $_GET["bag"] ?? null;
$sellerID = $_GET["seller"] ?? null;


// Check if any of these values are null or empty
if (empty($totalAmount) || empty($bagID) || empty($sellerID)) {
    header("Location: /index.php");
    exit();
}

$fullname = '';
if ($_SESSION["FirstName"]) {
    $fullname .= $_SESSION["FirstName"];
}
if ($_SESSION["LastName"]) {
    $fullname .= " " . $_SESSION["LastName"];
}

$_SESSION["username"];
$_SESSION["userEmail"];
$_SESSION["Phone"];

$hasShippingOptions = false; // Default to no shipping options
$standardOption = null;
$expressOption = null;
$rushOption = null;
$standardPrice = null;
$expressPrice = null;
$rushPrice = null;
$currency = 'ZAR';

if ($sellerID) {
    try {
        $pdo = initializeDatabase();

        // Query to join the two tables and get shipping options and prices
        $query = "
                SELECT 
                    s.standard, 
                    s.express, 
                    s.rush, 
                    p.standard_price, 
                    p.express_price, 
                    p.rush_price, 
                    p.currency 
                FROM sss_business_shipping s
                LEFT JOIN sss_business_shipping_prices p ON s.id = p.shipping_id
                WHERE s.sellerID = :sellerID
                ORDER BY p.effective_date DESC
                LIMIT 1
            ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([':sellerID' => $sellerID]);
        $shippingData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($shippingData) {
            // Assign values from the database
            $standardOption = $shippingData['standard'];
            $expressOption = $shippingData['express'];
            $rushOption = $shippingData['rush'];
            $standardPrice = $shippingData['standard_price'];
            $expressPrice = $shippingData['express_price'];
            $rushPrice = $shippingData['rush_price'];
            $currency = $shippingData['currency'] ?? 'ZAR';

            // Check if any shipping option is available
            $hasShippingOptions = ($standardOption && $standardPrice !== null) ||
                ($expressOption && $expressPrice !== null) ||
                ($rushOption && $rushPrice !== null);
        }
    } catch (PDOException $e) {
        // Handle database errors (optional: log the error)
        error_log("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php
    $request = $_SERVER['REQUEST_URI'];
    $parsedUrl = parse_url($request);
    $path = ltrim($parsedUrl['path'] ?? '', '/'); // Get the path part without leading slash
    $custompath = 'pay';
    $IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/includes/";
    include($IPATH . "metadata.php"); ?>
    <link rel="stylesheet" href="/styles/pay.css">
</head>


<body>
    <div class="shop-header">
        <nav>
            <div class="upper_nav">
                <div>
                    <label for="check" class="checkbtn">
                        <i class="ph ph-list" id="menu"></i>
                    </label>
                    <div class="logo">
                        <img src="/assets/images/themzansim_logo_nobg.png" onclick="continueShopping()" alt="logo">
                    </div>

                </div>

                <input type="hidden" name="store" id="" value="">
            </div>
        </nav>
    </div>

    <form method="POST" action="/yoco/">
        <input type="hidden" name="customerID" value="<?php echo $_SESSION['customerID'] ?>">
        <input type="hidden" name="amount" id="amount-input" value="<?php echo $totalAmount ?>">
        <input type="hidden" name="bagid" value="<?php echo $bagID ?>">
        <input type="hidden" name="sellerid" value="<?php echo $sellerID ?>">
        <div class="checkout-container">
            <div class="form-section">
                <!-- Contact Information -->
                <div class="section-form-group">
                    <h2>Contact Information</h2>
                    <div class="form-group">
                        <input type="text" id="fullname" name="fullname" value="<?php echo $fullname ?>" placeholder=" " required>
                        <label for="fullname">Full Name</label>
                    </div>
                    <div class="form-group">
                        <input type="email" id="email" name="email" value="<?php echo $_SESSION["userEmail"] ?>" placeholder=" " required>
                        <label for="email">Email</label>
                    </div>
                    <div class="form-group">
                        <input type="hidden" id="phone" name="phone" value="" placeholder=" " pattern="[0-9]{10}">
                        <!-- <label for="phone">Phone Number (Optional)</label> -->
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="section-form-group">
                    <h2>Shipping Address</h2>
                    <div class="form-group">
                        <input type="text" id="houseno" name="houseno" placeholder=" ">
                        <label for="houseno">House Number (Optional)</label>
                    </div>
                    <div class="form-group">
                        <input type="text" id="addressl1" name="addressl1" placeholder=" " required>
                        <label for="addressl1">Address Line 1</label>
                    </div>
                    <div class="form-group">
                        <input type="text" id="addressL2" name="addressl2" placeholder=" ">
                        <label for="addressL2">Address Line 2 (Optional)</label>
                    </div>
                    <div class="combo_field ">

                        <div class="form-group with-mb">
                            <input type="text" id="city" name="city" placeholder=" " required>
                            <label for="city">City</label>
                        </div>
                        <div class="form-group">
                            <input type="text" id="zip" name="pcode" placeholder=" " required pattern="[0-9]{4}">
                            <label for="zip">ZIP Code</label>
                        </div>
                    </div>
                    <select class="dropdown" id="state" name="state" required>
                        <option value="" selected disabled>--Select Province--</option>
                        <option value="KwaZuluNatal">KwaZulu Natal</option>
                        <option value="EasternCape">Eastern Cape</option>
                        <option value="FreeState">Free State</option>
                        <option value="Gauteng">Gauteng</option>
                        <option value="WesternCape">Western Cape</option>
                        <option value="NorthernCape">Northern Cape</option>
                        <option value="Limpopo">Limpopo</option>
                        <option value="Mpumalanga">Mpumalanga</option>
                        <option value="NorthWest">North West</option>
                        <!-- Add more states as needed -->
                    </select>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h2>Order Summary</h2>

                <!-- Shipping Options -->
                <div class="shipping-options">
                    <h3>Shipping Method</h3>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="shipping" value="0" class="shipping-radio" data-type="Collect in Store" checked>
                            Collect in Store
                        </label>
                    </div>
                    <?php if ($hasShippingOptions): ?>
                        <?php if ($standardOption && $standardPrice !== null): ?>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="shipping" value="<?php echo htmlspecialchars($standardPrice); ?>" class="shipping-radio" data-type="Standard Shipping">
                                    Standard Shipping (2-5 days)
                                </label>
                            </div>
                        <?php endif; ?>
                        <?php if ($expressOption && $expressPrice !== null): ?>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="shipping" value="<?php echo htmlspecialchars($expressPrice); ?>" class="shipping-radio" data-type="Express Shipping">
                                    Express Shipping (1-2 days)
                                </label>
                            </div>
                        <?php endif; ?>
                        <?php if ($rushOption && $rushPrice !== null): ?>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="shipping" value="<?php echo htmlspecialchars($rushPrice); ?>" class="shipping-radio" data-type="Rush Shipping">
                                    Rush Shipping
                                </label>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Coupon Code -->
                <!--  <div class="coupon-section">
                    <h3>Coupon Code</h3>
                    <div class="coupon-input">
                        <input type="text" id="coupon" name="coupon" placeholder="Enter coupon code">
                        <button class="apply-coupon">Apply</button>
                    </div>
                </div> -->

                <div class="order-item">
                    <span>Shipping</span>
                    <span class="shipping-cost">R0.00</span>
                </div>
                <div class="total">
                    <span>Total:</span>
                    <span class="total-amount">R<?php echo number_format((float)$totalAmount, 2, '.', ''); ?></span>
                </div>

                <input type="hidden" name="shippingType" id="shippingType">
                <input type="hidden" name="shippingAmnt" id="shippingAmnt">

                <button class="place-order">Place Order</button>
            </div>
        </div>
    </form>
    <div class="moveup">
        <i class="fa-solid fa-angle-up"></i>
    </div>
    <div id="alert-container" class="alert-container"></div>
    <br>
    <footer class="footer">
        <div class="payments-icons">
            <img loading="lazy" src="/assets/icons/yoco.svg" alt="yoco">
            <img loading="lazy" src="/assets/icons/mastercard-logo.png" style="object-fit: contain;" alt="mastercard">
            <img loading="lazy" src="/assets/icons/VISA-logo.png" alt="visa">
            <img loading="lazy" src="/assets/icons/eft.png" alt="eft" class="bg-white">
            <img loading="lazy" src="/assets/icons/google-pay.png" class="bg-white" alt="googlepay">
            <img loading="lazy" src="/assets/icons//apple-pay-og.png" class="bg-white" alt="applepay">
        </div>
        <div class="footer-row">
            <div class="footer-col">
                <h4>Mzansi Shop</h4>
                <ul class="links">
                    <li><a href="/portal/">Buying / Orders</a></li>
                    <li><a href="/portal/sellers.php">Sell with Us</a></li>
                    <li><a href="/portal/agents.php">Become an Agent</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Info</h4>
                <ul class="links">
                    <li><a href="/portal/#">About Us</a></li>
                    <li><a href="/portal/#">Stores</a></li>
                    <li><a href="/portal/#">Testimonials</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Legal</h4>
                <ul class="links">
                    <li><a href="/portal/legal/" target="_blank">Customer Agreement</a></li>
                    <li><a href="/portal/legal/" target="_blank">Privacy Policy</a></li>
                    <li><a href="/portal/legal/" target="_blank">Terms & Conditions</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Newsletter</h4>
                <p>
                    Subscribe to our newsletter for a weekly dose
                    of news, updates, helpful tips, and
                    exclusive offers.
                </p>
                <form method="POST" action="/api/subscribe.php">
                    <input name="email" type="text" placeholder="Write your email" required>
                    <button name="subscribe"><i class="fa-regular fa-paper-plane"></i></button>
                </form>
                <div class="icons">
                    <a href="https://www.facebook.com/profile.php?id=61574846430721&mibextid=ZbWKwL" target="_blank"><i
                            class="fa-brands fa-facebook-f"></i></a>
                    <a href="https://www.linkedin.com/in/luvuyo-makhanya-97a46a217" target="_blank"><i
                            class="fa-brands fa-linkedin"></i></a>
                    <a href=" https://wa.me/27695352229?text=Hi.%20I'm%20writing%20from%20The%20Mzansi%20Marketplace%20website..."
                        target="_blank"><i class="fa-brands fa-whatsapp"></i></a>
                    <!-- <i class="fa-brands fa-github"></i> -->
                </div>
            </div>
        </div>
        <div class="copy-right">
            <p>&copy; 2025 <strong>The Mzansi Marketplace</strong>. All rights reserved.
            </p>
            <p>
                Designed with ❤️ in South Africa by <strong><a href="http://bradazinvestments.co.za" target="_blank">The Bradaz
                        Investments (Pty) Ltd.</a></strong>
            </p>
        </div>
    </footer>

    <script>
        // Get all necessary elements
        const shippingRadios = document.querySelectorAll('.shipping-radio');
        const shippingCostElement = document.querySelector('.shipping-cost');
        const totalAmountElement = document.querySelector('.total-amount');
        const amountInput = document.querySelector('#amount-input');
        const shippingTypeInput = document.querySelector('#shippingType');
        const shippingAmntInput = document.querySelector('#shippingAmnt');
        const baseAmount = <?php echo (float)$totalAmount; ?>;

        // Function to update totals and hidden inputs
        function updateTotal() {
            const selectedShipping = document.querySelector('.shipping-radio:checked');
            const shippingCost = parseFloat(selectedShipping.value);
            const shippingType = selectedShipping.getAttribute('data-type');

            // Update shipping cost display
            shippingCostElement.textContent = 'R' + shippingCost.toFixed(2);

            // Calculate new total
            const newTotal = baseAmount + shippingCost;

            // Update display total
            totalAmountElement.textContent = 'R' + newTotal.toFixed(2);

            // Update hidden input values
            if (amountInput) amountInput.value = newTotal.toFixed(2);
            shippingTypeInput.value = shippingType;
            shippingAmntInput.value = shippingCost.toFixed(2);
        }

        // Add event listeners to all shipping radio buttons
        shippingRadios.forEach(radio => {
            radio.addEventListener('change', updateTotal);
        });

        // Initialize with default value
        updateTotal();

        function continueShopping() {
            window.location.href = '/';
        }
    </script>
</body>

</html>