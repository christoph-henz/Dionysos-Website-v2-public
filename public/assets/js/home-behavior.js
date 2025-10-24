document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.querySelector(".menu-toggle");
    const mobileMenu = document.getElementById("mobile-menu");
    const introHeader = document.querySelector(".intro-header");

    toggleButton.addEventListener("click", () => {
        const isOpen = mobileMenu.style.display === "flex";
        if (isOpen) {
            mobileMenu.style.display = "none";
            introHeader.classList.remove("menu-open");
        } else {
            mobileMenu.style.display = "flex";
            introHeader.classList.add("menu-open");
        }
    });

    // document.body.addEventListener("click", (event) => {
    //     if (
    //         !mobileMenu.contains(event.target) &&
    //         !toggleButton.contains(event.target)
    //     ) {
    //         mobileMenu.style.display = "none";
    //         introHeader.classList.remove("menu-open");
    //     }
    // });

    mobileMenu.addEventListener("click", (event) => {
        event.stopPropagation();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const willkommenSection = document.querySelector('.welcome-section');
    let lastScrollTop = 0;

    window.addEventListener('scroll', function () {
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

        if (currentScroll > 50) {
            willkommenSection.classList.add('scrolled');
        } else {
            willkommenSection.classList.remove('scrolled');
        }

        lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
    });
});

document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("menu-container");
    const prevBtn = document.getElementById("prevPage");
    const nextBtn = document.getElementById("nextPage");

    // Prüfe ob die erforderlichen Elemente existieren
    if (!container || !prevBtn || !nextBtn) {
        return; // Beende die Funktion wenn Elemente fehlen
    }

    prevBtn.addEventListener("click", () => changePage(-1));
    nextBtn.addEventListener("click", () => changePage(1));

    function changePage(direction) {
        let currentPage = parseInt(container.dataset.page);
        let newPage = currentPage + direction;
        if (newPage < 0) return; // keine negativen Seiten

        fetch('MenuHandler.php?page=' + newPage, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => {
                if (!response.ok) throw new Error('Fehler beim Laden der Speisekarte');
                return response.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Hier nur das Element mit Klasse .menu-page extrahieren:
                const menuPage = doc.querySelector('.menu-page');
                if (menuPage) {
                    container.innerHTML = ''; // vorherigen Inhalt löschen
                    container.appendChild(menuPage); // nur das relevante Element einfügen
                    container.dataset.page = newPage;
                } else {
                    console.warn("Kein .menu-page-Element gefunden.");
                }
            })
            .catch(error => {
                console.error('Fehler:', error);
            });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    let lastScrollPosition = 0;
    const stickyHeader = document.querySelector('.sticky-header');
    const scrollThreshold = 20;

    if (!stickyHeader) {
        return; // Beende die Funktion wenn das Element nicht existiert
    }

    window.addEventListener('scroll', () => {
        const currentScrollPosition = window.scrollY;

        if (currentScrollPosition > scrollThreshold) {
            if (currentScrollPosition < lastScrollPosition) {
                stickyHeader.classList.add('visible');
            } else {
                stickyHeader.classList.remove('visible');
            }
        } else {
            stickyHeader.classList.remove('visible');
        }

        lastScrollPosition = currentScrollPosition;
    });
});