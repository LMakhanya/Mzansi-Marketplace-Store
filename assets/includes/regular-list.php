<section id="list">
    <?php if (empty($data)): ?>
        <div class="empty-message-container">
            <img src="/assets/icons/empty_list.png" alt="No products" class="empty-image">
            <p class="empty-mssg">No products found matching your criteria.</p>
            <p class="empty-subtext">Try adjusting your filters or searching for something else.</p>
            <a href="/shop" class="empty-btn">Back to Shop</a>
        </div>
    <?php else: ?>
        <div class="i-wraper">
            <?php foreach ($data as $storePRow):
                $salePrice = $storePRow['Price'] * (1 - ($storePRow['discount'] / 100));
                $formattedPrice = number_format($storePRow['Price'], 2, '.', '');
                list($whole, $decimal) = explode('.', $formattedPrice);
                $formattedSalePrice = number_format($salePrice, 2, '.', '');
                list($wholeSale, $decimalSale) = explode('.', $formattedSalePrice);
            ?>
                <div class="items">
                    <?php if ($storePRow['discount'] > 0): ?>
                        <div class="wasPrice">
                            <p>
                                <?php echo (int)$storePRow['discount']; ?>% Off
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="item">
                        <a href="/item/<?php echo $storePRow['product_id']; ?>">
                            <div class="i-img">
                                <img loading="lazy" src="/uploads/<?php echo $storePRow['product_image']; ?>" alt="<?php echo $storePRow['ProductName']; ?>">
                            </div>
                            <div class="seperator"></div>
                            <p class="item-name"><?php echo $storePRow['ProductName'] ?></p>
                        </a>
                        <span class="seller-link">
                            <div class="seller_name">
                                <span><?php echo htmlspecialchars($storePRow['b_name']); ?></span>
                                <i class="ph-fill ph-seal-check"></i>
                            </div>
                            <div class="seller-info-board">
                                <div class="seller-info-content">
                                    <h4><?php echo htmlspecialchars($storePRow['b_name']); ?></h4>
                                    <div class="store-dtls">
                                        <p><i class="ph ph-map-pin"></i> <?php echo htmlspecialchars($storePRow['location'] ?? 'Not specified'); ?></p>
                                        <p><i class="ph ph-storefront"></i> Seller ID: <?php echo htmlspecialchars($storePRow['seller_id']); ?></p>
                                        <p><i class="ph ph-clock"></i> Joined: <?php echo date('M Y', strtotime($storePRow['created_at'] ?? 'now')); ?></p>

                                        <?php if ($storePRow['account_plan'] === 'Pro Plan' && $storePRow['active'] == 1): ?>
                                            <div class="board-footer">
                                                <a href="/storeprofile/<?php echo urlencode($storePRow['b_name']) . '/' . $storePRow['seller_id']; ?>" class="store-link">Visit Store</a>
                                                <i class="ph ph-arrow-right store--icon"></i>
                                            </div>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </div>
                        </span>

                        <div class="i-dtls">
                            <div>
                                <p class="item-color"><strong>Brand:</strong> <?php echo $storePRow['brandName'] ?> </p>
                            </div>
                            <div>
                                <p class="item-color"><strong>Condition : </strong><?php echo $storePRow['condition'] ?? 'Not set' ?> </p>
                                <span class="dot-seperator">•</span>
                                <p class="item-color"><strong>Color: </strong><?php echo $storePRow['color'] ?? '' ?> </p>
                            </div>
                        </div>
                        <div class="price-wrapper">
                            <p class="item-price"> <span class="amount">R<?php echo number_format($wholeSale, 0, '.', ','); ?></span><span class="decimal"><?php echo $decimalSale; ?></span></p>
                            <?php if ($storePRow['discount'] != 0) { ?>
                                <p class="item-price actual"> <span class="amount">R<?php echo number_format($whole, 0, '.', ','); ?></span><span class="decimal"><?php echo $decimal; ?></span></p>
                            <?php } ?>
                        </div>
                    </div>
                    <?php
                    $buyPrice = $wholeSale;
                    if ($storePRow['stock_qnty'] > 0 && !in_array($storePRow['category_id'], [4, 5, 11])): ?>
                        <div class="i-btn">
                            <button id="btn_<?php echo $storePRow['product_id']; ?>" onclick="addToCart(
                            <?php echo $storePRow['product_id']; ?>, 
                            '<?php echo htmlspecialchars($storePRow['ProductName']); ?>', 
                            '<?php echo htmlspecialchars($storePRow['Model']); ?>', 
                            <?php echo $buyPrice; ?>, 
                            '<?php echo htmlspecialchars($storePRow['product_image']); ?>', 
                            '<?php echo htmlspecialchars($storePRow['seller_id']); ?>'
                            )">
                                <span class="btn-loader" style="display: none;"></span>
                                <i class="ph ph-shopping-cart-simple"></i>
                                Add <span class='add-t-c-span'>To Cart</span>
                            </button>
                        </div>
                    <?php endif;
                    if ($storePRow['stock_qnty'] > 0 && in_array($storePRow['category_id'], [4, 5, 11])): ?>
                        <div class="i-btn">
                            <button id="btn_<?php echo $storePRow['product_id']; ?>" onclick="viewItem(
                            <?= $storePRow['product_id']; ?>
                            )">
                                <span class="btn-loader" style="display: none;"></span>
                                <i class="ph ph-arrow-square-out"></i>
                                View <span class='add-t-c-span'>Item</span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            $queryParams = array_filter($_GET, fn($key) => !in_array($key, ['category', 'subcat']), ARRAY_FILTER_USE_KEY);
            $queryParams['limit'] = $limit;

            if ($page > 1):
                $prevParams = array_merge($queryParams, ['page' => $page - 1]);
            ?>
                <a href="<?php echo $href . '?' . http_build_query($prevParams); ?>" class="prev">Previous</a>
            <?php endif; ?>

            <?php
            $range = 2;
            $showFirst = $page > $range + 1;
            $showLast = $page < $total_pages - $range;

            if ($showFirst): ?>
                <a href="<?php echo $href . '?' . http_build_query(array_merge($queryParams, ['page' => 1])); ?>"
                    class="page">1</a>
                <?php if ($page > $range + 2): ?>
                    <span class="ellipsis">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++):
                $pageParams = array_merge($queryParams, ['page' => $i]);
            ?>
                <a href="<?php echo $href . '?' . http_build_query($pageParams); ?>"
                    class="page <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php
            if ($showLast):
                if ($page < $total_pages - $range - 1): ?>
                    <span class="ellipsis">...</span>
                <?php endif; ?>
                <a href="<?php echo $href . '?' . http_build_query(array_merge($queryParams, ['page' => $total_pages])); ?>"
                    class="page">
                    <?php echo $total_pages; ?>
                </a>
            <?php endif; ?>

            <?php
            if ($page < $total_pages):
                $nextParams = array_merge($queryParams, ['page' => $page + 1]);
            ?>
                <a href="<?php echo $href . '?' . http_build_query($nextParams); ?>" class="next">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>