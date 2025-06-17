<?php

if (!$_POST['valid']) {
    header("Location: /");
    exit();
}

$IPATH_INCLUDES = $_SERVER["DOCUMENT_ROOT"] . "/assets/includes/";

$action = $_GET['action'] = 'getItem';
include($IPATH . "refresh_session.php");

$itemRow = $jsonRResponse['selectedProduct'];
$reviewsRow = $jsonRResponse['reviews'];
$variantsRaw = $jsonRResponse['variants'];

// Original price
$price = $itemRow['Price'];

// Discount percentage
$discount = $itemRow['discount'];

// Sale price after discount
$saleprice = $price - ($price * ($discount / 100));

// Format original price and sale price to two decimal places
$formattedPrice = number_format($price, 2, '.', '');
$formattedSalePrice = number_format($saleprice, 2, '.', '');

// Split prices into whole and decimal parts (optional)
list($whole, $decimal) = explode('.', $formattedPrice);
list($wholeSale, $decimalSale) = explode('.', $formattedSalePrice);

// Calculate saved amount
$savedAmount = $price - $saleprice;
$formattedSavedAmount = number_format($savedAmount, 2, '.', '');

// Optional: split saved amount
list($wholeSaved, $decimalSaved) = explode('.', $formattedSavedAmount);


$suggested = [];
$similarProducts = [];
$params = [];
$types = "";
$conditions = [];

$getProductsSQL = "SELECT p.*, ps.Model , s.* FROM product p
        LEFT JOIN product_specs ps ON p.product_id = ps.product_id
        JOIN product_categories pc ON p.category_id = pc.category_id
        JOIN sss_business s ON p.seller_id = s.seller_id
        JOIN sellers ss ON p.seller_id = ss.seller_id";

$categoryId = $itemRow['category_id'];
$getProductId = $itemRow['product_id'];
$conditions = ["p.category_id = $categoryId", "p.stock_qnty > 0", "ss.is_verified = 'yes'"];

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

// Fetch the results
while ($getProductsRow = $getProductResults->fetch_assoc()) {

    if (!$getProductsRow['Model']) {
        $getProductsRow['Model'] = "-";
    }
    if ($getProductsRow['product_id'] != $getProductId && count($similarProducts) < 5) {
        $similarProducts[] = $getProductsRow;
    }
}

$data = [
    'phones' => $similarProducts,
    'success' => true
];

// echo json_encode($variantsRaw, JSON_PRETTY_PRINT);
?>

<section class="item-section">

    <!-- Product Section -->
    <div class="flex flex-col lg:flex-row gap-8 mb-12 bg:red">
        <!-- Product Gallery -->

        <div class="product-images">
            <img src="/uploads/<?php echo (!empty($itemRow['product_image']) ? htmlspecialchars($itemRow['product_image']) : 'default-placeholder.jpg'); ?>" alt="Product Image" alt="SoundMax Pro Headphones" class="main-image" id="mainImage">
            <div class="thumbnail-container">
                <?php if (!empty($itemRow['product_image'])): ?>
                    <img class="thumbnail front active" onclick="changeImage(this)" src="/uploads/<?php echo htmlspecialchars($itemRow['product_image']); ?>" style="cursor:pointer" onclick="currentDiv(1)">
                <?php endif; ?>
                <?php if (!empty($itemRow['image2'])): ?>
                    <img class="thumbnail back" onclick="changeImage(this)" src="/uploads/<?php echo htmlspecialchars($itemRow['image2']); ?>" alt="" style="cursor:pointer" onclick="currentDiv(2)">
                <?php endif; ?>
                <?php if (!empty($itemRow['image3'])): ?>
                    <img class="thumbnail left" onclick="changeImage(this)" src="/uploads/<?php echo htmlspecialchars($itemRow['image3']); ?>" alt="" style="cursor:pointer" onclick="currentDiv(3)">
                <?php endif; ?>
                <?php if (!empty($itemRow['image4'])): ?>
                    <img class="thumbnail right" onclick="changeImage(this)" src="/uploads/<?php echo htmlspecialchars($itemRow['image4']); ?>" alt="" style="cursor:pointer" onclick="currentDiv(4)">
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Details -->
        <div class="lg:w-1/2 item-dtls">
            <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($itemRow['ProductName']); ?></h1>
            <div class="flex items-center">
                <?php

                // Extract ratings
                $ratings = array_map(function ($review) {
                    return (float)$review['review_rating'];
                }, $jsonRResponse['reviews']);

                $ratingCount = count($ratings);
                $reviewCount = $ratingCount; // same in this case

                $rating = $ratingCount > 0 ? array_sum($ratings) / $ratingCount : 0;

                // Calculate stars
                $fullStars = floor($rating);
                $ratedecimal = $rating - $fullStars;
                $halfStar = ($ratedecimal >= 0.25 && $ratedecimal < 0.75) ? 1 : 0;
                $fullStars += ($ratedecimal >= 0.75) ? 1 : 0;
                $emptyStars = 5 - $fullStars - $halfStar;
                ?>

                <div class="flex text-yellow-400 mr-2">
                    <?php
                    for ($i = 0; $i < $fullStars; $i++) {
                        echo '<i class="fas fa-star"></i>';
                    }
                    if ($halfStar) {
                        echo '<i class="fas fa-star-half-alt"></i>';
                    }
                    for ($i = 0; $i < $emptyStars; $i++) {
                        echo '<i class="far fa-star"></i>';
                    }
                    ?>
                </div>

                <span class="text-gray-600"><?= number_format($rating, 1) ?> (<?= number_format($reviewCount) ?> reviews)</span>
                <span class="mx-2" style="margin:auto .5rem;">|</span>
                <span class="text-green-600 font-medium">
                    <?php if (($itemRow['stock_qnty']) > 10): ?>
                        In stock
                    <?php endif; ?>
                    <?php if (($itemRow['stock_qnty']) == 0): ?>
                        Out of Stock
                    <?php endif; ?>
                    <?php if (($itemRow['stock_qnty']) <= 10): ?>
                        Few left - grap one now!
                    <?php endif; ?>
                </span>
            </div>

            <?php
            if ($itemRow['condition'] == 'Brand New') {
                $conditionClass = 'info-box-update';
            }
            if ($itemRow['condition'] == 'Second Hand') {
                $conditionClass = 'info-box-reminder';
            }
            if ($itemRow['condition'] == 'Refurbished') {
                $conditionClass = 'info-box-urgent';
            }
            ?>

            <div class="info-box <?php echo $conditionClass; ?>">
                <div class="title-wrapper">
                    <i class="ph ph-gear-fine"></i>
                    <h4 class="info-title">Condition:</h4>
                </div>
                <p class="info-list">
                    The product is <span class="highlight"><?php echo $itemRow['condition'] ?></span>.
                </p>
            </div>


            <div class="price-options">
                <h2 class="text-3xl font-bold text-gray-900 mb-0" id="selected-price">R<?php echo number_format($wholeSale, 0, '.', ','); ?></span><span class="decimal"><?php echo $decimalSale; ?></span></h2>
                <?php if ($itemRow['discount'] != 0): ?>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-tag text-orange-500 mr-2"></i>
                            <span class="font-medium">Special Offer: </span>
                            <span class="ml-1" style="margin-left: .2rem;">Get <?php echo htmlspecialchars($itemRow['discount']); ?>% off</span>
                        </div>
                        <?php if ($itemRow['discount'] != 0) { ?>
                            <p class="text-red-500 font-bold" style="font-size:.8rem">Was <span class="text-red-500 font-bold line-through">R<?php echo number_format($whole, 0, '.', ','); ?></span><span class="decimal font-bold line-through"><?php echo $decimal; ?></span> <span span class="text-gray-500" style="margin:auto .5rem;">|</span> <span class="text-green-500 font-bold">You save R<?php echo number_format($wholeSaved, 0, '.', ','); ?></span><span class="decimal text-green-500 font-bold"><?php echo $decimalSaved; ?></span></p>
                        <?php } ?>
                    </div>
                <?php endif ?>
            </div>

            <div class="color-options">
                <span class="option-title">Color:</span>
                <div class="colors">

                    <?php
                    $color = $itemRow['color_hex'] ?? '#cccccc'; // always safe
                    ?>
                    <span class="color selected" style="background-color: <?php echo htmlspecialchars($color); ?>;" onclick="selectColor(this)" title="<?php echo htmlspecialchars($itemRow['color_name'] ?? ''); ?>"></span>

                </div>
            </div>

            <?php if ($itemRow['category_id'] == 4 || $itemRow['category_id'] == 5 || $itemRow['category_id'] == 11): ?>
                <div class="size-options">
                    <h2 class="option-title">Size:</h2>
                    <div class="size-selector">
                        <?php
                        $variants = $variantsRaw;

                        if ($itemRow['sizing_type'] == 'foot') { ?>
                            <?php
                            // Define standard foot sizes
                            $footSizes = range(4, 13);
                            foreach ($footSizes as $size) {
                                $sizeStr = number_format($size, 1);
                                $variant = array_filter($variants, function ($v) use ($sizeStr) {
                                    return $v['size'] == $sizeStr;
                                });
                                $variant = reset($variant);
                                $disabled = empty($variant) || $variant['quantity'] <= 0;
                                $price = !empty($variant['price']) && $variant['price'] != '0' ? 'R' . number_format($variant['price'], 2) : '';
                            ?>
                                <div class="size-option">
                                    <input type="radio" name="size" id="size_<?= $sizeStr ?>" value="<?= $sizeStr ?>"
                                        <?= $disabled ? 'disabled' : '' ?>>
                                    <label for="size_<?= $sizeStr ?>">
                                        <?= $sizeStr ?>
                                        <?php if ($price): ?>
                                            <div class="price-indicator"><?= $price ?></div>
                                        <?php endif; ?>
                                        <?php if ($disabled): ?>
                                            <div class="out-of-stock" title="Out of stock">!</div>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php } ?>

                        <?php } elseif ($itemRow['sizing_type'] == 'alpha') { ?>
                            <?php
                            $alphaSizes = ['xs', 's', 'm', 'l', 'xl', 'xxl', 'xxxl'];
                            foreach ($alphaSizes as $size) {
                                $variant = array_filter($variants, function ($v) use ($size) {
                                    return strtolower($v['size']) == strtolower($size);
                                });
                                $variant = reset($variant);
                                $disabled = empty($variant) || $variant['quantity'] <= 0;
                                $price = !empty($variant['price']) && $variant['price'] != '0' ? 'R' . number_format($variant['price'], 2) : '';
                            ?>
                                <div class="size-option">
                                    <input type="radio" name="size" id="size_<?= $size ?>" value="<?= $size ?>"
                                        <?= $disabled ? 'disabled' : '' ?>>
                                    <label for="size_<?= $size ?>">
                                        <?= strtoupper($size) ?>
                                        <?php if ($price): ?>
                                            <div class="price-indicator"><?= $price ?></div>
                                        <?php endif; ?>
                                        <?php if ($disabled): ?>
                                            <div class="out-of-stock" title="Out of stock">!</div>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php } ?>

                        <?php } elseif ($itemRow['sizing_type'] == 'waist') { ?>
                            <?php
                            $waistSizes = range(28, 42, 2);
                            foreach ($waistSizes as $size) {
                                $variant = array_filter($variants, function ($v) use ($size) {
                                    return $v['size'] == $size;
                                });
                                $variant = reset($variant);
                                $disabled = empty($variant) || $variant['quantity'] <= 0;
                                $price = !empty($variant['price']) && $variant['price'] != '0' ? 'R' . number_format($variant['price'], 2) : '';
                            ?>
                                <div class="size-option">
                                    <input type="radio" name="size" id="size_<?= $size ?>" value="<?= $size ?>"
                                        <?= $disabled ? 'disabled' : '' ?>>
                                    <label for="size_<?= $size ?>">
                                        <?= $size ?>
                                        <?php if ($price): ?>
                                            <div class="price-indicator"><?= $price ?></div>
                                        <?php endif; ?>
                                        <?php if ($disabled): ?>
                                            <div class="out-of-stock" title="Out of stock">!</div>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php } ?>

                        <?php } elseif ($itemRow['sizing_type'] == 'inches') { ?>
                            <?php foreach ($variants as $variant) {
                                $disabled = $variant['quantity'] <= 0;
                                $priceRaw = !empty($variant['price']) && $variant['price'] != '0' ? $variant['price'] : $wholeSale . '.' . $decimalSale;
                                $priceFormatted = 'R' . number_format($priceRaw, 2);
                            ?>
                                <div class="size-option">
                                    <input type="radio" name="size" id="size_<?= $variant['size'] ?>" value="<?= $variant['size'] ?>" data-price="<?= $priceRaw ?>" <?= $disabled ? 'disabled' : '' ?>>
                                    <label for="size_<?= $variant['size'] ?>">
                                        <?= $variant['size'] ?>"
                                        <?php if (!empty($variant['price']) && $variant['price'] != '0'): ?>
                                            <div class="price-indicator"><?= $priceFormatted ?></div>
                                        <?php endif; ?>
                                        <?php if ($disabled): ?>
                                            <div class="out-of-stock" title="Out of stock">!</div>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                    <?php if ($itemRow['sizing_type'] == 'inches'): ?>
                        <p class="option-sub-title">Cap Size: <span class=""><?php echo htmlspecialchars($itemRow['CapSize'] ?? 'N/A'); ?></span></p>
                    <?php endif ?>
                    <p class="size-guide-link" onclick="openSizeGuide()"><i class="ph ph-ruler"></i>Size Guide</p>
                </div>
            <?php endif ?>

            <div class="quantity-options">
                <span class="option-title">Quantity:</span>
                <div class="flex items-center mr-6">
                    <div class="quantity-selector">
                        <div class="item-quantity-btn" onclick="adjustQuantity(-1)">-</div>
                        <input type="number" id="quantityInput" class="quantity-input" min="1" value="1" data-max-stock="<?php echo (int)$itemRow['stock_qnty']; ?>">
                        <div class="item-quantity-btn" onclick="adjustQuantity(1)">+</div>
                    </div>
                </div>
            </div>
            <?php
            $btn_disabled = '';
            if ($itemRow["stock_qnty"] == 0) {
                $btn_disabled = 'Disabled';
            }

            echo $btn_disabled;
            ?>
            <div class="action-buttons">
                <button <?php echo $btn_disabled ?> class="btn btn-primary" id="btn_<?php echo $itemRow['product_id']; ?>" onclick="addToCart(
                                    <?php echo $itemRow['product_id']; ?>,
                                    `<?php echo htmlspecialchars($itemRow['ProductName']); ?>`,
                                    `<?php echo htmlspecialchars(!empty($storePRow['Model']) ? $storePRow['Model'] : 'Unknown'); ?>`,
                                    <?php echo $wholeSale; ?>, 
                                    `<?php echo htmlspecialchars($itemRow['product_image']); ?>`,
                                    `<?php echo htmlspecialchars($itemRow['seller_id']); ?>`)">
                    <span class="btn-loader" style="display: none;"></span>
                    <i class="ph ph-shopping-cart-simple"></i> Add to Cart
                </button>
                <!--        <button class="btn btn-secondary" onclick="buyNow()">
                    <i class="fas fa-bolt"></i> Buy Now
                </button> -->
                <button class="wishlist" title="Add to Wishlist">
                    <i class="far fa-heart"></i>
                </button>
            </div>

            <div class="delivery-info">
                <div class="faq-item">
                    <div class="delivery-row" aria-expanded="false" aria-controls="delivery-content">
                        <div>
                            <i class="fas fa-truck delivery-icon" aria-hidden="true"></i>
                            <h3>Delivery</h3>
                        </div>
                    </div>
                    <p id="delivery-content">Get free delivery on all orders over R500. Standard shipping takes 3-5 business days. Express options available at checkout.</p>
                </div>

                <div class="faq-item">
                    <div class="delivery-row" aria-expanded="false" aria-controls="returns-content">
                        <div>
                            <i class="fas fa-undo delivery-icon" aria-hidden="true"></i>
                            <h3>Return Policy</h3>
                        </div>
                    </div>
                    <p id="returns-content">Easy returns within 30 days of purchase. Items must be in original condition with tags attached. We'll email you a prepaid return label.</p>
                </div>

                <div class="faq-item">
                    <div class="delivery-row" aria-expanded="false" aria-controls="warranty-content">
                        <div>
                            <i class="fas fa-shield-alt delivery-icon" aria-hidden="true"></i>
                            <h3>Warranty</h3>
                        </div>
                    </div>
                    <p id="warranty-content">All products come with a 1-year manufacturer warranty against defects. Contact our support team with your order number to initiate a warranty claim.</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Product Tabs -->
    <div class="" style="margin-top: 1rem;">
        <div class="flex border-b">
            <button class="item-tab-btn opened font-bold" onclick="openTab('details', event)">Product Details</button>
            <?php if (!in_array($itemRow['category_id'], [2, 4, 5, 11])): ?>
                <button class="item-tab-btn font-bold" onclick="openTab('specs', event)">Specifications</button>
            <?php endif ?>
            <button class="item-tab-btn font-bold" onclick="openTab('reviews', event)">Reviews</button>
        </div>

        <div id="details" class="item-tab-content details active p-3 mb:p-10">
            <!--  <h2 class="text-xl mb-4">About Product</h2> -->
            <!-- Additional Notes Section -->
            <div id="additionalNotes"><?php echo $itemRow['additional_notes'] ?></div>
        </div>

        <div id="specs" class="item-tab-content p-3 mb:p-10">
            <!--  <h2 class="text-xl mb-4">Technical Specifications</h2> -->
            <!-- Electronics -->
            <?php if ($itemRow['category_id'] == 1): ?>
                <div class="data" id="electronics_specs">
                    <div class="specs grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="specs-row">
                            <div class="spec-label">Model</div>
                            <div class="spec-value" id="eModel"><?php echo htmlspecialchars($itemRow['Model'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Power Supply</div>
                            <div class="spec-value" id="ePower"><?php echo htmlspecialchars($itemRow['PowerSupply'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Features</div>
                            <div class="spec-value" id="eFeatures"><?php echo htmlspecialchars($itemRow['Features'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Phones -->
            <?php if ($itemRow['category_id'] == 6): ?>
                <div class="data" id="phones_specs">
                    <div class="specs grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="specs-row">
                            <div class="spec-label">Model</div>
                            <div class="spec-value" id="pModel"><?php echo htmlspecialchars($itemRow['Model'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Processor</div>
                            <div class="spec-value" id="pProcessor"><?php echo htmlspecialchars($itemRow['Processor'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Display Size</div>
                            <div class="spec-value" id="pDisplay"><?php echo htmlspecialchars($itemRow['DisplaySize'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">RAM</div>
                            <div class="spec-value" id="pRam"><?php echo htmlspecialchars($itemRow['RAM'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Storage</div>
                            <div class="spec-value" id="pStorage"><?php echo htmlspecialchars($itemRow['Storage'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Camera</div>
                            <div class="spec-value" id="pCamera"><?php echo htmlspecialchars($itemRow['Camera'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Laptops -->
            <?php if ($itemRow['category_id'] == 7): ?>
                <div class="data" id="laptops_specs">
                    <div class="specs grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="specs-row">
                            <div class="spec-label">Model</div>
                            <div class="spec-value" id="lModel"><?php echo htmlspecialchars($itemRow['Model'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Processor</div>
                            <div class="spec-value" id="lProcessor"><?php echo htmlspecialchars($itemRow['Processor'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">RAM</div>
                            <div class="spec-value" id="lRam"><?php echo htmlspecialchars($itemRow['RAM'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Storage</div>
                            <div class="spec-value" id="lStorage"><?php echo htmlspecialchars($itemRow['Storage'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Display Size</div>
                            <div class="spec-value" id="lDisplay"><?php echo htmlspecialchars($itemRow['DisplaySize'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Graphics</div>
                            <div class="spec-value" id="lGraphic"><?php echo htmlspecialchars($itemRow['GraphicsCard'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Health & Beauty -->
            <?php if ($itemRow['category_id'] == 8): ?>
                <div class="data" id="health_beauty_specs">
                    <div class="specs grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="specs-row">
                            <div class="spec-label">Type</div>
                            <div class="spec-value" id="hbType"><?php echo htmlspecialchars($itemRow['Type'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Volume/Weight</div>
                            <div class="spec-value" id="hbVolume"><?php echo htmlspecialchars($itemRow['VolumeWeight'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Key Ingredients</div>
                            <div class="spec-value" id="hbIngredients"><?php echo htmlspecialchars($itemRow['KeyIngredients'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Books -->
            <?php if ($itemRow['category_id'] == 9): ?>
                <div class="data" id="books_education_specs">
                    <div class="specs grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="specs-row">
                            <div class="spec-label">Author</div>
                            <div class="spec-value" id="bAuthor"><?php echo htmlspecialchars($itemRow['Author'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Type</div>
                            <div class="spec-value" id="bType"><?php echo htmlspecialchars($itemRow['Type'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Number of Pages</div>
                            <div class="spec-value" id="bPages"><?php echo htmlspecialchars($itemRow['Pages'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Fragrances -->
            <?php if ($itemRow['category_id'] == 10): ?>
                <div class="data" id="fragrances_specs">
                    <div class="specs grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="specs-row">
                            <div class="spec-label">Type</div>
                            <div class="spec-value" id="fType"><?php echo htmlspecialchars($itemRow['Type'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Volume</div>
                            <div class="spec-value" id="fVolume"><?php echo htmlspecialchars($itemRow['VolumeWeight'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="specs-row">
                            <div class="spec-label">Scent Notes</div>
                            <div class="spec-value" id="fNotes"><?php echo htmlspecialchars($itemRow['ScentNotes'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div id="reviews" class="item-tab-content p-3 mb:p-10">
            <!--  <h2 class="text-xl mb-2">Customer Reviews</h2> -->
            <div class="flex flex-col bg-gray-50 rounded-lg text-center gap-2" style="padding: 1.5rem 2rem; width: fit-content; margin:auto">
                <div class="text-3xl font-bold text-gray-800 "><?= number_format($rating, 1) ?>/5 </div>
                <div class="flex text-yellow-400 flex justify-center">
                    <?php
                    for ($i = 0; $i < $fullStars; $i++) {
                        echo '<i class="fas fa-star"></i>';
                    }
                    if ($halfStar) {
                        echo '<i class="fas fa-star-half-alt"></i>';
                    }
                    for ($i = 0; $i < $emptyStars; $i++) {
                        echo '<i class="far fa-star"></i>';
                    }
                    ?>
                </div>
                <span class="text-gray-600">Based on <?= number_format($reviewCount) ?> reviews</span>
            </div>

            <button class="flex m-auto mr-0 mt-4 md:mt-0 mb-8 bg-orange-600 hover:bg-orange-700 text-white py-2 px-4 rounded-lg font-medium">
                Write a Review
            </button>


            <div class="space-y-6 grid grid-cols-1 md:grid-cols-3 gap:2 md:gap-6">
                <?php foreach ($jsonRResponse['reviews'] as $row) { ?>
                    <div class="review-card bg-white p-6 rounded-lg shadow">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-semibold"><?php echo htmlspecialchars($row['reviewer_name']); ?></h4>
                                <div class="flex text-yellow-400 text-sm mt-1">
                                    <?php
                                    $rating = (int) $row['review_rating']; // Convert rating to i  
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star text-yellow-400"></i>'; // Filled star
                                        } else {
                                            echo '<i class="far fa-star text-yellow-400"></i>'; // Empty star
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <span class="text-gray-500 text-sm"> <?php echo date("F j, Y", strtotime($row['review_date'])); ?></span>
                        </div>
                        <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($row['review_text']); ?></p>
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                            <span>Verified Purchase</span>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>

<section>
    <div class="line-with-text">
        <!-- <div class="line"></div> -->
        <div class="text">Similar Products</div>
        <div class="line"></div>
    </div>
    <div class="i-wraper">
        <?php foreach ($similarProducts as $row) {
            $saleprice = $row['Price']  - ($row['Price'] * ($row['discount'] / 100));

            $price = $row['Price']; // Example price
            $formattedPrice = number_format($price, 2, '.', ''); // Ensure two decimal places
            list($whole, $decimal) = explode('.', $formattedPrice); // Split into whole and decimal parts


            $pricesale = $saleprice; // Discounted price
            $formattedSalePrice = number_format($pricesale, 2, '.', ''); // Ensure two decimal places
            list($wholeSale, $decimalSale) = explode('.', $formattedSalePrice); // Split into whole and decimal parts
        ?>
            <div class="items">
                <?php if ($row['discount'] != 0) { ?>
                    <div class="wasPrice">
                        <p><?php echo $row['discount']; ?>% Off</p>
                    </div>
                <?php } ?>

                <div class="item">
                    <a href="/item/<?php echo $row['product_id']; ?>" class="item">
                        <div class="i-img">
                            <img loading="lazy" src="/uploads/<?php echo $row['product_image']; ?>" alt="<?php echo $row['ProductName']; ?>">
                        </div>
                        <div class="seperator"></div>
                        <p class="item-name"><?php echo $row['ProductName'] ?></p>
                    </a>
                    <a href="/storeprofile/<?php echo $row['b_name'] . "/" . $row['seller_id'] ?>">
                        <div class="seller_name">
                            <span><?php echo $row['b_name']; ?></span>
                            <i class="ph-fill ph-shield-check"></i>
                        </div>
                    </a>
                    <a href="/item/<?php echo $row['product_id']; ?>" class="item">
                        <div class="i-dtls">
                            <div>
                                <span>Model:</span>
                                <p class="item-model"><?php echo $row['Model'] ?></p>
                            </div>
                            <div>
                                <span>Brand:</span>
                                <p class="item-color"><?php echo $row['brandName'] ?> </p>
                            </div>
                            <div>
                                <span>Color:</span>
                                <p class="item-color"><?php echo $row['color'] ?></p>
                            </div>
                        </div>
                        <div class="price-wrapper">
                            <p class="item-price"> <span class="amount">R<?php echo number_format($wholeSale, 0, '.', ','); ?></span><span class="decimal"><?php echo $decimalSale; ?></span></p>
                            <?php if ($row['discount'] != 0) { ?>
                                <p class="item-price actual"> <span class="amount">R<?php echo number_format($whole, 0, '.', ','); ?></span><span class="decimal"><?php echo $decimal; ?></span></p>
                            <?php } ?>
                        </div>
                    </a>
                </div>
                <?php
                $buyPrice = $wholeSale;

                if ($row['stock_qnty'] > 0 && !in_array($row['category_id'], [4, 5, 11])): ?>
                    <div class="i-btn">
                        <button id="btn_<?php echo $row['product_id']; ?>" onclick="addToCart(
                            <?php echo $row['product_id']; ?>, 
                            `<?php echo htmlspecialchars($row['ProductName']); ?>`, 
                            `<?php echo htmlspecialchars($row['Model']); ?>`, 
                            <?php echo $buyPrice; ?>, 
                            `<?php echo htmlspecialchars($row['product_image']); ?>`, 
                            `<?php echo htmlspecialchars($row['seller_id']); ?>`)">
                            <span class="btn-loader" style="display: none;"></span>
                            <i class="ph ph-shopping-cart-simple"></i>
                            Add <span class='add-t-c-span'>To Cart</span>
                        </button>
                    </div>
                <?php endif;
                if ($row['stock_qnty'] > 0 && in_array($row['category_id'], [4, 5, 11])): ?>
                    <div class="i-btn">
                        <button id="btn_<?php echo $row['product_id']; ?>" onclick="viewItem(<?= $row['product_id']; ?>)">
                            <span class="btn-loader" style="display: none;"></span>
                            <i class="ph ph-arrow-square-out"></i>
                            View <span class='add-t-c-span'>Item</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php } ?>
    </div>
</section>

<!-- Size Guide Overlay HTML -->
<div class="size-guide-overlay" id="sizeGuideOverlay">
    <div class="size-guide-container">
        <div class="size-guide-header">
            <h3 class="size-guide-title">Size Guide</h3>
            <button class="close-guide-btn" id="closeGuideBtn">&times;</button>
        </div>

        <div class="size-guide-content">
            <div class="guide-tabs">
                <button class="guide-tab active" data-tab="clothing">Clothing</button>
                <button class="guide-tab" data-tab="footwear">Footwear</button>
                <button class="guide-tab" data-tab="waist">Waist</button>
            </div>

            <div class="size-tables">
                <!-- Clothing Sizes Table -->
                <table class="size-table active" id="clothing-table">
                    <thead>
                        <tr>
                            <th>Size</th>
                            <th>Chest (in)</th>
                            <th>Waist (in)</th>
                            <th>Hip (in)</th>
                            <th>Intl. Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>XS</td>
                            <td>32-34</td>
                            <td>26-28</td>
                            <td>34-36</td>
                            <td>UK 6 / US 2</td>
                        </tr>
                        <tr>
                            <td>S</td>
                            <td>35-37</td>
                            <td>29-31</td>
                            <td>37-39</td>
                            <td>UK 8 / US 4</td>
                        </tr>
                        <tr>
                            <td class="highlight-cell">M</td>
                            <td class="highlight-cell">38-40</td>
                            <td class="highlight-cell">32-34</td>
                            <td class="highlight-cell">40-42</td>
                            <td class="highlight-cell">UK 10 / US 6</td>
                        </tr>
                        <tr>
                            <td>L</td>
                            <td>41-43</td>
                            <td>35-37</td>
                            <td>43-45</td>
                            <td>UK 12 / US 8</td>
                        </tr>
                        <tr>
                            <td>XL</td>
                            <td>44-46</td>
                            <td>38-40</td>
                            <td>46-48</td>
                            <td>UK 14 / US 10</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Footwear Sizes Table -->
                <table class="size-table" id="footwear-table">
                    <thead>
                        <tr>
                            <th>US</th>
                            <th>UK</th>
                            <th>EU</th>
                            <th>CM</th>
                            <th>IN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>5</td>
                            <td>3</td>
                            <td>35.5</td>
                            <td>22</td>
                            <td>8.66</td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td>4</td>
                            <td>37</td>
                            <td>23.5</td>
                            <td>9.25</td>
                        </tr>
                        <tr>
                            <td class="highlight-cell">7</td>
                            <td class="highlight-cell">5</td>
                            <td class="highlight-cell">38</td>
                            <td class="highlight-cell">24.1</td>
                            <td class="highlight-cell">9.5</td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td>6</td>
                            <td>39.5</td>
                            <td>25</td>
                            <td>9.84</td>
                        </tr>
                        <tr>
                            <td>9</td>
                            <td>7</td>
                            <td>41</td>
                            <td>26</td>
                            <td>10.24</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Waist Sizes Table -->
                <table class="size-table" id="waist-table">
                    <thead>
                        <tr>
                            <th>Waist (in)</th>
                            <th>US Size</th>
                            <th>UK Size</th>
                            <th>EU Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>28</td>
                            <td>6</td>
                            <td>8</td>
                            <td>36</td>
                        </tr>
                        <tr>
                            <td>30</td>
                            <td>8</td>
                            <td>10</td>
                            <td>38</td>
                        </tr>
                        <tr>
                            <td class="highlight-cell">32</td>
                            <td class="highlight-cell">10</td>
                            <td class="highlight-cell">12</td>
                            <td class="highlight-cell">40</td>
                        </tr>
                        <tr>
                            <td>34</td>
                            <td>12</td>
                            <td>14</td>
                            <td>42</td>
                        </tr>
                        <tr>
                            <td>36</td>
                            <td>14</td>
                            <td>16</td>
                            <td>44</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="measurement-help">
                <h3 class="measurement-title">How to Measure</h3>
                <div class="measurement-steps">
                    <div class="measurement-step">
                        <p><span class="step-number">1</span> <strong>Chest:</strong> Measure around the fullest part of your chest, keeping the tape horizontal.</p>
                    </div>
                    <div class="measurement-step">
                        <p><span class="step-number">2</span> <strong>Waist:</strong> Measure around your natural waistline, keeping one finger between the tape and your body.</p>
                    </div>
                    <div class="measurement-step">
                        <p><span class="step-number">3</span> <strong>Hips:</strong> Measure around the fullest part of your hips, about 8 inches below your waist.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Change main product image
    function changeImage(element) {
        const mainImage = document.getElementById('mainImage');
        mainImage.src = element.src;

        // Update active thumbnail
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        element.classList.add('active');
    }

    // Select color option
    function selectColor(element) {
        document.querySelectorAll('.color').forEach(color => {
            color.classList.remove('selected');
        });
        element.classList.add('selected');
    }


    // Update quantity
    const quantityInput = document.querySelector('.quantity-input');

    function adjustQuantity(change) {
        const maxStock = parseInt(quantityInput.getAttribute('data-max-stock')) || 1;
        let newValue = parseInt(quantityInput.value) + change;

        if (newValue < 1) {
            newValue = 1;
        } else if (newValue > maxStock) {
            newValue = maxStock;
        }

        quantityInput.value = newValue;
    }

    quantityInput.addEventListener('input', function() {
        const maxStock = parseInt(this.getAttribute('data-max-stock')) || 1;
        let value = parseInt(this.value) || 1;

        if (value > maxStock) {
            this.value = maxStock;
        } else if (value < 1) {
            this.value = 1;
        }
    });

    // Delivery Toggle
    document.querySelectorAll('.faq-item .delivery-row').forEach(item => {
        item.addEventListener('click', () => {
            const parent = item.parentElement;
            const isActive = parent.classList.contains('active');

            // Close all other items
            document.querySelectorAll('.faq-item').forEach(el => {
                if (el !== parent) el.classList.remove('active');
            });

            // Toggle current item
            parent.classList.toggle('active', !isActive);
        });
    });

    // Tab functionality
    function openTab(tabName, event) {
        const tabContents = document.querySelectorAll('.item-tab-content');
        const tabButtons = document.querySelectorAll('.item-tab-btn');

        // Hide all tab contents
        tabContents.forEach(content => content.classList.remove('active'));

        // Reset button styles
        tabButtons.forEach(button => {
            button.classList.remove('border-b-2', 'border-orange-500', 'text-orange-600', 'opened');
            button.classList.add('text-gray-600');
        });

        // Show selected tab content
        document.getElementById(tabName).classList.add('active');

        // Style the clicked button
        event.currentTarget.classList.add('border-b-2', 'border-orange-500', 'text-orange-600', 'opened');
        event.currentTarget.classList.remove('text-gray-600');
    }


    // Wishlist toggle
    document.querySelector('.wishlist').addEventListener('click', function() {
        const icon = this.querySelector('i');
        if (icon.classList.contains('far')) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            this.style.color = 'red';
            this.style.borderColor = 'red';
        } else {
            icon.classList.remove('fas');
            icon.classList.add('far');
            this.style.color = '';
            this.style.borderColor = '';
        }
    });
</script>


<?php
include($IPATH_INCLUDES . "footer.php"); ?>
<!-- Link your JS file -->
<script src="/js/item.js"></script>
</body>

</html>