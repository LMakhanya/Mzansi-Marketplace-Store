<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Navigation -->
    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/portal/assets/includes/common-meta.html';
    ?>
    <meta name="description" content="Become a seller on The Mzansi Marketplace, South Africa’s fastest-growing online marketplace. List your products, reach millions, and grow your business today!">
    <meta name="keywords" content="Mzansi Marketplace, become a seller, sell online, South Africa marketplace, list products, e-commerce, online business">
    <meta name="robots" content="index, follow">
    <meta name="author" content="The Mzansi Marketplace">
    <title>Become a Seller | The Mzansi Marketplace</title>
    <link rel="icon" type="image/png" href="/assets/images/themzansi_logo.png" sizes="32x32">
    <link rel="canonical" href="https://themzansimarketplace.co.za/portal/seller.php">
    <meta property="og:title" content="Become a Seller | The Mzansi Marketplace">
    <meta property="og:description" content="Join The Mzansi Marketplace as a seller. List your products and reach millions on South Africa’s fastest-growing online marketplace.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://themzansimarketplace.co.za/portal/seller.php">
    <meta property="og:image" content="https://themzansimarketplace.co.za/assets/images/themzansi_logo.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="The Mzansi Marketplace">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Become a Seller - Mzansi Marketplace">
    <meta name="twitter:description" content="Sell your products on The Mzansi Marketplace, South Africa’s top online marketplace. Join now and grow your business!">
    <meta name="twitter:image" content="https://themzansimarketplace.co.za/assets/images/themzansi_logo.png">
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebPage",
            "name": "Become a Seller",
            "description": "Join The Mzansi Marketplace as a seller to list your products and reach millions of customers on South Africa's fastest-growing online marketplace.",
            "url": "https://themzansimarketplace.co.za/portal/seller.php",
            "publisher": {
                "@type": "Organization",
                "name": "The Mzansi Marketplace",
                "logo": {
                    "@type": "ImageObject",
                    "url": "https://themzansimarketplace.co.za/assets/images/themzansi_logo.png",
                    "width": 1200,
                    "height": 630
                }
            }
        }
    </script>


    <link rel="stylesheet" href="/portal/assets/css/seller.css">

    <!-- Reesponsive -->
    <link rel="stylesheet" href="/portal/assets/css/responsive.css">
</head>

<body>
    <!-- Navigation -->
    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/portal/assets/includes/navigation.html';
    ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <h1>Launch Your Seller Journey</h1>
            <p>Build your business with a platform trusted by thousands of sellers.</p>
            <span class="hero-button"
                onclick="selectPlan('Pro Plan', 'R350/month', 'register')">Sign up
                Now</span>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="section bg-white">
        <div class="container">
            <div class="section-title">
                <p class="section-subtitle">Seller Benefits</p>
                <h2 class="section-heading">Why Sell on Us?</h2>
            </div>
            <div class="card-grid">
                <!-- Benefit 1 -->
                <div class="card">
                    <div class="card-icon orange">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Quick Setup</h3>
                    <p>Get your store live in just a few clicks.</p>
                </div>

                <!-- Benefit 2 -->
                <div class="card">
                    <div class="card-icon green">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Global Reach</h3>
                    <p>Connect with customers across the world.</p>
                </div>

                <!-- Benefit 3 -->
                <div class="card">
                    <div class="card-icon purple">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>Trusted Platform</h3>
                    <p>Safe transactions and dedicated support.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="section how-it-works" id="hiw">
        <div class="container">
            <div class="section-title">
                <h2 class="section-heading">Get Started in 4 Simple Steps</h2>
            </div>
            <div class="steps-grid">
                <div class="step">
                    <span class="step-number">1</span>
                    <h4>Sign Up</h4>
                    <p>Create your free seller account.</p>
                </div>
                <div class="step">
                    <span class="step-number">2</span>
                    <h4>Build Your Store</h4>
                    <p>Add products and personalize your shop.</p>
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    <h4>Go Live</h4>
                    <p>List your items and start selling.</p>
                </div>
                <div class="step">
                    <span class="step-number">4</span>
                    <h4>Earn Money</h4>
                    <p>Enjoy fast, secure payouts.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Seller Plans Section -->
    <section class="section plans" id="pricing">
        <div class="container">
            <h2 class="section-heading">Choose Your Seller Plan</h2>
            <div class="plans-grid">
                <!-- Free Plan -->
                <div class="plan-card">
                    <h3>📦 Free Plan</h3>
                    <p class="price">R0/month</p>
                    <ul>
                        <li><span>✅</span>
                            <p>Products: Max 50 listings</p>
                        </li>
                        <li><span>✅</span>
                            <p>Audience Reach: 50% visibility</p>
                        </li>
                        <li><span>✅</span>
                            <p>Commission: 10% per withdrawal</p>
                        </li>
                        <li><span>✅</span>
                            <p>Communication: Client-seller messaging</p>
                        </li>
                        <li><span>🚫</span>
                            <p>No premium support</p>
                        </li>
                        <li><span>🚫</span>
                            <p>No featured listings</p>
                        </li>
                        <li><span>🚫</span>
                            <p>No store website</p>
                        </li>
                        <li><span>🚫</span>
                            <p>No ads for visibility boost</p>
                        </li>
                        <li><span>🚫</span>
                            <p>Limited product insights</p>
                        </li>
                    </ul>
                    <button class="plan-button" aria-label="Start Free Plan"
                        onclick="selectPlan('Free Plan', 'R0/month', 'register')">Start for Free</button>
                </div>

                <!-- Basic Plan -->
                <div class="plan-card popular">
                    <h3>🚀 Basic Plan</h3>
                    <p class="price">R170/month</p>
                    <ul>
                        <li><span>✅</span>
                            <p>Products: Max 200 listings</p>
                        </li>
                        <li><span>✅</span>
                            <p>Audience Reach: 75% visibility</p>
                        </li>
                        <li><span>✅</span>
                            <p>Commission: 7% per withdrawal</p>
                        </li>
                        <li><span>✅</span>
                            <p>Communication: Client-seller messaging</p>
                        </li>
                        <li><span>✅</span>
                            <p>Priority customer support</p>
                        </li>
                        <li><span>✅</span>
                            <p>1 featured product per month</p>
                        </li>
                        <li><span>✅</span>
                            <p>Basic analytics (views & engagement)</p>
                        </li>
                        <li><span>✅</span>
                            <p>Option to display ads</p>
                        </li>
                        <li><span>🚫</span>
                            <p>No store website</p>
                        </li>
                    </ul>
                    <button class="plan-button" aria-label="Choose Basic Plan"
                        onclick="selectPlan('Basic Plan', 'R170/month', 'register')">Choose Basic</button>
                </div>

                <!-- Pro Plan -->
                <div class="plan-card">
                    <h3>🏆 Pro Plan</h3>
                    <p class="price">R350/month</p>
                    <ul>
                        <li><span>✅</span>
                            <p>Products: Unlimited listings</p>
                        </li>
                        <li><span>✅</span>
                            <p>Audience Reach: 100% visibility</p>
                        </li>
                        <li><span>✅</span>
                            <p>Commission: 5% per withdrawal</p>
                        </li>
                        <li><span>✅</span>
                            <p>Communication: Client-seller messaging</p>
                        </li>
                        <li><span>✅</span>
                            <p>24/7 premium support</p>
                        </li>
                        <li><span>✅</span>
                            <p>5 featured products per month</p>
                        </li>
                        <li><span>✅</span>
                            <p>Advanced analytics & insights</p>
                        </li>
                        <li><span>✅</span>
                            <p>Advertising boost for products</p>
                        </li>
                        <li><span>✅</span>
                            <p>Custom store website</p>
                        </li>
                    </ul>
                    <button class="plan-button" aria-label="Go Pro Plan"
                        onclick="selectPlan('Pro Plan', 'R350/month', 'register')">Go Pro</button>
                </div>
            </div>
        </div>
    </section>

    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/portal/assets/includes/footer.php';
    ?>
</body>

</html>