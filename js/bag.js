
// Get the checkout button by its ID
var checkoutButton = document.getElementById("bagcheckoutButton");
var totalAmount = 0;

fetchBagItems();

// Ensure the button exists before modifying its properties
if (checkoutButton) {
    // Check if totalAmount is zero and adjust button properties
    if (totalAmount === 0) {
        checkoutButton.style.opacity = "0.5"; // Set opacity to make it visually disabled
        checkoutButton.disabled = true; // Disable the button
    } else {
        checkoutButton.style.opacity = "1"; // Restore full opacity for enabled state
        checkoutButton.disabled = false; // Enable the button
    }
}

var screenWidth = window.innerWidth;

if (screenWidth >= 750) {
    function openCartPanel() {
        document.getElementById("cartPanel").style.width = "40vw";
        document.getElementById("overlay").style.display = "block";

        closePopup(activePopupId);
        document.body.classList.add("side-panel-open");
    }

    function openProfilePanel() {
        document.getElementById("profilePanel").style.width = "25vw";
        document.getElementById("overlay2").style.display = "block";
        closePopup(activePopupId);
        document.body.classList.add("profile-panel-open");
    }
} else {
    function openProfilePanel() {
        document.getElementById("profilePanel").style.width = "100vw";
        document.getElementById("overlay2").style.display = "block";
        closePopup(activePopupId);
        document.body.classList.add("profile-panel-open");
    }
}
