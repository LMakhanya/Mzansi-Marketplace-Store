var lowerNav = document.querySelector(".navbar");
var activePopupId = null; // Track the currently active popup
var lastScrollTop = 0;

// Get URL parameters
const errorParams = new URLSearchParams(window.location.search);

// Check if 'error' parameter exists and its value is '1'
if (errorParams.get('error')) {
    slideShow('account-tabs', true)
    openPopup('accountOverlay');
    document.body.classList.add('popup-open'); // when popup is shown
}


function redirectToShoppingBag() {
    // Redirect to the shopping bag page
    window.location.href = "/bag.php";
}

function openCartPanel() {
    document.getElementById("cartPanel").style.width = "450px";
    if (activePopupId != null) {
        closePopup(activePopupId);
    }
}


function registerAsASeller() {
    window.open(`${js_domain}/account/`, "_blank");
}


document.getElementById('toggleLoginPassword').addEventListener('click', function () {
    let passwordInput = document.getElementById('loginPassword');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        this.setAttribute('class', 'ph ph-eye');
    } else {
        passwordInput.type = 'password';
        this.setAttribute('class', 'ph ph-eye-slash');
    }
});
document.getElementById('togglePassword').addEventListener('click', function () {
    let passwordInput = document.getElementById('password');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        this.setAttribute('class', 'ph ph-eye');
    } else {
        passwordInput.type = 'password';
        this.setAttribute('class', 'ph ph-eye-slash');
    }
});

function slideShow(divId, back) {

    if (back) {
        document.getElementById('arrow-back').style.display = 'block';
    } else {
        document.getElementById('arrow-back').style.display = 'none';
    }

    const regContainer = document.querySelectorAll('.reg');


    regContainer.forEach(content => {
        content.classList.remove('show');
    });

    document.getElementById(divId).classList.add('show');
}


document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.acc_col');

    // Function to handle tab switching
    const switchTab = (targetButton) => {
        // Remove active class from all buttons and contents
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));

        // Add active class to clicked button and corresponding content
        targetButton.classList.add('active');
        const tabId = targetButton.getAttribute('data-tab');
        document.getElementById(tabId).classList.add('active');
    };

    tabButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            switchTab(event.target.closest('.tab-button') || event.target); // Ensure we get the button even if the span is clicked
        });
    });

    // Add event listeners for <span> elements that have the same data-tab as buttons
    const spanButtons = document.querySelectorAll('.tab-button[data-tab]');
    spanButtons.forEach(span => {
        span.addEventListener('click', (event) => {
            event.preventDefault();
            const button = document.querySelector(`.tab-button[data-tab="${span.getAttribute('data-tab')}"]`);
            if (button) {
                switchTab(button); // Switch to the tab associated with this span
            }
        });
    });
});

function closePopup(popupId) {
    var popup = document.getElementById(popupId);
    popup.style.display = "none";

    // Remove 'error' parameter from URL
    const url = new URL(window.location);
    url.searchParams.delete('error');
    window.history.replaceState({}, document.title, url);
    document.body.classList.remove('no-scroll');
}

function openPopup(popupId) {
    // Close any active popup
    if (activePopupId !== null) {
        closePopup(activePopupId);
        document.body.classList.remove('no-scroll');
    }

    var popup = document.getElementById(popupId);
    popup.style.display = "flex";
    document.body.classList.add('no-scroll');


    activePopupId = popupId;

}

function validateForm() {
    var name = document.getElementById("name").value.trim();
    var surname = document.getElementById("surname").value.trim();
    var phone = document.getElementById("phone").value.trim();
    var email = document.getElementById("email").value.trim();
    var password = document.getElementById("password").value.trim();
    /*  var cpassword = document.getElementById("c-password").value.trim(); */

    // Reset error messages
    document.getElementById("error-name").innerHTML = "";
    document.getElementById("error-surname").innerHTML = "";
    document.getElementById("error-phone").innerHTML = "";
    document.getElementById("error-email").innerHTML = "";
    document.getElementById("error-password").innerHTML = "";
    /*  document.getElementById("error-cpassword").innerHTML = ""; */

    var isValid = true;
    var passwordRegex = /^.{8,}$/; // At least 8 characters

    if (name === "") {
        document.getElementById("error-name").innerHTML = "Please enter your First Name";
        isValid = false;
    }

    if (surname === "") {
        document.getElementById("error-surname").innerHTML = "Please enter your Last Name";
        isValid = false;
    }

    if (phone === "") {
        document.getElementById("error-phone").innerHTML = "Please enter your Phone Number";
        isValid = false;
    }

    if (email === "") {
        document.getElementById("error-email").innerHTML = "Please enter your Email Address";
        isValid = false;
    }

    if (password === "") {
        document.getElementById("error-password").innerHTML = "Please enter a Password";
        isValid = false;
    } else if (!passwordRegex.test(password)) {
        document.getElementById("error-password").innerHTML = "Password must be at least 8 characters long";
        isValid = false;
    }

    /*  if (cpassword === "") {
         document.getElementById("error-cpassword").innerHTML = "Please confirm your Password";
         isValid = false;
     } else if (password !== cpassword) {
         document.getElementById("error-cpassword").innerHTML = "Passwords do not match";
         isValid = false;
     } */

    return isValid;
}
