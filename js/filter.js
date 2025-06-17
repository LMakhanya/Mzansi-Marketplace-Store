window.addEventListener("scroll", function () {
    const filterPanel = document.getElementById("filterPanel");
    const header = document.querySelector(".shop-header");

    const headerHeight = header.offsetHeight;
    filterPanel.style.top = headerHeight + "px"; // Dynamically adjust position
});

document.getElementById('side-filter').style.display = 'block';
document.getElementById('breadcrumb').style.display = 'flex';

var storeLastScrollTop = 0;
var s_col_one = document.querySelector(".s_col");
var filterPanel = document.getElementById('filterPanel');
var closefilter = document.getElementById('close-filter');
var filterScreenWidth = window.innerWidth;


document.querySelectorAll('.color-option').forEach(label => {
    label.addEventListener('click', function (e) {
        if (e.target.tagName !== 'INPUT') {
            const input = this.querySelector('input');
            input.checked = true;
            input.dispatchEvent(new Event('change'));
        }
    });
});

document.querySelectorAll('.brand-option').forEach(label => {
    label.addEventListener('click', function (e) {
        if (e.target.tagName !== 'INPUT') {
            const input = this.querySelector('input');
            input.checked = true;
            input.dispatchEvent(new Event('change'));
        }
    });
});

function toggleOptions(optionType) {
    var options = document.getElementById(optionType + 'Options');
    var downIcon = document.querySelector('.fa-chevron-down');
    var upIcon = document.querySelector('.fa-chevron-up');

    if (options.style.display === 'none' || options.style.display === '') {
        options.style.display = 'block';
        downIcon.style.display = 'none';
        upIcon.style.display = 'inline';
    } else {
        options.style.display = 'none';
        downIcon.style.display = 'inline';
        upIcon.style.display = 'none';
    }
}
function toggleFilter() {
    var filterDiv = document.querySelector('.filter_container_demo');

    // Get the computed display property
    var computedStyle = window.getComputedStyle(filterDiv).display;

    // Toggle display based on computed style
    if (computedStyle === 'none') {
        filterDiv.style.display = 'flex';
        filterDiv.style.width = 'fit-content';
    } else {
        filterDiv.style.display = 'none';
    }
}


function submitFilter(filterType, value) {
    let urlParams = new URLSearchParams(window.location.search);
    console.log(filterType, value);
    // Update the URL query parameters with the selected filter value
    urlParams.set(filterType, value);
    // update page param

    urlParams.set('page', 1);

    console.log(urlParams);
    window.location.search = urlParams.toString();
}


function applyPriceFilter() {
    let minPrice = document.getElementById('minPrice').value.trim();
    let maxPrice = document.getElementById('maxPrice').value.trim();

    if (minPrice === '' || maxPrice === '' || isNaN(minPrice) || isNaN(maxPrice) || parseInt(minPrice) > parseInt(maxPrice)) {
        alert("Please enter a valid price range.");
        return;
    }

    let urlParams = new URLSearchParams(window.location.search);
    urlParams.set('minPrice', minPrice);
    urlParams.set('maxPrice', maxPrice);
    window.location.search = urlParams.toString();
}

document.addEventListener('DOMContentLoaded', function () {
    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);

    // Helper function to check the selected radio and update the label input
    function setSelectedRadio(name, value) {
        const radios = document.getElementsByName(name);
        radios.forEach(radio => {
            if (radio.value === value) {
                radio.checked = true;
                const label = radio.closest('label').querySelector('input');
                if (label) {
                    label.value = value; // Set the disabled input value
                }
            }
        });
    }

    // Set the selected filters based on URL parameters
    if (urlParams.has('condition')) {
        setSelectedRadio('type_opt', urlParams.get('condition'));
    }
    if (urlParams.has('brand')) {
        setSelectedRadio('brand_opt', urlParams.get('brand'));
    }
    if (urlParams.has('color')) {
        setSelectedRadio('color_opt', urlParams.get('color'));
    }

    // Handle price filter
    if (urlParams.has('minPrice') && urlParams.has('maxPrice')) {
        document.getElementById('minPrice').value = urlParams.get('minPrice');
        document.getElementById('maxPrice').value = urlParams.get('maxPrice');
        const priceLabel = document.getElementById('priceLabel');
        priceLabel.value = `R${urlParams.get('minPrice')} - R${urlParams.get('maxPrice')}`;
    }

    // Handle delivery option
    if (urlParams.has('delivery')) {
        setSelectedRadio('delivery_opt', urlParams.get('delivery'));
    }

    // Handle seller option
    if (urlParams.has('seller')) {
        setSelectedRadio('seller_opt', urlParams.get('seller'));
    }
});
