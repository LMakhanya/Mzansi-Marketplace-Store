<?php
// Standard meta tags
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta http-equiv="x-ua-compatible" content="ie=edge">

<?php
// Initialize default values
$pageTitle = 'Shop | The Mzansi Marketplace Official';
$metaDescription = 'Discover great deals from trusted local stores on The Mzansi Marketplace. Support your community while shopping safely and conveniently.';
$metaKeywords = 'The Mzansi, marketplace, online shopping, local stores, South Africa, ecommerce';

// Assume product details are available (set in pageData.php)
$viewedProduct = isset($_GET['product_id']) ? htmlspecialchars(urldecode($_GET['product_id']), ENT_QUOTES, 'UTF-8') : '';
$productName = isset($filterParams['ProductName']) && !empty($filterParams['ProductName']) ? htmlspecialchars($filterParams['ProductName'], ENT_QUOTES, 'UTF-8') : ($viewedProduct ?: '');

// Determine page type and set dynamic metadata
if (strpos($path, 'categories') !== false || (isset($_GET['page']) && $_GET['page'] === 'categories')) {
    // Categories page
    $categoryCount = isset($totalCategories) ? (int)$totalCategories : 0;
    $pageTitle = 'Browse Categories - The Mzansi Marketplace';
    $metaDescription = 'Explore ' . $categoryCount . ' product categories on The Mzansi Marketplace. Find everything from electronics to fashion with great deals from local South African stores.';
    $metaKeywords .= ', product categories, browse, shop categories';
} elseif (!empty($viewedProduct) && !empty($productName)) {
    // Item/Product page
    $pageTitle = $productName . ' - The Mzansi Marketplace';
    $metaDescription = 'Shop ' . $productName . ' on The Mzansi Marketplace. ';
    if (!empty($viewedCategory)) {
        $metaDescription .= 'Find this in our ' . htmlspecialchars(urldecode($viewedCategory), ENT_QUOTES, 'UTF-8') . ' collection. ';
        $metaKeywords .= ', ' . htmlspecialchars(urldecode($viewedCategory), ENT_QUOTES, 'UTF-8');
    }
    $metaDescription .= 'Great deals, fast delivery, trusted local sellers.';
    $metaKeywords .= ', ' . $productName . ', product';
} elseif (!empty($viewedCategory)) {
    // Category or Subcategory page
    $pageTitle = 'Buy ';
    $metaDescription = 'Shop top-quality ';

    if (!empty($viewedSubcat)) {
        // Subcategory page
        $pageTitle .= $rawSubcat . ' in ' . $rawCategory;
        $metaDescription .= htmlspecialchars(urldecode($viewedSubcat), ENT_QUOTES, 'UTF-8') . ' in our ' . htmlspecialchars(urldecode($viewedCategory), ENT_QUOTES, 'UTF-8') . ' collection.';
        $metaKeywords .= ', ' . htmlspecialchars(urldecode($viewedSubcat), ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars(urldecode($viewedCategory), ENT_QUOTES, 'UTF-8');
    } else {
        // Category page
        $pageTitle .=  $rawCategory;
        $metaDescription .= 'products in our ' . htmlspecialchars(urldecode($viewedCategory), ENT_QUOTES, 'UTF-8') . ' collection.';
        $metaKeywords .= ', ' . htmlspecialchars(urldecode($viewedCategory), ENT_QUOTES, 'UTF-8');
    }

    $pageTitle .= ' - The Mzansi Marketplace';
    $metaDescription .= ' Best deals, fast delivery, and trusted service.';
} elseif (!empty($viewedBusiness) || !empty($viewedSeller)) {
    // Store/Seller profile page
    $businessName = htmlspecialchars(urldecode($viewedBusiness), ENT_QUOTES, 'UTF-8');
    $pageTitle = $businessName . ' Store - The Mzansi Marketplace';
    $metaDescription = 'Shop exclusive products from ' . $businessName . ' on The Mzansi Marketplace. Enjoy great deals and support local South African sellers.';
    $metaKeywords .= ', ' . $businessName . ', local sellers, South African stores';
} elseif (strpos($path, 'cart') !== false) {
    // Bag/Cart page
    $itemCount = isset($_SESSION['totalQuantity']) ? (int)$_SESSION['totalQuantity'] : 0;
    $pageTitle = 'Your Shopping Bag - The Mzansi Marketplace';
    $metaDescription = 'Review ' . $itemCount . ' item' . ($itemCount !== 1 ? 's' : '') . ' in your shopping bag on The Mzansi Marketplace. Proceed to checkout for fast delivery.';
    $metaKeywords .= ', shopping bag, cart, checkout';
} elseif (strpos($path, 'pay') !== false) {
    // Pay/Checkout page
    $pageTitle = 'Checkout - The Mzansi Marketplace';
    $metaDescription = 'Complete your purchase on The Mzansi Marketplace with secure payment options and fast delivery from trusted local stores.';
    $metaKeywords .= ', checkout, payment, secure shopping';
} else {
    // Home/Index page
    $pageTitle = 'Shop | The Mzansi Marketplace Official';
    $metaDescription = 'Discover great deals from trusted local stores on The Mzansi Marketplace. Support your community while shopping safely and conveniently.';
    $metaKeywords .= ', home, featured products';
}

// Debug output (enabled via ?debug=1 in URL)
if (isset($_GET['debug'])) {
    echo 'Category: ' . htmlspecialchars($viewedCategory) . '<br>';
    echo 'Sub Category: ' . htmlspecialchars($viewedSubcat) . '<br>';
    echo 'Business: ' . htmlspecialchars($viewedBusiness) . '<br>';
    echo 'Seller ID: ' . htmlspecialchars($viewedSeller) . '<br>';
    echo 'Product: ' . htmlspecialchars($productName) . '<br>';
}
?>

<!-- Output meta tags -->
<title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords, ENT_QUOTES, 'UTF-8'); ?>">

<meta name="author" content="The Mzansi Marketplace Team">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Open Graph (Facebook, WhatsApp, LinkedIn, etc.) -->
<meta property="og:title" content="The Mzansi Marketplace | Shop Smart, Shop Local">
<meta property="og:description"
    content="Discover great deals from trusted local stores on The Mzansi Marketplace. Support your community while shopping safely and conveniently.">
<meta property="og:image" content="https://themzansimarketplace.co.za/assets/images/themzansi_logo.png">
<!-- Replace with your image URL -->
<meta property="og:url" content="https://themzansimarketplace.co.za/">
<meta property="og:type" content="website">

<!-- Favicon (Optional but recommended) -->
<link rel="icon" type="image/x-icon" href="/assets/images/themzansi_logo.png" />

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" type="text/css"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>

<link rel="stylesheet" type="text/css"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- ====== Phosphoric Icons ======= -->
<link rel="stylesheet" type="text/css"
    href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css" />
<link rel="stylesheet" type="text/css"
    href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css" />

<link rel="stylesheet" type="text/css" href="/styles/output.css">
<link rel="stylesheet" type="text/css" href="/styles/default.css">
<link rel="stylesheet" type="text/css" href="/styles/footer.css">
<link rel="stylesheet" type="text/css" href="/styles/overlay.css">
<link rel="stylesheet" type="text/css" href="/styles/category.css">
<link rel="stylesheet" type="text/css" href="/styles/headerStyle.css">
<link rel="stylesheet" type="text/css" href="/styles/homeStyle.css">
<link rel="stylesheet" type="text/css" href="/styles/slider.css">
<link rel="stylesheet" type="text/css" href="/styles/signup.css">
<link rel="stylesheet" type="text/css" href="/styles/shopingbag.css">
<link rel="stylesheet" type="text/css" href="/styles/profileSlide.css">
<link rel="stylesheet" type="text/css" href="/styles/item-style.css">
<link rel="stylesheet" type="text/css" href="/styles/storeStyle.css">
<link rel="stylesheet" type="text/css" href="/styles/seller.css">
<link rel="stylesheet" type="text/css" href="/styles/alert.css">

<link rel="stylesheet" type="text/css" href="/styles/bag-style.css">

<link rel="stylesheet" type="text/css" href="/styles/responsive/tablet.css">
<link rel="stylesheet" type="text/css" href="/styles/responsive/medium.css">
<link rel="stylesheet" type="text/css" href="/styles/responsive/smallscreens.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const bag = 'true';
    const categoryName = '';
    const section = '';
    const totalQuantity = 0.0;
    const productName = "";
    const productDescr = "";
    const price = 0;
    const productImage = "";
    const quantity = 0;
    const bagID = '';
    let js_domain = 'https://seller.themzansimarketplace.co.za/';
    var error = window.location.search.substring(1).split("=")[1];
</script>