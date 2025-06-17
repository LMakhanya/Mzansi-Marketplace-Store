document.addEventListener('DOMContentLoaded', function () {
    const mobileMenuBtn = document.querySelector('.mobile-menu-button');
    const navLinks = document.querySelector('.nav-links');

    mobileMenuBtn.addEventListener('click', () => {
        navLinks.classList.toggle('show');
    });

    const path = window.location.pathname;

    document.querySelectorAll('.nav-links a').forEach(function (item) {
        const option = item.getAttribute('data-option');
        let match = false;

        switch (option) {
            case 'index':
                match = path.endsWith('index.php');
                break;
            case 'shop':
                match = path === '/';
                break;
            case 'agents':
                match = path.includes('agents.php');
                break;
            case 'sellers':
                match = path.includes('sellers.php');
                break;
            case 'trial':
                match = path.includes('trial.php');
                break;
            case 'contact':
                match = path.includes('contact.php');
                break;
        }

        if (match) {
            document.querySelectorAll('.nav-links a').forEach(el => el.classList.remove('active'));
            item.classList.add('active');
        }
    });
});


function selectPlan(name, price, page, trial) {
    if (trial) {
        trial = true;
    } else {
        trial = false;
    }
    // Save the selected plan to localStorage
    localStorage.setItem('selectedPlan', JSON.stringify({
        name: name,
        price: price
    }));
    localStorage.setItem('trial', trial);
    // Redirect to confirmplan.html
    if (page == 'login') {
        window.location.href = 'https://seller.themzansimarketplace.co.za/login/';
    }
    if (page == 'register') {
        window.location.href = 'https://seller.themzansimarketplace.co.za/registration/account/';
    }
}
/* When the user clicks on the button,
toggle between hiding and showing the dropdown content */
function myFunction() {
    document.getElementById("myDropdown").classList.toggle("show");
}


function showBtnLoader(buttonID) {
    const button = document.getElementById(buttonID);
    const loader = button.querySelector(".btn-loader");

    // Show loader and disable button
    button.classList.add("loading");
    // disable button
    button.style.pointerEvents = "none";
    button.style.cursor = "not-allowed";

    loader.style.display = "inline-block";
}
function hideBtnLoader(buttonID) {
    const button = document.getElementById(buttonID);
    const loader = button.querySelector(".btn-loader");

    // Hide loader and re-enable button
    button.classList.remove("loading");
    // enable button
    button.style.pointerEvents = "auto";
    button.style.cursor = "pointer";

    loader.style.display = "none";
}