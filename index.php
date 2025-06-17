<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start(); // Start output buffering
session_start();

// If no match, show the home page// Get the request URI (e.g., /category/Electronics/15ffvlrpl)
$request = $_SERVER['REQUEST_URI'];

$parsedUrl = parse_url($request);
$path = ltrim($parsedUrl['path'] ?? '', '/'); // Get the path part without leading slash
$query = $parsedUrl['query'] ?? ''; // Get the query string (e.g., "color=red")

// Parse query string into $_GET if it exists
if ($query) {
    parse_str($query, $queryParams); // Converts "color=red" into array ['color' => 'red']
    $_GET = array_merge($_GET, $queryParams); // Merge with existing $_GET
}

// Match the path for routing
if (preg_match('#^category/([^/]+)#', $path, $matches)) {
    $_GET['category'] = $matches[1]; // "Electronics"
}

if (preg_match('#^category/([^/]+)/([^/]+)$#', $path, $matches)) {
    $_GET['category'] = $matches[1]; // "Electronics"
    $_GET['subcat'] = $matches[2]; // "15ffvlrpl"
}

if (preg_match('#^storeprofile/([^/]+)/([^/]+)$#', $path, $matches)) {
    $_GET['b_name'] = urldecode($matches[1]); // "Mzansi Mass"
    $_GET['seller_id'] = urldecode($matches[2]); // "SELLER_72114"
}

if (preg_match('#^item/([^/]+)#', $path, $matches)) {
    $_GET['product_id'] = $matches[1];
}
if (preg_match('#^search/([^/]+)#', $path, $matches)) {
    $_GET['search'] = $matches[1];
}

// Filter parameters with sanitization
$filterParams = [
    'ProductName' => htmlspecialchars($_GET['ProductName'] ?? '', ENT_QUOTES, 'UTF-8'),
    'condition' => htmlspecialchars($_GET['condition'] ?? '', ENT_QUOTES, 'UTF-8'),
    'b_type' => htmlspecialchars($_GET['seller'] ?? '', ENT_QUOTES, 'UTF-8'),
    'brandName' => htmlspecialchars($_GET['brand'] ?? '', ENT_QUOTES, 'UTF-8'),
    'color' => htmlspecialchars($_GET['color'] ?? '', ENT_QUOTES, 'UTF-8'),
    'minPrice' => filter_var($_GET['minPrice'] ?? null, FILTER_VALIDATE_FLOAT) ?: null,
    'maxPrice' => filter_var($_GET['maxPrice'] ?? null, FILTER_VALIDATE_FLOAT) ?: null,
];

// Database connection setting
$IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/php/";
$ENV_IPATH = $_SERVER["DOCUMENT_ROOT"] . "/env/";
// include($ENV_IPATH . "conn.php");
include($ENV_IPATH . "env.php");

include './env/conn.php';


// Initialize the database connection
$pdo = initializeDatabase();
include($IPATH . "userData.php");

// Update the last activity time to the current time
$_SESSION['last_activity'] = time();

$rawCategory = isset($_GET['category']) ? trim(urldecode($_GET['category'])) : '';
$rawSubcat = isset($_GET['subcat']) ? trim(urldecode($_GET['subcat'])) : '';

// Get category and subcategory from GET parameters
$viewedCategory = isset($_GET['category']) ? htmlspecialchars(urldecode($_GET['category']), ENT_QUOTES, 'UTF-8') : '';
$viewedSubcat = isset($_GET['subcat']) ? htmlspecialchars(urldecode($_GET['subcat']), ENT_QUOTES, 'UTF-8') : '';

// Get category and subcategory from GET parameters
$viewedBusiness = isset($_GET['b_name']) ? $_GET['b_name'] : '';
$viewedSeller = isset($_GET['seller_id']) ? htmlspecialchars(urldecode($_GET['seller_id']), ENT_QUOTES, 'UTF-8') : '';

// get page no int
$viewdPage = isset($_GET['page']) ? $_GET['page'] : 1;


$cart_opt = isset($_GET['cart_opt']) ? $_GET['cart_opt'] : (isset($_SESSION["cart_opt"]) ? $_SESSION["cart_opt"] : null);
$brand_opt = isset($_GET['brand_opt']) ? $_GET['brand_opt'] : (isset($_SESSION["brand_opt"]) ? $_SESSION["brand_opt"] : null);
$type_opt = isset($_GET['type_opt']) ? $_GET['type_opt'] : (isset($_SESSION["type_opt"]) ? $_SESSION["type_opt"] : null);
$size_opt = isset($_GET['size_opt']) ? $_GET['size_opt'] : (isset($_SESSION["size_opt"]) ? $_SESSION["size_opt"] : null);


$selectedBrand = isset($_GET['brandName']) ? $_GET['brandName'] : (isset($_SESSION["brandName"]) ? $_SESSION["brandName"] : null);
$selectedType = isset($_GET['type']) ? $_GET['type'] : (isset($_SESSION["type"]) ? $_SESSION["type"] : null);
$category = isset($_GET['category']) ? $_GET['category'] : (isset($_SESSION["category"]) ? $_SESSION["category"] : null);
$selectedSize = isset($_GET['size']) ? $_GET['size'] : (isset($_SESSION["size"]) ? $_SESSION["size"] : null);


if (!empty($cart_opt)) {
    $category = $cart_opt;
}
if (!empty($type_opt)) {
    $selectedType = $type_opt;
}
if (!empty($brand_opt)) {
    $selectedBrand = $brand_opt;
}
if (!empty($size_opt)) {
    $selectedSize = $size_opt;
}

$_SESSION["category"] = $category;
if ($category == null) {
    $category = "instore";
}

$_SESSION["brandName"] = $selectedBrand;
$_SESSION["type"] = $selectedType;
$_SESSION["size"] = $selectedSize;

function getTotalQuantityAndAmount($customerID)
{
    global $pdo;
    try {
        $getTotalQuantitySQL = "
            SELECT bagID,
                bagID,
                SUM(Quantity) AS totalQuantity, 
                SUM(totalAmount) AS totalAmount  
            FROM bagtbl 
            WHERE customerID = :customerID AND purchased = 0";

        $getTotalQuantitySTMT = $pdo->prepare($getTotalQuantitySQL);
        $getTotalQuantitySTMT->bindParam(':customerID', $customerID, PDO::PARAM_STR);
        $getTotalQuantitySTMT->execute();

        $result = $getTotalQuantitySTMT->fetch(PDO::FETCH_ASSOC);
        $totalQuantity = ($result['totalQuantity'] !== null) ? (int)$result['totalQuantity'] : 0;
        $bagTotalAmount = ($result['totalAmount'] !== null) ? (float)$result['totalAmount'] : 0;
        $bagID = ($result['bagID'] !== null) ? $result['bagID'] : '';

        $_SESSION["totalAmount"] = $bagTotalAmount;
        $_SESSION["totalQuantity"] = $totalQuantity;

        return ['bagID' => $bagID, 'totalQuantity' => $totalQuantity, 'totalAmount' => $bagTotalAmount];
    } catch (PDOException $e) {
        log_to_file("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database query failed']);
        exit;
    }
}

$result = getTotalQuantityAndAmount($customerID);

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

// Initialize data arrays
$suggested = $data = $brands = $colors = [];

$conditions = [];

// Escape function for SQL input
function escape($conn, $str)
{
    return mysqli_real_escape_string($conn, $str);
}

// Handle category filter
if (!empty($viewedCategory)) {
    $viewedCategoryEsc = escape($conn, $viewedCategory);
    $conditions[] = "pc.CategoryName = '$viewedCategoryEsc'";
}

// Handle seller filter
if (!empty($viewedSeller)) {
    $viewedSellerEsc = escape($conn, $viewedSeller);
    $conditions[] = "p.seller_id = '$viewedSellerEsc'";
}

// Get search value from GET parameters
$searchedValue = isset($_GET['search']) ? htmlspecialchars(urldecode($_GET['search']), ENT_QUOTES, 'UTF-8') : '';

// If search value is present, add to SQL conditions
if (!empty($searchedValue)) {
    $searchedValueEsc = '%' . escape($conn, $searchedValue) . '%';
    $conditions[] = "(p.ProductName LIKE '$searchedValueEsc' OR p.brandName LIKE '$searchedValueEsc' OR s.b_name LIKE '$searchedValueEsc')";
}

// Generate WHERE clause
$whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";

// Construct SQL to get distinct colors and brands
$colorsSQL = "
    SELECT DISTINCT p.color, p.brandName
    FROM product p
    JOIN product_categories pc ON p.category_id = pc.category_id
    JOIN sss_business s ON p.seller_id = s.seller_id
    $whereClause
";

// Execute query
$colorsResult = mysqli_query($conn, $colorsSQL);

// Initialize arrays for storing unique values
$colors = [];
$brands = [];

if ($colorsResult) {
    while ($row = mysqli_fetch_assoc($colorsResult)) {
        if (!in_array($row["color"], $colors)) {
            $colors[] = $row["color"];
        }
        if (!in_array($row["brandName"], $brands)) {
            $brands[] = $row["brandName"];
        }
    }
} else {
    // Handle SQL error (optional)
    echo "SQL Error: " . mysqli_error($conn);
}

if (!empty($_GET['product_id'])) {
    $productId = htmlspecialchars(urldecode($_GET['product_id']), ENT_QUOTES, 'UTF-8');
    $stmt = $pdo->prepare("SELECT ProductName FROM product WHERE product_id = :product_id");
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_STR);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $filterParams['ProductName'] = $product['ProductName'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $IPATH_INCLUDES = $_SERVER["DOCUMENT_ROOT"] . "/assets/includes/";
    include($IPATH_INCLUDES . "metadata.php");
    ?>
    <meta name="ahrefs-site-verification" content="9ad1f45713e6c59ff18e1d7ff323ca985f80eeb904f6d2517b8d87adf802b38b">
    <script src="https://analytics.ahrefs.com/analytics.js" data-key="l32x/XUqkQYIioGkTcAMyw" async></script>
</head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-RR0LXZ981W"></script>
<script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }
    gtag('js', new Date());

    gtag('config', 'G-RR0LXZ981W');
</script>

<body>
    <?php
    $IPATH3 = $_SERVER["DOCUMENT_ROOT"] . "/containers/";
    include($IPATH_INCLUDES . "headernav.php");

    $suggested = [];
    $randomProducts = [];
    $params = [];
    $types = "";

    $getProductsSQL = "SELECT p.*, ps.Model , s.*, ss.account_plan, ss.active FROM product p
        LEFT JOIN product_specs ps ON p.product_id = ps.product_id
        JOIN product_categories pc ON p.category_id = pc.category_id
        JOIN sss_business s ON p.seller_id = s.seller_id
        JOIN sellers ss ON p.seller_id = ss.seller_id";

    $conditions = [];
    $conditions = ["p.stock_qnty > 0", "ss.is_verified = 'yes'"];

    // Add conditions dynamically
    if (!empty($filterCategoryName)) {
        $conditions[] = "pc.CategoryName = ?";
        $params[] = $filterCategoryName;
        $types .= "s";
    }

    if (!empty($selectedType)) {
        $getProductsSQL .= " JOIN products_subcategory sc ON p.subcategoryid = sc.SubcategoryID";
        $conditions[] = "sc.subCatName = ?";
        $params[] = $selectedType;
        $types .= "s";
    }

    // Add WHERE clause if there are conditions
    if (!empty($conditions)) {
        $getProductsSQL .= " WHERE " . implode(" AND ", $conditions);
    }

    // Prepare the statement
    $getProductSTMT = $conn->prepare($getProductsSQL);
    if (!$getProductSTMT) {
        echo json_encode(['success' => false, 'error' => $conn->error], JSON_PRETTY_PRINT);
        exit;
    }

    // Bind parameters dynamically if needed
    if (!empty($params)) {
        $getProductSTMT->bind_param($types, ...$params);
    }

    // Execute the statement
    $getProductSTMT->execute();
    $getProductResults = $getProductSTMT->get_result();

    $allProducts = [];

    // Fetch the results
    while ($getProductsRow = $getProductResults->fetch_assoc()) {
        if (!$getProductsRow['Model']) {
            $getProductsRow['Model'] = "-";
        }

        if ($getProductsRow['suggested'] === 'yes') {
            $suggested[] = $getProductsRow;
        }

        $allProducts[] = $getProductsRow;
    }

    // Randomize only once per page load
    $randomProducts = [];
    if (!empty($allProducts)) {
        shuffle($allProducts); // Randomize the array
        $randomProducts = array_slice($allProducts, 0, 20); // Get first 20
    }
    $data = [
        'suggested' => $suggested,
        'randomProducts' => $randomProducts,
        'success' => true
    ];

    // echo json_encode($data, JSON_PRETTY_PRINT);

    try {
        $stmt = $pdo->query("SELECT category_id, COUNT(*) AS product_count FROM product GROUP BY category_id");

        $categoryCounts = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categoryCounts[$row['category_id']] = $row['product_count'];
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
    ?>

    <div class="max-content" id="store">
        <div class="ribbon">
            Hey YOU! Ndiphas Gagdets are now available! <a href="/storeprofile/Ndipha's Gadgets/SELLER_73245">Click here to SHOP NOW!</a>
        </div>
        <section class="slider-container" id="main-ad">
            <div class="slider">
                <div class="slide" style="background-image: url('/assets/images/ads/eetrical-gadgets.png')">
                    <div class="slide-content">
                        <h1>Exclusive Deals on Electronics</h1>
                        <p>Find the latest gadgets and accessories at unbeatable prices.</p>
                        <a href="/category/Eletronics" class="btn-shop-now">Shop Eletronics <ion-icon name="chevron-forward-outline"></ion-icon></a>
                    </div>
                </div>
                <div class="slide" style="background-image: url('/assets/images/display/Shoes.png')">
                    <div class="slide-content">
                        <h1>Step Into Style with the Latest Shoes</h1>
                        <p>Explore top-quality footwear for every occasion – from casual kicks to formal favorites.</p>
                        <a href="/category/Shoes" class="btn-shop-now">Shop Shoes Now <ion-icon name="chevron-forward-outline"></ion-icon></a>
                    </div>
                </div>
                <div class="slide" style="background-image: url('/assets/images/ads/crafts.png')">
                    <div class="slide-content">
                        <h1>Handmade Crafts & Unique Gifts</h1>
                        <p>Support local artisans and find one-of-a-kind treasures.</p>
                        <a href="/category/Accessories" class="btn-shop-now">Explore Now <ion-icon name="chevron-forward-outline"></ion-icon></a>
                    </div>
                </div>
            </div>
            <div class="dots-container">
                <span class="dot active" onclick="changeSlide(0)"></span>
                <span class="dot" onclick="changeSlide(1)"></span>
                <span class="dot" onclick="changeSlide(3)"></span>
            </div>
        </section>


        <!-- //?PRIORITY LISTED SECTION  -->
        <?php
        include($IPATH_INCLUDES . "priority-listed.php");
        ?>




        <section style="margin-bottom: 0px;">
            <div class="line-with-text">
                <!-- <div class="line"></div> -->
                <h2 class="text">Top Selling Collections</h2>
                <div class="line"></div>
            </div>
            <!-- Featured Categories -->
            <div class="featured-categories on-home">
                <div class="category-card">
                    <a href="/category/Phones">
                        <div class="category-image">
                            <img loading="lazy" src="../assets/images/display/Phones.png" alt="Phones category">
                        </div>
                        <div class="category-content">
                            <h3 class="category-title">Phones</h3>
                            <div class="c-c-combo">
                                <p class="category-count"><?php echo $categoryCounts[6] ?? 0 ?> items</p>
                                <i class="ph ph-caret-right"></i>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="category-card">
                    <a href="/category/Electronics">
                        <div class="category-image">
                            <img loading="lazy" src="/assets/images/display/Laptops.png" alt="Electronics category">
                        </div>
                        <div class="category-content">
                            <h3 class="category-title">Laptops</h3>
                            <div class="c-c-combo">
                                <p class="category-count"><?php echo $categoryCounts[7] ?? 0 ?> items</p>
                                <i class="ph ph-caret-right"></i>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="category-card">
                    <a href="/category/Hair & Beauty">
                        <div class="category-image">
                            <img loading="lazy" src="/assets/images/display/Hair & Beauty.png" alt="Hair & Beauty category">
                        </div>
                        <div class="category-content">
                            <h3 class="category-title">Hair & Beauty</h3>
                            <div class="c-c-combo">
                                <p class="category-count"><?php echo $categoryCounts[11] ?? 0 ?> items</p>
                                <i class="ph ph-caret-right"></i>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="category-card">
                    <a href="/category/Fragrances">
                        <div class="category-image">
                            <img loading="lazy" src="/assets/images/display/Fragrances.png" alt="Fregrances category">
                        </div>
                        <div class="category-content">
                            <h3 class="category-title">Fragrances</h3>
                            <div class="c-c-combo">
                                <p class="category-count"><?php echo $categoryCounts[10] ?? 0 ?> items</p>
                                <i class="ph ph-caret-right"></i>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="category-card">
                    <a href="/category/Health Products">
                        <div class="category-image">
                            <img loading="lazy" src="/assets/images/display/Health Products.png" alt="Health category">
                        </div>
                        <div class="category-content">
                            <h3 class="category-title">Health Products</h3>
                            <div class="c-c-combo">
                                <p class="ctegory-count"><?php echo $categoryCounts[8] ?? 0 ?> items</p>
                                <i class="ph ph-caret-right"></i>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="category-card">
                    <a href="/category/Accessories">
                        <div class="category-image">
                            <img loading="lazy" src="/assets/images/display/Accessories.png" alt="Accessories category">
                        </div>
                        <div class="category-content">
                            <h3 class="category-title">Accessories</h3>
                            <div class="c-c-combo">
                                <p class="category-count"><?php echo $categoryCounts[1] ?? 0 ?> items</p>
                                <i class="ph ph-caret-right"></i>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="category-card">
                    <a href="/category/Clothes">
                        <div class="category-image">
                            <img loading="lazy" src="/assets/images/display/Clothes.png" alt="Fashion category">
                        </div>
                        <div class="category-content">
                            <h3 class="category-title">Fashion</h3>
                            <div class="c-c-combo">
                                <p class="category-count"><?php echo $categoryCounts[5] ?? 0 ?> items</p>
                                <i class="ph ph-caret-right"></i>
                            </div>
                        </div>
                    </a>
                </div>

            </div>
            <a href="/categories" class="view-all-btn btn">View All Categories<i class="ph ph-caret-double-right"></i></a>
        </section>

        <!-- //? WEEKEND SPECIAL SECTION  -->
        <?php
        include($IPATH_INCLUDES . "weekend-specials.php");
        ?>


        <section>
            <div class="line-with-text">
                <!-- <div class="line"></div> -->
                <h2 class="text">Suggested Products</h2>
                <div class="line"></div>
            </div>
            <!-- Includ Suggested List-->
            <?php
            if (empty($suggested)) {
                $suggested = $randomProducts;
            }
            include($IPATH_INCLUDES . "item-list.php"); ?>
        </section>

        <!-- //? HOMEPAGE FEATURED SECTION  -->
        <?php
        include($IPATH_INCLUDES . "homepage-listed.php");
        ?>


        <?php
        include($IPATH_INCLUDES . "footer.php"); ?>

        <!-- Link your JS file -->
        <script src="/js/js.js"></script>

        <script>
            document.querySelectorAll('.seller-link').forEach(link => {
                const infoBoard = link.querySelector('.seller-info-board');

                // Toggle on click for mobile
                link.addEventListener('click', (e) => {
                    if (window.innerWidth <= 600) {
                        e.preventDefault();
                        infoBoard.classList.toggle('active');
                    }
                });

                // Ensure board closes when clicking outside
                document.addEventListener('click', (e) => {
                    if (!link.contains(e.target)) {
                        infoBoard.classList.remove('active');
                    }
                });
            });
        </script>
</body>

</html>