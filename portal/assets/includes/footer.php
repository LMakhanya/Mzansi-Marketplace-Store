<footer class="footer">
    <div class="footer-row w-pt">
        <div class="footer-col">
            <h4>Mzansi Shop</h4>
            <ul class="links">
                <li><a href="/portal/">Buying / Orders</a></li>
                <li><a href="/portal/sellers.php">Sell with Us</a></li>
                <li><a href="/portal/agents.php">Become an Agent</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Info</h4>
            <ul class="links">
                <li><a href="/portal/">Our Portal</a></li>
                <li><a href="/portal/#agents">About Agent</a></li>
                <li><a href="/portal/#sellers">About Sellers</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Legal</h4>
            <ul class="links">
                <li><a href="/portal/legal/" target="_blank">Customer Agreement</a></li>
                <li><a href="/portal/legal/" target="_blank">Privacy Policy</a></li>
                <li><a href="/portal/legal/" target="_blank">Terms & Conditions</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Newsletter</h4>
            <p>
                Subscribe to our newsletter for a weekly dose
                of news, updates, helpful tips, and
                exclusive offers.
            </p>
            <form method="POST" action="/api/subscribe.php">
                <input name="email" type="text" placeholder="Write your email" required>
                <button name="subscribe"><i class="fa-regular fa-paper-plane"></i></button>
            </form>
            <div class="icons">
                <a href="https://www.facebook.com/profile.php?id=61574846430721&mibextid=ZbWKwL" target="_blank"><i
                        class="fa-brands fa-facebook-f"></i></a>
                <a href="https://www.linkedin.com/in/luvuyo-makhanya-97a46a217" target="_blank"><i
                        class="fa-brands fa-linkedin"></i></a>
                <a href=" https://wa.me/27695352229?text=Hi.%20I'm%20writing%20from%20The%20Mzansi%20Marketplace%20website..."
                    target="_blank"><i class="fa-brands fa-whatsapp"></i></a>
                <!-- <i class="fa-brands fa-github"></i> -->
            </div>
        </div>
    </div>
    <div class="copy-right">
        <p>&copy; 2025 <strong>The Mzansi Marketplace</strong>. All rights reserved.
        </p>
        <p>
            Designed with ❤️ in South Africa by <strong><a href="http://bradazinvestments.co.za" target="_blank">The
                    Bradaz
                    Investments (Pty) Ltd.</a></strong>
        </p>
    </div>
</footer>

<!-- Scripts -->
<script src="assets/js/scripts.js"></script>


<?php
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    echo '
<script>
    Swal.fire({
        icon: "success",
        title: "Subscription Successful",
        text: "Thank you for subscribing to The Mzansi Marketplace. We are excited to keep you updated with the latest news and exclusive offers."
    }).then((result) => {
        if (result.isConfirmed) {
            const url = new URL(window.location.href);
            url.searchParams.delete("status");
            window.history.replaceState({}, document.title, url);
        }
    });
</script>';
}

if (isset($_GET['status']) && $_GET['status'] == 'fail') {
    echo '
    <script>
        Swal.fire({
            icon: "error",
            title: "Oops...",
            text: "Something went wrong. Please try again later"
        }).then((result) => {
            if (result.isConfirmed) {
                const url = new URL(window.location.href);
                url.searchParams.delete("status");
                window.history.replaceState({}, document.title, url);
            }
        });
    </script>';
}
?>