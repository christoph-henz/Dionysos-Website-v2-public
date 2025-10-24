// Notification System (zuerst definieren, da es überall verwendet wird)
                    function showNotification(message, type = 'info') {
                        // Entferne vorherige Notifications
                        const existingNotifications = document.querySelectorAll('.notification');
                        existingNotifications.forEach(n => n.remove());
                        
                        // Erstelle neue Notification
                        const notification = document.createElement('div');
                        notification.className = `notification notification-${type}`;
                        notification.textContent = message;
                        
                        // Style basierend auf Typ
                        const styles = {
                            info: { background: '#2196f3', color: 'white' },
                            success: { background: '#4caf50', color: 'white' },
                            warning: { background: '#ff9800', color: 'white' },
                            error: { background: '#f44336', color: 'white' }
                        };
                        
                        const style = styles[type] || styles.info;
                        Object.assign(notification.style, {
                            position: 'fixed',
                            top: '20px',
                            right: '20px',
                            padding: '12px 20px',
                            borderRadius: '6px',
                            background: style.background,
                            color: style.color,
                            zIndex: '10000',
                            fontSize: '14px',
                            fontWeight: 'bold',
                            boxShadow: '0 4px 12px rgba(0,0,0,0.2)',
                            animation: 'slideInRight 0.3s ease-out'
                        });
                        
                        document.body.appendChild(notification);
                        
                        // Auto-Remove nach 4 Sekunden
                        setTimeout(() => {
                            if (notification.parentNode) {
                                notification.style.animation = 'slideOutRight 0.3s ease-in';
                                setTimeout(() => notification.remove(), 300);
                            }
                        }, 4000);
                    }

                    // Animation CSS für Notifications (inline da im JavaScript)
                    if (!document.getElementById('notification-styles')) {
                        const style = document.createElement('style');
                        style.id = 'notification-styles';
                        style.textContent = `
                            @keyframes slideInRight {
                                from { transform: translateX(100%); opacity: 0; }
                                to { transform: translateX(0); opacity: 1; }
                            }
                            @keyframes slideOutRight {
                                from { transform: translateX(0); opacity: 1; }
                                to { transform: translateX(100%); opacity: 0; }
                            }
                        `;
                        document.head.appendChild(style);
                    }

                    function showTab(tabName, clickEvent) {
                        console.log('🐛 DEBUG showTab called with:', tabName);
                        
                        // Alle Tab-Inhalte ausblenden
                        const tabContents = document.querySelectorAll('.tab-content');
                        console.log('🐛 DEBUG found tab contents:', tabContents.length);
                        tabContents.forEach(content => {
                            console.log('🐛 DEBUG hiding tab:', content.id);
                            content.classList.remove('active');
                        });

                        // Alle Tab-Buttons deaktivieren
                        const tabButtons = document.querySelectorAll('.tab-btn');
                        tabButtons.forEach(btn => btn.classList.remove('active'));

                        // Gewählten Tab anzeigen
                        const targetTab = document.getElementById(tabName);
                        console.log('🐛 DEBUG target tab element:', targetTab);
                        
                        if (targetTab) {
                            targetTab.classList.add('active');
                            console.log('🐛 DEBUG activated tab:', tabName);
                        } else {
                            console.error('🐛 DEBUG ERROR: Tab element not found:', tabName);
                        }
                        
                        // Button aktiv markieren (entweder aus Event oder über ID suchen)
                        if (clickEvent && clickEvent.target) {
                            clickEvent.target.classList.add('active');
                        } else {
                            document.querySelector('.tab-btn[data-tab="' + tabName + '"]').classList.add('active');
                        }
                        
                        // URL aktualisieren ohne Neuladen der Seite
                        const url = new URL(window.location);
                        url.searchParams.set('tab', tabName);
                        window.history.pushState({}, '', url);

                        // Spezielle Initialisierung für Galerie-Tab
                        if (tabName === 'gallery') {
                            initializeGalleryDragAndDrop();
                        }
                    }

                    // Galerie Drag & Drop Funktionalität
                    function initializeGalleryDragAndDrop() {
                        const galleryList = document.getElementById('galleryItemsList');
                        if (galleryList && typeof Sortable !== 'undefined') {
                            new Sortable(galleryList, {
                                animation: 150,
                                ghostClass: 'sortable-ghost',
                                onEnd: function(evt) {
                                    updateGalleryOrder();
                                }
                            });
                        }
                    }

                    function updateGalleryOrder() {
                        const galleryItems = document.querySelectorAll('#galleryItemsList .gallery-item');
                        const orderData = [];
                        
                        galleryItems.forEach((item, index) => {
                            const galleryId = item.getAttribute('data-gallery-id');
                            orderData.push({
                                gallery_id: galleryId,
                                display_order: index + 1
                            });
                        });

                        // An Server senden
                        fetch('/public/api/settings_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'updateGalleryOrder',
                                orderData: orderData
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Reihenfolge erfolgreich aktualisiert', 'success');
                                // Reihenfolge-Anzeige aktualisieren
                                galleryItems.forEach((item, index) => {
                                    const orderDisplay = item.querySelector('.gallery-item-order');
                                    if (orderDisplay) {
                                        orderDisplay.textContent = `Reihenfolge: ${index + 1}`;
                                    }
                                });
                            } else {
                                showNotification('Fehler beim Aktualisieren der Reihenfolge: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            showNotification('Fehler beim Aktualisieren der Reihenfolge', 'error');
                            console.error('Error:', error);
                        });
                    }

                    function saveGalleryDescription(galleryId) {
                        const textarea = document.querySelector(`textarea[data-gallery-id="${galleryId}"]`);
                        if (!textarea) return;

                        const description = textarea.value.trim();

                        fetch('/public/api/settings_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'updateGalleryDescription',
                                gallery_id: galleryId,
                                description: description
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Beschreibung erfolgreich gespeichert', 'success');
                            } else {
                                showNotification('Fehler beim Speichern der Beschreibung: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            showNotification('Fehler beim Speichern der Beschreibung', 'error');
                            console.error('Error:', error);
                        });
                    }

                    function addToGallery(imageId) {
                        fetch('/public/api/settings_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'addToGallery',
                                image_id: imageId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Bild zur Galerie hinzugefügt', 'success');
                                // Seite neu laden um Änderungen zu zeigen
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                showNotification('Fehler beim Hinzufügen zur Galerie: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            showNotification('Fehler beim Hinzufügen zur Galerie', 'error');
                            console.error('Error:', error);
                        });
                    }

                    function removeFromGallery(galleryId) {
                        if (!confirm('Möchten Sie dieses Bild wirklich aus der Galerie entfernen?')) {
                            return;
                        }

                        fetch('/public/api/settings_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'removeFromGallery',
                                gallery_id: galleryId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Bild aus Galerie entfernt', 'success');
                                // Seite neu laden um Änderungen zu zeigen
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                showNotification('Fehler beim Entfernen aus der Galerie: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            showNotification('Fehler beim Entfernen aus der Galerie', 'error');
                            console.error('Error:', error);
                        });
                    }

                    function updateOrderStatus(orderId, status) {
                        if (!confirm('Sind Sie sicher?')) return;

                        const requestData = {
                            action: 'update_status',
                            type: 'order',
                            id: orderId,
                            status: status
                        };

                        fetch('/api/order_status_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            credentials: 'include',
                            body: JSON.stringify(requestData)
                        })
                        .then(response => {
                            const contentType = response.headers.get('content-type');
                            
                            if (!contentType || !contentType.includes('application/json')) {
                                return response.text().then(text => {
                                    throw new Error('Antwort ist kein JSON. Response beginnt mit: ' + text.substring(0, 100));
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                let message = 'Status erfolgreich aktualisiert!';
                                if (data.telegram_status) {
                                    message += '\n' + data.telegram_status;
                                }
                                alert(message);
                                location.reload();
                            } else {
                                let errorMsg = 'Fehler: ' + (data.error || 'Unbekannter Fehler');
                                alert(errorMsg);
                            }
                        })
                        .catch(error => {
                            alert('Ein Fehler ist aufgetreten: ' + error.message);
                        });
                    }

                    function updateReservationStatus(reservationId, status) {
                        if (!confirm('Sind Sie sicher?')) return;

                        fetch('/api/reservation_status_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            credentials: 'include',
                            body: JSON.stringify({
                                action: 'update_status',
                                type: 'reservation',
                                id: reservationId,
                                status: status
                            })
                        })
                        .then(response => {
                            const contentType = response.headers.get('content-type');
                            
                            if (!contentType || !contentType.includes('application/json')) {
                                return response.text().then(text => {
                                    throw new Error('Antwort ist kein JSON. Response beginnt mit: ' + text.substring(0, 100));
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                let message = 'Status erfolgreich aktualisiert!';
                                if (data.email_status) {
                                    message += '\n' + data.email_status;
                                }
                                if (data.telegram_status) {
                                    message += '\n' + data.telegram_status;
                                }
                                alert(message);
                                location.reload();
                            } else {
                                let errorMsg = 'Fehler: ' + (data.error || 'Unbekannter Fehler');
                                alert(errorMsg);
                            }
                        })
                        .catch(error => {
                            alert('Ein Fehler ist aufgetreten: ' + error.message);
                        });
                    }

                    function setDebugTime() {
                        const date = document.getElementById('debug_date').value;
                        const time = document.getElementById('debug_time').value;

                        fetch('/api/debug_time_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'set_debug_time',
                                date: date || null,
                                time: time || null
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Debug-Zeit erfolgreich gesetzt!');
                                refreshDebugInfo();
                            } else {
                                alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
                            }
                        })
                        .catch(error => {
                            alert('Ein Fehler ist aufgetreten');
                        });
                    }

                    function resetDebugTime() {
                        if (!confirm('Debug-Zeit zurücksetzen?')) return;

                        fetch('/api/debug_time_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'reset_debug_time'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Debug-Zeit zurückgesetzt!');
                                document.getElementById('debug_date').value = '';
                                document.getElementById('debug_time').value = '';
                                refreshDebugInfo();
                            } else {
                                alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
                            }
                        })
                        .catch(error => {
                            alert('Ein Fehler ist aufgetreten');
                        });
                    }

                    function setQuickTime(time) {
                        document.getElementById('debug_time').value = time;
                        setDebugTime();
                    }

                    function refreshDebugInfo() {
                        // Seite neu laden um aktuelle Debug-Info anzuzeigen
                        if (document.getElementById('debug').classList.contains('active')) {
                            location.reload();
                        }
                    }

                    // Auto-Refresh Funktionalität
                    let autoRefreshInterval = null;

                    function startAutoRefresh() {
                        // Alle 60 Sekunden (1 Minute) die Seite aktualisieren
                        autoRefreshInterval = setInterval(function() {
                            // Aktuelle URL mit Tab-Parameter beibehalten
                            const urlParams = new URLSearchParams(window.location.search);
                            const currentTab = urlParams.get('tab') || 'orders';
                            
                            // Sanfte Aktualisierung - kurz anzeigen dass geladen wird
                            const activeTab = document.querySelector('.tab-content.active');
                            if (activeTab) {
                                // Kurzen Loading-Indikator zeigen
                                const loadingDiv = document.createElement('div');
                                loadingDiv.className = 'loading-indicator';
                                loadingDiv.innerHTML = '🔄 Aktualisierung...';
                                loadingDiv.style.cssText = `
                                    position: fixed;
                                    top: 20px;
                                    right: 20px;
                                    background: #ffab66;
                                    color: white;
                                    padding: 10px 20px;
                                    border-radius: 6px;
                                    font-weight: bold;
                                    z-index: 1000;
                                    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                                `;
                                document.body.appendChild(loadingDiv);
                                
                                // Nach 500ms die Seite aktualisieren
                                setTimeout(() => {
                                    window.location.reload();
                                }, 500);
                            }
                        }, 60000);
                    }

                    function stopAutoRefresh() {
                        if (autoRefreshInterval) {
                            clearInterval(autoRefreshInterval);
                            autoRefreshInterval = null;
                        }
                    }

                    function toggleAutoRefresh() {
                        if (autoRefreshInterval) {
                            stopAutoRefresh();
                            updateRefreshButton(false);
                        } else {
                            startAutoRefresh();
                            updateRefreshButton(true);
                        }
                    }

                    function updateRefreshButton(isActive) {
                        const button = document.getElementById('auto-refresh-btn');
                        if (button) {
                            if (isActive) {
                                button.textContent = '⏸️ Auto-Refresh AN';
                                button.className = 'btn btn-success';
                                button.title = 'Auto-Refresh ist aktiv (alle 60 Sekunden). Klicken zum Deaktivieren.';
                            } else {
                                button.textContent = '▶️ Auto-Refresh AUS';
                                button.className = 'btn btn-secondary';
                                button.title = 'Auto-Refresh ist inaktiv. Klicken zum Aktivieren.';
                            }
                        }
                    }

                // Beim Laden der Seite den korrekten Tab aus der URL anzeigen
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('🐛 DEBUG: Page loaded, checking tabs...');
                    
                    // Debug: Alle vorhandenen Tab-Elemente auflisten
                    const allTabs = document.querySelectorAll('.tab-content');
                    console.log('🐛 DEBUG: Found tab elements:');
                    allTabs.forEach(tab => {
                        console.log('🐛 DEBUG: Tab ID:', tab.id, 'Display:', window.getComputedStyle(tab).display, 'Classes:', tab.className);
                    });
                    
                    // URL-Parameter "tab" auslesen
                    const urlParams = new URLSearchParams(window.location.search);
                    const tabParam = urlParams.get('tab');
                    console.log('🐛 DEBUG: URL tab parameter:', tabParam);
                    
                    // Falls URL-Parameter existiert und Tab existiert, diesen aktivieren
                    if (tabParam && document.getElementById(tabParam)) {
                        console.log('🐛 DEBUG: Activating tab from URL:', tabParam);
                        // Tab ohne Event aufrufen, da initial keine Click-Event vorhanden ist
                        showTab(tabParam);
                    } else {
                        console.log('🐛 DEBUG: No URL tab param or tab not found, showing default');
                    }

                    // Auto-Refresh standardmäßig aktivieren
                    startAutoRefresh();
                    updateRefreshButton(true);
                    
                    // Image Upload Handler
                    const uploadForm = document.getElementById('imageUploadForm');
                    if (uploadForm) {
                        uploadForm.addEventListener('submit', function(e) {
                                e.preventDefault();
                                
                                const fileInput = document.getElementById('imageFile');
                                const file = fileInput.files[0];
                                
                                if (!file) {
                                    alert('Bitte wählen Sie eine Datei aus.');
                                    return;
                                }
                                
                                // Datei-Validierung
                                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                                if (!allowedTypes.includes(file.type)) {
                                    alert('Nur JPG, PNG und WEBP Dateien sind erlaubt.');
                                    return;
                                }
                                
                                if (file.size > 5 * 1024 * 1024) { // 5MB
                                    alert('Die Datei ist zu groß. Maximum: 5MB');
                                    return;
                                }
                                
                                const formData = new FormData();
                                formData.append('action', 'upload_image');
                                formData.append('image', file);
                                
                                // Upload-Button deaktivieren
                                const submitBtn = uploadForm.querySelector('button[type="submit"]');
                                const originalText = submitBtn.textContent;
                                submitBtn.disabled = true;
                                submitBtn.textContent = '⏳ Lädt hoch...';
                                
                                fetch('/api/settings_api.php', {
                                    method: 'POST',
                                    credentials: 'include',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert('Bild erfolgreich hochgeladen!');
                                        location.reload();
                                    } else {
                                        alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
                                    }
                                })
                                .catch(error => {
                                    alert('Ein Fehler ist aufgetreten: ' + error.message);
                                })
                                .finally(() => {
                                    submitBtn.disabled = false;
                                    submitBtn.textContent = originalText;
                                });
                            });
                        }
                    });

                    // Auto-Refresh stoppen wenn Seite verlassen wird
                    window.addEventListener('beforeunload', function() {
                        stopAutoRefresh();
                    });

                    // Settings Functions
                    function toggleSystemSetting(settingKey, newValue) {
                        if (!confirm('Sind Sie sicher, dass Sie diese Einstellung ändern möchten?')) return;

                        fetch('/public/api/settings_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'updateSystemSetting',
                                setting_key: settingKey,
                                setting_value: newValue ? '1' : '0'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Einstellung erfolgreich aktualisiert!', 'success');
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                showNotification('Fehler: ' + (data.error || 'Unbekannter Fehler'), 'error');
                            }
                        })
                        .catch(error => {
                            showNotification('Ein Fehler ist aufgetreten: ' + error.message, 'error');
                        });
                    }

                    function deleteImage(imageId, imageName) {
                        if (!confirm('Sind Sie sicher, dass Sie das Bild "' + imageName + '" löschen möchten? Dies kann nicht rückgängig gemacht werden.')) return;

                        fetch('/public/api/settings_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'deleteImage',
                                image_id: imageId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Bild erfolgreich gelöscht!', 'success');
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                showNotification('Fehler: ' + (data.error || 'Unbekannter Fehler'), 'error');
                            }
                        })
                        .catch(error => {
                            showNotification('Ein Fehler ist aufgetreten: ' + error.message, 'error');
                        });
                    }