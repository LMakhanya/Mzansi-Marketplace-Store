/* Start Search Bar */
// Call function on Enter key press
document.getElementById("searchInput").addEventListener("keydown", function (event) {
  if (event.key === "Enter") {
    event.preventDefault(); // optional, prevents form submission if inside a form
    searchProduct();
  }
});

// Your search function
function searchProduct() {
  var searchInput = document.getElementById('searchInput').value;

  if (searchInput.trim() === "") {
    return;
  } else {
    var searchUrl = '/search/' + encodeURIComponent(searchInput);
    window.location.href = searchUrl;
  }
}

/* End Search Bar */


/* ******************************* Category Start ******************************* */
const navbar = document.getElementById('navbar');
if (navbar) {

  // Organize child categories into columns of 9
  document.querySelectorAll('.child-categories').forEach(container => {
    const items = Array.from(container.children);
    const chunkSize = 9;
    container.innerHTML = ''; // Clear old items

    for (let i = 0; i < items.length; i += chunkSize) {
      const column = document.createElement('div');
      column.classList.add('child-categories-column');
      items.slice(i, i + chunkSize).forEach(item => column.appendChild(item));
      container.appendChild(column);
    }
  });

  const browseAllBtn = document.getElementById('browseAllBtn');
  const categoriesDropdown = document.getElementById('categoriesDropdown');
  const parentCategories = document.querySelectorAll('.parent-category');
  const childCategoryGroups = document.querySelectorAll('.child-categories');

  const browseAllContainer = document.querySelector('.browse-all-container');

  // Hover behavior (for desktop only)
  browseAllContainer.addEventListener('mouseenter', () => {
    if (window.innerWidth > 768) {
      browseAllBtn.classList.add('_active');
      categoriesDropdown.classList.add('_active');
      browseAllBtn.setAttribute('aria-expanded', 'true');
    }
  });

  browseAllContainer.addEventListener('mouseleave', () => {
    if (window.innerWidth > 768) {
      browseAllBtn.classList.remove('_active');
      categoriesDropdown.classList.remove('_active');
      browseAllBtn.setAttribute('aria-expanded', 'false');
    }
  });

  // Click toggle (for mobile or small screens)
  browseAllBtn.addEventListener('click', (e) => {
    if (window.innerWidth <= 768) {
      e.preventDefault();
      const isActive = browseAllBtn.classList.contains('_active');
      browseAllBtn.classList.toggle('_active', !isActive);
      categoriesDropdown.classList.toggle('_active', !isActive);
      browseAllBtn.setAttribute('aria-expanded', !isActive);
    }
  });


  // Also close dropdown when hovering outside it entirely
  document.addEventListener('mousemove', (event) => {
    if (!browseAllContainer.contains(event.target)) {
      browseAllBtn.classList.remove('_active');
      categoriesDropdown.classList.remove('_active');
      browseAllBtn.setAttribute('aria-expanded', 'false');
    }
  });

  // Handle category hover (desktop) and click (mobile + desktop)
  parentCategories.forEach(category => {
    // Hover (desktop only)
    category.addEventListener('mouseenter', (event) => {
      if (window.innerWidth > 768) {
        handleCategoryInteraction(event.currentTarget);
      }
    });

    // Click (both mobile & desktop)
    category.addEventListener('click', (event) => {
      event.preventDefault();
      handleCategoryInteraction(event.currentTarget);
    });
  });

  function handleCategoryInteraction(category) {
    const targetCategory = category.getAttribute('data-category');
    const childCategories = document.getElementById(targetCategory);
    const isActive = childCategories.classList.contains('_active');

    if (window.innerWidth <= 768) {
      // Mobile: Toggle selected category
      if (isActive) {
        // Close if already open
        childCategories.classList.remove('_active');
        category.classList.remove('_active');
      } else {
        // Close all, then open selected
        childCategoryGroups.forEach(group => group.classList.remove('_active'));
        parentCategories.forEach(pc => pc.classList.remove('_active'));

        childCategories.classList.add('_active');
        category.classList.add('_active');
      }
    } else {
      // Desktop: Always show selected category
      parentCategories.forEach(pc => pc.classList.remove('_active'));
      category.classList.add('_active');

      childCategoryGroups.forEach(group => {
        group.classList.toggle('_active', group.id === targetCategory);
      });
    }
  }

  // Adjust layout on load and resize
  function setupResponsiveLayout() {
    if (window.innerWidth <= 768) {
      // Mobile layout
      childCategoryGroups.forEach(group => group.classList.remove('_active'));

      parentCategories.forEach(category => {
        const target = document.getElementById(category.getAttribute('data-category'));
        if (target) {
          category.insertAdjacentElement('afterend', target);

          // Append ▼ if not already present
          if (!category.innerHTML.includes('▼')) {
            category.innerHTML = category.innerHTML.replace(/<i>.*<\/i>/, '') + '<i>▼</i>';
          }
        }
      });

      // Optionally open default category
      const defaultID = ''; // Optional: set to something like 'Electronics'
      const defaultGroup = document.getElementById(defaultID);
      if (defaultGroup) defaultGroup.classList.add('_active');
      const defaultParent = document.querySelector(`.parent-category[data-category="${defaultID}"]`);
      if (defaultParent) defaultParent.classList.add('_active');

    } else {
      // Desktop layout
      childCategoryGroups.forEach(group => {
        group.classList.toggle('_active', group.id === 'Electronics');
      });

      parentCategories.forEach(pc => pc.classList.remove('_active'));

      const defaultParent = document.querySelector('.parent-category[data-category="Electronics"]');
      if (defaultParent) defaultParent.classList.add('_active');
    }
  }

  // Init
  window.addEventListener('load', setupResponsiveLayout);
  window.addEventListener('resize', setupResponsiveLayout);

}

/* ******************************* Category End ******************************* */

document.addEventListener("DOMContentLoaded", function () {

  // Scroll when pagination links are clicked
  document.querySelectorAll(".pagination-link").forEach(link => {
    link.addEventListener("click", function (e) {
      setTimeout(scrollToSection, 100); // Delay for smoother transition
    });
  });

  /* ******************************* Settings Nav ******************************* */
  // Add an event listener to close notifications and overlay on any click
  document.addEventListener('click', function (event) {
    const miniNotifications = document.querySelector('.alert');

    const profile = document.querySelector('.user-info');
    const profileOverlay = document.getElementById('profile-overlay');
    const profileIcon = document.getElementById('profile-icon');

    /*  if (miniNotifications && !miniNotifications.contains(event.target)) {
       document.querySelector('.alerts-list').style.display = 'none';
     } */

    if (profile && !profile.contains(event.target)) {
      // Close the profile overlay if it's visible and the click is outside it
      if (profileOverlay && profileOverlay.classList.contains('show') &&
        !profileOverlay.contains(event.target) &&
        event.target !== profileIcon) {
        profileOverlay.classList.remove('show');
        profileIcon.setAttribute('name', 'caret-down-outline');
      }
    }
  });
});

function selectBusiness(businessName) {
  document.getElementById("store").value = businessName; // Update the hidden input value
  alert(`You selected: ${businessName}`);
}
function toggleSearchBar() {
  const searchBar = document.getElementById('searchBar');
  searchBar.classList.toggle('active');
}

function closeSearchBar() {
  const searchBar = document.getElementById('searchBar');
  searchBar.classList.remove('active');
}

// Optional: Close the search bar when clicking outside it
document.addEventListener('click', (event) => {
  const searchBar = document.getElementById('searchBar');
  if (searchBar.contains(event.target)) {
    closeSearchBar();
  }
});



function submitSubCat(category, sub_cat,) {
  // Create a new URL object based on the current window location
  const url = new URL(window.location);

  // Set the new category, sub_cat_opt, and type parameters in the URL
  url.searchParams.set('category', category);     // Ensure 'category' is passed to the function
  if (sub_cat) {
    url.searchParams.set('type_opt', sub_cat);
  }

  // Update the browser's URL without reloading
  window.history.pushState({}, '', url);

  // Reload the page to reflect the updated URL
  window.location.reload();
}


function redirectToProfile(userEmail) {

  if (userEmail == "" || userEmail == null) {
    openPopup('accountOverlay');
  } else {
    window.location.href = "/user/";
  }
}


function logout() {
  window.location.href = '/assets/php/logout.php'
}

const elements = document.querySelectorAll('.glow-animation');

function showLetters(index) {
  if (index < elements.length) {
    elements[index].classList.add('show');
    setTimeout(() => showLetters(index + 1), 200);
  } else {
    setTimeout(() => makeWordGlow(), 500);
  }
}

function makeWordGlow() {
  elements.forEach((element) => {
    element.classList.add('glow');
  });
}


