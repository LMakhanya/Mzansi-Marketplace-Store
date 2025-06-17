<div class="i-wraper">
    <?php
    // Assuming $pdo is already initialized
    $cmd = $pdo->prepare("SELECT product_id FROM product_enhancements WHERE STATUS = 'active'");
    $cmd->execute();
    $enhancedItemsId = $cmd->fetchAll(PDO::FETCH_COLUMN); // Fetch only product_id column

    $enhancedItem = [];

    if (!empty($enhancedItemsId)) {
        // Use IN clause to fetch all matching products
        $placeholders = implode(',', array_fill(0, count($enhancedItemsId), '?'));
        $enhancedItemStmt = $pdo->prepare("SELECT * FROM product WHERE product_id IN ($placeholders)");
        $enhancedItemStmt->execute($enhancedItemsId);
        $enhancedItem = $enhancedItemStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Merge suggested and enhanced items
    $totalSuggestedItems = array_merge($suggested, $enhancedItem);

    // Remove duplicates based on product_id
    $uniqueItems = [];
    foreach ($totalSuggestedItems as $item) {
        $uniqueItems[$item['product_id']] = $item; // Overwrites duplicate keys, keeping only unique items
    }

    // Convert back to indexed array
    $totalSuggestedItems = array_values($uniqueItems);

    foreach ($totalSuggestedItems as $row) {
        $saleprice = $row['Price'] - ($row['Price'] * ($row['discount'] / 100));

        $price = $row['Price']; // Example price
        $formattedPrice = number_format($price, 2, '.', ''); // Ensure two decimal places
        list($whole, $decimal) = explode('.', $formattedPrice); // Split into whole and decimal parts

        // echo $row['ProductName'] . ' ' .  $row['product_id'] . '<br>';

        $pricesale = $saleprice; // Discounted price
        $formattedSalePrice = number_format($pricesale, 2, '.', ''); // Ensure two decimal places
        list($wholeSale, $decimalSale) = explode('.', $formattedSalePrice); // Split into whole and decimal parts

    ?>
        <div class="items">
            <?php if ($row['discount'] != 0) { ?>
                <div class="wasPrice">
                    <p><?php echo $row['discount'] ?? 0; ?>% Off</p>
                </div>
            <?php } ?>

            <div class="item">
                <a href="/item/<?php echo $row['product_id']; ?>">
                    <div class="i-img">
                        <img loading="lazy" src="/uploads/<?php echo $row['product_image']; ?>" alt="<?php echo $row['ProductName']; ?>">
                    </div>
                    <div class="seperator"></div>
                    <p class="item-name"><?php echo $row['ProductName'] ?? '' ?></p>
                </a>
                <span class="seller-link">
                    <div class="seller_name" style="display: <?php echo !empty($row['b_name'] ?? '') ? 'block' : 'none'; ?>;">
                        <span><?php echo htmlspecialchars($row['b_name'] ?? ''); ?></span>
                        <i class="ph-fill ph-seal-check"></i>
                    </div>
                    <div class=" seller-info-board">
                        <div class="seller-info-content">
                            <h4><?php echo htmlspecialchars($row['b_name'] ?? ''); ?></h4>
                            <div class="store-dtls">
                                <p><i class="ph ph-map-pin"></i> <?php echo htmlspecialchars($row['location'] ?? 'Not specified'); ?></p>
                                <p><i class="ph ph-storefront"></i> Seller ID: <?php echo htmlspecialchars($row['seller_id']); ?></p>
                                <p><i class="ph ph-clock"></i> Joined: <?php echo date('M Y', strtotime($row['created_at'] ?? 'now')); ?></p>

                                <?php if ($row['account_plan'] ?? 'Free Plan'  === 'Pro Plan' && $row['active'] == 1): ?>
                                    <div class="board-footer">
                                        <a href="/storeprofile/<?php echo urlencode($row['b_name']) . '/' . $row['seller_id']; ?>" class="store-link">Visit Store</a>
                                        <i class="ph ph-arrow-right store--icon"></i>
                                    </div>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                </span>

                <div class="i-dtls">
                    <div>
                        <p class="item-color"><strong>Brand:</strong> <?php echo $row['brandName'] ?? ''; ?> </p>
                    </div>
                    <div>
                        <p class="item-color"><strong>Condition : </strong><?php echo $row['condition'] ?? 'Not set' ?> </p>
                        <span class="dot-seperator">•</span>
                        <p class="item-color"><strong>Color: </strong><?php echo $row['color'] ?? '' ?> </p>
                    </div>
                </div>
                <div class="price-wrapper">
                    <p class="item-price"> <span class="amount">R<?php echo number_format($wholeSale, 0, '.', ','); ?></span><span class="decimal"><?php echo $decimalSale; ?></span></p>
                    <?php if ($row['discount'] != 0) { ?>
                        <p class="item-price actual"> <span class="amount">R<?php echo number_format($whole, 0, '.', ','); ?></span><span class="decimal"><?php echo $decimal; ?></span></p>
                    <?php } ?>
                </div>
            </div>
            <?php
            $buyPrice = $wholeSale;

            if ($row['stock_qnty'] > 0 && !in_array($row['category_id'], [4, 5, 11])): ?>
                <div class="i-btn">
                    <button id="btn_<?php echo $row['product_id']; ?>" onclick="addToCart(
                                <?php echo $row['product_id']; ?>, 
                                `<?php echo htmlspecialchars($row['ProductName'] ?? ''); ?>`, 
                                `<?php echo htmlspecialchars($row['Model'] ?? ''); ?>`, 
                                <?php echo $buyPrice; ?>, 
                                `<?php echo htmlspecialchars($row['product_image'] ?? ''); ?>`, 
                                `<?php echo htmlspecialchars($row['seller_id'] ?? ''); ?>`)">
                        <span class="btn-loader" style="display: none;"></span>
                        <i class="ph ph-shopping-cart-simple"></i>
                        Add <span class='add-t-c-span'>To Cart</span>
                    </button>
                </div>
            <?php endif;
            if ($row['stock_qnty'] > 0 && in_array($row['category_id'], [4, 5, 11])): ?>
                <div class="i-btn">
                    <button id="btn_<?php echo $row['product_id']; ?>" onclick="viewItem(
                                <?= $row['product_id']; ?>
                                )">
                        <span class="btn-loader" style="display: none;"></span>
                        <i class="ph ph-arrow-square-out"></i>
                        View <span class='add-t-c-span'>Item</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php } ?>
</div>