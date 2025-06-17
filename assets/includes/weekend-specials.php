<!-- ----------------------------------- CAROUSEL SECTION : WEEKEND SPECIALS -->
<?php
// Check if today is Saturday (6) or Sunday (7)
$isWeekend = in_array(date('N'), [6, 7]);
$isWeekend = 6;
$carouselDisplay = $isWeekend ? 'block' : 'none';

// Check highlighted_listing
$highlightedItem = isset($row['highlighted_listing']) && $row['highlighted_listing'] == 1 ? 'block' : 'none';
?>
<div class="carousel" style="display: <?php echo $carouselDisplay; ?>;">
    <div class="line-with-text">
        <h2 class="text">Weekend Specials</h2>
        <div class="line"></div>
    </div>
    <div class="list" id="carouselList">
        <?php
        if ($isWeekend) {
            try {
                $weekendSpecialCMD = $pdo->prepare("
                    SELECT p.*, pe.weekend_special, pe.bold_title, pe.highlighted_listing
                    FROM product p
                    INNER JOIN product_enhancements pe ON p.product_id = pe.product_id
                    WHERE pe.weekend_special = 1 AND pe.status = 'active'
                ");
                $weekendSpecialCMD->execute();
                $weekendItems = $weekendSpecialCMD->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($weekendItems)) {
                    foreach ($weekendItems as $row) {
                        $saleprice = $row['Price'] - ($row['Price'] * ($row['discount'] / 100));
                        $formattedPrice = number_format($row['Price'], 2, '.', '');
                        list($whole, $decimal) = explode('.', $formattedPrice);
                        $formattedSalePrice = number_format($saleprice, 2, '.', '');
                        list($wholeSale, $decimalSale) = explode('.', $formattedSalePrice);


                        $highlightedItem = isset($row['highlighted_listing']) && $row['highlighted_listing'] == 1 ? 'block' : 'none';
        ?>
                        <div class="items">
                            <div class="wasPrice">
                                <p>Weekend Special</p>
                            </div>
                            <div class="item">
                                <i class="ph-fill ph-fire" style="display: <?php echo $highlightedItem; ?>;" title="Highlighted Listing"></i>
                                <a href="/item/<?php echo $row['product_id']; ?>">
                                    <div class="i-img">
                                        <img loading="lazy" src="/uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['ProductName']); ?>">
                                    </div>
                                    <div class="seperator"></div>
                                    <?php if (isset($row['bold_title']) && $row['bold_title'] == 1) { ?>
                                        <h2 class="item-name"><?php echo htmlspecialchars($row['ProductName']); ?></h2>
                                    <?php } else { ?>
                                        <p class="item-name"><?php echo htmlspecialchars($row['ProductName']); ?></p>
                                    <?php } ?>
                                </a>
                                <span class="seller-link">
                                    <div class="seller_name" style="display: <?php echo !empty($row['b_name'] ?? '') ? 'block' : 'none'; ?>;">
                                        <span><?php echo htmlspecialchars($row['b_name'] ?? ''); ?></span>
                                        <i class="ph-fill ph-seal-check"></i>
                                    </div>
                                    <div class="seller-info-board">
                                        <div class="seller-info-content">
                                            <h4><?php echo htmlspecialchars($row['b_name'] ?? ''); ?></h4>
                                            <div class="store-dtls">
                                                <p><i class="ph ph-map-pin"></i> <?php echo htmlspecialchars($row['location'] ?? 'Not specified'); ?></p>
                                                <p><i class="ph ph-storefront"></i> Seller ID: <?php echo htmlspecialchars($row['seller_id']); ?></p>
                                                <p><i class="ph ph-clock"></i> Joined: <?php echo date('M Y', strtotime($row['created_at'] ?? 'now')); ?></p>
                                                <?php if (($row['account_plan'] ?? 'Free Plan') === 'Pro Plan' && $row['active'] == 1) { ?>
                                                    <div class="board-footer">
                                                        <a href="/storeprofile/<?php echo urlencode($row['b_name']) . '/' . $row['seller_id']; ?>" class="store-link">Visit Store</a>
                                                        <i class="ph ph-arrow-right store--icon"></i>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </span>
                                <div class="i-dtls">
                                    <div>
                                        <p class="item-color"><strong>Brand:</strong> <?php echo htmlspecialchars($row['brandName'] ?? ''); ?></p>
                                    </div>
                                    <div>
                                        <p class="item-color"><strong>Condition:</strong> <?php echo htmlspecialchars($row['condition'] ?? 'Not set'); ?></p>
                                        <span class="dot-seperator">•</span>
                                        <p class="item-color"><strong>Color:</strong> <?php echo htmlspecialchars($row['color'] ?? ''); ?></p>
                                    </div>
                                </div>
                                <div class="price-wrapper">
                                    <p class="item-price">
                                        <span class="amount">R<?php echo number_format($wholeSale, 0, '.', ','); ?></span>
                                        <span class="decimal"><?php echo $decimalSale; ?></span>
                                    </p>
                                    <?php if ($row['discount'] != 0) { ?>
                                        <p class="item-price actual">
                                            <span class="amount">R<?php echo number_format($whole, 0, '.', ','); ?></span>
                                            <span class="decimal"><?php echo $decimal; ?></span>
                                        </p>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php
                            $buyPrice = $wholeSale;
                            if ($row['stock_qnty'] > 0 && !in_array($row['category_id'], [4, 5, 11])) { ?>
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
                                        Add <span class="add-t-c-span">To Cart</span>
                                    </button>
                                </div>
                            <?php } elseif ($row['stock_qnty'] > 0 && in_array($row['category_id'], [4, 5, 11])) { ?>
                                <div class="i-btn">
                                    <button id="btn_<?php echo $row['product_id']; ?>" onclick="viewItem(<?php echo $row['product_id']; ?>)">
                                        <span class="btn-loader" style="display: none;"></span>
                                        <i class="ph ph-arrow-square-out"></i>
                                        View <span class="add-t-c-span">Item</span>
                                    </button>
                                </div>
                            <?php } ?>
                        </div>
                    <?php
                    }
                } else {
                    ?>
                    <span class="no-data">
                        <h1 class="text">No weekend special items...</h1>
                    </span>
        <?php
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                echo '<p>Error loading Weekend Specials.</p>';
            }
        }
        ?>
    </div>
</div>

<?php if ($isWeekend) { ?>
    <script>
        // Duplicate items for seamless scrolling
        const list = document.getElementById('carouselList');
        const items = list.getElementsByClassName('items');

        // Pause/resume animation on hover
        const carousel = document.querySelector('.carousel');
        carousel.addEventListener('mouseenter', () => {
            list.style.animationPlayState = 'paused';
            const clonedList = carousel.querySelector('.cloned');
            if (clonedList) clonedList.style.animationPlayState = 'paused';
        });
        carousel.addEventListener('mouseleave', () => {
            list.style.animationPlayState = 'running';
            const clonedList = carousel.querySelector('.cloned');
            if (clonedList) clonedList.style.animationPlayState = 'running';
        });
    </script>
<?php } ?>