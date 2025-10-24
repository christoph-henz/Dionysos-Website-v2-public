// Funktion zum Laden der Zeitslots
function loadTimeSlots(selectedDate) {
    const timeSelect = document.getElementById("time");
    
    if (selectedDate) {
        timeSelect.disabled = true;
        timeSelect.innerHTML = "<option value=\"\">Lade Zeitslots...</option>";
        
        const formData = new FormData();
        formData.append("action", "get_time_slots");
        formData.append("date", selectedDate);
        
        fetch(window.location.pathname, {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            timeSelect.innerHTML = "<option value=\"\">Bitte wählen...</option>";
            if (data.success && data.time_slots && data.time_slots.length > 0) {
                data.time_slots.forEach(slot => {
                    const option = document.createElement("option");
                    option.value = slot;
                    option.textContent = slot;
                    timeSelect.appendChild(option);
                });
                timeSelect.disabled = false;
            } else {
                timeSelect.innerHTML = "<option value=\"\">Keine Zeitslots verfügbar</option>";
            }
        })
        .catch(error => {
            timeSelect.innerHTML = "<option value=\"\">Fehler beim Laden</option>";
        });
    } else {
        timeSelect.disabled = true;
        timeSelect.innerHTML = "<option value=\"\">Bitte wählen Sie zuerst ein Datum...</option>";
    }
}

// Live-Validierung für Anmerkungsfeld auf Außenbereich-Begriffe
function checkOutdoorKeywords() {
    const notesField = document.getElementById("notes");
    const warningDiv = document.getElementById("outdoorWarning");
    const notes = notesField.value.toLowerCase();
    
    const outdoorKeywords = [
        "außenbereich","raus", "aussenbereich", "außen", "aussen",
        "draußen", "draussen", "drausen", "main", "terasse",
        "terrasse", "garten", "outdoor", "balkon", "veranda",
        "freiluft", "im freien", "auf der terrasse", "am main",
        "außenterrasse", "ausenterrasse"
    ];
    
    let hasOutdoorKeyword = false;
    for (let keyword of outdoorKeywords) {
        if (notes.includes(keyword)) {
            hasOutdoorKeyword = true;
            break;
        }
    }
    
    if (hasOutdoorKeyword) {
        warningDiv.style.display = "block";
        notesField.style.borderColor = "#dc3545";
    } else {
        warningDiv.style.display = "none";
        notesField.style.borderColor = "";
    }
    
    return hasOutdoorKeyword;
}

// Event-Listener werden nach dem DOM-Load initialisiert
document.addEventListener('DOMContentLoaded', function() {
    // Event-Listener für Datumsänderung
    document.getElementById("date").addEventListener("change", function() {
        loadTimeSlots(this.value);
    });

    // Event-Listener für Anmerkungsfeld
    document.getElementById("notes").addEventListener("input", checkOutdoorKeywords);

    // Event-Listener für Gästeanzahl
    document.getElementById("guests").addEventListener("change", function() {
        const customGuestsGroup = document.getElementById("customGuestsGroup");
        if (this.value === "more") {
            customGuestsGroup.style.display = "block";
            document.getElementById("customGuests").required = true;
        } else {
            customGuestsGroup.style.display = "none";
            document.getElementById("customGuests").required = false;
            document.getElementById("customGuests").value = "";
        }
    });

    // Event-Listener für Formular-Submission
    document.getElementById("reservationForm").addEventListener("submit", function(e) {
        e.preventDefault();
        
        // Prüfe auf Außenbereich-Begriffe
        if (checkOutdoorKeywords()) {
            alert("Reservierungen sind nur für den Innenbereich möglich. Bitte entfernen Sie etwaige Anfragen zu einem Platz im Außenbereich aus Ihren Anmerkungen.");
            return;
        }
        
        const requiredFields = this.querySelectorAll("[required]");
        let isValid = true;
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = "#dc3545";
            } else {
                field.style.borderColor = "";
            }
        });
        if (!isValid) {
            alert("Bitte füllen Sie alle Pflichtfelder aus.");
            return;
        }
        const formData = new FormData(this);
        fetch(window.location.pathname, {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Ihre Reservierung wurde erfolgreich übermittelt! Sie erhalten in Kürze eine Bestätigung.");
                this.reset();
            } else {
                alert("Fehler: " + data.message);
            }
        })
        .catch(error => {
            alert("Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.");
        });
    });
});
