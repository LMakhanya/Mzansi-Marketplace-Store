<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Navigation -->
    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/portal/assets/includes/common-meta.html';
    ?>
    <title>Contact Us | The Mzansi Marketplace </title>

    <link rel="stylesheet" href="/portal/assets/css/contact.css">

    <!-- Reesponsive -->
    <link rel="stylesheet" href="/portal/assets/css/responsive.css">
</head>

<body>
    <!-- Navigation -->
    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/portal/assets/includes/navigation.html';
    ?>
    <div class="">
        <div class="container contact-container">
            <!-- Left Section: Text & Hours -->
            <div class="contact-text">
                <h1>Contact us</h1>
                <p>
                    Need to get in touch with us? Either fill out the form with your inquiry or find the
                    <a href="support@themzansimarketplace.co.za" class="department-link">department email</a> you’d like to contact below.
                </p>

                <!-- Operating Hours -->
                <div class="operating-hours">
                    <h2>Operating Hours</h2>
                    <ul>
                        <li>Monday - Friday: 8:00 AM - 4:00 PM</li>
                        <li>Saturday: 10:00 AM - 3:00 PM</li>
                        <li>Sunday: Closed</li>
                    </ul>
                </div>
            </div>

            <!-- Right Section: Form -->
            <div class="contact-form">
                <form method="post" action="/api/reg/contact/" id="contactus-form">
                    <input type="hidden" value="valid" id="valid" name="valid">
                    <h2>Send Us A Message</h2>
                    <div class="combo_field">
                        <div class="inputs form-group">
                            <input type="text" name="first-name" step="0.01" id="contact-firstname" placeholder=" "
                                required>
                            <label for="contact-firstname">First Names(s)</label>
                        </div>
                        <div class="inputs form-group">
                            <input type="text" name="last-name" id="contact-lastname" step="0.01" placeholder=" "
                                required>
                            <label for="contact-lastname">Last Name</label>
                        </div>
                    </div>
                    <div class="inputs form-group">
                        <input type="email" name="email" id="contact-email" step="0.01" placeholder=" " required>
                        <label for="contact-email">Email</label>
                    </div>
                    <div class="inputs form-group">
                        <textarea name="message" id="contact-message" rows="4" step="0.01" placeholder=" "
                            required></textarea>
                        <label for="contact-message">Message</label>
                    </div>
                    <button type="submit" class="submit-button" id="contactus-submit-btn">Submit</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Map Section -->
    <section class="map-section">
        <!--    <h2>Find Us Here</h2> -->
        <span>Map loading...</span>
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d6911.386616407054!2d30.83263689999999!3d-29.988242799999984!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sza!4v1737598624902!5m2!1sen!2sza"
            width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </section>

    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/portal/assets/includes/footer.php';

    if (isset($_GET['success']) && $_GET['success'] == 1) {
        echo '
    <script>
        Swal.fire({
            icon: "success",
            title: "Thank you for contacting us",
            text: "We will get back to you very soon"
        }).then((result) => {
            if (result.isConfirmed) {
                const url = new URL(window.location.href);
                url.searchParams.delete("success");
                window.history.replaceState({}, document.title, url);
            }
        });
    </script>';
    }

    if (isset($_GET['error']) && $_GET['error'] == 1) {
        echo '
    <script>
        Swal.fire({
            icon: "error",
            title: "Oops...",
            text: "Something went wrong. Please try again later"
        }).then((result) => {
            if (result.isConfirmed) {
                const url = new URL(window.location.href);
                url.searchParams.delete("error");
                window.history.replaceState({}, document.title, url);
            }
        });
    </script>';
    }
    ?>

</body>

</html>