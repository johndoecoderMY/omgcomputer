@push('styles')
    <link rel="stylesheet" href="{{ asset('vendors/leaflet/leaflet.css') }}">
    <style>
        #ticket-location-picker-map { min-height: 320px; height: min(62vh, 620px); border-radius: .5rem; z-index: 0; }
    </style>
@endpush

<div class="modal fade" id="ticket-location-picker-modal" tabindex="-1"
    aria-labelledby="ticket-location-picker-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5" id="ticket-location-picker-title">Choose a location on the map</h2>
                    <div class="small text-body-secondary">Selecting a pin fills the active address.</div>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-sm btn-outline-secondary" id="ticket-use-current-location" type="button">
                        <span class="bi bi-crosshair me-1" aria-hidden="true"></span>Use my current location
                    </button>
                </div>
                <div class="small mb-2" aria-live="polite">
                    <div class="text-body-secondary" id="ticket-location-active-label">Pickup address</div>
                    <div class="fw-semibold text-break" id="ticket-location-active-preview">No address selected</div>
                </div>
                <div id="ticket-location-picker-map" role="application" aria-label="Map for selecting a pickup or drop-off location"></div>
                <p class="form-text mb-0 mt-2" id="ticket-location-picker-status" role="status" aria-live="polite">
                    Tap the map or drag the pin to select the location.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="button" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('vendors/leaflet/leaflet.js') }}"></script>
    <script>
        (() => {
            const modalElement = document.getElementById('ticket-location-picker-modal');
            const mapElement = document.getElementById('ticket-location-picker-map');
            const statusElement = document.getElementById('ticket-location-picker-status');
            const activeLabel = document.getElementById('ticket-location-active-label');
            const activePreview = document.getElementById('ticket-location-active-preview');
            const currentLocationButton = document.getElementById('ticket-use-current-location');
            if (!modalElement || !mapElement || !statusElement || !activeLabel || !activePreview || !currentLocationButton) return;

            const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
            const defaultCenter = [4.326, 101.15];
            const reverseGeocodeUrl = @json(route('bookings.reverse-geocode'));
            const markers = { pickup: null, dropoff: null };
            let map = null;
            let activeForm = null;
            let activeMode = null;
            let lookupSequence = 0;

            const getFields = (form, mode) => ({
                address: form.querySelector(`[data-ticket-location-address="${mode}"]`),
                latitude: form.querySelector(`[data-ticket-location-latitude="${mode}"]`),
                longitude: form.querySelector(`[data-ticket-location-longitude="${mode}"]`),
                preview: form.querySelector(`[data-ticket-location-preview="${mode}"]`),
            });
            const currentFields = () => activeForm && activeMode ? getFields(activeForm, activeMode) : null;
            const coordinatesFor = (fields) => {
                if (!fields || !fields.latitude || !fields.longitude
                    || fields.latitude.value === '' || fields.longitude.value === '') return null;
                const coordinates = [Number(fields.latitude.value), Number(fields.longitude.value)];
                return coordinates.every(Number.isFinite) ? coordinates : null;
            };
            const updatePreviews = (fields) => {
                if (!fields) return;
                const address = fields.address.value.trim() || 'No address selected';
                fields.preview.textContent = address;
                activePreview.textContent = address;
            };
            const updateMarker = (mode, coordinates) => {
                if (!map) return;
                if (!markers[mode]) {
                    markers[mode] = window.L.marker(coordinates, { draggable: true }).addTo(map);
                    markers[mode].on('dragend', () => {
                        const position = markers[mode].getLatLng();
                        saveLocation(mode, [position.lat, position.lng]);
                    });
                } else {
                    markers[mode].setLatLng(coordinates);
                }
            };
            const showActiveLocation = () => {
                const fields = currentFields();
                if (!fields) return;
                activeLabel.textContent = activeMode === 'pickup' ? 'Pickup address' : 'Drop-off address';
                updatePreviews(fields);
                if (!map) return;
                const coordinates = coordinatesFor(fields);
                if (coordinates) {
                    updateMarker(activeMode, coordinates);
                    map.setView(coordinates, Math.max(map.getZoom(), 15));
                } else {
                    map.setView(defaultCenter, 12);
                }
            };
            const reverseGeocode = async (mode, coordinates) => {
                const fields = getFields(activeForm, mode);
                const requestId = ++lookupSequence;
                const addressBeforeLookup = fields.address.value;
                statusElement.textContent = 'Looking up the address…';
                try {
                    const formToken = activeForm.querySelector('input[name="_token"]');
                    const response = await fetch(reverseGeocodeUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': formToken ? formToken.value : '',
                        },
                        body: JSON.stringify({ latitude: coordinates[0], longitude: coordinates[1] }),
                    });
                    const result = await response.json();
                    if (requestId !== lookupSequence) return;
                    if (!response.ok) {
                        statusElement.textContent = response.status === 429
                            ? 'Please wait a moment before looking up another address.'
                            : result.message || 'Address lookup is temporarily unavailable.';
                        return;
                    }
                    if (fields.address.value === addressBeforeLookup) {
                        fields.address.value = result.address;
                    }
                    updatePreviews(fields);
                    statusElement.textContent = 'Address filled from the selected map pin.';
                } catch (error) {
                    if (requestId === lookupSequence) {
                        console.error('Could not look up the address for a repair-ticket map pin.', error);
                        statusElement.textContent = 'Address lookup is temporarily unavailable. The map pin was saved.';
                    }
                }
            };
            const saveLocation = (mode, coordinates) => {
                const fields = activeForm ? getFields(activeForm, mode) : null;
                if (!fields || !fields.latitude || !fields.longitude) return;
                fields.latitude.value = coordinates[0].toFixed(7);
                fields.longitude.value = coordinates[1].toFixed(7);
                updateMarker(mode, coordinates);
                map.setView(coordinates, Math.max(map.getZoom(), 15));
                reverseGeocode(mode, coordinates);
            };
            const initializeMap = () => {
                if (map) {
                    window.setTimeout(() => map.invalidateSize(), 100);
                    showActiveLocation();
                    return;
                }
                if (!window.L) {
                    statusElement.textContent = 'The map is unavailable. You can still enter the address manually.';
                    return;
                }
                try {
                    map = window.L.map(mapElement).setView(defaultCenter, 12);
                    const tileLayer = window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors',
                    }).addTo(map);
                    tileLayer.on('tileerror', () => {
                        statusElement.textContent = 'Map tiles are unavailable. Try again or enter the address manually.';
                    });
                    map.on('click', (event) => saveLocation(activeMode, [event.latlng.lat, event.latlng.lng]));
                    showActiveLocation();
                    window.setTimeout(() => map.invalidateSize(), 100);
                } catch (error) {
                    map = null;
                    console.error('Could not initialize the repair-ticket location map.', error);
                    statusElement.textContent = 'The map is unavailable. You can still enter the address manually.';
                }
            };

            document.querySelectorAll('[data-ticket-location-open]').forEach((button) => {
                button.addEventListener('click', () => {
                    activeForm = button.closest('form');
                    activeMode = button.dataset.ticketLocationOpen;
                    if (!activeForm || !['pickup', 'dropoff'].includes(activeMode)) return;
                    showActiveLocation();
                    modal.show();
                });
            });
            modalElement.addEventListener('shown.bs.modal', initializeMap);
            document.querySelectorAll('[data-ticket-location-address]').forEach((address) => {
                address.addEventListener('input', () => {
                    const mode = address.dataset.ticketLocationAddress;
                    const form = address.closest('form');
                    const fields = getFields(form, mode);
                    lookupSequence++;
                    fields.latitude.value = '';
                    fields.longitude.value = '';
                    if (markers[mode] && map) {
                        map.removeLayer(markers[mode]);
                        markers[mode] = null;
                    }
                    updatePreviews(fields);
                });
            });
            currentLocationButton.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    statusElement.textContent = 'Location services are not supported by this browser.';
                    return;
                }
                currentLocationButton.disabled = true;
                navigator.geolocation.getCurrentPosition((position) => {
                    saveLocation(activeMode, [position.coords.latitude, position.coords.longitude]);
                    currentLocationButton.disabled = false;
                }, (error) => {
                    console.warn('Could not determine the current location.', error);
                    statusElement.textContent = 'Your current location could not be determined.';
                    currentLocationButton.disabled = false;
                }, { enableHighAccuracy: true, timeout: 10000 });
            });
        })();
    </script>
@endpush
