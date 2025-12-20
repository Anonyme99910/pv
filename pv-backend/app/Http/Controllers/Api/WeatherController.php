<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WeatherController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Get current weather for a location
     */
    public function current(Request $request): JsonResponse
    {
        $location = $request->input('location', $request->input('q', 'auto:ip'));

        $result = $this->weatherService->getCurrentWeather($location);

        return response()->json($result);
    }

    /**
     * Get weather by coordinates (auto-detect)
     */
    public function byCoordinates(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        $result = $this->weatherService->getWeatherByCoordinates(
            $request->lat,
            $request->lon
        );

        return response()->json($result);
    }

    /**
     * Get weather forecast
     */
    public function forecast(Request $request): JsonResponse
    {
        $location = $request->input('location', 'auto:ip');
        $days = $request->input('days', 3);

        $result = $this->weatherService->getForecast($location, $days);

        return response()->json($result);
    }

    /**
     * Search locations
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $result = $this->weatherService->searchLocations($request->q);

        return response()->json($result);
    }

    /**
     * Get solar forecast (weather + solar impact)
     */
    public function solarForecast(Request $request): JsonResponse
    {
        $location = $request->input('location', 'auto:ip');

        $current = $this->weatherService->getCurrentWeather($location);
        $forecast = $this->weatherService->getForecast($location, 7);

        if (!$current['success']) {
            return response()->json($current);
        }

        return response()->json([
            'success' => true,
            'location' => $current['location'],
            'current' => [
                'weather' => $current['current'],
                'solar_impact' => $current['solar_impact'],
            ],
            'forecast' => $forecast['success'] ? $forecast['forecast'] : [],
        ]);
    }
}
