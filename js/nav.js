
function selectBusiness(businessName) {
    document.getElementById("selected-business").innerText = businessName;
    document.getElementById("store").value = businessName; // Update the hidden input value
    document.getElementById("dropdown-list").style.display = "none";
}

// Close the dropdown if the user clicks outside of it
document.addEventListener("click", function (event) {
    const dropdown = document.getElementById("businesses-dropdown");
    if (!dropdown.contains(event.target)) {
        document.getElementById("dropdown-list").style.display = "none";
    }
});

function logout() {
    window.location.href = 'logout.php'
}

