const bagQuantity = document.querySelector('.bagQuantity');

function showAlert(type, message) {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;

    // Create message span
    const messageSpan = document.createElement('span');
    messageSpan.textContent = message;

    // Create close button
    const closeBtn = document.createElement('button');
    closeBtn.className = 'alert-close';
    closeBtn.textContent = '×';

    // Add click event to close button
    closeBtn.onclick = function () {
        alert.classList.add('fade-out');
        setTimeout(() => alert.remove(), 300);
    };

    // Append elements
    alert.appendChild(messageSpan);
    alert.appendChild(closeBtn);

    // Add to container
    const container = document.getElementById('alert-container');
    container.appendChild(alert);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentElement) {
            alert.classList.add('fade-out');
            setTimeout(() => alert.remove(), 300);
        }
    }, 2500);
}

function closeCartPanel() {
    document.getElementById("cartPanel").style.width = "0";
}

function continueShopping() {
    window.location.href = '/';
}
function viewItem(id) {
    window.location.href = '/item/' + id;
}

function updateBagQuantity(quantity) {
    let newTotal = document.getElementById("bagQ").value;

    // Update total amount
    if (!quantity) {
        document.getElementById("bagQuantity").innerText = newTotal;
        // console.log('Total amount updated to:', newTotal);
    } else {
        document.getElementById("bagQuantity").innerText = quantity;
        // console.log('Total amount updated to:', newTotal);
    }

}

updateBagQuantity(quantity);

function redirectToItem(prooductId) {
    window.location.href = `/item/${prooductId}`;
}

function toggleProfileOverlay() {
    const overlay = document.getElementById('profile-overlay');
    const icon = document.getElementById('profile-icon');

    if (overlay) {
        const isShowing = overlay.classList.toggle('show');
        icon.setAttribute('name', isShowing ? 'caret-up-outline' : 'caret-down-outline');
    }
}


function fetchItems(categoryName, selectedType) {
    if (categoryName == 'store') {
        categoryName = '';
    }
    $.post('/assets/php/fetch_products.php', {
        action: 'fetch',
        categoryName: categoryName,
        selectedType: selectedType
    })
        .done(data => {
            console.log('Raw response:', data); // Log the raw response to debug
            const itemBody = document.getElementById('i-wraper');
            const phoneSec = document.getElementById('phone-section');

            // Check if the data is an array and contains items
            if (!Array.isArray(data.suggested) || data.suggested.length === 0) {
                itemBody.innerHTML = '<p style="text-align:center;">No item(s) found.</p>';
                return;
            }

            if (!Array.isArray(data.phones) || data.phones.length === 0) {
                phoneSec.innerHTML = '<p style="text-align:center;">No item(s) found.</p>';
                return;
            }

            // Map the array of items to HTML and display it
            itemBody.innerHTML = data.suggested.map(item => `
<div class="items" >
    <div class="wasPrice">
        <p>${item.discount}% Off</p>
    </div>
    <div class="item" onclick="redirectToItem('${item.product_id}')">
        <div class="i-img">
            <img loading="lazy" src="/uploads/${item.product_image}" alt="${item.ProductName}">
        </div>
        <div class="seperator"></div>
        <div class="i-dtls">
            <p class="item-name">${item.ProductName}</p>
            <a href="/storeprofile/${item.b_name}/${item.seller_id}">
            <div class="seller_name">
                <span>${item.b_name} </span>
                <i class="ph-fill ph-shield-check"></i>
            </div></a>
            <div>
                <span>Model:</span>
                <p class="item-model">${item.Model}</p>
            </div>
            <div>
                <span>Brand:</span>
                <p class="item-color">${item.brandName}</p>
            </div>
            <div>
                <span>Color:</span>
                <p class="item-color">Black</p>
            </div>
        </div>
    </div>
    <div class="i-btn">
        <p class="item-price">R${item.Price}</p>
        <button onclick="addToCart(${item.product_id}, '${item.ProductName}', '${item.Model}', ${item.Price}, '${item.product_image}', '${item.seller_id}')">Add <span class='add-t-c-span'>To Cart</span></button>
    </div> 
</div>`).join('');

            phoneSec.innerHTML = data.phones.map(item => `
<div class="items" >
    <div class="wasPrice">
        <p>${item.discount}% Off</p>
    </div>
    <div class="item" onclick="redirectToItem('${item.product_id}')">
        <div class="i-img">
            <img loading="lazy" src="/uploads/${item.product_image}" alt="${item.ProductName}">
        </div>
        <div class="seperator"></div>
        <div class="i-dtls">
            <p class="item-name">${item.ProductName}</p>
            <a href="/storeprofile/${item.b_name}/${item.seller_id}">
            <div class="seller_name">
                <span>${item.b_name} </span>
                <i class="ph-fill ph-shield-check"></i>
            </div></a>
            <div>
                <span>Model:</span>
                <p class="item-model">${item.Model}</p>
            </div>
            <div>
                <span>Brand:</span>
                <p class="item-color">${item.brandName}</p>
            </div>
            <div>
                <span>Color:</span>
                <p class="item-color">Black</p>
            </div>
        </div>
    </div>
    <div class="i-btn">
        <p class="item-price">R${item.Price}</p>
        <button onclick="addToCart(${item.product_id}, '${item.ProductName}', '${item.Model}', ${item.Price}, '${item.product_image}', '${item.seller_id}')">Add <span class='add-t-c-span'>To Cart</span></button>
    </div> 
</div>
`).join('');

        })
        .fail(error => {
            console.error('Error fetching item:', error);
            document.getElementById('i-wraper').innerHTML = '<p style="text-align:center;color:red;">Failed to load item.</p>';
        });
}

function closeAllSubMenus() {
    const openSubMenus = document.querySelectorAll('.sub-menu.show');
    const rotatedButtons = document.querySelectorAll('.dropdown-btn.rotate');

    openSubMenus.forEach((menu) => menu.classList.remove('show'));
    rotatedButtons.forEach((button) => button.classList.remove('rotate'));
}

function fetchBagItems() {
    fetch('/assets/php/getBagItems.php?action=fetch') // Ensure the correct endpoint
        .then(response => response.json()) // Parse the response as JSON
        .then(data => {
            const itemBody = document.getElementById('bagItems');

            // Check if data is valid and not empty
            if (!data || !data.eachSeller || data.eachSeller.length === 0) {
                itemBody.innerHTML = `<div class="empty-bag-message">
                <img loading="lazy" src="/assets/images/emptyCart.png" alt="">
                <p>You’ve got nothing in your cart... yet.</br><span>Let’s go shopping!</span></p>
                <a href='/'>Go to store.</a>
            </div>`;
            } else {
                let totalSellers = 0;
                // Dynamically create the HTML for each seller and their items
                itemBody.innerHTML = data.eachSeller.map(sellerGroup => {
                    totalSellers++;
                    // Access the seller's information from seller_info
                    const sellerInfo = sellerGroup.seller_info;
                    const sellerFName = sellerInfo.firstname || "Unknown Seller";
                    const sellerName = sellerInfo.firstname + " " + sellerInfo.lastname || "Unknown Seller";
                    const sellerRatings = sellerInfo.seller_ratings || "No ratings available";
                    const sellerJoinDate = sellerInfo.created_at || "Unknown Join Date";
                    const bagID = sellerInfo.bagID || "No Bag ID";
                    const sellerID = sellerInfo.seller_id || "";
                    const sellerBName = sellerInfo.b_name || "";

                    let totalAmount = 0;
                    let totalItems = 0;
                    sellerGroup.products.forEach(item => {
                        const quantity = parseInt(item.Quantity, 10) || 0;
                        const pricePerItem = item.totalAmount / quantity;
                        const discountedPrice = pricePerItem * (1 - item.discount / 100);
                        const roundedPrice = Math.round(discountedPrice * 100) / 100;
                        totalAmount += roundedPrice * quantity;
                        totalItems += quantity;
                    });


                    // Create seller and items HTML
                    const sellerDetails = `
                    <!-- Seller Group 1 -->
                    <div class="seller-group">
                        <!-- Seller Header -->
                        <div class="seller-header">
                            <div class="seller-info-wraaper">
                                <img src=" /assets/images/500x500.jpg" alt="Seller" class="seller-avatar">
                                <div class="seller-info">
                                    <h3 class="seller-name">${sellerName}</h3>
                                    <div class="seller-meta">
                                        <div class="rating">
                                            <i class="fas fa-star" style="font-size: 14px;"></i>
                                            <span class="rating-text">4.8 (1.2k)</span>
                                        </div>
                                        <span class="meta-divider">•</span>
                                        <span class="member-since">Member since 2018</span>
                                    </div>
                                </div>
                                <a href="/storeprofile/${sellerBName}/${sellerID}" class="visit-store">
                                    <i class="fas fa-store" style="margin-right: 4px;"></i> Visit Store
                                </a>
                            </div>
                        </div>

                        <!-- List of Products of a seller -->
                        <div class="product-list">
                            <!-- Each Product Design-->
                            ${sellerGroup.products.map(item => {
                        const quantity = parseInt(item.Quantity, 10) || 0;
                        const pricePerItem = item.totalAmount / quantity;
                        const finalPrice = Math.round(pricePerItem * (1 - item.discount / 100) * 100) / 100;

                        const sizeText = item.size ? ` • Size: ${item.size}` : '';
                        const safeSize = item.size ? `'${item.size}'` : `'• Color: ${item.color}'`; // Ensures size is safely passed to JS functions
                        const defaultSize = item.size ? item.size : 0;
                        return `
                                <div class="product-item">
                                    <div class="product-row">
                                        <div class="product-image" onclick="redirectToItem(${item.product_id})">
                                            <img src="/uploads/${item.product_image}" alt="${item.ProductName}">
                                        </div>
                                        <div class="product-details">
                                            <div class="product-header">
                                                <div>
                                                    <h4 class="product-title" onclick="redirectToItem(${item.product_id})">${item.ProductName}</h4>
                                                    <p class="product-variants">Color: ${item.color} ${sizeText}</p>
                                                </div>
                                                <div>
                                                    <p class="product-price">R${finalPrice}</p>
                                                </div>
                                            </div>
                                            <div class="product-actions">
                                                <div class="quantity-control">
                                                    <button class="quantity-btn" onclick="processQuantity('minusQ', ${item.product_id}, '${item.bagID}', ${finalPrice}, ${defaultSize})">
                                                        <i class="fas fa-minus" style="font-size: 12px;"></i>
                                                    </button>
                                                    <input class="quantity-value" name="quantity" id="quantityInput_${item.product_id}_${defaultSize}" min="1"
                                                        value="${item.Quantity}" step="1">
                                                    <button class="quantity-btn" onclick="processQuantity('addQ', ${item.product_id}, '${item.bagID}', ${finalPrice}, ${defaultSize})">
                                                        <i class="fas fa-plus" style="font-size: 12px;"></i>
                                                    </button>
                                                </div>
                                                <div class="action-buttons">
                                                    <!--<button class="action-btn">
                                                        <i class="far fa-heart"></i>
                                                    </button>-->
                                                    <button class="action-btn" onclick="processCart('delete','${item.bagID}', '${item.product_id}', ${pricePerItem}, ${defaultSize})">
                                                        <i class="far fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                `;
                    }).join('')}
                        </div>

                        <!-- Check out -->
                        <div class="seller-checkout">
                            <div class="checkout-summary">
                                <div class="summary-row">
                                    <span class="summary-label">Total - ${totalItems} item(s)</span>
                                    <span class="summary-value">R${totalAmount}</span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Shipping</span>
                                    <span class="summary-value">Not Included</span>
                                </div>
                                <!-- <div class="summary-divider"></div> -->
                            </div>
                            <div class="checkout-actions">
                                <a class="checkout-btn seller-checkout-btn" href="/pay/?total=${totalAmount}&bag=${bagID}&seller=${sellerID}">
                                    <i class="fas fa-shopping-bag" style="margin-right: 8px;"></i> Checkout with ${sellerFName}
                                </a>
                            </div>
                        </div>
                    </div>
`;
                    return sellerDetails;

                }).join(''); // Join all sellers' item details

                if (totalSellers > 0) {
                    document.getElementById('cart-badge').style.display = 'block';
                    document.getElementById('totalSellers').textContent = `${totalSellers}`;
                }
            }
        })
        .catch(error => {
            console.error('Error fetching items:', error);
            document.getElementById('i-wraper').innerHTML = '<p style="text-align:center;color:red;">Failed to load items.</p>';
        });
}

function showBtnLoader(buttonID) {
    const button = document.getElementById(buttonID);
    const loader = button.querySelector('.btn-loader');
    const icon = button.querySelector('.ph');

    // Show loader and disable button
    button.disabled = true;
    icon.style.display = 'none';
    button.classList.add('loading');
    loader.style.display = 'inline-block';
}
function hideBtnLoader(buttonID) {
    const button = document.getElementById(buttonID);
    const loader = button.querySelector('.btn-loader');
    const icon = button.querySelector('.ph');

    // Hide loader and re-enable button
    button.disabled = false;
    button.classList.remove('loading');
    loader.style.display = 'none';
    icon.style.display = 'block';
}

function addToCart(pID, pName, model, pPrice, imgName, sellerID) {
    showBtnLoader(`btn_${pID}`);
    document.body.classList.add('no-scroll');
    // Get quantity
    const quantityInput = document.getElementById('quantityInput');
    let quantity = quantityInput && quantityInput.value ? parseInt(quantityInput.value, 10) : 1;
    quantity = (isNaN(quantity) || quantity < 1) ? 1 : Math.min(quantity, 999);

    let size = "";
    let selectedPrice = parseFloat(pPrice); // default/fallback wholesale price from PHP

    if (document.querySelector('input[name="size"]')) {
        // Get selected size
        const selected = document.querySelector('input[name="size"]:checked');

        if (selected) {
            size = selected.value;
            // Use dataset price if it exists, otherwise keep parsed pPrice
            const priceAttr = selected.dataset.price;
            if (priceAttr && priceAttr !== "0") {
                selectedPrice = parseFloat(priceAttr);
            }
            console.log("Selected Size:", size);
            console.log("Final Price Used:", selectedPrice);
        } else {
            showAlert('error', "Please select a size.")
            hideBtnLoader(`btn_${pID}`);
            return;
        }
    }

    // Send request to server
    fetch('/assets/php/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            pID: pID,
            pName: pName,
            model: model,
            pPrice: selectedPrice,
            quantity: quantity,
            sellerID: sellerID,
            size: size
        })
    })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            const text = await response.text(); // Get raw response as text

            // console.log("Raw server response:", text); // 🔍 Log full server response

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            if (contentType && contentType.includes("application/json")) {
                return JSON.parse(text); // Parse JSON only if content-type is correct
            } else {
                throw new Error(`Expected JSON, got: ${text}`); // Fail with the HTML or error message
            }
        })
        .then(data => {
            // console.log("Parsed JSON:", data);
            if (data.status === 'success') {
                // Sanitize inputs to prevent XSS
                const safePName = escapeHtml(pName);
                const safePPrice = escapeHtml(String(selectedPrice));

                // Build HTML string
                const addToCartHTML = `
                <span class="item-overlay-close" onclick="closeNoticePopup()">✕</span>
                <h2>Success!</h2>
                <div class="success-message">
                    <div class="overlay-item-dtls">
                        <img loading="lazy" src="/uploads/${escapeHtml(imgName)}" alt="${safePName}">
                        <div class="item-details">
                            <h3 class="item-name">${safePName}</h3>
                            <p class="item-price">R${safePPrice}</p>
                        </div>
                    </div>
                </div>
                <p>Item has been added successfully.</p>
                <div class="popup-btn">
                    <a href="/cart" class="popup-view-btn" onclick="viewBag()">
                        <i class="ph ph-shopping-cart-simple"></i> View Cart
                    </a>
                </div>
            `;
                hideBtnLoader(`btn_${pID}`);
                // Update DOM
                const popup = document.getElementById('added-to-cart-popup');
                if (popup) {
                    popup.innerHTML = addToCartHTML;
                } else {
                    // console.warn('Popup element not found');
                }

                // Execute UI updates
                addedToCartOverlay();
                updateBagQuantity(data.newQuantity);
            } else {
                alert(`Failed to add item to your bag: ${data.message || 'Unknown error'}`);
            }
        })
        .catch(error => {
            // console.error('Error adding to cart:', error);
            alert(`An error occurred: ${error.message || 'Please try again later'}`);
        })
}

// Helper function to escape HTML and prevent XSS
function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Optional: Debounce wrapper to prevent rapid calls
const debouncedAddToCart = debounce(addToCart, 300);

// Debounce helper function
function debounce(func, delay) {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(null, args), delay);
    };
}

function closeNoticePopup() {
    document.body.classList.remove('no-scroll');
    document.getElementById("success-overlay").style.display = "none";
}
