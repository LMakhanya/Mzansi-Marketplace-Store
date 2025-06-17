document.getElementById('side-filter').style.display = 'none';

let currentIndex = 0;
const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");
const totalSlides = slides.length;
const isSmallScreen = () => window.matchMedia("(max-width: 768px)").matches;

function getMaxIndex() {
    return isSmallScreen() ? totalSlides - 1 : totalSlides - 2;
}

function changeSlide(index) {
    const maxIndex = getMaxIndex();
    currentIndex = Math.min(Math.max(index, 0), maxIndex);
    updateSlider();
}

function updateSlider() {
    const translatePercentage = isSmallScreen() ? currentIndex * 100 : currentIndex * 50;
    document.querySelector(".slider").style.transform = `translateX(-${translatePercentage}%)`;
    dots.forEach((dot, i) => dot.classList.toggle("active", i === currentIndex));
}

function autoSlide() {
    const maxIndex = getMaxIndex();
    currentIndex = currentIndex >= maxIndex ? 0 : currentIndex + 1;
    updateSlider();
}

// Update slider on window resize
window.addEventListener('resize', () => {
    changeSlide(currentIndex);
});

setInterval(autoSlide, 10000);

// Initial update to ensure correct positioning
updateSlider();