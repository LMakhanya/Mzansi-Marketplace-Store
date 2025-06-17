<?php
ob_start(); // Start output buffering
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Database connection setting
$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
include($ENV_IPATH . "conn.php");
include($ENV_IPATH . "env.php");

// Initialize the database connection
$pdo = initializeDatabase();
include($IPATH . "userData.php");

// gettotal number of product categoories
$totalCategories = "SELECT COUNT(DISTINCT c.category_id) AS total_categories FROM product AS p
JOIN product_categories AS c ON p.category_id = c.category_id";
$totalCategoriesResult = mysqli_query($conn, $totalCategories);
$totalCategoriesRow = mysqli_fetch_assoc($totalCategoriesResult);
$totalCategories = $totalCategoriesRow["total_categories"];

$totalCategories = $totalCategories - 1; // Subtract 1 to exclude the 'All' category

/* echo $totalCategories; */

//geting all the categories form the ctegory table 
$getCategoriesSQL = "SELECT DISTINCT * FROM product_categories LIMIT 10";
$ctegoryResult = mysqli_query($conn, $getCategoriesSQL);


//get Electronics category list
$getAllBusinesses = "SELECT * FROM sss_business sb 
    JOIN sellers  s ON sb.seller_id = s.seller_id
    WHERE s.is_verified = 'yes'";
$getAllBusinessesResult = mysqli_query($conn, $getAllBusinesses);

?>
<!DOCTYPE html>
<html>

<head>
    <?php
    $INCLUDES_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/includes/";

    include($INCLUDES_IPATH . "metadata.php"); ?>
    <link rel="stylesheet" href="/styles/orderChat.css">
    <link rel="stylesheet" type="text/css" href="/styles/profileStyle.css">
    <link rel="stylesheet" type="text/css" href="/styles/orderStyle.css">
    <link rel="stylesheet" type="text/css" href="/styles/responsive/profile.css">
    <link rel="stylesheet" type="text/css" href="/styles/reciepts.css">

</head>

<body>
    <?php
    include($INCLUDES_IPATH . "headernav.php");
    $checkoutTotal = 0;
    $itemsTotal = 0;
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
        echo '<script>window.location.href = "/";</script>';
        exit();
    }
    ?>

    <div class="mybody">
        <div class="profile_column">
            <div class="p_col one" id="side-profile">
                <div class="item_container">
                    <ul class="profile-options">
                        <!-- <li class="selected" data-target="dashboard">Dashboard</li> -->
                        <li class="selected" data-target="personaD">Personal Details</li>
                        <li data-target="order">My Orders</li>
                        <li data-target="deliveryAddress">Manage Address</li>
                    </ul>
                </div>
                <div class="togglePMenu">
                    <p id="minimise" onclick="toggleCloseProfile()"><i class="fa-solid fa-caret-down"></i></p>
                </div>
            </div>

            <div class="p_col two">

                <div style="display: none;" class="p_text_content personaD">
                    <h2 class="section-title">Personal Details</h2>
                    <button type="button" class="edit-button" onclick="toggleEdit()">Edit Details</button>
                    <form id="personalDetailsForm" class="details-form" method="POST" action="/api/update_profile.php">
                        <?php
                        $sql = "SELECT * FROM customer c
                        LEFT JOIN customer_address ca ON c.addressID = ca.id
                        WHERE c.CustomerID = '" . $_SESSION['customerID'] . "'";

                        $result = $conn->query($sql);

                        if ($result && $row = $result->fetch_assoc()) {
                            $streetName = ($row['addressl1'] ?? '') . ' ' . ($row['addressl2'] ?? '');
                            $city = $row['city'] ?? '';
                            $stateProvince = $row['province'] ?? '';
                            $postalCode = $row['postalCode'] ?? '';
                            $fullAddress = trim($streetName . ", " . $city . ", " . $stateProvince . " " . $postalCode);
                        ?>
                            <div class="input-combo">
                                <div class="inputfield">
                                    <input type="text" class="text-field" name="firstName" id="firstName" value="<?php echo htmlspecialchars($row['firstname']); ?>" placeholder=" " disabled>
                                    <label for="firstName">First Name</label>
                                </div>
                                <div class="inputfield">
                                    <input type="text" class="text-field" name="lastName" id="lastName" value="<?php echo htmlspecialchars($row['lastname']); ?>" placeholder=" " disabled>
                                    <label for="lastName">LastName</label>
                                </div>
                            </div>

                            <div class="inputfield">
                                <input type="email" class="text-field" name="email" id="pemail" value="<?php echo htmlspecialchars($row['Email']); ?>" placeholder=" " disabled>
                                <label for="email">Email</label>
                            </div>

                            <div class="input-combo">
                                <div class="inputfield">
                                    <input type="tel" class="text-field" name="phone" id="pphone" value="<?php echo htmlspecialchars($row['phone']); ?>" placeholder=" " disabled>
                                    <label for="phone">Phone Number</label>
                                </div>
                                <div class="inputfield">
                                    <input type="date" class="text-field" name="dob" id="dob" value="<?php echo htmlspecialchars($row['dateOfBirth'] ?? ''); ?>" placeholder=" " disabled>
                                    <label for="dob">Date of Birth</label>
                                </div>
                            </div>

                            <div class="inputfield">
                                <select class="text-field" name="gender" id="gender" placeholder=" " disabled>
                                    <option value="male" <?php echo ($row['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($row['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($row['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                    <label for="gender">Gender</label>
                                </select>
                            </div>

                            <div class="inputfield">
                                <textarea class="text-field" name="address" id="address" rows="3" placeholder=" " disabled><?php echo htmlspecialchars($fullAddress); ?></textarea>
                                <label for="address">Address</label>
                            </div>

                            <div class="button-group">
                                <button type="submit" class="save-button" style="display: none;">Save Changes</button>
                            </div>
                        <?php } ?>
                    </form>
                </div>

                <div style="display: none;" class="p_text_content deliveryAddress">
                    <h2 class="section-title">Delivery Address</h2>

                    <!-- Address List Container -->
                    <div id="addressList" class="address-list">
                        <?php
                        $count = 0;
                        $sql = "SELECT * FROM customer_address WHERE CustomerID = '$customerID'";
                        $result = $conn->query($sql);

                        if ($result) {
                            while ($row = $result->fetch_assoc()) {
                                $count++;
                                $isShipping = $row['is_shipping'] ?? 0;
                        ?>
                                <div class="address-card <?php echo $isShipping ? 'selected' : ''; ?>" data-address-id="<?php echo $row['id']; ?>">
                                    <div class="address-summary">
                                        <div class="radio-group">
                                            <label class="custom-radio">
                                                <input type="radio"
                                                    name="shippingAddress"
                                                    value="<?php echo $row['id']; ?>"
                                                    <?php echo $isShipping ? 'checked' : ''; ?>
                                                    onchange="selectAddress('<?php echo $row['id']; ?>')">
                                                <span class="radio-checkmark"></span>
                                                <!-- <span class="radio-label">Use as Shipping</span> -->
                                            </label>
                                            <span class="address-preview">
                                                <?php echo $row['houseNumber'] . ' ' . $row['addressl1'] . ', ' . $row['city']; ?>
                                            </span>
                                        </div>
                                        <div class="card-actions">
                                            <button class="btn view-btn" onclick="toggleAdditionalInfo(this)">View Details</button>
                                            <button class="btn edit-btn" style="display: none;" onclick="editAddress(this)"><i class="ph ph-pencil-simple"></i></button>
                                            <button class="btn remove-btn" style="display: none; background-color: red;" onclick="removeAddress(this)"><i class="ph ph-trash"></i></button>
                                        </div>
                                    </div>
                                    <div class="address-details" style="display: none;">
                                        <div class="form-row">
                                            <div class="field">
                                                <label>House Number</label>
                                                <input disabled type="text" class="text-field" name="edit-houseNumber" value="<?php echo $row['houseNumber']; ?>">
                                            </div>
                                            <div class="field">
                                                <label>Street Name (Address Line 1)</label>
                                                <input disabled type="text" class="text-field" name="edit-addressl1" value="<?php echo $row['addressl1']; ?>">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="field">
                                                <label>Address Line 2</label>
                                                <input disabled type="text" class="text-field" name="edit-addressl2" value="<?php echo $row['addressl2']; ?>">
                                            </div>
                                            <div class="field">
                                                <label>City</label>
                                                <input disabled type="text" class="text-field" name="edit-city" value="<?php echo $row['city']; ?>">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="field">
                                                <label>Province</label>
                                                <input disabled type="text" class="text-field" name="edit-province" value="<?php echo $row['province']; ?>">
                                            </div>
                                            <div class="field">
                                                <label>Postal Code</label>
                                                <input disabled type="text" class="text-field" name="edit-postalCode" value="<?php echo $row['postalCode']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        <?php
                            }
                        }
                        ?>
                    </div>

                    <!-- Add New Address Button -->
                    <button id="addNewAddressBtn" class="btn primary-btn" onclick="showAddForm()">Add New Address</button>

                    <!-- Add Address Form (hidden by default) -->
                    <div id="addAddressForm" class="address-form" style="display: none;">
                        <h3>Add New Address</h3>
                        <form id="addAddressFormElement" method="POST" action="">
                            <div class="form-row">
                                <div class="field">
                                    <label>House Number</label>
                                    <input type="number" class="text-field" name="houseNumber" required>
                                </div>
                                <div class="field">
                                    <label>Street Name (Address Line 1)</label>
                                    <input type="text" class="text-field" name="addressl1" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="field">
                                    <label>Address Line 2</label>
                                    <input type="text" class="text-field" name="addressl2">
                                </div>
                                <div class="field">
                                    <label>City</label>
                                    <input type="text" class="text-field" name="city" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="field">
                                    <label>Province</label>
                                    <input type="text" class="text-field" name="province" required>
                                </div>
                                <div class="field">
                                    <label>Postal Code</label>
                                    <input type="number" class="text-field" name="postalCode" required>
                                </div>
                            </div>
                            <input type="hidden" name="customerID" value="<?php echo $customerID; ?>">
                            <input type="hidden" name="action" value="save_address">
                            <div class="form-actions">
                                <button type="submit" class="btn primary-btn">Save Address</button>
                                <button type="button" class="btn cancel-btn" onclick="hideAddForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div style="display: block;" class="p_text_content  order">
                    <h2 class="section-title">Oders</h2>
                    <div class="order-d-container">
                        <?php
                        $sqlOrders = "SELECT DISTINCT orderNo FROM `orders`  WHERE  (CustomerID = '" . $_SESSION['customerID'] . "' OR email = '" . $_SESSION['userEmail'] . "')";
                        $stmtOrders = $pdo->prepare($sqlOrders);
                        $stmtOrders->execute();
                        $resultOrders = $stmtOrders->fetchAll(PDO::FETCH_COLUMN);
                        if (empty($resultOrders)) {
                        ?>
                            <div class="empty-bag-message">
                                <img loading="lazy" src="/assets/images/emptyCart.png" alt="">
                                <p>No Orders found</p>
                            </div>
                            <?php
                        } else {
                            // Iterate over each order number and display products
                            foreach ($resultOrders as $orderNo) {
                                // Fetch products for the current orderNo
                                $sqlProducts = "SELECT op.*, ps.Model, p.*, o.*, s.firstname, sb.b_name
                                    FROM orderproducts AS op
                                    LEFT JOIN product_specs ps ON op.product_id = ps.product_id
                                    INNER JOIN product AS p ON op.product_id = p.product_id
                                    INNER JOIN orders AS o ON op.orderNo = o.orderNo
                                    INNER JOIN sellers AS s ON o.seller_id = s.seller_id
                                    INNER JOIN sss_business AS sb ON o.seller_id = sb.seller_id
                                    WHERE op.orderNo = :orderNo";
                                $stmtProducts = $pdo->prepare($sqlProducts);
                                $stmtProducts->bindParam(':orderNo', $orderNo);
                                $stmtProducts->execute();
                                $resultProducts = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
                                $itemsTotal = 0;
                            ?>
                                <div class="table">
                                    <?php if ($resultProducts) {
                                        $date = $resultProducts[0]['order_date'];
                                        $customerID = $resultProducts[0]['customerID'];
                                        $checkoutID = $resultProducts[0]['checkoutID'];
                                        $sellerId = $resultProducts[0]['seller_id'];
                                        $orderNo = $resultProducts[0]['orderNo'];
                                        $sFirstName = $resultProducts[0]['firstname'];
                                        $businessName = $resultProducts[0]['b_name'];
                                        $trackUrl = $resultProducts[0]['trackUrl'];
                                        $formattedDate = date("d M Y", strtotime($date));
                                    ?>

                                        <table class="order-details item">
                                            <tr class="t-headers">
                                                <th style="text-align: left; ">OrderNO: <span class="orderNo"><?php echo $orderNo; ?></span></th>
                                                <th>Status: <span class="orderStatus"><?php echo $resultProducts[0]['status']; ?></span></th>
                                                <th>Date: <span class="orderDate"><?php echo $formattedDate; ?></span></th>
                                            </tr>
                                            <?php foreach ($resultProducts as $row) {
                                                $price = $row['Price'];
                                                $itemQ = $row['quantity'];
                                                $itemsTotal += $price * $itemQ;

                                                if (!$row['Model']) {
                                                    $row['Model'] = "-";
                                                }

                                            ?>
                                                <tr>
                                                    <div class="data-row">
                                                        <td class="bag-item">
                                                            <div class="item-col one">
                                                                <img loading="lazy" src="/uploads/<?php echo "{$row['product_image']}"; ?>" alt="product-image">
                                                            </div>
                                                            <div class="item-col two">
                                                                <p class="bag-item-name"><?php echo "{$row['ProductName']}"; ?></p>
                                                                <p class="bag-item-descr"><?php echo "{$row['Model']}"; ?></p>
                                                                <p class="bag-item-brand"><?php echo "{$row['brandName']}" ?></p>
                                                            </div>
                                                        </td>
                                                        <td class="bag-price" colspan="2">
                                                            <p class="price">R<?php echo $price; ?></p>
                                                            <p class="quantity">Qty: <?php echo "{$row['quantity']}"; ?> </p>
                                                        </td>
                                                    </div>
                                                </tr>
                                            <?php } ?>
                                        </table>
                                        <div class="total">
                                            <p>Total: <span id="totalAmount">R<?php echo $itemsTotal; ?></span></p>
                                        </div>
                                        <div class="order-actions">
                                            <?php if ($trackUrl) { ?>
                                                <a target="_blank" href="<?php echo $trackUrl ?>" class="actionBtn trackOrder-btn">
                                                    <i class="ph ph-map-pin-area"></i> Track Order
                                                </a>
                                            <?php } ?>
                                            <a target="_blank" href="/order/?customer=<?php echo $customerID ?>&id=<?php echo $checkoutID ?>" class="actionBtn viewOrder-btn">
                                                <i class="ph ph-chats-circle"></i> View Order
                                            </a>
                                            <button class="actionBtn messageSeller-btn"
                                                onclick="openChat(
                                                    '<?php echo htmlspecialchars($customerID, ENT_QUOTES, 'UTF-8'); ?>', 
                                                    '<?php echo htmlspecialchars($sellerId, ENT_QUOTES, 'UTF-8'); ?>', 
                                                    '<?php echo htmlspecialchars($orderNo, ENT_QUOTES, 'UTF-8'); ?>',
                                                    '<?php echo htmlspecialchars($sFirstName, ENT_QUOTES, 'UTF-8'); ?>',
                                                    '<?php echo htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8'); ?>'
                                                )">
                                                <i class="ph ph-chats-circle"></i> Message Seller
                                            </button>

                                        </div>

                                    <?php } else { ?>
                                        <div class="empty-bag-message">
                                            <img loading="lazy" src="/assets/images/emptyCart.png" alt="">
                                            <p>No products found for orderNo: <?php echo $orderNo; ?></p>
                                        </div>
                                    <?php } ?>
                                </div>
                        <?php
                            }
                        } ?>
                    </div>

                    <div class="chat-overlay" id="chat-overlay">
                        <div class="chat-container">
                            <div class="chat-header">
                                <div class="titles">
                                    <div class="chat-title">Chat with <span id="sellerName"></span></div>
                                    <div class="chat-sub-title" id="business-name"></div>
                                </div>
                                <div class="chat-close-btn" onclick="closeChat()">✕</div>
                            </div>
                            <div class="empty-chat" id="emptyChat">
                                <img loading="lazy" src="/assets/images/no-chats.png" alt="No messages">
                                <p>No messages yet. Start a chat with the seller about your order!</p>
                            </div>
                            <div class="chat-messages" id="chatMessages">

                            </div>
                            <div class="chat-footer">
                                <input type="text" id="chatInput" placeholder="Type a message...">
                                <button class="send-btn" id="sendBtn"><i class="ph ph-paper-plane-right"></i></button>
                            </div>
                            <div class="unread-dot" id="unreadDot">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php $INCLUDES_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/includes/";
    include($INCLUDES_IPATH . "footer.php"); ?>
    <script src="/js/orderchats.js"></script>
    <script src="/js/profile.js"></script>
</body>

</html>