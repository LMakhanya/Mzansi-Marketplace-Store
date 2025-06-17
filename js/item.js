const sizeRadios = document.querySelectorAll('input[name="size"]');
const selectedPriceEl = document.getElementById('selected-price');

// Assume pPrice is globally available or fetched from a hidden element
const fallbackPrice = typeof pPrice !== "undefined" ? parseFloat(pPrice) : 0;

sizeRadios.forEach(radio => {
    radio.addEventListener('change', function () {
        let rawPrice = this.dataset.price;
        if (rawPrice) {
            const finalPrice = (rawPrice && parseFloat(rawPrice) > 0) ? parseFloat(rawPrice) : fallbackPrice;
            const formatted = finalPrice.toFixed(2).split('.');
            const whole = Number(formatted[0]).toLocaleString();
            const decimal = formatted[1];
            selectedPriceEl.innerHTML = `R${whole}<span class="decimal">${decimal}</span>`;
        }
        // Use data-price if valid, else fallback to wholesale price
    });
});


// Get all size options and the input field
const sizeOptions = document.querySelectorAll('.size-option');
const sizeInput = document.getElementById('item-size');

document.querySelectorAll('.toggle-icon').forEach(icon => {
    icon.addEventListener('click', () => {
        const targetId = icon.getAttribute('data-target');
        const paragraph = document.getElementById(targetId);

        if (paragraph.style.display === 'none' || !paragraph.style.display) {
            // Show the paragraph and change the icon to minus
            paragraph.style.display = 'block';
            icon.setAttribute('name', 'remove-circle-outline');
        } else {
            // Hide the paragraph and change the icon to plus
            paragraph.style.display = 'none';
            icon.setAttribute('name', 'add-circle-outline');
        }
    });
});

function currentDiv(n) {
    showDivs(slideIndex = n);
}

function showDivs(n) {
    var i;
    var x = document.getElementsByClassName("mySlides");
    var dots = document.getElementsByClassName("demo");
    if (n > x.length) {
        slideIndex = 1
    }
    if (n < 1) {
        slideIndex = x.length
    }
    for (i = 0; i < x.length; i++) {
        x[i].style.display = "none";
    }
    for (i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" w3-opacity-off", "");
    }
    x[slideIndex - 1].style.display = "block";
    dots[slideIndex - 1].className += " w3-opacity-off";
}

function currentSlide(n) {
    var displayContainer = document.getElementById("displayContainer");
    var displayedImage = document.getElementById("displayedImage");
    var selectedImage = document.getElementsByClassName("demo")[n - 1].src;
    var selectedAlt = document.getElementsByClassName("demo")[n - 1].alt;

    displayedImage.src = selectedImage;
    displayedImage.alt = selectedAlt;
}



function likeReview(id) {
    let likedReviews = JSON.parse(localStorage.getItem("likedReviews")) || [];

    // Check if the review has already been liked
    if (likedReviews.includes(id)) {
        Swal.fire({
            title: "Already Liked",
            text: "You can only like this review once.",
            icon: "info",
        });
        return; // Exit function if already liked
    }

    // Proceed to like the review
    $.ajax({
        url: "/assets/process/item/", // Replace with the correct endpoint
        type: "POST",
        data: JSON.stringify({
            reviewid: id,
            action: "like",
        }),
        contentType: "application/json",
        success: function (response) {
            try {
                if (typeof response === "string") response = JSON.parse(response);

                if (response.status === "success") {
                    const reviewId = response.reviewid;
                    const newLikesCount = response.newLikes; // This should come from the server

                    // Update the likes count in the DOM
                    const likeElement = document.getElementById(`review-like-${reviewId}`);
                    if (likeElement) {
                        likeElement.textContent = newLikesCount;

                        // Update the thumb icon
                        const thumbIcon = document.getElementById(`thumb-icon-${reviewId}`);
                        if (thumbIcon) {
                            thumbIcon.setAttribute("name", "thumbs-up");
                        }

                        // Add the review ID to the list of liked reviews
                        likedReviews.push(id);
                        localStorage.setItem("likedReviews", JSON.stringify(likedReviews));
                    }
                } else {
                    Swal.fire({
                        title: "Error",
                        text: response.message || "Failed to like the review. Please try again.",
                        icon: "error",
                    });
                }
            } catch (err) {
                console.error("Error processing response:", err);
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                title: "Error",
                text: "Failed to communicate with the server. Please try again later.",
                icon: "error",
            });
            console.error("AJAX Error:", { status, error, responseText: xhr.responseText });
        },
    });
}

let selectedRating = 0;

function rate(rating) {
    selectedRating = rating;
    highlightStars(rating);
    document.getElementById("rating").value = rating;
}

function highlightStars(rating) {
    const stars = document.getElementsByClassName("star");
    for (let i = 0; i < stars.length; i++) {
        if (i < rating) {
            stars[i].innerHTML = "<span style='color: gold;'>&#9733;</span>"; // Filled star in gold color
        } else {
            stars[i].innerHTML = "&#9734;"; // Empty star
        }
    }
}

function validateForm() {
    if (selectedRating === 0) {
        alert("Please select 1 - 5 stars for your rate.");
        return false;
    }
    return true;
}

function postReview() {
    const reviewText = document.getElementById('review-text').value;
    const reviewRating = document.getElementById('review-rating').value;

    if (!reviewText || !reviewRating) {
        Swal.fire({
            title: 'Incomplete Form',
            text: 'Please fill in all required fields.',
            icon: 'warning',
        });
        return;
    }

    $.ajax({
        url: "/assets/process/item/", // Replace with the correct endpoint
        type: "POST",
        data: JSON.stringify({
            reviewText,
            reviewRating,
            action: "postReview",
        }),
        contentType: "application/json",
        success: function (response) {
            try {
                if (typeof response === "string") response = JSON.parse(response);

                if (response.status === "success") {
                    Swal.fire({
                        title: "Review Posted",
                        text: "Your review has been submitted successfully.",
                        icon: "success",
                    });

                    // Clear the form
                    document.getElementById('review-text').value = '';
                    document.getElementById('review-rating').value = '';

                    // Append the new review
                    const newReview = {
                        review_id: response.review_id,
                        reviewer_name: response.reviewer_name,
                        review_text: reviewText,
                        review_rating: reviewRating,
                        review_date: new Date().toISOString(),
                        likes: 0,
                    };

                    allPeviews.push(newReview);
                    appendReviews(allPeviews);
                } else {
                    Swal.fire({
                        title: "Error",
                        text: response.message || "Failed to post the review. Please try again.",
                        icon: "error",
                    });
                }
            } catch (err) {
                console.error("Error processing response:", err);
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                title: "Error",
                text: "Failed to communicate with the server. Please try again later.",
                icon: "error",
            });
            console.error("AJAX Error:", { status, error, responseText: xhr.responseText });
        },
    });
}

function closeRForm() {
    document.getElementById('r-r-form').style.display = 'none';
}
function openRForm() {
    document.getElementById('r-r-form').style.display = 'flex';
}

/* ******************* Sizing Guide ************************ */
const overlay = document.getElementById('sizeGuideOverlay');
const closeBtn = document.getElementById('closeGuideBtn');
const tabs = document.querySelectorAll('.guide-tab');

// Open size guide (you would call this when the size guide link is clicked)
function openSizeGuide() {
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close size guide
function closeSizeGuide() {
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

// Tab switching
tabs.forEach(tab => {
    tab.addEventListener('click', function () {
        // Remove active class from all tabs and tables
        document.querySelectorAll('.guide-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.size-table').forEach(t => t.classList.remove('active'));

        // Add active class to clicked tab and corresponding table
        this.classList.add('active');
        const tabId = this.getAttribute('data-tab');
        document.getElementById(`${tabId}-table`).classList.add('active');
    });
});

// Close button event
closeBtn.addEventListener('click', closeSizeGuide);

// Close when clicking outside the container
overlay.addEventListener('click', function (e) {
    if (e.target === overlay) {
        closeSizeGuide();
    }
});

// Make this function available globally to open the guide from your size options
window.openSizeGuide = openSizeGuide;

/* ******************* Sizing Guide ************************ */