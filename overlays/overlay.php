<div id="accountOverlay" class="overlay">
    <div id="account-popup" class="popup">
        <div class="overlay-actions">
            <i class="ph ph-arrow-left" id="arrow-back" onclick="slideShow('option-popup','')"></i>
            <span class="close" onclick="closePopup('accountOverlay')">✕</span>
        </div>

        <div class="reg option-popup show" id="option-popup">
            <div class="headline">We’re so happy to see you!</div>
            <div class="subheadline">Awesome new faves this way. Let’s go shopping…</div>
            <div class="button-container">
                <button class="sign-in-btn" onclick="slideShow('account-tabs',true)">CUSTOMER SIGN IN</button>
            </div>
            <div class="divider"><span>OR SELL WITH US</span></div>
            <button class="register-btn" onclick="registerAsASeller()">INSTORE SELLERS</button>
        </div>

        <div class="reg account-tabs" id="account-tabs">
            <div class="tab-nav">
                <button class="tab-button active" data-tab="login">Login</button>
                <button class="tab-button" data-tab="create-acc">Sign Up</button>
            </div>

            <div class="acc_col active" id='login'>
                <form action="/api/auth/login/" method="POST">
                    <?php if (isset($_GET['error'])) { ?>
                        <div class="error-cont">
                            <i class="ph ph-info"></i>
                            <p class="error">Failed: <?php echo $_GET['error']; ?></p>
                        </div>

                    <?php } ?>
                    <?php if (isset($_GET['success'])) { ?>
                        <p class="success">
                            <?php echo $_GET['success']; ?>
                        </p>
                    <?php } ?>
                    <input type="hidden" value="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" name="currentPage">
                    <div class="inputfield">
                        <input required type="text" name="username" id="username2" placeholder=" ">
                        <label for="username">username</label>
                    </div>

                    <div class="inputfield">
                        <div>
                            <input required type="password" name="password" id="loginPassword" placeholder=" ">
                            <label for="password">Password</label>
                            <i class="ph ph-eye-slash" id="toggleLoginPassword"></i>
                        </div>
                    </div>

                    <div class="btn">
                        <input type="submit" name="login" value="Sign In">
                    </div>
                    <br>
                    <p>Don't have an Account?<spn class="tab-button" data-tab="create-acc" style="color: var(--primary-color); cursor:pointer; font-weight:500;"> Sign Up</spn>
                    </p>
                </form>
            </div>

            <div class="acc_col create-acc" id="create-acc">
                <form action="/api/auth/login/" class="form" method="POST" onsubmit="return validateForm()">
                    <input type="hidden" value="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" name="currentPage">
                    <div class="input-combo">
                        <div class="inputfield">
                            <input type="text" name="name" id="name" placeholder=" ">
                            <label for="name">First Name</label>
                            <span id="error-name" class="error"></span>
                        </div>

                        <div class="inputfield">
                            <input type="text" name="surname" id="surname" placeholder=" ">
                            <label for="surname">Last Name</label>
                            <span id="error-surname" class="error"></span>
                        </div>
                    </div>
                    <div class="input-combo">
                        <div class="inputfield">
                            <input type="text" name="phone" id="phone" placeholder=" ">
                            <label for="phone">Phone</label>
                            <span id="error-phone" class="error"></span>
                        </div>

                        <div class="inputfield">
                            <input type="email" name="email" id="email" placeholder=" ">
                            <label for="email">email</label>
                            <span id="error-email" class="error"></span>
                        </div>
                    </div>

                    <div class="inputfield">
                        <div>
                            <input type="password" autocomplete="new-password" name="password" id="password" placeholder=" ">
                            <label for="password">Password</label>
                            <i class="ph ph-eye-slash" id="togglePassword"></i>
                        </div>
                        <span id="error-password" class="error"></span>
                    </div>

                    <!--  <div class="inputfield">
                        <div>
                            <input type="password" name="c-password" id="c-password" placeholder=" ">
                            <label for="c-password">Confirm Password</label>
                            <ion-icon id="toggleCPassword" name="eye-off-outline"></ion-icon>
                        </div>
                        <span id="error-cpassword" class="error"></span>
                    </div> -->


                    <div class="btn">
                        <input type="submit" name="create-account" value="Sign Up" onsubmit="validateForm()">
                    </div>
                    <br>
                    <p>Already have Account?<span class="tab-button" data-tab="login" style="color: var(--primary-color); cursor:pointer; font-weight:500;"> Sign In</span></p>
                </form>
            </div>


        </div>
    </div>
</div>

<div class="success-overlay" id="success-overlay">
    <div class="success-popup" id="added-to-cart-popup">
    </div>
</div>

<div class="form-container" id="r-r-form">
    <form method="POST" action="/assets/php/rate_review.php" id="r-form">
        <div class="close" id="close-r-form" onclick="closeRForm()">
            <span class="close">✕</span>
        </div>
        <div class="r-form-content">
            <p>Rate & review item. &#128515;</p>
            <div class="rating-stars">
                <span class="star" onclick="rate(1)">&#9734;</span>
                <span class="star" onclick="rate(2)">&#9734;</span>
                <span class="star" onclick="rate(3)">&#9734;</span>
                <span class="star" onclick="rate(4)">&#9734;</span>
                <span class="star" onclick="rate(5)">&#9734;</span>
            </div>

            <label for="name">Your Name:</label>
            <input type="text" id="firstname" name="name" placeholder="Write your name" class="form-input" required>
            <label for="reason">Give feedback for your rating:</label>
            <textarea class="form-input" id="reason" name="reason" placeholder="Write your feedback" required></textarea>

            <input type="hidden" id="rating" name="rating">
            <div class="button">
                <input type="submit" value="Submit" id="submit" class="submit-button" name="submit">
            </div>
        </div>
    </form>
</div>