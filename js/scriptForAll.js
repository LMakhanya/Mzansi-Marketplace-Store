var screenWidth1 = window.innerWidth;


var myIndex = 0;

// Function to show the overlay with a bounce animation
function showOrderSuccessOverlay() {
    const popup = document.getElementById('popup');
    const overlay = document.getElementById('successOverlay');
    overlay.style.display = 'flex';
    popup.classList.add('show');

    const url = new URL(window.location);

    // remove all params from the url
    url.searchParams.delete('total');
    url.searchParams.delete('bag');
    url.searchParams.delete('seller');
    window.history.pushState({}, '', url);


    // Hide the overlay after a delay (e.g., 3 seconds)
    setTimeout(() => {

    }, 15000); // 3000ms = 3 seconds
}

function clearParamsandOverlay() {
    const url = new URL(window.location);

    const popup = document.getElementById('popup');
    const overlay = document.getElementById('successOverlay');
    popup.classList.remove('show');
    overlay.style.display = 'none';
    // remove all params from the url
    url.searchParams.delete('total');
    url.searchParams.delete('bag');
    url.searchParams.delete('seller');
    window.history.pushState({}, '', url);
}
function viewOrder(orderNo) {
    clearParamsandOverlay();
    window.location.href = "viewOrder.php?order_no=" + orderNo;
}

function returnToStore() {
    clearParamsandOverlay()
    // go to index page
    window.location.href = "index.php";
}


function addedToCartOverlay() {
    const popup = document.getElementById('added-to-cart-popup');
    const overlay = document.getElementById('success-overlay');
    overlay.style.display = 'flex';
    popup.classList.add('show');

    // Hide the overlay after a delay (e.g., 3 seconds)
    /*  setTimeout(() => {
         popup.classList.remove('show');
         overlay.style.display = 'none';
     }, 5000); */
}