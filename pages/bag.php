<?php
if (!$_POST['valid']) {
    header("Location: /");
    exit();
}

$IPATH_INCLUDES = $_SERVER["DOCUMENT_ROOT"] . "/assets/includes/";
?>
<section class="bag-section">
    <!-- Header -->
    <div class="header">
        <h1>Your Shopping Cart</h1>
        <div class="cart-badge" id="cart-badge">
            <i class="ph ph-user" style="margin-right: 4px;"></i> <span id="totalSellers">0</span> seller(s)
        </div>
    </div>

    <!-- Cart Content -->
    <div class="cart-content" id="bagItems">
    </div>
</section>

<div id="loader-overlay" class="loader-overlay"></div>
<div id="loader" class="loader"></div>

<?php include($IPATH_INCLUDES . "footer.php"); ?>

<!-- Link your JS file -->
<script src="/js/cart.js"></script>
<script src="/js/bag.js"></script>

</body>

</html>