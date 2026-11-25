<x-guest-layout>
    <div class="w-full max-w-3xl mx-auto">
        <form id="register-form" method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div class="text-center mb-4">
                <h1 class="text-xl font-bold text-gray-800">Register</h1>
                <p class="text-sm text-gray-500">Select your membership category & level, pin your location on the map,
                    and fetch local weather.</p>
            </div>

            @if ($errors->any())
                <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded text-sm">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="name" :value="__('Full Name')" />
                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')"
                        required autofocus />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')"
                        required />
                </div>

                <div>
                    <x-input-label for="phone" :value="__('Phone (Optional)')" />
                    <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" />
                </div>

                <div>
                    <x-input-label for="membership_category_id" :value="__('Membership Category')" />
                    <select id="membership_category_id" name="membership_category_id"
                        class="block mt-1 w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('membership_category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="membership_level_id" :value="__('Membership Level')" />
                    <select id="membership_level_id" name="membership_level_id"
                        class="block mt-1 w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                        <option value="">-- Select Membership Level --</option>
                        @foreach($levels as $level)
                            <option value="{{ $level->id }}" @selected(old('membership_level_id') == $level->id)>
                                {{ $level->name }} — {{ number_format($level->fee, 2) }} ({{ $level->validity_days }} days)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <div style="position: relative; width: 100%; margin-top: 0.25rem;">
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                            style="width: 100%; padding: 0.5rem 2.5rem 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; box-sizing: border-box;">
                        <button type="button" onclick="togglePass('password', this)"
                            aria-label="Toggle password visibility"
                            style="position: absolute; top: 50%; right: 0.65rem; transform: translateY(-50%); background: transparent; border: 0; padding: 0.25rem; color: #64748b; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                    <div style="position: relative; width: 100%; margin-top: 0.25rem;">
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            autocomplete="new-password"
                            style="width: 100%; padding: 0.5rem 2.5rem 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; box-sizing: border-box;">
                        <button type="button" onclick="togglePass('password_confirmation', this)"
                            aria-label="Toggle confirm password visibility"
                            style="position: absolute; top: 50%; right: 0.65rem; transform: translateY(-50%); background: transparent; border: 0; padding: 0.25rem; color: #64748b; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label class="block text-sm font-medium text-gray-700">Google Map Location & OpenWeather</label>
                    <div class="flex items-center gap-3">
                        <button type="button" id="use-gps-btn"
                            class="text-xs px-2.5 py-1 bg-gray-800 text-white rounded hover:bg-gray-700">
                            Use My Current Location
                        </button>
                        <span id="location-status" class="text-xs text-gray-500">Initializing map...</span>
                    </div>
                </div>

                <input id="reg-place-search" type="text" placeholder="Search address or place..."
                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm">

                <div id="reg-map" style="width: 100%; height: 280px;"
                    class="rounded border border-gray-300 bg-gray-100"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div>
                        <label for="reg-address" class="block text-xs font-medium text-gray-600">Address (Auto-filled
                            from Map)</label>
                        <input id="reg-address" type="text" name="address" value="{{ old('address') }}"
                            class="mt-1 block w-full border-gray-300 rounded-md text-xs"
                            placeholder="Address will populate from pin...">
                    </div>
                    <div>
                        <label for="reg-lat" class="block text-xs font-medium text-gray-600">Latitude</label>
                        <input id="reg-lat" type="text" name="latitude" value="{{ old('latitude', '14.5995000') }}"
                            class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50 text-xs" readonly>
                    </div>
                    <div>
                        <label for="reg-lng" class="block text-xs font-medium text-gray-600">Longitude</label>
                        <input id="reg-lng" type="text" name="longitude" value="{{ old('longitude', '120.9842000') }}"
                            class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50 text-xs" readonly>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div>
                        <label for="reg-weather-summary" class="block text-xs font-medium text-gray-600">OpenWeather in
                            Area</label>
                        <input id="reg-weather-summary" type="text" name="weather_summary"
                            value="{{ old('weather_summary') }}"
                            class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50 text-xs" readonly>
                    </div>
                    <div>
                        <label for="reg-weather-temp" class="block text-xs font-medium text-gray-600">Temperature
                            (°C)</label>
                        <input id="reg-weather-temp" type="text" name="weather_temp_c"
                            value="{{ old('weather_temp_c') }}"
                            class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50 text-xs" readonly>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                    {{ __('Already registered? Log in') }}
                </a>

                <x-primary-button>
                    {{ __('Register') }}
                </x-primary-button>
            </div>
        </form>
    </div>

    <script>
        let regMap, regMarker, regGeocoder;
        let geocodeResolved = false;

        function togglePass(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.style.color = isHidden ? '#2563eb' : '#64748b';
        }

        function setStatus(text, isError = false) {
            const el = document.getElementById('location-status');
            if (!el) return;
            el.textContent = text;
            el.className = isError ? 'text-xs text-red-600' : 'text-xs text-gray-500';
        }

        function initRegMap() {
            const latInput = document.getElementById('reg-lat');
            const lngInput = document.getElementById('reg-lng');
            const savedLat = parseFloat(latInput.value);
            const savedLng = parseFloat(lngInput.value);
            const startPos = (!Number.isNaN(savedLat) && !Number.isNaN(savedLng))
                ? { lat: savedLat, lng: savedLng }
                : { lat: 14.5995, lng: 120.9842 };

            regGeocoder = new google.maps.Geocoder();
            regMap = new google.maps.Map(document.getElementById('reg-map'), {
                center: startPos,
                zoom: 13,
                mapTypeControl: false,
                streetViewControl: false,
            });

            regMarker = new google.maps.Marker({
                position: startPos,
                map: regMap,
                draggable: true,
            });

            const searchInput = document.getElementById('reg-place-search');
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') e.preventDefault();
            });

            const autocomplete = new google.maps.places.Autocomplete(searchInput);
            autocomplete.bindTo('bounds', regMap);

            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (!place.geometry || !place.geometry.location) {
                    setStatus('Please pick a location from the dropdown suggestions.', true);
                    return;
                }
                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();
                regMap.setCenter({ lat, lng });
                regMap.setZoom(15);
                updateRegLocation(lat, lng, place.formatted_address || place.name || null);
            });

            regMap.addListener('click', (e) => {
                updateRegLocation(e.latLng.lat(), e.latLng.lng());
            });

            regMarker.addListener('dragend', (e) => {
                updateRegLocation(e.latLng.lat(), e.latLng.lng());
            });

            updateRegLocation(startPos.lat, startPos.lng, document.getElementById('reg-address').value || null);
        }

        function updateRegLocation(lat, lng, formattedAddress = null) {
            const numLat = Number(lat);
            const numLng = Number(lng);

            if (regMarker) {
                regMarker.setPosition({ lat: numLat, lng: numLng });
            }

            document.getElementById('reg-lat').value = numLat.toFixed(7);
            document.getElementById('reg-lng').value = numLng.toFixed(7);
            geocodeResolved = false;
            setStatus('Updating address & weather...');

            const addressEl = document.getElementById('reg-address');

            if (formattedAddress) {
                addressEl.value = formattedAddress;
                geocodeResolved = true;
            } else if (regGeocoder) {
                regGeocoder.geocode({ location: { lat: numLat, lng: numLng } }, (results, status) => {
                    if (status === 'OK' && results && results[0]) {
                        addressEl.value = results[0].formatted_address;
                        geocodeResolved = true;
                    }
                });
            }

            const url = `{{ route('api.location.weather') }}?lat=${encodeURIComponent(numLat)}&lng=${encodeURIComponent(numLng)}`;
            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(async (res) => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        if (!geocodeResolved && !addressEl.value) {
                            addressEl.value = `Lat ${numLat.toFixed(5)}, Lng ${numLng.toFixed(5)}`;
                        }
                        setStatus(data.message || 'Could not fetch weather.', true);
                        return;
                    }

                    if (!geocodeResolved) {
                        addressEl.value = data.location_label || `Lat ${numLat.toFixed(5)}, Lng ${numLng.toFixed(5)}`;
                    }

                    document.getElementById('reg-weather-summary').value = data.station_area
                        ? `${data.summary} (${data.station_area})`
                        : (data.summary ?? '');
                    document.getElementById('reg-weather-temp').value = data.temp_c ?? '';
                    setStatus('Map location & weather loaded.');
                })
                .catch(() => {
                    if (!geocodeResolved && !addressEl.value) {
                        addressEl.value = `Lat ${numLat.toFixed(5)}, Lng ${numLng.toFixed(5)}`;
                    }
                    setStatus('Network error fetching weather data.', true);
                });
        }

        document.getElementById('use-gps-btn').addEventListener('click', () => {
            if (!navigator.geolocation) {
                setStatus('Geolocation is not supported by your browser.', true);
                return;
            }
            setStatus('Detecting your current location...');
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    if (regMap) {
                        regMap.setCenter({ lat, lng });
                        regMap.setZoom(15);
                    }
                    updateRegLocation(lat, lng);
                },
                () => setStatus('Location permission denied or unavailable.', true)
            );
        });

        window.gm_authFailure = function () {
            setStatus('Google Maps key error. Using default coordinates for weather.', true);
            const lat = parseFloat(document.getElementById('reg-lat').value) || 14.5995;
            const lng = parseFloat(document.getElementById('reg-lng').value) || 120.9842;
            updateRegLocation(lat, lng);
        };
    </script>

    @if(config('services.google_maps.key'))
        <script async defer
            src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=initRegMap"></script>
    @else
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                setStatus('MAPS_KEY missing in .env; fetching weather for default pin.', true);
                updateRegLocation(14.5995, 120.9842);
            });
        </script>
    @endif
</x-guest-layout>