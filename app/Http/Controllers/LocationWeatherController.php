<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LocationWeatherController extends Controller
{
    public function weather(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $apiKey = trim((string) config('services.openweather.key'));
        $baseUrl = trim((string) config('services.openweather.base_url', 'https://api.openweathermap.org/data/2.5/weather'));
        $units = trim((string) config('services.openweather.units', 'metric'));

        if ($apiKey === '' || $apiKey === 'enteropenweatherkeyhere') {
            return response()->json([
                'message' => 'OPENWEATHER_API_KEY is missing or still set to placeholder.',
            ], 422);
        }

        $response = Http::timeout(10)->acceptJson()->get($baseUrl, [
            'lat' => $validated['lat'],
            'lon' => $validated['lng'],
            'appid' => $apiKey,
            'units' => $units,
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'OpenWeather API error: ' . ($response->json('message') ?? $response->status()),
            ], 502);
        }

        $data = $response->json();
        $area = $data['name'] ?? null;
        $country = $data['sys']['country'] ?? null;
        $locationLabel = trim(implode(', ', array_filter([$area, $country])));

        return response()->json([
            'station_area' => $area,
            'location_label' => $locationLabel !== '' ? $locationLabel : null,
            'main' => $data['weather'][0]['main'] ?? 'Clear',
            'summary' => ucfirst($data['weather'][0]['description'] ?? 'clear sky'),
            'temp_c' => isset($data['main']['temp']) ? round((float) $data['main']['temp'], 1) : null,
            'humidity' => $data['main']['humidity'] ?? null,
        ]);
    }
}