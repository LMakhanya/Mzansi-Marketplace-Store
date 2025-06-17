<?php
if (!$_POST['valid']) {
    header("Location: /");
    exit();
}

// Pagination setup
$limit = 10;
$page = max(1, (int)($viewdPage ?? 1));
$offset = ($page - 1) * $limit;

// Build store URL
$href = sprintf("/storeprofile/%s/%s/", urlencode($viewedBusiness), urlencode($viewedSeller));
$href .= !empty($viewedCategory) ? urlencode($viewedCategory) : '';
$href .= !empty($viewedSubcat) ? '/' . urlencode($viewedSubcat) : '';

// Build SQL filters
$conditions = ["p.seller_id = ?", "p.stock_qnty > 0", "p.suggested != 'yes'"];
$params = [$viewedSeller];
$types = "s";

// Category filters
if (!empty($viewedCategory)) {
    $conditions[] = "pc.CategoryName = ?";
    $params[] = $viewedCategory;
    $types .= "s";
}
if (!empty($viewedSubcat)) {
    $conditions[] = "sc.subCatName = ?";
    $params[] = $viewedSubcat;
    $types .= "s";
}

// Filter inputs
if (!empty($filterParams['ProductName'])) {
    $conditions[] = "p.ProductName LIKE ?";
    $params[] = "%" . $filterParams['ProductName'] . "%";
    $types .= "s";
}
if (!empty($filterParams['condition'])) {
    $conditions[] = "p.condition LIKE ?";
    $params[] = "%" . $filterParams['condition'] . "%";
    $types .= "s";
}
if (!empty($filterParams['b_type'])) {
    $conditions[] = "s.b_type LIKE ?";
    $params[] = "%" . $filterParams['b_type'] . "%";
    $types .= "s";
}
if (!empty($filterParams['brandName'])) {
    $conditions[] = "p.brandName LIKE ?";
    $params[] = "%" . $filterParams['brandName'] . "%";
    $types .= "s";
}
if (!empty($filterParams['color'])) {
    $conditions[] = "p.color LIKE ?";
    $params[] = "%" . $filterParams['color'] . "%";
    $types .= "s";
}

// Price filter
$conditions[] = "p.Price >= ?";
$params[] = $filterParams['minPrice'] ?? 0;
$types .= "d";

$conditions[] = "p.Price <= ?";
$params[] = $filterParams['maxPrice'] ?? PHP_INT_MAX;
$types .= "d";

$whereClause = " WHERE " . implode(" AND ", $conditions);

// --- Count Products ---
$countSQL = "SELECT COUNT(DISTINCT p.product_id) AS total FROM product p
    INNER JOIN product_categories pc ON p.category_id = pc.category_id
    LEFT JOIN products_subcategory sc ON p.subcategoryid = sc.SubcategoryID
    INNER JOIN sss_business s ON p.seller_id = s.seller_id
    $whereClause";

$total_products = 0;
try {
    $stmt = $conn->prepare($countSQL);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_products = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} catch (Exception $e) {
    error_log("Count query failed: " . $e->getMessage());
}

$total_pages = max(1, ceil($total_products / $limit));

// --- Fetch Products ---
$productSQL = "SELECT p.*, ps.Model , s.*, ss.account_plan, ss.active FROM product p
    LEFT JOIN product_specs ps ON p.product_id = ps.product_id
    INNER JOIN product_categories pc ON p.category_id = pc.category_id
    INNER JOIN sss_business s ON p.seller_id = s.seller_id
    LEFT JOIN products_subcategory sc ON p.subcategoryid = sc.SubcategoryID
    JOIN sellers ss ON p.seller_id = ss.seller_id
    $whereClause LIMIT ? OFFSET ?";

$data = [];
try {
    $stmt = $conn->prepare($productSQL);
    $stmt->bind_param($types . "ii", ...array_merge($params, [$limit, $offset]));
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!$row['Model']) $row['Model'] = "-";
        $data[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Product query failed: " . $e->getMessage());
}

// --- Suggested Products ---
$suggested = [];
$business = null;
$seller = null;

$sql = "
    SELECT p.*, ps.Model, s.*, ss.account_plan, ss.active 
    FROM product p
    LEFT JOIN product_specs ps ON p.product_id = ps.product_id
    INNER JOIN product_categories pc ON p.category_id = pc.category_id
    INNER JOIN sss_business s ON p.seller_id = s.seller_id
    LEFT JOIN products_subcategory sc ON p.subcategoryid = sc.SubcategoryID
    JOIN sellers ss ON p.seller_id = ss.seller_id
    WHERE p.seller_id = ? 
    AND p.stock_qnty > 0 
    AND p.suggested = 'yes'
    LIMIT 10
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $viewedSeller);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Set business info once
        $business ??= [
            'b_id' => $row['b_id'],
            'b_name' => $row['b_name'],
            'description' => $row['description'],
            'slogan' => $row['slogan'],
            'b_owner_name' => $row['b_owner_name'],
            'b_type' => $row['b_type'],
            'b_regno' => $row['b_regno'],
            'b_taxno' => $row['b_taxno'],
            'logo' => $row['logo'],
            'created_at' => $row['created_at']
        ];

        // Set seller info once
        $seller ??= [
            'seller_id' => $row['seller_id'],
            'account_plan' => $row['account_plan'],
            'active' => $row['active']
        ];

        // Prepare product data
        $product = array_diff_key(
            $row,
            array_flip([
                'b_id',
                'slogan',
                'b_owner_name',
                'b_type',
                'b_regno',
                'b_taxno',
                'logo',
                'created_at'
            ])
        );
        $product['Model'] = $product['Model'] ?: '-';

        $suggested[] = $product;
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Suggested products query failed: " . $e->getMessage());
}

// If no suggested products, fetch seller info separately
if ($seller === null) {
    $sql = "SELECT account_plan, active FROM sellers WHERE seller_id = ?";
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $viewedSeller);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $seller = [
                'seller_id' => $viewedSeller,
                'account_plan' => $row['account_plan'],
                'active' => $row['active']
            ];
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Seller info query failed: " . $e->getMessage());
        header("Location: /");
        exit();
    }
}

// Update response with seller data
$response = [
    "business" => $business,
    "seller" => $seller,
    "products" => $suggested
];

// Perform account plan and active status checks
if ($response['seller']['account_plan'] === 'Basic Plan' || $response['seller']['account_plan'] === 'Free Plan') {
    header("Location: /");
    exit();
}

if ($response['seller']['account_plan'] === 'Pro Plan' && $response['seller']['active'] != 1) {
    header("Location: /");
    exit();
}

// --- Display Section Flag ---
if (empty(array_filter($filterParams)) && $page == 1) {
    $displaySection = true;
} else {
    $displaySection = false;
}

// echo json_encode($suggested, JSON_PRETTY_PRINT);

if ($displaySection): ?>
    <div class="seller-header-container">
        <h1>
            <?php echo htmlspecialchars_decode($viewedBusiness, ENT_QUOTES); ?>
        </h1>
        <!--  <h2 class="slogan"><?= $response['business']['slogan'] ?></h2> -->
        <p class="tagline">Explore our latest elite products. Get great prices, fast delivery, and trusted quality today.</p>
    </div>
<?php endif; ?>

<!-- Tabs Section -->
<div class="tabs-container">
    <!--  <div class="tab-buttons">
        <button class="tab-btn active" data-tab="listings">Seller Listings</button>
        <button class="tab-btn" data-tab="ratings">Ratings</button>
        <button class="tab-btn" data-tab="recently-sold">Recently Sold</button>
    </div> -->
    <div class="tab-content bg-black">
        <!-- Seller Listings -->
        <div id="listings" class="tab-pane active">
            <?php if (!empty($suggested)): ?>
                <?php if ($displaySection): ?>
                    <section class="toggle-items-list">
                        <div class="line-with-text">
                            <h2 class="text">Suggested Products</h2>
                            <div class="line"></div>
                        </div>

                        <!-- Includ Suggested List-->
                        <?php include($IPATH_INCLUDES . "/item-list.php"); ?>

                    </section>
                <?php endif; ?>
            <?php endif; ?>

            <section class="filterPanel" id="filterPanel">
                <div class="line-with-text">
                    <div class="text">Regular listing</div>
                </div>
                <div class="filter-wrapper" onclick="toggleFilter()">
                    <i class="ph ph-sliders-horizontal"></i>
                    <p>Filter by</p>
                </div>
            </section>

            <!-- Includ Regular List-->
            <?php include($IPATH_INCLUDES . "/regular-list.php"); ?>
        </div>

        <!-- Ratings -->
        <div id="ratings" class="tab-pane">
            <p><strong>Average Rating:</strong> 4.7/5 (based on 342 reviews)</p>
            <ul>
                <li>John D. - "Great quality products!" - ★★★★★
                    <span class="date">March 5, 2025</span>
                </li>
                <li>Sarah M. - "Fast shipping, decent service." - ★★★★☆
                    <span class="date">March 8, 2025</span>
                </li>
                <li>Mike P. - "Lamp broke after a week." - ★★★☆☆
                    <span class="date">March 10, 2025</span>
                </li>
            </ul>
        </div>

        <!-- Recently Sold -->
        <div id="recently-sold" class="tab-pane">
            <ul>
                <li><strong>Wireless Earbuds</strong> - Sold on Mar 10, 2025 - $79.99</li>
                <li><strong>Stainless Steel Kettle</strong> - Sold on Mar 8, 2025 - $34.99</li>
                <li><strong>Leather Notebook</strong> - Sold on Mar 5, 2025 - $19.99</li>
            </ul>
        </div>
    </div>
</div>
<script>

</script>
<?php
// Ensure $IPATH_INCLUDES is defined before including
if (isset($IPATH_INCLUDES) && file_exists($IPATH_INCLUDES . "footer.php")) {
    include($IPATH_INCLUDES . "footer.php");
}
?>
<script>
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and panes
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));

            // Add active class to clicked button and corresponding pane
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
</script>

<script src="/js/filter.js"></script>
</body>

</html>

