
var sideProfilePanel = document.getElementById('side-profile');
var minimiseBtn = document.getElementById('minimise');
var profileDiv = document.querySelector('.p_col.one');
var profileDiv = document.querySelector('.p_col.one');

var profileScreenWidth = window.innerWidth;

if (profileScreenWidth >= 0 && profileScreenWidth < 767) {
    sideProfilePanel.style.display = 'flex';
    minimiseBtn.style.display = 'flex';
    profileDiv.style.width = '0vw';

} else {
    sideProfilePanel.style.display = 'flex';
    minimiseBtn.style.display = 'none';

}

function toggleCloseProfile() {

    // Toggle the display property
    if (profileDiv.style.width === '0vw') {
        profileDiv.style.display = 'flex';
        profileDiv.style.width = '55vw';
    } else {
        profileDiv.style.width = '0vw';
    }
}


// JavaScript function to toggle visibility of additional information
function toggleAdditionalInfo(button) {
    var card = button.closest('.addressCard');
    var additionalInfo = card.querySelector('.additional-info');
    additionalInfo.classList.toggle('show');
}

document.addEventListener("DOMContentLoaded", function () {
    var profileOptions = document.querySelectorAll('.profile-options li');
    var pTextContents = document.querySelectorAll('.p_text_content');

    profileOptions.forEach(function (option) {
        option.addEventListener('click', function () {
            var target = this.getAttribute('data-target');

            // Hide all p_text_content divs
            pTextContents.forEach(function (content) {
                content.style.display = 'none';
            });

            // Remove 'selected' class from all list items
            profileOptions.forEach(function (item) {
                item.classList.remove('selected');
            });

            if (profileScreenWidth >= 0 && profileScreenWidth <= 767) {
                toggleCloseProfile();
            }

            // Show the selected p_text_content div
            document.querySelector('.' + target).style.display = 'block';

            // Adjusted condition with proper comparison operator
            /*    if (target === 'deliveryAddress') {
                   document.querySelector('.' + target).style.display = 'flex';
               } */

            // Add 'selected' class to the clicked list item
            this.classList.add('selected');
        });
    });
});


function toggleEdit() {
    const inputs = document.querySelectorAll('.text-field');
    const editButton = document.querySelector('.edit-button');
    const saveButton = document.querySelector('.save-button');

    inputs.forEach(input => {
        input.disabled = !input.disabled;
    });

    if (editButton.textContent === 'Edit Details') {
        editButton.textContent = 'Cancel';
        saveButton.style.display = 'inline-block';
    } else {
        editButton.textContent = 'Edit Details';
        saveButton.style.display = 'none';
        // Reset form to original values if needed
    }
}

function openPasswordModal() {
    // Implement password change modal logic here
    alert('Password change functionality to be implemented');
}


if (errorParams.get('personalD')) {
    if (errorParams.get('personalD') == 'success') {
        showAlert('success', 'Your profile details have been updated successfully');
    }
    else {
        showAlert('error', 'Failed to update your profile details');
    }
    removeParam('personalD');
}

function removeParam(name) {
    var url = new URL(window.location.href);
    url.searchParams.delete(name);
    window.history.replaceState({}, document.title, url);
}


/* Addess */
function showAddForm() {
    document.getElementById('addressList').style.display = 'none';
    document.getElementById('addNewAddressBtn').style.display = 'none';
    document.getElementById('addAddressForm').style.display = 'block';
}

function hideAddForm() {
    document.getElementById('addressList').style.display = 'block';
    document.getElementById('addNewAddressBtn').style.display = 'block';
    document.getElementById('addAddressForm').style.display = 'none';
}

function selectAddress(addressId) {

    document.querySelectorAll('.address-card').forEach(card => {
        card.classList.remove('selected');
    });

    const selectedCard = document.querySelector(`.address-card[data-address-id="${addressId}"]`);
    selectedCard.classList.add('selected');

    fetch('/api/profile/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'set_shipping',
            addressId: addressId,
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Address selected:', data.message);
                showAlert('success', 'Shipping Address update successfuly.');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Handle form submission with AJAX
document.getElementById('addAddressFormElement').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent form submission

    const formData = new FormData(this);
    const formObject = Object.fromEntries(formData.entries()); // Convert to a plain object

    fetch('/api/profile/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formObject) // Convert object to JSON
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Reload or update UI dynamically
                showAlert('success', 'Address added successfully');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
});

function toggleAdditionalInfo(button) {
    const card = button.closest('.address-card');
    const details = card.querySelector('.address-details');
    details.style.display = details.style.display === 'none' ? 'block' : 'none';

    const editBtn = card.querySelector('.edit-btn');
    editBtn.style.display = details.style.display === 'block' ? 'inline-block' : 'none';

    const removeBtn = card.querySelector('.remove-btn');
    removeBtn.style.display = details.style.display === 'block' ? 'inline-block' : 'none';
}

function editAddress(button) {
    const card = button.closest('.address-card');
    const inputs = card.querySelectorAll('.address-details input');

    inputs.forEach(input => input.removeAttribute('disabled'));

    button.textContent = 'Save';
    button.onclick = function () { saveAddress(card, button); };
}

function saveAddress(card, button) {
    const addressId = card.getAttribute('data-address-id');
    const inputs = card.querySelectorAll('.address-details input');

    const updatedData = {};
    inputs.forEach(input => {
        updatedData[input.name] = input.value;
        input.setAttribute('disabled', true);
    });
    console.log(updatedData);

    updatedData.action = 'update_address';
    updatedData.addressId = addressId;

    fetch('/api/profile/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updatedData)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Address updated successfully');

                // Clear button content
                button.innerHTML = '';

                // Create new icon element
                const icon = document.createElement('ion-icon');
                icon.setAttribute('name', 'create-outline');

                // Append the icon to the button
                button.appendChild(icon);

                // Set onclick to edit mode
                button.onclick = function () { editAddress(button); };
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
}


function removeAddress(button) {
    const card = button.closest('.address-card');
    const addressId = card.getAttribute('data-address-id');

    if (confirm('Are you sure you want to delete this address?')) {
        fetch('/api/profile/', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_address', addressId: addressId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    card.remove();
                    showAlert('success', 'Address removed successfully');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
    }
}
