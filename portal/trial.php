<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Navigation -->
    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/portal/assets/includes/common-meta.html';
    ?>
    <title>Free Trial | The Mzansi Marketplace</title>

    <meta name="description" content="Sign up for a free trial on The Mzansi Marketplace, South Africa’s fastest-growing online marketplace. Sell products or recruit sellers with no risk!">
    <meta name="keywords" content="Mzansi Marketplace, free trial, sell online, Mzansi Agent, South Africa marketplace, e-commerce, online business, affiliate program">
    <meta name="robots" content="index, follow">
    <meta name="author" content="The Mzansi Marketplace">
    <title>Free Trial | The Mzansi Marketplace</title>
    <link rel="icon" type="image/png" href="/assets/images/themzansi_logo.png" sizes="32x32">
    <link rel="canonical" href="https://themzansimarketplace.co.za/portal/trial.php">
    <meta property="og:title" content="Free Trial | The Mzansi Marketplace">
    <meta property="og:description" content="Try The Mzansi Marketplace for free! Sell products or recruit sellers on South Africa’s fastest-growing online marketplace with no risk.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://themzansimarketplace.co.za/portal/trial.php">
    <meta property="og:image" content="https://themzansimarketplace.co.za/assets/images/themzansi_logo.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="The Mzansi Marketplace">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Free Trial - Mzansi Marketplace">
    <meta name="twitter:description" content="Join The Mzansi Marketplace with a free trial. Sell online or earn commissions as an agent in South Africa’s top marketplace.">
    <meta name="twitter:image" content="https://themzansimarketplace.co.za/assets/images/themzansi_logo.png">
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebPage",
            "name": "Free Trial",
            "description": "Sign up for a free trial to sell products or recruit sellers on The Mzansi Marketplace, South Africa's fastest-growing online marketplace.",
            "url": "https://themzansimarketplace.co.za/portal/trial.php",
            "publisher": {
                "@type": "Organization",
                "name": "The Mzansi Marketplace",
                "logo": {
                    "@type": "ImageObject",
                    "url": "https://themzansimarketplace.co.za/assets/images/themzansi_logo.png",
                    "width": 1200,
                    "height": 630
                }
            },
            "offers": {
                "@type": "Offer",
                "name": "Free Trial",
                "description": "Free trial to access The Mzansi Marketplace for selling products or recruiting sellers.",
                "availability": "https://schema.org/InStock",
                "price": "0",
                "priceCurrency": "ZAR"
            }
        }
    </script>

    <link rel="stylesheet" href="/portal/assets/css/tial.css">

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
            <h1>Our Free Month Trial</h1>
            <p>Experience the future of online selling with our <strong>Pro Plan</strong>. Sign up today for a no-risk, 1-month
                free trial and discover why sellers trust us to grow their businesses. No credit card
                required—just pure opportunity.</p>
            <span class="hero-button" onclick="selectPlan('Pro Plan', 'R350/month', 'register','true')">
                Start Your Free Trial</span>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <p class="section-subtitle">Free-Month-Trial Benefits</p>
                <h2 class="section-heading">Why Become a Mzansi Agent?</h2>
            </div>
            <div class="card-grid">
                <!-- Benefit 1 -->
                <div class="card">
                    <div class="card-icon purple">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Advanced Analytics</h3>
                    <p>Gain insights into your sales and customer behavior with real-time data at your fingertips.</p>
                </div>

                <!-- Benefit 2 -->
                <div class="card">
                    <div class="card-icon green">
                        <i class="fa-solid fa-box"></i>
                    </div>
                    <h3>Unlimited Listings</h3>
                    <p>Showcase as many products as you want, from fashion to electronics, with no restrictions.</p>
                </div>

                <!-- Benefit 3 -->
                <div class="card">
                    <div class="card-icon orange">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <h3>Dedicated Support</h3>
                    <p>Our team is available 24/7 to assist you every step of the way, ensuring your success.</p>
                </div>
            </div>
        </div>
    </section>
    <div class="cta-container">
        <button class="cta-button now" onclick="selectPlan('Pro Plan', 'R350/month', 'register','true')">Start
            Your Free Month Trial</button>
    </div>

    <section class="section bg-white">
        <div class="container">
            <div class="section-title">
                <p class="section-subtitle">Testimonials</p>
            </div>
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <p>"The Free-Month-Trial gave me the confidence to scale my business. The tools and support are
                        unmatched!"</p>
                    <span>— Luvuyo M., Mzansi Store</span>
                </div>
                <div class="testimonial-card">
                    <p>"I doubled my sales within weeks. The free trial was a game-changer for my small business."
                    </p>
                    <span>— Nandipha M., Ndiphas Gedgets</span>
                </div>
                <div class="testimonial-card">
                    <p>"No hidden fees, just results. This marketplace is perfect for startups like mine."</p>
                    <span>— Lerato N., Artisan</span>
                </div>
            </div>
        </div>
    </section>

    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/portal/assets/includes/footer.php';
    ?>
</body>

</html>