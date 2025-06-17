<?php
// Get search value from GET parameters
$rwawSearchedValue = isset($_GET['search']) ? trim(urldecode($_GET['search'])) : '';
$searchedValue = isset($_GET['search']) ? htmlspecialchars(urldecode($_GET['search']), ENT_QUOTES, 'UTF-8') : '';

// Set up pagination parameters
$limit = 10; // Items per page
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$offset = ($page - 1) * $limit;

// Build URL base
$href = '/search/';
if (!empty($rwawSearchedValue)) {
    $href .= urlencode($rwawSearchedValue);
}

// echo 'Searched Value: ' . $rwawSearchedValue;

// Prepare query conditions for search
$conditions = [];
$params = [];
$types = '';

if (!empty($rwawSearchedValue)) {
    $conditions[] = "(p.ProductName LIKE ? OR p.brandName LIKE ? OR s.b_name LIKE ?)";
    $searchPattern = '%' . $rwawSearchedValue . '%';
    $params = [$searchPattern, $searchPattern, $searchPattern];
    $types = 'sss';
}

// Always include stock quantity condition
$conditions[] = "p.stock_qnty > 0";

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

// Fetch total count
$countSQL = "SELECT COUNT(*) AS total FROM product p
    JOIN product_categories pc ON p.category_id = pc.category_id 
    JOIN sss_business s ON p.seller_id = s.seller_id
    LEFT JOIN products_subcategory sc ON p.subcategoryid = sc.SubcategoryID
    $whereClause";

$total_products = 0;
if ($stmt = $conn->prepare($countSQL)) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_products = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

$total_pages = ceil($total_products / $limit);

// Fetch paginated products
$productSQL = "SELECT p.*, ps.Model , s.*, ss.account_plan, ss.active FROM product p
    LEFT JOIN product_specs ps ON p.product_id = ps.product_id
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    JOIN sss_business s ON p.seller_id = s.seller_id
    JOIN sellers ss ON p.seller_id = ss.seller_id
    LEFT JOIN products_subcategory sc ON p.subcategoryid = sc.SubcategoryID
    $whereClause
    LIMIT ? OFFSET ?";

$data = [];
if ($stmt = $conn->prepare($productSQL)) {
    $stmt->bind_param($types . "ii", ...array_merge($params, [$limit, $offset]));
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!$row['Model']) {
            $row['Model'] = "-";
        }
        $data[] = $row;
    }
    $stmt->close();
}
?>
<div class="s_col two section" id="category-section">

    <section class="filterPanel" id="filterPanel">
        <div class="line-with-text">
            <div class="text" style="color: var(--text-color);">Search Results: <?php echo $searchedValue; ?></div>
        </div>
        <div class="filter-wrapper" onclick="toggleFilter()">
            <i class="ph ph-sliders-horizontal"></i>
            <p>Filter by</p>
        </div>
    </section>

    <!-- Includ Regular List-->
    <?php include($IPATH_INCLUDES . "/regular-list.php"); ?>
</div>

<?php include($IPATH_INCLUDES . "footer.php"); ?>

<script src="/js/filter.js"></script>
<script>
    document.getElementById('breadcrumb').style.display = 'none';
</script>
</body>

</html>