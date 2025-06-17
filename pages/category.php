<?php
if (!$_POST['valid']) {
    header("Location: /");
    exit();
}

// Get raw category and subcategory names from GET parameters, then trim and urldecode
$rawCategory = isset($_GET['category']) ? trim(urldecode($_GET['category'])) : '';
$rawSubcat = isset($_GET['subcat']) ? trim(urldecode($_GET['subcat'])) : '';

// For display in HTML (escaped)
$displayCategory = htmlspecialchars($rawCategory, ENT_QUOTES, 'UTF-8');
$displaySubcat = htmlspecialchars($rawSubcat, ENT_QUOTES, 'UTF-8');

// Set up pagination parameters
$limit = 10; // Items per page
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$offset = ($page - 1) * $limit;

// Build URL base for pagination
$href = '/category/';
if (!empty($rawCategory)) {
    $href .= urlencode($rawCategory);
}
if (!empty($rawSubcat)) {
    $href .= "/" . urlencode($rawSubcat);
}

// Fetch suggested products
$suggested = [];
$suggestedBaseSQL = "SELECT p.*, COALESCE(ps.Model, '-') AS Model, s.*, ss.account_plan, ss.active FROM product p
    LEFT JOIN product_specs ps ON p.product_id = ps.product_id
    JOIN product_categories pc ON p.category_id = pc.category_id
    JOIN sss_business s ON p.seller_id = s.seller_id
    JOIN sellers ss ON p.seller_id = ss.seller_id
    LEFT JOIN products_subcategory sc ON p.subcategoryid = sc.SubcategoryID";

$suggestedConditions = ["pc.CategoryName = ?", "p.stock_qnty > 0", "p.suggested = 'yes'"];
$suggestedParams = [$rawCategory];
$suggestedTypes = "s";

if (!empty($rawSubcat)) {
    $suggestedConditions[] = "sc.subCatName = ?";
    $suggestedParams[] = $rawSubcat;
    $suggestedTypes .= "s";
}
$suggestedWhereClause = " WHERE " . implode(" AND ", $suggestedConditions);
$suggestedSQLQuery = $suggestedBaseSQL . $suggestedWhereClause . " LIMIT 10";

if ($stmt = $conn->prepare($suggestedSQLQuery)) {
    if (!empty($suggestedParams)) {
        $stmt->bind_param($suggestedTypes, ...$suggestedParams);
    }
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $suggested[] = $row;
        }
    } else {
        error_log("Suggested products query execution failed: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Suggested products query prepare failed: " . $conn->error);
}

// Prepare query conditions for main product list
$mainConditions = ["pc.CategoryName = ?", "p.stock_qnty > 0", "p.suggested != 'yes'"];
$mainParams = [$rawCategory];
$mainTypes = "s";

if (!empty($rawSubcat)) {
    $mainConditions[] = "sc.subCatName = ?";
    $mainParams[] = $rawSubcat;
    $mainTypes .= "s";
}

// Apply filter inputs to main query conditions
if (!empty($filterParams['ProductName'])) {
    $mainConditions[] = "p.ProductName LIKE ?";
    $mainParams[] = "%" . $filterParams['ProductName'] . "%";
    $mainTypes .= "s";
}
if (!empty($filterParams['condition'])) {
    $mainConditions[] = "p.condition LIKE ?";
    $mainParams[] = "%" . $filterParams['condition'] . "%";
    $mainTypes .= "s";
}
if (!empty($filterParams['b_type'])) {
    $mainConditions[] = "s.b_type LIKE ?";
    $mainParams[] = "%" . $filterParams['b_type'] . "%";
    $mainTypes .= "s";
}
if (!empty($filterParams['brandName'])) {
    $mainConditions[] = "p.brandName LIKE ?";
    $mainParams[] = "%" . $filterParams['brandName'] . "%";
    $mainTypes .= "s";
}
if (!empty($filterParams['color'])) {
    $mainConditions[] = "p.color LIKE ?";
    $mainParams[] = "%" . $filterParams['color'] . "%";
    $mainTypes .= "s";
}

// Price filter for main query
$mainConditions[] = "p.Price >= ?";
$mainParams[] = $filterParams['minPrice'] ?? 0;
$mainTypes .= "d";

$mainConditions[] = "p.Price <= ?";
$mainParams[] = $filterParams['maxPrice'] ?? PHP_INT_MAX;
$mainTypes .= "d";

$mainWhereClause = " WHERE " . implode(" AND ", $mainConditions);

// Fetch total count
$countSQL = "SELECT COUNT(*) AS total FROM product p
    JOIN product_categories pc ON p.category_id = pc.category_id
    LEFT JOIN products_subcategory sc ON p.subcategoryid = sc.SubcategoryID
    $mainWhereClause";

$total_products = 0;
if ($stmt = $conn->prepare($countSQL)) {
    if (!empty($mainParams)) {
        $stmt->bind_param($mainTypes, ...$mainParams);
    }
    if ($stmt->execute()) {
        $total_products = $stmt->get_result()->fetch_assoc()['total'];
    } else {
        error_log("Count query execution failed: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Count query prepare failed: " . $conn->error);
}

$total_pages = ceil($total_products / $limit);

// Fetch paginated products
$productSQL = "SELECT p.*, COALESCE(ps.Model, '-') AS Model, s.*, ss.account_plan, ss.active FROM product p
    LEFT JOIN product_specs ps ON p.product_id = ps.product_id
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    JOIN sss_business s ON p.seller_id = s.seller_id
    LEFT JOIN products_subcategory sc ON p.subcategoryid = sc.SubcategoryID
    JOIN sellers ss ON p.seller_id = ss.seller_id
    $mainWhereClause
    LIMIT ? OFFSET ?";

$data = [];
if ($stmt = $conn->prepare($productSQL)) {
    $currentParams = $mainParams; // Make a copy to add limit and offset
    $currentTypes = $mainTypes . "ii";
    $currentParams[] = $limit;
    $currentParams[] = $offset;

    $stmt->bind_param($currentTypes, ...$currentParams);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    } else {
        error_log("Product query execution failed: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Product query prepare failed: " . $conn->error);
}


//echo json_encode([$suggested], JSON_PRETTY_PRINT);
//echo json_encode([$data], JSON_PRETTY_PRINT);
/* exit; */

// --- Display Section Flag ---
if (empty(array_filter($filterParams)) && $page == 1) {
    $displaySection = true;
} else {
    $displaySection = false;
}
?>

<div class="s_col two section" id="category-section">

    <?php
    include($_SERVER["DOCUMENT_ROOT"] . "/assets/includes/filter.php");

    if ($displaySection == true) {
    ?>
        <section class="toggle-items-list">
            <div class="section-ad">
                <div class="s-ad-col">
                    <?php if ($rawCategory) { ?>
                        <img loading="lazy" src="/assets/images/display/<?php echo htmlspecialchars($rawCategory, ENT_QUOTES, 'UTF-8'); ?>.png" alt="<?php echo $displayCategory; ?> category image" class="section7-img">
                        <div class="ad-text">
                            <h1>
                                Shop Top <?php echo $displayCategory; ?>
                                <?php if (!empty($displaySubcat)) : ?>
                                    – Best Deals on <?php echo $displaySubcat; ?>
                                <?php endif; ?>
                            </h1>
                            <p>
                                Explore our latest collection of
                                <?php if (!empty($displaySubcat)) : ?>
                                    premium <?php echo $displaySubcat; ?> in the <?php echo $displayCategory; ?> category.
                                <?php else : ?>
                                    quality items in our <?php echo $displayCategory; ?> collection.
                                <?php endif; ?>
                                Get great prices, fast delivery, and trusted service today.
                            </p>
                        </div>

                    <?php } ?>
                </div>
            </div>
            <?php if (!empty($suggested)): ?>
                <!-- Suggested Products -->
                <div class="line-with-text">
                    <h2 class="text">Suggested Products</h2>
                    <div class="line"></div>
                </div>
                <!-- Includ Suggested List-->
                <?php include($IPATH_INCLUDES . "item-list.php"); ?>

            <?php endif; ?>
        </section>
    <?php
    }
    ?>

    <section class="filterPanel" id="filterPanel">
        <div class="line-with-text">
            <h2 class="text">Regular listing</h2>
        </div>
        <div class="filter-wrapper" onclick="toggleFilter()">
            <i class="ph ph-sliders-horizontal"></i>
            <p>Filter by</p>
        </div>
    </section>

    <!-- Includ Regular List-->
    <?php include($IPATH_INCLUDES . "regular-list.php"); ?>

</div>

<!-- //? INCLUDE OTHER CATEGORY LISTED ITEMS -->
<?php include($IPATH_INCLUDES . "category-listed.php"); ?>

<?php include($IPATH_INCLUDES . "footer.php"); ?>

<script src="/js/filter.js"></script>

</body>

</html>