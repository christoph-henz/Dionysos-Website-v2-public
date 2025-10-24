let slideIndex = 0;

function showSlides(n) {
    const slides = document.getElementsByClassName("slide");
    const dots = document.getElementsByClassName("dot");

    if (n >= slides.length) slideIndex = 0;
    if (n < 0) slideIndex = slides.length - 1;

    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
        dots[i].classList.remove("active");
    }

    slides[slideIndex].style.display = "block";
    dots[slideIndex].classList.add("active");
}

function changeSlide(n) {
    showSlides(slideIndex += n);
}

function currentSlide(n) {
    showSlides(slideIndex = n);
}

function enlargeImage(img) {
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("modalImage");
    modal.style.display = "flex";
    modalImg.src = img.src;
}

function closeModal() {
    document.getElementById("imageModal").style.display = "none";
}

// Tastatur-Navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') {
        changeSlide(-1);
    } else if (e.key === 'ArrowRight') {
        changeSlide(1);
    }
});

// Touch-Gesten
let touchstartX = 0;
let touchendX = 0;

document.querySelector('.slideshow-container').addEventListener('touchstart', e => {
    touchstartX = e.changedTouches[0].screenX;
});

document.querySelector('.slideshow-container').addEventListener('touchend', e => {
    touchendX = e.changedTouches[0].screenX;
    handleGesture();
});

function handleGesture() {
    if (touchendX < touchstartX) changeSlide(1);
    if (touchendX > touchstartX) changeSlide(-1);
}

// Initialisierung
showSlides(slideIndex);