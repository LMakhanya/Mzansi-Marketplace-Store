const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => document.querySelectorAll(selector);

// Define updateProgress function
function updateProgress(step) {
    const progressBar = $('#progressBar');
    const totalSteps = 2;
    const percentage = (step / totalSteps) * 100;
    progressBar.style.width = `${percentage}%`;
}

// Step 1: ID Validation
const idForm = $('#selfieForm');
const saIdInput = $('#saIdNumber');
const errorMessage = $('#errorMessage');
const step1 = $('#step1');
const step2 = $('#step2');

// Function to format SA ID number
function formatSAIdNumber(input) {
    let value = input.value.replace(/\D/g, '');
    value = value.slice(0, 13);
    let formatted = '';
    if (value.length > 0) {
        formatted += value.slice(0, 6);
    }
    if (value.length > 6) {
        formatted += ' ' + value.slice(6, 10);
    }
    if (value.length > 10) {
        formatted += ' ' + value.slice(10, 12);
    }
    if (value.length > 12) {
        formatted += ' ' + value.slice(12, 13);
    }
    input.value = formatted.trim();
}

function validateSAId(id) {
    if (!/^\d{13}$/.test(id)) return false;
    const date = id.substring(0, 6);
    const year = parseInt(id.substring(0, 2));
    const month = parseInt(id.substring(2, 4));
    const day = parseInt(id.substring(4, 6));
    const fullYear = year < 23 ? `20${year}` : `19${year}`;
    const isValidDate = (d, m, y) => {
        const date = new Date(y, m - 1, d);
        return date.getFullYear() == y && date.getMonth() == m - 1 && date.getDate() == d;
    };
    if (!isValidDate(day, month, fullYear)) return false;
    let sum = 0;
    for (let i = 0; i < 12; i++) {
        let digit = parseInt(id[i]);
        if (i % 2 === 1) {
            digit *= 2;
            if (digit > 9) digit -= 9;
        }
        sum += digit;
    }
    const checkDigit = (10 - (sum % 10)) % 10;
    return checkDigit === parseInt(id[12]);
}

idForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (validateSAId(saIdInput.value.replace(/\s/g, ''))) {
        step1.classList.remove('active');
        step2.classList.add('active');
        updateProgress(2);
    } else {
        saIdInput.classList.add('error');
        errorMessage.style.display = 'block';
    }
});

saIdInput.addEventListener('input', (e) => {
    formatSAIdNumber(e.target);
    saIdInput.classList.remove('error');
    errorMessage.style.display = 'none';
    if (saIdInput.value.replace(/\s/g, '').length === 13) {
        validateSAId(saIdInput.value.replace(/\s/g, ''))
            ? errorMessage.style.display = 'none'
            : errorMessage.style.display = 'block';
    }
});

// Step 2: Selfie Verification (unchanged)
const video = document.getElementById('camera');
const canvas = document.createElement('canvas');
const photo = document.getElementById('photo');
let takePhotoButton = document.getElementById('takePhoto');
const submitPhotoButton = document.getElementById('submitPhoto');
const retakePhotoButton = document.getElementById('retakePhoto');
const imageDataInput = document.getElementById('imageData');
const loader = document.getElementById('loader');
const successMessage = document.getElementById('successMessage');

const maskOverlay = document.getElementById('maskOverlay');
const cameraContainer = document.getElementById('cameraContainer');
const rules = document.getElementById('rules');
const openCameraButton = document.getElementById('openCamera');

let mediaStream;

function openCamera() {
    rules.style.display = 'none';
    openCameraButton.style.display = 'none';
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
        .then(stream => {
            mediaStream = stream;
            video.srcObject = stream;
            maskOverlay.style.display = 'flex';
            cameraContainer.style.display = 'block';
        })
        .catch(err => {
            alert('Error accessing the camera. Please allow camera access.');
        });
    takePhotoButton.classList.remove('hidden');
    submitPhotoButton.disabled = false;
}

function stopCamera() {
    if (mediaStream) {
        mediaStream.getTracks().forEach(track => track.stop());
    }
    video.style.display = 'none';
    maskOverlay.style.display = 'none';
    cameraContainer.style.display = 'none';
}

takePhotoButton.addEventListener('click', () => {
    const context = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.scale(-1, 1);
    context.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
    const imageData = canvas.toDataURL('image/png');
    photo.src = imageData;
    photo.style.display = 'block';
    imageDataInput.value = imageData;
    stopCamera();
    submitPhotoButton.classList.remove('hidden');
    retakePhotoButton.classList.remove('hidden');
    takePhotoButton.classList.add('hidden');
    submitPhotoButton.disabled = false;
});

submitPhotoButton.addEventListener('click', () => {
    loader.style.display = 'block';
    submitPhotoButton.disabled = true;
    // Ensure actionInput exists before setting its value
    const actionInput = document.getElementById('actionInput');
    if (actionInput) {
        actionInput.value = 'upload';
    }

    // Submit the form only once
    document.getElementById('selfieForm').submit();
    setTimeout(() => {
        loader.style.display = 'none';
        successMessage.classList.remove('hidden');
    }, 2000);
});

retakePhotoButton.addEventListener('click', () => {
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
        .then(stream => {
            mediaStream = stream;
            video.srcObject = stream;
            maskOverlay.style.display = 'flex';
            cameraContainer.style.display = 'block';
        })
        .catch(err => {
            alert('Error accessing the camera. Please allow camera access.');
        });
    photo.style.display = 'none';
    video.style.display = 'block';
    submitPhotoButton.classList.add('hidden');
    retakePhotoButton.classList.add('hidden');
    takePhotoButton.classList.remove('hidden');
});

function cancelReg() {
    window.open("/registration/", "_blank");
}

// Initial setup
updateProgress(1);