@extends('layouts.app')

@section('title', 'Schedule & Consultation Fee')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Real-Time Floating Toast Notification -->
    <div id="save-toast" class="fixed top-6 right-6 z-50 transform transition-all duration-300 -translate-y-5 opacity-0 pointer-events-none max-w-sm w-full bg-slate-900/95 backdrop-blur-md text-white rounded-2xl p-4 shadow-2xl border border-emerald-500/40 flex items-start space-x-3">
        <div id="save-toast-icon" class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
            <i class="fa-solid fa-check"></i>
        </div>
        <div class="flex-1 min-w-0">
            <h4 id="save-toast-title" class="text-xs font-bold text-white">Settings Saved</h4>
            <p id="save-toast-msg" class="text-[11px] text-slate-300 mt-0.5">Profile & consultation settings saved successfully.</p>
        </div>
        <button type="button" onclick="hideSaveToast()" class="text-slate-400 hover:text-white text-xs p-1">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-800">Veterinarian Profile & Settings</h1>
        <p class="text-xs text-slate-500 mt-1">Set your consultation fee, clinic address, and weekly availability hours</p>

        <form method="POST" action="{{ route('vet.schedule.profile') }}" id="profile-settings-form" class="mt-6 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Base Consultation Fee (₱) *</label>
                    <input type="number" step="0.01" name="consultation_fee" value="{{ old('consultation_fee', $profile->consultation_fee) }}" required
                        class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Clinic / Hospital Name</label>
                    <input type="text" name="clinic_name" value="{{ old('clinic_name', $profile->clinic_name) }}"
                        class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            <!-- Additional Pet Pricing & Time Settings -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-brand-50/50 p-4 rounded-xl border border-brand-100">
                <div>
                    <label class="block text-xs font-bold text-brand-900 uppercase tracking-wider mb-1">
                        Additional Pet Extra Fee (₱) *
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold text-xs">₱</span>
                        <input type="number" step="0.01" name="additional_pet_fee" value="{{ old('additional_pet_fee', $profile->effective_additional_pet_fee) }}" required min="0"
                            class="w-full pl-7 pr-3.5 rounded-xl border-slate-200 text-sm py-2.5 focus:ring-brand-500 focus:border-brand-500 bg-white">
                    </div>
                    <span class="text-[11px] text-slate-500 mt-1 block">Fee added for each additional pet included in a consultation.</span>
                </div>
                <div>
                    <label class="block text-xs font-bold text-brand-900 uppercase tracking-wider mb-1">
                        Additional Pet Extra Time (Minutes) *
                    </label>
                    <div class="relative">
                        <input type="number" name="additional_pet_duration" value="{{ old('additional_pet_duration', $profile->effective_additional_pet_duration) }}" required min="1" max="120"
                            class="w-full pl-3.5 pr-12 rounded-xl border-slate-200 text-sm py-2.5 focus:ring-brand-500 focus:border-brand-500 bg-white">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 font-bold text-xs">mins</span>
                    </div>
                    <span class="text-[11px] text-slate-500 mt-1 block">Extra duration added to the video call / chat session for each extra pet.</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Clinic Street Address</label>
                    <input type="text" name="clinic_address" id="input-clinic-address" value="{{ old('clinic_address', $profile->clinic_address) }}" placeholder="e.g. 123 Health Ave."
                        class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">City / Municipality</label>
                    <input type="text" name="city" id="input-city" value="{{ old('city', $profile->city) }}" placeholder="e.g. Quezon City"
                        class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Province / Region</label>
                    <input type="text" name="province" id="input-province" value="{{ old('province', $profile->province) }}" placeholder="e.g. Metro Manila"
                        class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            <!-- Doctor Location Map Picker (For Proximity Distance Calculation) -->
            <div class="bg-slate-50/90 rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                            <i class="fa-solid fa-map-location-dot text-brand-600 text-base"></i>
                            <span>Practice Location on Map</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Set your location so pet owners can see accurate proximity distance (e.g. <em>"1.8 km away"</em>).
                        </p>
                    </div>

                    <!-- Coordinates Status Badge & Clear Action -->
                    <div class="flex items-center space-x-2">
                        <div id="coord-display" class="text-xs">
                            @if($profile->latitude && $profile->longitude)
                                <span class="inline-flex items-center text-xs font-semibold text-emerald-800 bg-emerald-100/80 border border-emerald-300 px-3 py-1.5 rounded-xl">
                                    <i class="fa-solid fa-circle-check mr-1.5 text-emerald-600"></i>
                                    <span>Pinned: {{ number_format($profile->latitude, 4) }}, {{ number_format($profile->longitude, 4) }}</span>
                                </span>
                            @else
                                <span class="inline-flex items-center text-xs text-slate-500 bg-white border border-slate-200 px-3 py-1.5 rounded-xl">
                                    <i class="fa-solid fa-location-dot mr-1.5 text-slate-400"></i>
                                    <span>No pin set yet</span>
                                </span>
                            @endif
                        </div>
                        <button type="button" id="clear-coord-btn" class="{{ ($profile->latitude && $profile->longitude) ? '' : 'hidden' }} text-rose-600 hover:text-rose-700 text-xs font-semibold px-2.5 py-1.5 rounded-lg hover:bg-rose-50 border border-rose-200 transition-colors">
                            <i class="fa-solid fa-xmark mr-1"></i> Clear Pin
                        </button>
                    </div>
                </div>

                <!-- Privacy Guarantee Banner -->
                <div class="flex items-start space-x-3 bg-emerald-50 border border-emerald-200/90 text-emerald-950 rounded-2xl p-4 text-xs shadow-sm">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-shield-halved text-sm"></i>
                    </div>
                    <div class="space-y-0.5">
                        <strong class="font-bold text-emerald-900 block text-xs">Doctor Privacy Guarantee:</strong>
                        <p class="text-emerald-800 leading-relaxed text-[11px]">
                            Your exact map pin and GPS coordinates are <strong>strictly confidential</strong> and will <strong>never be shown to clients</strong>.
                            Clients only see an approximate calculated distance (e.g. <em>"3.2 km away"</em>) and your city/province.
                        </p>
                    </div>
                </div>

                <!-- Map Tools: Search & Geolocation -->
                <div class="flex flex-col sm:flex-row gap-2 pt-1">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" id="search-location-input" placeholder="Search address, landmark, or city in Philippines..." 
                            class="w-full pl-9 pr-24 rounded-xl border-slate-200 text-xs py-2.5 focus:ring-brand-500 focus:border-brand-500 bg-white">
                        <button type="button" id="search-location-btn" class="absolute inset-y-1 right-1 px-3 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold transition-colors flex items-center justify-center space-x-1">
                            <span>Search</span>
                        </button>
                    </div>
                    <button type="button" id="locate-me-btn" class="inline-flex items-center justify-center space-x-1.5 bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-colors shrink-0">
                        <i class="fa-solid fa-location-crosshairs text-brand-600"></i>
                        <span>Use My Current Location</span>
                    </button>
                </div>

                <!-- Leaflet Map Container -->
                <div class="relative">
                    <div id="vet-location-map" class="w-full h-80 rounded-2xl border-2 border-slate-200/90 shadow-inner z-0 overflow-hidden"></div>
                    <div class="absolute bottom-2 left-2 z-[400] bg-white/90 backdrop-blur-sm text-[11px] text-slate-600 px-3 py-1.5 rounded-xl border border-slate-200/80 shadow-sm pointer-events-none flex items-center space-x-1.5">
                        <i class="fa-solid fa-hand-pointer text-brand-600"></i>
                        <span>Click anywhere or drag marker to set your exact location</span>
                    </div>
                </div>

                <!-- Hidden form inputs for coordinates -->
                <input type="hidden" name="latitude" id="input-latitude" value="{{ old('latitude', $profile->latitude) }}">
                <input type="hidden" name="longitude" id="input-longitude" value="{{ old('longitude', $profile->longitude) }}">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Languages Spoken</label>
                    <input type="text" name="languages" value="{{ old('languages', $profile->languages) }}" placeholder="e.g. English, Tagalog"
                        class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Areas of Expertise</label>
                    <input type="text" name="expertise" value="{{ old('expertise', $profile->expertise) }}" required
                        class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Professional Bio</label>
                <textarea name="bio" rows="3" class="w-full rounded-xl border-slate-200 text-sm py-2.5 px-3.5 focus:ring-brand-500 focus:border-brand-500">{{ old('bio', $profile->bio) }}</textarea>
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" id="save-profile-btn" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs px-6 py-2.5 rounded-xl shadow-sm transition-all flex items-center space-x-2">
                    <span id="save-btn-text">Save Profile & Fee Settings</span>
                </button>
                <span id="save-status-inline" class="text-xs text-emerald-600 font-semibold hidden flex items-center space-x-1.5">
                    <i class="fa-solid fa-circle-check text-emerald-500"></i>
                    <span>Saved without page refresh!</span>
                </span>
            </div>
        </form>
    </div>

    <!-- Weekly Availability Slots -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-8 shadow-sm space-y-6">
        <h2 class="text-xl font-bold text-slate-800">Weekly Availability Schedule</h2>

        <form method="POST" action="{{ route('vet.schedule.availability') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end bg-slate-50 p-4 rounded-2xl border border-slate-100">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Day of Week</label>
                <select name="day_of_week" required class="w-full rounded-xl border-slate-200 text-xs py-2 px-3">
                    <option value="1">Monday</option>
                    <option value="2">Tuesday</option>
                    <option value="3">Wednesday</option>
                    <option value="4">Thursday</option>
                    <option value="5">Friday</option>
                    <option value="6">Saturday</option>
                    <option value="0">Sunday</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Start Time</label>
                <input type="time" name="start_time" value="09:00" required class="w-full rounded-xl border-slate-200 text-xs py-2 px-3">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">End Time</label>
                <input type="time" name="end_time" value="17:00" required class="w-full rounded-xl border-slate-200 text-xs py-2 px-3">
            </div>
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs py-2.5 px-4 rounded-xl">
                Add Slot
            </button>
        </form>

        <div class="space-y-2">
            @forelse($availabilities as $slot)
                <div class="flex items-center justify-between p-3.5 bg-white border border-slate-200 rounded-xl text-xs">
                    <span class="font-bold text-slate-800 w-28">{{ $slot->day_name }}</span>
                    <span class="text-slate-600 font-mono">{{ date('h:i A', strtotime($slot->start_time)) }} — {{ date('h:i A', strtotime($slot->end_time)) }}</span>
                    <form method="POST" action="{{ route('vet.schedule.availability.delete', $slot) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-rose-500 hover:text-rose-700 font-semibold"><i class="fa-solid fa-trash"></i> Remove</button>
                    </form>
                </div>
            @empty
                <p class="text-xs text-slate-400 italic text-center py-4">No recurring schedule slots defined yet.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #vet-location-map {
        cursor: crosshair;
    }
    .leaflet-popup-content-wrapper {
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        font-family: inherit;
    }
    .leaflet-popup-content {
        margin: 14px 16px;
        line-height: 1.4;
    }
    .leaflet-container {
        font-family: inherit;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const latInput = document.getElementById('input-latitude');
    const lngInput = document.getElementById('input-longitude');
    const cityInput = document.getElementById('input-city');
    const provinceInput = document.getElementById('input-province');
    const coordDisplay = document.getElementById('coord-display');
    const clearBtn = document.getElementById('clear-coord-btn');
    const locateBtn = document.getElementById('locate-me-btn');
    const searchBtn = document.getElementById('search-location-btn');
    const searchInput = document.getElementById('search-location-input');

    if (!document.getElementById('vet-location-map')) return;

    // Check if vet has saved coordinates
    const savedLat = parseFloat(latInput ? latInput.value : '');
    const savedLng = parseFloat(lngInput ? lngInput.value : '');
    const hasSavedCoord = !isNaN(savedLat) && !isNaN(savedLng);

    // Default to Philippines / Metro Manila if no coordinates yet
    const initialLat = hasSavedCoord ? savedLat : 14.5995;
    const initialLng = hasSavedCoord ? savedLng : 120.9842;
    const initialZoom = hasSavedCoord ? 15 : 12;

    const map = L.map('vet-location-map', {
        zoomControl: true,
        scrollWheelZoom: true
    }).setView([initialLat, initialLng], initialZoom);

    // Free OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Ensure map tiles calculate correctly
    setTimeout(() => {
        map.invalidateSize();
    }, 250);

    let marker = null;

    function updateCoordinates(lat, lng, pan = false) {
        lat = Number(parseFloat(lat).toFixed(7));
        lng = Number(parseFloat(lng).toFixed(7));

        if (latInput) latInput.value = lat;
        if (lngInput) lngInput.value = lng;

        if (coordDisplay) {
            coordDisplay.innerHTML = `
                <span class="inline-flex items-center text-xs font-semibold text-emerald-800 bg-emerald-100/90 border border-emerald-300 px-3 py-1.5 rounded-xl shadow-xs">
                    <i class="fa-solid fa-circle-check mr-1.5 text-emerald-600"></i>
                    <span>Pinned: ${lat.toFixed(4)}, ${lng.toFixed(4)}</span>
                </span>
            `;
        }

        if (clearBtn) clearBtn.classList.remove('hidden');

        if (!marker) {
            marker = L.marker([lat, lng], {
                draggable: true,
                title: 'Your Practice Location'
            }).addTo(map);

            marker.bindPopup(`
                <div class="text-xs">
                    <strong class="font-bold text-slate-800 block text-sm">Your Practice Location</strong>
                    <p class="text-emerald-700 text-[11px] mt-0.5"><i class="fa-solid fa-shield-halved mr-1"></i> Exact pin is strictly private from clients.</p>
                    <span class="text-slate-400 text-[10px] block mt-1">Drag marker anytime to fine-tune</span>
                </div>
            `);

            marker.on('dragend', function() {
                const pos = marker.getLatLng();
                updateCoordinates(pos.lat, pos.lng, false);
            });
        } else {
            marker.setLatLng([lat, lng]);
        }

        if (pan) {
            map.setView([lat, lng], Math.max(map.getZoom(), 15));
        }
    }

    // Set initial marker if coordinates are present
    if (hasSavedCoord) {
        updateCoordinates(savedLat, savedLng, false);
    }

    // Click anywhere on map to position/move the marker
    map.on('click', function(e) {
        updateCoordinates(e.latlng.lat, e.latlng.lng, false);
    });

    // Clear coordinates
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (latInput) latInput.value = '';
            if (lngInput) lngInput.value = '';
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
            if (coordDisplay) {
                coordDisplay.innerHTML = `
                    <span class="inline-flex items-center text-xs text-slate-500 bg-white border border-slate-200 px-3 py-1.5 rounded-xl">
                        <i class="fa-solid fa-location-dot mr-1.5 text-slate-400"></i>
                        <span>No pin set yet</span>
                    </span>
                `;
            }
            clearBtn.classList.add('hidden');
        });
    }

    // Locate Me via HTML5 Geolocation API
    if (locateBtn) {
        locateBtn.addEventListener('click', function() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }

            locateBtn.disabled = true;
            const originalHtml = locateBtn.innerHTML;
            locateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-brand-600"></i> <span>Detecting GPS...</span>';

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    locateBtn.disabled = false;
                    locateBtn.innerHTML = originalHtml;
                    updateCoordinates(position.coords.latitude, position.coords.longitude, true);
                },
                function(err) {
                    locateBtn.disabled = false;
                    locateBtn.innerHTML = originalHtml;
                    alert('Unable to access current location: ' + err.message + '. You can click directly on the map or search above.');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        });
    }

    // Address search using Nominatim OpenStreetMap Geocoding
    async function searchLocation() {
        const query = searchInput ? searchInput.value.trim() : '';
        if (!query) {
            if (searchInput) searchInput.focus();
            return;
        }

        if (searchBtn) {
            searchBtn.disabled = true;
            searchBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        }

        try {
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ph&limit=1&addressdetails=1`;
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });

            if (response.ok) {
                const data = await response.json();
                if (data && data.length > 0) {
                    const result = data[0];
                    const lat = parseFloat(result.lat);
                    const lon = parseFloat(result.lon);
                    updateCoordinates(lat, lon, true);

                    // Autofill city and province if empty
                    if (result.address) {
                        const detectedCity = result.address.city || result.address.town || result.address.municipality || result.address.county || '';
                        const detectedProvince = result.address.state || result.address.region || '';
                        if (cityInput && !cityInput.value && detectedCity) {
                            cityInput.value = detectedCity;
                        }
                        if (provinceInput && !provinceInput.value && detectedProvince) {
                            provinceInput.value = detectedProvince;
                        }
                    }
                } else {
                    alert('Location not found in the Philippines. Please try a different street name, landmark, or city.');
                }
            } else {
                alert('Geocoding search service is temporarily busy. You can click directly on the map to pin your location.');
            }
        } catch (e) {
            console.error('Search location error:', e);
            alert('Search failed. Please click directly on the map to set your location.');
        } finally {
            if (searchBtn) {
                searchBtn.disabled = false;
                searchBtn.innerHTML = '<span>Search</span>';
            }
        }
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', searchLocation);
    }
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchLocation();
            }
        });
    }

    // Toast Notification helper
    let toastTimeout = null;
    function showSaveToast(message, type = 'success') {
        const toast = document.getElementById('save-toast');
        const toastMsg = document.getElementById('save-toast-msg');
        const toastTitle = document.getElementById('save-toast-title');
        const toastIcon = document.getElementById('save-toast-icon');

        if (!toast) return;

        if (toastMsg) toastMsg.innerHTML = message;
        if (toastTitle) toastTitle.textContent = type === 'success' ? 'Settings Saved' : 'Update Failed';

        if (toastIcon) {
            if (type === 'success') {
                toastIcon.className = 'w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5';
                toastIcon.innerHTML = '<i class="fa-solid fa-check"></i>';
                toast.className = 'fixed top-6 right-6 z-50 transform transition-all duration-300 translate-y-0 opacity-100 max-w-sm w-full bg-slate-900/95 backdrop-blur-md text-white rounded-2xl p-4 shadow-2xl border border-emerald-500/40 flex items-start space-x-3 pointer-events-auto';
            } else {
                toastIcon.className = 'w-8 h-8 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0 mt-0.5';
                toastIcon.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
                toast.className = 'fixed top-6 right-6 z-50 transform transition-all duration-300 translate-y-0 opacity-100 max-w-sm w-full bg-slate-900/95 backdrop-blur-md text-white rounded-2xl p-4 shadow-2xl border border-rose-500/40 flex items-start space-x-3 pointer-events-auto';
            }
        }

        if (toastTimeout) clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => {
            hideSaveToast();
        }, 4500);
    }

    function hideSaveToast() {
        const toast = document.getElementById('save-toast');
        if (toast) {
            toast.classList.add('-translate-y-5', 'opacity-0', 'pointer-events-none');
            toast.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
        }
    }
    window.hideSaveToast = hideSaveToast;

    // AJAX Form Submission (No page refresh)
    const profileForm = document.getElementById('profile-settings-form');
    if (profileForm) {
        profileForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('save-profile-btn');
            const submitBtnText = document.getElementById('save-btn-text');
            const originalText = submitBtnText ? submitBtnText.innerHTML : 'Save Profile & Fee Settings';

            if (submitBtn) {
                submitBtn.disabled = true;
                if (submitBtnText) {
                    submitBtnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Saving...';
                }
            }

            try {
                const formData = new FormData(profileForm);
                const response = await fetch(profileForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showSaveToast(data.message || 'Profile & consultation settings updated successfully.', 'success');

                    const inlineStatus = document.getElementById('save-status-inline');
                    if (inlineStatus) {
                        inlineStatus.classList.remove('hidden');
                        setTimeout(() => {
                            inlineStatus.classList.add('hidden');
                        }, 4000);
                    }

                    // Keep pinned badge in sync
                    const latVal = latInput ? latInput.value : '';
                    const lngVal = lngInput ? lngInput.value : '';
                    if (latVal && lngVal && coordDisplay) {
                        const latNum = parseFloat(latVal);
                        const lngNum = parseFloat(lngVal);
                        coordDisplay.innerHTML = `
                            <span class="inline-flex items-center text-xs font-semibold text-emerald-800 bg-emerald-100/90 border border-emerald-300 px-3 py-1.5 rounded-xl shadow-xs">
                                <i class="fa-solid fa-circle-check mr-1.5 text-emerald-600"></i>
                                <span>Pinned: ${latNum.toFixed(4)}, ${lngNum.toFixed(4)}</span>
                            </span>
                        `;
                    }
                } else if (response.status === 422) {
                    const errorMessages = data.errors ? Object.values(data.errors).flat().join('<br>') : (data.message || 'Please check form errors.');
                    showSaveToast(errorMessages, 'error');
                } else {
                    showSaveToast(data.message || 'An error occurred while saving.', 'error');
                }
            } catch (error) {
                console.error('AJAX profile save error:', error);
                showSaveToast('Network connection error while saving.', 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    if (submitBtnText) submitBtnText.innerHTML = originalText;
                }
            }
        });
    }
});
</script>
@endpush
