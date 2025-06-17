<?php


// echo "Viewed Category: " . $viewedCategory;
// echo json_encode(array_values([$colors, $brands]), JSON_PRETTY_PRINT);
?>
<div class="shop-header">
    <nav>
        <div class="upper_nav">
            <div>
                <label for="check" class="checkbtn">
                    <i class="ph ph-list"></i>
                </label>
                <div class="logo-wrapper">
                    <div class="logo">
                        <img src="/assets/images/themzansim_logo_nobg.png" onclick="continueShopping()" alt="logo">
                    </div>
                    <?php if ((isset($viewedSeller) && $viewedSeller != '') || (isset($viewedBusiness)  && $viewedBusiness != '')) : ?>
                        <!--  <img class="store-logo" src="/uploads/b_logo/ss-logo.png" onclick="continueShopping()" alt="logo">
                    --> <?php endif ?>
                </div>
            </div>

            <div class="search-bar">
                <i class="ph ph-magnifying-glass f-s-icon" onclick="toggleSearchBar()"></i>
                <div class="s-input-overlay" id="searchBar">
                    <div class="s-input" onclick="event.stopPropagation()">
                        <input type="text" id="searchInput" value="" placeholder="Search item, brand & Categories" />
                        <div>
                            <i class="ph ph-magnifying-glass" onclick="searchProduct()"></i>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="unlist">
                <?php
                // Debug: Uncomment this line if you want to see the session content
                // var_dump($_SESSION);
                // Check if the user is authenticated
                if (isset($_SESSION["loggedin"]) && !empty($_SESSION["loggedin"])) {
                    // User is logged in
                ?>
                    <div class="user-info" onclick="toggleProfileOverlay()">
                        <i class="ph ph-user"></i>
                        <div class="user-name">
                            <span><?php echo htmlspecialchars($_SESSION['FirstName']); // Use htmlspecialchars to avoid XSS 
                                    ?></span>
                            <i class="fa-solid fa-caret-down"></i>
                            <div class="profile-options" id="profile-overlay">
                                <a href="/user/">
                                    <div class="no-item">
                                        <i class="ph ph-gear-six"></i>
                                        <span>My Account</span>
                                    </div>
                                </a>
                                <a href="/reach-us.html">
                                    <div class="no-item">
                                        <i class="ph ph-chats-circle"></i>
                                        <span>Contact Us</span>
                                    </div>
                                </a>
                                <a href="/portal/help.html">
                                    <div class="no-item">
                                        <i class="ph ph-info"></i>
                                        <span>Help</span>
                                    </div>
                                </a>
                                <div class="item" data-target="underConstruction">
                                    <i class="ph ph-trash"></i>
                                    <span>Delete Account</span>
                                </div>
                                <div class="no-item with-border" onclick="logout()">
                                    <i class="ph ph-sign-out"></i>
                                    <span>Logout</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                } else {
                    // User is not logged in
                ?>
                    <li href="#" onclick="redirectToProfile('<?php echo isset($_SESSION['userEmail']) ? htmlspecialchars($_SESSION['userEmail']) : ''; ?>')">
                        <div class="user-info">
                            <i class="ph ph-user"></i>
                            <p class="s-r-text">Sign In / Register</p>
                        </div>
                    </li>
                <?php
                }
                ?>

                <li class="bag">
                    <a href="/cart"><i class="ph ph-shopping-cart-simple"></i>
                        <div class="c_number">
                            <input type="hidden" name="bagQ" id="bagQ" value="<?php echo $_SESSION["totalQuantity"] ?>">
                            <p class="bagQuantity" id="bagQuantity"></p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <?php if ((!isset($viewedSeller) || $viewedSeller == '') || (!isset($viewedBusiness)  || $viewedBusiness == '')) : ?>
            <div class="navbar" id="navbar">
                <div class="browse-all-container">
                    <button class="browse-all-btn" id="browseAllBtn" aria-expanded="false" aria-controls="categoriesDropdown">
                        All Categories
                        <i>▼</i>
                    </button>

                    <div class="categories-dropdown" id="categoriesDropdown">
                        <div class="dropdown-content">
                            <div class="parent-categories">
                                <?php
                                // Fetch all categories into an array for reuse
                                $categories = [];
                                while ($row = $ctegoryResult->fetch_assoc()) {
                                    $categories[] = $row;
                                }

                                foreach ($categories as $categoryRow) {
                                    $categoryName = htmlspecialchars($categoryRow['CategoryName'], ENT_QUOTES, 'UTF-8');
                                    $category_id = (int)$categoryRow['category_id']; // Ensure it's an integer
                                ?>
                                    <div class="parent-category" data-category="<?php echo $categoryName ?>"><?php echo $categoryName ?></div>
                                <?php } ?>
                            </div>

                            <?php
                            foreach ($categories as $categoryRow) {
                                $categoryName = htmlspecialchars($categoryRow['CategoryName'], ENT_QUOTES, 'UTF-8');
                                $category_id = (int)$categoryRow['category_id'];

                                $getSubCatSQL = "SELECT * FROM products_subcategory WHERE category_id = $category_id LIMIT 30";
                                $subCatResult = mysqli_query($conn, $getSubCatSQL);


                                // Count the number of products in this Parent category
                                $getParentTopTalProductSQL = "SELECT COUNT(*) AS product_count FROM product WHERE category_id = '$category_id'";
                                $getParentTopTalProductSTMT = $pdo->query($getParentTopTalProductSQL);
                                $getParentTopTalProductSTMT->execute();
                                $parentProductCountRow = $getParentTopTalProductSTMT->fetch(PDO::FETCH_ASSOC);
                                $parentProductCount = $parentProductCountRow['product_count'] ?? 0; // Default to 0 if no products found

                            ?>
                                <div class="child-categories" id="<?php echo $categoryName; ?>">
                                    <div class="child-category">
                                        <a href="/category/<?php echo $categoryName ?>">All<span>(<?php echo $parentProductCount; ?>)</span>
                                        </a>
                                    </div>
                                    <?php
                                    while ($subCatRow = $subCatResult->fetch_assoc()) {
                                        $subCatName = htmlspecialchars($subCatRow['subCatName'], ENT_QUOTES, 'UTF-8');
                                        $subCatID = htmlspecialchars($subCatRow['SubcategoryID'], ENT_QUOTES, 'UTF-8');
                                        $url = "/category/" . $categoryName . "/" . $subCatName;

                                        $getTopTalProductSTMT = $pdo->query("SELECT subcategoryid, COUNT(*) AS product_count FROM product WHERE subcategoryid = '$subCatID' GROUP BY subcategoryid");
                                        $getTopTalProductSTMT->execute();
                                        $productCountRow = $getTopTalProductSTMT->fetch(PDO::FETCH_ASSOC);
                                        $productCount = $productCountRow['product_count'] ?? 0; // Default to 0 if no products found

                                    ?>
                                        <div class="child-category">
                                            <a href="<?php echo $url; ?>">
                                                <?php echo $subCatName; ?> <span>(<?php echo $productCount; ?>)</span>
                                            </a>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="sellers-maquee">
                    <p class="maquee-tilte">Stores</p>
                    <marquee direction="left" scrollamount="3">
                        <?php
                        while ($businessesRow = $getAllBusinessesResult->fetch_assoc()) {
                            $businessName = htmlspecialchars($businessesRow['b_name'], ENT_QUOTES, 'UTF-8');
                        ?>
                            <div class="seller">
                                <p><?php echo $businessName ?></p>
                            </div>
                        <?php } ?>
                    </marquee>
                </div>
                <input type="checkbox" id="check">
            </div>

        <?php endif; ?>
        <!-- This show the current category the user is at with it bsub-cat -->
        <div class="breadcrumb" id="breadcrumb">
            <?php if (($viewedCategory) || ($viewedSubcat)) : ?>
                <span class="">Current:</span>
            <?php endif ?>
            <ul class="opened-links">
                <?php
                if ($viewedCategory) { ?>
                    <li><a href="/category/<?php echo $viewedCategory ?>"><?php echo $viewedCategory ?></a></li>

                <?php }
                if ($viewedSubcat) { ?>
                    <li><a href="/category/<?php echo $viewedCategory . "/" . $viewedSubcat ?>"><?php echo $viewedSubcat  ?></a></li>
                <?php }
                ?>
            </ul>
        </div>
    </nav>

    <div class="s_col one" id="side-filter">
        <div class="filter_container_demo">
            <span class="close" onclick="toggleFilter()">Close ✕</span>
            <div class="filter-group">
                <label class="label" for="type">Condition: <input class="selected_label" disabled type="text" name="type" value="<?php echo $_SESSION['type']; ?>"></label>
                <i class='fas fa-chevron-down icon' onclick="toggleOptions('type')"></i>
                <i class='fas fa-chevron-up icon' style='display:none' onclick="toggleOptions('type')"></i>
                <div class="options" id="typeOptions">
                    <ul class="ul-brand">
                        <li><label class="brand-option"> <input type="radio" name="type_opt" value="" onclick="submitFilter('condition','')"><span class="brand-indicator"></span>All</label> </li>
                        <li><label class="brand-option"> <input type="radio" name="type_opt" value="Brand New" onclick="submitFilter('condition','Brand New')"><span class="brand-indicator"></span>Brand New</label> </li>
                        <li><label class="brand-option"> <input type="radio" name="type_opt" value="Second Hand" onclick="submitFilter('condition','Second Hand')"><span class="brand-indicator"></span>Second Hand</label> </li>
                        <li><label class="brand-option"> <input type="radio" name="type_opt" value="Refurbished" onclick="submitFilter('condition','Refurbished')"><span class="brand-indicator"></span>Refurbished</label> </li>
                    </ul>
                </div>
            </div>

            <div class="filter-group">
                <label class="label" for="seller">Type of Seller<input class="selected_label" disabled type="text" name="seller" value=""></label>
                <i class='fas fa-chevron-down icon' onclick="toggleOptions('seller')"></i>
                <i class='fas fa-chevron-up icon' style='display:none' onclick="toggleOptions('seller')"></i>
                <div class="options" id="sellerOptions">
                    <ul class="ul-brand">
                        <li><label class="brand-option"> <input type="radio" name="seller_opt" value="" onclick="submitFilter('seller','')"><span class="brand-indicator"></span>All</label></li>
                        <li><label class="brand-option"> <input type="radio" name="seller_opt" value="Business" onclick="submitFilter('seller','Business')"><span class="brand-indicator"></span>Stores</label></li>
                        <li><label class="brand-option"> <input type="radio" name="seller_opt" value="individual" onclick="submitFilter('seller','individual')"><span class="brand-indicator"></span>Individual Seller</label></li>
                    </ul>
                </div>
            </div>


            <div class="filter-group">
                <label class="label" for="brand">Brand: <input class="selected_label" disabled type="text" name="brand" value="<?php echo $_SESSION['brandName']; ?>"></label>
                <i class='fas fa-chevron-down icon' onclick="toggleOptions('brand')"></i>
                <i class='fas fa-chevron-up icon' style='display:none' onclick="toggleOptions('brand')"></i>
                <div class="options" id="brandOptions">
                    <!-- HTML/PHP -->
                    <ul class="ul-brand">
                        <li>
                            <label class="brand-option">
                                <input type="radio" name="brand_opt" value="all" onchange="submitFilter('brand','')">
                                <span class="brand-indicator"></span>
                                All
                            </label>
                        </li>
                        <?php foreach ($brands as $brand): ?>
                            <li>
                                <label class="brand-option">
                                    <input type="radio" name="brand_opt" value="<?php echo htmlspecialchars($brand); ?>"
                                        onchange="submitFilter('brand','<?php echo htmlspecialchars($brand); ?>')">
                                    <span class="brand-indicator"></span>
                                    <?php echo htmlspecialchars($brand); ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="filter-group">
                <label class="label" for="color">Color: <input class="selected_label" disabled type="text" name="color" value=""></label>
                <i class='fas fa-chevron-down icon' onclick="toggleOptions('color')"></i>
                <i class='fas fa-chevron-up icon' style='display:none' onclick="toggleOptions('color')"></i>
                <div class="options" id="colorOptions">
                    <ul class="ul-color">
                        <li>
                            <label class="color-option">
                                <input type="radio" name="color_opt" value="all" onchange="submitFilter('color','')">
                                <span class="color-circle all-colors"></span>
                                All
                            </label>
                        </li>
                        <?php foreach ($colors as $color): ?>
                            <li>
                                <label class="color-option">
                                    <input type="radio" name="color_opt" value="<?php echo htmlspecialchars($color); ?>"
                                        onchange="submitFilter('color','<?php echo htmlspecialchars($color); ?>')">
                                    <span class="color-circle" style="background-color: <?php echo htmlspecialchars($color); ?>"></span>
                                    <?php echo htmlspecialchars($color); ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="filter-group">
                <label class="label" for="price">Price:
                    <input class="selected_label" id="priceLabel" disabled type="text" name="price" value="R10 - R1000">
                </label>
                <i class='fas fa-chevron-down icon' onclick="toggleOptions('price')"></i>
                <i class='fas fa-chevron-up icon' style='display:none' onclick="toggleOptions('price')"></i>
                <div class="options price-input-container" id="priceOptions">
                    <input type="number" id="minPrice" placeholder="min" min="10" max="1000" step="10">
                    <input type="number" id="maxPrice" placeholder="max" min="10" max="1000" step="10">
                    <button id="applyPriceFilter" onclick="applyPriceFilter()">✔</button>
                </div>
            </div>
        </div>
    </div>
</div>



<?php
$IPATH4 = $_SERVER["DOCUMENT_ROOT"] . "/overlays/";
include($IPATH4 . "overlay.php");

// Ignore requests for static assets (CSS, JS, images)
if (preg_match('#\.(css|js|jpg|jpeg|png|gif|svg|woff2?|ttf|eot)$#', $path)) {
    return false; // Let Apache handle static files
}

// Routing logic: Match category with two string parameters
if (preg_match('#^category/([^/]+)/([^/]+)/page/(\d+)$#', $path, $matches)) {
    // Extract parameters from URL
    $_GET['category'] = $matches[1]; // "Phones"
    $_GET['subcat'] = $matches[2];   // "Smartphones"
    $_GET['page'] = $matches[3];     // "1"
    $_POST['valid'] = 1;     // "1"

    require 'pages/category.php';
    exit;
} elseif (preg_match('#^category/([^/]+)/([^/]+)$#', $path, $matches)) {
    // Example: /category/Electronics/15ffvlrpl → category.php?name=Electronics&id=15ffvlrpl
    $_GET['category'] = $matches[1]; // "Electronics"
    $_GET['subcat'] = $matches[2];   // "15ffvlrpl"
    $_POST['valid'] = 1;     // "1"

    require 'pages/category.php';
    exit;
}
if (preg_match('#^category/([^/]+)/page/(\d+)$#', $path, $matches)) {
    // Extract parameters from URL
    $_GET['category'] = $matches[1]; // "Phones"
    $_GET['page'] = $matches[2];     // "1"
    $_POST['valid'] = 1;     // "1"
    require 'pages/category.php';
    exit;
} elseif (preg_match('#^item/(\d+)$#', $path, $matches)) {
    // Extract parameters from URL
    $_GET['productId'] = $matches[1];     // "1"
    $_POST['valid'] = 1;     // "1"
    require 'pages/item.php';
    exit;
} elseif (preg_match('#^category/([^/]+)#', $path, $matches)) {
    $_GET['category'] = $matches[1]; // "Electronics"
    $_POST['valid'] = 1;     // "1"

    require 'pages/category.php';
    exit;
} elseif (preg_match('#^storeprofile/([^/]+)/([^/]+)$#', trim($path, '/'), $matches)) {
    $_GET['b_name'] = urldecode($matches[1]); // "Nandi's i-Phones"
    $_GET['seller_id'] = urldecode($matches[2]); // "SELLER_27988"
    $_POST['valid'] = 1;     // "1"
    // Ensure query parameters are preserved if already parsed
    require 'pages/store.php';
    exit;
} elseif (preg_match('#^search/([^/]+)#', $path, $matches)) {
    $_GET['search'] = $matches[1]; // "Electronics"
    require 'pages/search.php';
    exit;
} elseif ($path === 'contact') {
    require 'pages/contact.php';
    exit;
} elseif ($path === 'products') {
    require 'pages/products.php';
    exit;
} elseif ($path === 'categories') {
    require 'pages/categories.php';
    exit;
} elseif ($path === 'cart') {
    $_POST['valid'] = 1;     // "1"
    require 'pages/bag.php';
    exit;
}
?>