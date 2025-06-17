function processCart(action, bagID, product_id, price, size) {
    $.post('/assets/php/quantity.php', {
        action: action,
        bagID: bagID,
        product_id: product_id,
        price: price,
        size: size
    }, function (response) {
        if (response.status === 'success') {
            console.log('Quantity updated successfully:', response);
            updateBagQuantity(response.quantity);

            if (action !== 'delete') {
                fetchCartItems(bagID); // Refresh cart items
                // Update quantity in UI
                const quantityInput = document.querySelector(`.quantity_${product_id}`);
                const pTag = document.querySelector(`.pQuantity_${product_id}`);

                if (quantityInput && pTag) {
                    quantityInput.value = response.quantity;
                    pTag.textContent = response.quantity;
                } else {
                    console.warn(`UI elements for product_id ${product_id} not found`);
                }

                // Update session-based total quantity display
                const sessionQuantityDisplay = document.querySelector('.c_number p');
                if (sessionQuantityDisplay) {
                    sessionQuantityDisplay.textContent = response.quantity; // Use response.quantity instead of session2
                }
            } else {
                fetchCartItems(bagID); // Refresh after deletion
            }
            fetchBagItems(); // Assuming this updates another part of the UI
        } else {
            console.error('Failed to update quantity:', response);
            alert(`Error: ${response.message || 'Unknown error occurred'} - Details: ${response.errorDetails || 'No additional details'}`);
        }
    }, 'json')
        .fail(function (jqXHR, textStatus, errorThrown) {
            console.error('AJAX request failed:', textStatus, errorThrown, jqXHR.responseText);
            alert(`Network error: ${textStatus} - ${errorThrown}`);
        });
}

function fetchCartItems() {
    const itemBody = document.getElementById('item_container');
    const checkoutButton = document.getElementById('checkoutButton');
    const loader = document.getElementById('loader');
    const loaderOverlay = document.getElementById('loader-overlay');

    // Early return if critical DOM elements are missing
    if (!itemBody || !checkoutButton || !loader || !loaderOverlay) {
        console.warn('Required DOM elements not found: item_container, checkoutButton, loader, or loader-overlay');
        return;
    }

    // Show loader and overlay before fetching products
    loader.style.display = 'block';
    loaderOverlay.style.display = 'block';

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10-second timeout

    fetch('/assets/php/quantity.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=fetch',
        signal: controller.signal
    })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            const contentType = response.headers.get('Content-Type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new Error(`Expected JSON, got ${contentType || 'unknown type'}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            // console.log('Raw response:', data);
            if (data.status === 'success') {
                if (Array.isArray(data.items) && data.items.length > 0) {
                    checkoutButton.disabled = false;
                    checkoutButton.style.backgroundColor = '#292929';
                    itemBody.innerHTML = data.items.map(item => `
                    <div class="bag_item">
                        <div class="pImage">
                            <img loading="lazy" src="/uploads/${item.product_image || 'default-placeholder.jpg'}" alt="${item.ProductName || 'Product'}">
                        </div>
                        <div class="b_item_details">
                            <p id="pName">${item.ProductName || 'Unnamed Product'}</p>
                            <p id="pDescription">Design: <span>${item.brandName || 'N/A'}</span></p>
                            <p id="pPrice">Item price: <span>R${item.Price || '0.00'}</span></p>
                            <p id="pQuantity">Quantity: <span class="pQuantity_${item.product_id}">${item.Quantity || 1}</span></p>
                            <div class="quantity_btn">
                                <div>
                                    <button name="minusQ" onclick="processCart('minusQ', '${item.bagID}', '${item.product_id}', ${item.Price || 0})">
                                        <i class="fas fa-minus" id="remove_quantity"></i>
                                    </button>
                                </div>
                                <input type="text" name="quantity" class="quantity_${item.product_id}" value="${item.Quantity || 1}">
                                <div>
                                    <button name="addQ" onclick="processCart('addQ', '${item.bagID}', '${item.product_id}', ${item.Price || 0})">
                                        <i class="fas fa-plus" id="add_quantity"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="delete">
                            <button name="delete" onclick="processCart('delete', '${item.bagID}', '${item.product_id}', ${item.Price || 0})">
                                <i class="fas fa-trash" name="delete" id="trash"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
                } else {
                    checkoutButton.disabled = true;
                    checkoutButton.style.backgroundColor = 'darkgrey';
                    itemBody.innerHTML = '<p style="text-align:center;">No item(s) found.</p>';
                }
            } else {
                checkoutButton.disabled = true;
                checkoutButton.style.backgroundColor = 'darkgrey';
                itemBody.innerHTML = '<p style="text-align:center;">Error fetching items: ' + (data.message || 'Unknown error') + '</p>';
            }
            // Hide loader and overlay after products or message are visible
            loader.style.display = 'none';
            loaderOverlay.style.display = 'none';
        })
        .catch(error => {
            console.error('Fetch error:', error.message);
            checkoutButton.disabled = true;
            checkoutButton.style.backgroundColor = 'darkgrey';
            itemBody.innerHTML = '<p style="text-align:center;">Failed to load cart items. Please try again later.</p>';
            // Hide loader and overlay after error message is visible
            loader.style.display = 'none';
            loaderOverlay.style.display = 'none';
        });
}

// Call the function
fetchCartItems();

function processQuantity(action, product_id, bagID, pPrice, size) {

    const quantityInput = document.querySelector(`#quantityInput_${product_id}_${size}`);

    console.log(product_id);
    
    let currentQuantity = parseInt(quantityInput.value, 10) || 1;

    // Prevent quantity from going below 1
    if (currentQuantity === 1 && action === 'minusQ') {
        return alert('Quantity cannot be less than 1');
    }

    // Adjust quantity based on action
    const newQuantity = action === 'addQ' ? currentQuantity + 1 : Math.max(currentQuantity - 1, 1);
    const price = newQuantity * pPrice; // Calculate total price
    // Optimistically update the UI
    quantityInput.value = newQuantity;

    // Send request to server
    fetch('/assets/php/quantity.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: action,
            product_id: product_id,
            bagID: bagID,
            price: price,
            quantity: newQuantity,
            size: size
        })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Update UI elements (assuming pTag exists elsewhere)
                const pTag = document.querySelector(`.pQuantity_${product_id}`);
                if (pTag) {
                    pTag.textContent = data.quantity;
                }

                // Update bag quantity and cart items (assuming these functions exist)
                updateBagQuantity(data.quantity);
                fetchCartItems(bagID);
                fetchBagItems();

                // Update session-based total quantity display
                const sessionQuantityDisplay = document.querySelector('.c_number p');
                if (sessionQuantityDisplay) {
                    sessionQuantityDisplay.textContent = data.quantity;
                }
            } else {
                console.error('Failed to update quantity:', data);
                // Revert UI on failure
                quantityInput.value = currentQuantity;
                alert(`Error: ${data.message || 'Unknown error occurred'} - Details: ${data.errorDetails || 'No additional details'}`);
            }
        })
        .catch(error => {
            console.error('Error updating quantity:', error);
            // Revert UI on network error
            quantityInput.value = currentQuantity;
            alert(`Network error: ${error.message || 'Please try again later'}`);
        });
}
