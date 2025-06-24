<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    public function getWeather(Request $request)
    {
        $city = $request->input('city');

        if (empty($city)) {
            return response()->json(['error' => 'City parameter is required.'], 400);
        }

        // You would typically use a weather API key here. Replace 'YOUR_WEATHER_API_KEY' with an actual key.
        // For example, using OpenWeatherMap API: https://openweathermap.org/api
        $apiKey = env('WEATHER_API_KEY'); // Store your API key in .env file
        $apiUrl = "http://api.weatherapi.com/v1/current.json?key={$apiKey}&q={$city}";

        try {
            $response = Http::get($apiUrl);
            $data = $response->json();

            if ($response->successful()) {
                return response()->json($data);
            } else {
                return response()->json(['error' => $data['error']['message'] ?? 'Could not retrieve weather data.'], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching weather data: ' . $e->getMessage()], 500);
        }
    }
}