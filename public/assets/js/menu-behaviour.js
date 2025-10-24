let pdfDoc = null;
let currentPage = 1;
let totalPages = 0;

const canvas1 = document.getElementById("pdfCanvas1");
const canvas2 = document.getElementById("pdfCanvas2");
const ctx1 = canvas1.getContext("2d");
const ctx2 = canvas2.getContext("2d");

const renderPage = (num, canvas, ctx, callback) => {
    pdfDoc.getPage(num).then(page => {
        const containerWidth = window.innerWidth > 1000 ? window.innerWidth / 2 - 60 : window.innerWidth - 40;
        const viewport = page.getViewport({ scale: 1 });
        const scale = containerWidth / viewport.width;
        const scaledViewport = page.getViewport({ scale: scale });

        canvas.height = scaledViewport.height;
        canvas.width = scaledViewport.width;

        const renderContext = {
            canvasContext: ctx,
            viewport: scaledViewport
        };
        page.render(renderContext).promise.then(() => {
            if (callback) callback();
        });
    });
};

const renderCurrentPages = (withAnimation = false) => {
    const isWide = window.innerWidth > 1000;

    if (withAnimation) {
        canvas1.classList.add("flipping");
        canvas2.classList.add("flipping");
        setTimeout(() => {
            canvas1.classList.remove("flipping");
            canvas2.classList.remove("flipping");
        }, 600);
    }

    if (!isWide || currentPage === totalPages) {
        canvas2.style.display = "none";
        renderPage(currentPage, canvas1, ctx1);
    } else {
        canvas2.style.display = "inline-block";
        renderPage(currentPage, canvas1, ctx1, () => {
            if (currentPage + 1 <= totalPages) {
                renderPage(currentPage + 1, canvas2, ctx2);
            } else {
                canvas2.style.display = "none";
            }
        });
    }

    document.getElementById("pageNum").textContent = currentPage + (isWide && currentPage + 1 <= totalPages ? "–" + (currentPage + 1) : "");
};

window.addEventListener("resize", () => {
    renderCurrentPages();
});

// PDF-Laden mit verschiedenen Pfad-Versuchen
const loadPDF = async () => {
    // Bestimme den Basis-Pfad basierend auf der aktuellen URL
    const basePath = window.location.pathname.includes('/Dionysos-Website-v2/') 
        ? '/Dionysos-Website-v2/' 
        : '/';
    
    const paths = [
        basePath + "public/speisekarte.pdf",      // Korrekter XAMPP-Pfad
        "public/speisekarte.pdf",                 // Relativer Pfad
        "/public/speisekarte.pdf",                // Absoluter Pfad
        "speisekarte.pdf",                        // Direkter Pfad
        basePath + "speisekarte.pdf"              // Alternative
    ];
    
    for (let path of paths) {
        try {
            const pdf = await pdfjsLib.getDocument(path).promise;
            return pdf;
        } catch (error) {
            // Versuche nächsten Pfad
        }
    }
    throw new Error("PDF konnte von keinem Pfad geladen werden");
};

loadPDF().then(pdf => {
    pdfDoc = pdf;
    totalPages = pdf.numPages;
    document.getElementById("pageCount").textContent = totalPages;
    renderCurrentPages();
}).catch(error => {
    document.getElementById("pageCount").textContent = "PDF nicht verfügbar";
    
    // Zeige Fehlermeldung im Canvas
    const canvas = document.getElementById("pdfCanvas1");
    const ctx = canvas.getContext("2d");
    canvas.width = 400;
    canvas.height = 300;
    ctx.fillStyle = "#f0f0f0";
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = "#666";
    ctx.font = "16px Arial";
    ctx.textAlign = "center";
    ctx.fillText("Speisekarte wird geladen...", canvas.width/2, canvas.height/2 - 20);
    ctx.fillText("Falls das Problem bestehen bleibt,", canvas.width/2, canvas.height/2);
    ctx.fillText("kontaktieren Sie uns unter 06021 25779", canvas.width/2, canvas.height/2 + 20);
});

document.getElementById("prevPage").addEventListener("click", () => {
    const isWide = window.innerWidth > 1000;
    if (currentPage <= 1) return;
    currentPage -= isWide ? 2 : 1;
    if (currentPage < 1) currentPage = 1;
    renderCurrentPages(true);
});

document.getElementById("nextPage").addEventListener("click", () => {
    const isWide = window.innerWidth > 1000;
    if (currentPage >= totalPages) return;
    currentPage += isWide ? 2 : 1;
    if (currentPage > totalPages) currentPage = totalPages;
    renderCurrentPages(true);
});

// Zusätzliche Event-Listener
document.getElementById("firstPage").addEventListener("click", () => {
    currentPage = 1;
    renderCurrentPages(true);
});

document.getElementById("lastPage").addEventListener("click", () => {
    currentPage = totalPages;
    renderCurrentPages(true);
});

// Tastatur-Navigation
document.addEventListener("keydown", (e) => {
    if (e.key === "ArrowLeft") {
        document.getElementById("prevPage").click();
    } else if (e.key === "ArrowRight") {
        document.getElementById("nextPage").click();
    }
});

// Touch-Events für Swipe auf page-wrapper
const pageWrapper = document.querySelector('.page-wrapper');
let startX = 0;
let startY = 0;
let distX = 0;
let distY = 0;

pageWrapper.addEventListener('touchstart', function(e) {
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
    e.preventDefault(); // Verhindert Standard-Touch-Verhalten
}, { passive: false });

pageWrapper.addEventListener('touchmove', function(e) {
    if (!startX || !startY) return;

    distX = e.touches[0].clientX - startX;
    distY = e.touches[0].clientY - startY;
    e.preventDefault(); // Verhindert Scrolling während des Swipens
}, { passive: false });

pageWrapper.addEventListener('touchend', function(e) {
    if (!startX || !startY) return;

    const threshold = 50; // Minimale Swipe-Distanz
    const restraint = 100; // Maximale vertikale Abweichung
    const allowedTime = 300; // Maximale Zeit für Swipe in ms

    // Prüfe, ob die vertikale Bewegung kleiner als die Beschränkung ist
    if (Math.abs(distY) <= restraint) {
        // Prüfe, ob die horizontale Bewegung größer als der Schwellenwert ist
        if (Math.abs(distX) >= threshold) {
            if (distX > 0) {
                // Swipe nach rechts -> vorherige Seite
                document.getElementById("prevPage").click();
            } else {
                // Swipe nach links -> nächste Seite
                document.getElementById("nextPage").click();
            }
        }
    }

    // Reset der Werte
    startX = 0;
    startY = 0;
    distX = 0;
    distY = 0;
}, { passive: false });

// Optional: Hinzufügen einer visuellen Feedback-Klasse während des Swipens
pageWrapper.addEventListener('touchmove', function(e) {
    if (Math.abs(distX) > 20) { // Minimale Bewegung für visuelles Feedback
        if (distX > 0) {
            pageWrapper.classList.add('swiping-right');
            pageWrapper.classList.remove('swiping-left');
        } else {
            pageWrapper.classList.add('swiping-left');
            pageWrapper.classList.remove('swiping-right');
        }
    }
});

pageWrapper.addEventListener('touchend', function() {
    pageWrapper.classList.remove('swiping-left', 'swiping-right');
});