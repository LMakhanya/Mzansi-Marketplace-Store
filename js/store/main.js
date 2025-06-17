// Get the category and sub-category params from the URL
const indexUrlParams = new URLSearchParams(window.location.search);
const pcategoryName = indexUrlParams.get('category') || ''; // Default to '' if not present
const pSubcategory = indexUrlParams.get('subcategory') || ''; // Default to '' if not present

// On page load, run a function
fetchItems(pcategoryName, pSubcategory);