<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    protected string $apiUrl = 'https://api.weatherapi.com/v1';
    protected ?string $apiKey = null;

    public function __construct()
    {
        $this->apiKey = SystemSetting::get('weather_api_key');
    }

    /**
     * Get current weather for a location
     */
    public function getCurrentWeather(string $location): array
    {
        if (!$this->apiKey) {
            return [
                'success' => false,
                'message' => 'Weather API not configured',
            ];
        }

        $cacheKey = "weather.current.{$location}";

        return Cache::remember($cacheKey, 600, function () use ($location) {
            try {
                $response = Http::get("{$this->apiUrl}/current.json", [
                    'key' => $this->apiKey,
                    'q' => $location,
                    'aqi' => 'yes',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'success' => true,
                        'location' => [
                            'name' => $data['location']['name'],
                            'region' => $data['location']['region'],
                            'country' => $data['location']['country'],
                            'lat' => $data['location']['lat'],
                            'lon' => $data['location']['lon'],
                            'localtime' => $data['location']['localtime'],
                            'timezone' => $data['location']['tz_id'],
                        ],
                        'current' => [
                            'temp_c' => $data['current']['temp_c'],
                            'temp_f' => $data['current']['temp_f'],
                            'condition' => $data['current']['condition']['text'],
                            'condition_icon' => $data['current']['condition']['icon'],
                            'wind_kph' => $data['current']['wind_kph'],
                            'wind_dir' => $data['current']['wind_dir'],
                            'humidity' => $data['current']['humidity'],
                            'cloud' => $data['current']['cloud'],
                            'feelslike_c' => $data['current']['feelslike_c'],
                            'uv' => $data['current']['uv'],
                            'vis_km' => $data['current']['vis_km'],
                            'pressure_mb' => $data['current']['pressure_mb'],
                        ],
                        'air_quality' => $data['current']['air_quality'] ?? null,
                        'solar_impact' => $this->calculateSolarImpact($data['current']),
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Failed to fetch weather data',
                ];

            } catch (\Exception $e) {
                Log::error('Weather API error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Get weather forecast
     */
    public function getForecast(string $location, int $days = 3): array
    {
        if (!$this->apiKey) {
            return [
                'success' => false,
                'message' => 'Weather API not configured',
            ];
        }

        $cacheKey = "weather.forecast.{$location}.{$days}";

        return Cache::remember($cacheKey, 1800, function () use ($location, $days) {
            try {
                $response = Http::get("{$this->apiUrl}/forecast.json", [
                    'key' => $this->apiKey,
                    'q' => $location,
                    'days' => $days,
                    'aqi' => 'yes',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $forecast = [];

                    foreach ($data['forecast']['forecastday'] as $day) {
                        $forecast[] = [
                            'date' => $day['date'],
                            'maxtemp_c' => $day['day']['maxtemp_c'],
                            'mintemp_c' => $day['day']['mintemp_c'],
                            'avgtemp_c' => $day['day']['avgtemp_c'],
                            'condition' => $day['day']['condition']['text'],
                            'condition_icon' => $day['day']['condition']['icon'],
                            'maxwind_kph' => $day['day']['maxwind_kph'],
                            'avghumidity' => $day['day']['avghumidity'],
                            'daily_chance_of_rain' => $day['day']['daily_chance_of_rain'],
                            'uv' => $day['day']['uv'],
                            'sunrise' => $day['astro']['sunrise'],
                            'sunset' => $day['astro']['sunset'],
                            'solar_hours' => $this->calculateSolarHours($day['astro']),
                            'expected_efficiency' => $this->predictSolarEfficiency($day['day']),
                        ];
                    }

                    return [
                        'success' => true,
                        'location' => $data['location']['name'],
                        'forecast' => $forecast,
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Failed to fetch forecast data',
                ];

            } catch (\Exception $e) {
                Log::error('Weather API forecast error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Get weather by coordinates (auto-detect)
     */
    public function getWeatherByCoordinates(float $lat, float $lon): array
    {
        return $this->getCurrentWeather("{$lat},{$lon}");
    }

    /**
     * Search locations
     */
    public function searchLocations(string $query): array
    {
        if (!$this->apiKey) {
            return ['success' => false, 'message' => 'Weather API not configured'];
        }

        try {
            $response = Http::get("{$this->apiUrl}/search.json", [
                'key' => $this->apiKey,
                'q' => $query,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'locations' => $response->json(),
                ];
            }

            return ['success' => false, 'message' => 'Search failed'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Calculate solar impact based on weather conditions
     */
    protected function calculateSolarImpact(array $current): array
    {
        $cloudCover = $current['cloud'] ?? 0;
        $uv = $current['uv'] ?? 5;
        $humidity = $current['humidity'] ?? 50;
        $temp = $current['temp_c'] ?? 25;

        // Base efficiency calculation
        $efficiency = 100;

        // Cloud cover impact (major factor)
        $efficiency -= ($cloudCover * 0.7);

        // Temperature impact (optimal around 25°C)
        $tempDiff = abs($temp - 25);
        $efficiency -= ($tempDiff * 0.5);

        // Humidity impact (minor)
        if ($humidity > 80) {
            $efficiency -= 5;
        }

        // UV boost
        $efficiency += min($uv * 2, 10);

        $efficiency = max(0, min(100, $efficiency));

        $rating = match (true) {
            $efficiency >= 80 => 'Excellent',
            $efficiency >= 60 => 'Good',
            $efficiency >= 40 => 'Moderate',
            $efficiency >= 20 => 'Poor',
            default => 'Very Poor',
        };

        return [
            'efficiency_percent' => round($efficiency, 1),
            'rating' => $rating,
            'factors' => [
                'cloud_impact' => -round($cloudCover * 0.7, 1),
                'temp_impact' => -round($tempDiff * 0.5, 1),
                'uv_boost' => min($uv * 2, 10),
            ],
            'recommendation' => $this->getSolarRecommendation($efficiency, $cloudCover, $temp),
        ];
    }

    /**
     * Calculate solar hours from sunrise/sunset
     */
    protected function calculateSolarHours(array $astro): float
    {
        $sunrise = strtotime($astro['sunrise']);
        $sunset = strtotime($astro['sunset']);
        return round(($sunset - $sunrise) / 3600, 1);
    }

    /**
     * Predict solar efficiency for forecast day
     */
    protected function predictSolarEfficiency(array $day): float
    {
        $efficiency = 100;

        // Cloud/rain impact
        $rainChance = $day['daily_chance_of_rain'] ?? 0;
        $efficiency -= ($rainChance * 0.5);

        // Temperature impact
        $avgTemp = $day['avgtemp_c'] ?? 25;
        $tempDiff = abs($avgTemp - 25);
        $efficiency -= ($tempDiff * 0.4);

        // UV boost
        $uv = $day['uv'] ?? 5;
        $efficiency += min($uv * 1.5, 8);

        return max(0, min(100, round($efficiency, 1)));
    }

    /**
     * Get solar recommendation based on conditions
     */
    protected function getSolarRecommendation(float $efficiency, int $cloudCover, float $temp): string
    {
        if ($efficiency >= 80) {
            return 'Optimal conditions for solar generation. Expect peak performance.';
        } elseif ($efficiency >= 60) {
            return 'Good conditions. Solar panels should perform well.';
        } elseif ($cloudCover > 70) {
            return 'Heavy cloud cover reducing efficiency. Consider scheduling maintenance during this period.';
        } elseif ($temp > 40) {
            return 'High temperature may reduce panel efficiency. Ensure proper ventilation.';
        } elseif ($temp < 10) {
            return 'Low temperature. Panels may perform better but check for frost.';
        }
        return 'Moderate conditions. Monitor panel performance.';
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        if (!$this->apiKey) {
            return ['success' => false, 'message' => 'API key not configured'];
        }

        try {
            $response = Http::get("{$this->apiUrl}/current.json", [
                'key' => $this->apiKey,
                'q' => 'London',
            ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Connection successful' : 'Connection failed',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
