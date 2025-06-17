<?php
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

<body>
    <section>
        <!-- Featured Categories -->
        <div class="featured-categories">
            <div class="category-card">
                <a href="/category/Phones">
                    <div class="category-image">
                        <img loading="lazy" src="/assets/images/display/Phones.png" alt="Phones category">
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
                <a href="/category/Laptops">
                    <div class="category-image">
                        <img loading="lazy" src="/assets/images/display/Laptops.png" alt="Laptops category">
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
                <a href="/category/Electronics">
                    <div class="category-image">
                        <img loading="lazy" src="/assets/images/display/Electronics.png" alt="Electronics category">
                    </div>
                    <div class="category-content">
                        <h3 class="category-title">Electronics</h3>
                        <div class="c-c-combo">
                            <p class="category-count"><?php echo $categoryCounts[1] ?? 0 ?> items</p>
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

            <div class="category-card">
                <a href="/category/Home & Garden">
                    <div class="category-image">
                        <img loading="lazy" src="/assets/images/display/Home & Garden.png" alt="Home & Garden category">
                    </div>
                    <div class="category-content">
                        <h3 class="category-title">Home & Garden</h3>
                        <div class="c-c-combo">
                            <p class="category-count"><?php echo $categoryCounts[2] ?? 0 ?> items</p>
                            <i class="ph ph-caret-right"></i>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="category-card">
                <a href="/category/Books & Education">
                    <div class="category-image">
                        <img loading="lazy" src="/assets/images/display/Books & Education.png" alt="Books category">
                    </div>
                    <div class="category-content">
                        <h3 class="category-title">Books & Education</h3>
                        <div class="c-c-combo">
                            <p class="category-count"><?php echo $categoryCounts[9] ?? 0 ?> items</p>
                            <i class="ph ph-caret-right"></i>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="category-card">
                <a href="/category/Shoes">
                    <div class="category-image">
                        <img loading="lazy" src="/assets/images/display/Shoes.png" alt="Shoes category">
                    </div>
                    <div class="category-content">
                        <h3 class="category-title">Shoes</h3>
                        <div class="c-c-combo">
                            <p class="category-count"><?php echo $categoryCounts[4] ?? 0 ?> items</p>
                            <i class="ph ph-caret-right"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </section>
    <?php
    include($IPATH_INCLUDES . "footer.php"); ?>
</body>

</html>