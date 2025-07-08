<?php

namespace App\Services;

use App\Models\Airport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AirportService
{
    protected $apiBaseUrl = 'https://airportdb.eu/api/v1';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.airportdb.key');
    }

    /**
     * Search airports by IATA code
     */
    public function findByIataCode(string $iataCode)
    {
        try {
            // Check cache first
            $cacheKey = "airport_iata_{$iataCode}";
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}"
            ])->get("{$this->apiBaseUrl}/airport/{$iataCode}");
            
            if ($response->successful()) {
                $data = $response->json();
                // Cache for 24 hours
                Cache::put($cacheKey, $data, now()->addHours(24));
                return $data;
            }
            
            Log::error('Airport API Error', [
                'iata_code' => $iataCode,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Airport API Exception', [
                'iata_code' => $iataCode,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Search airports by query
     */
    public function searchAirports(string $query)
    {
        try {
            $cacheKey = "airport_search_" . md5($query);
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}"
            ])->get("{$this->apiBaseUrl}/airports/search", [
                'q' => $query
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Cache::put($cacheKey, $data, now()->addHours(1));
                return $data;
            }

            Log::error('Airport Search API Error', [
                'query' => $query,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Airport Search API Exception', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Format airport data from API response
     */
    public function formatAirportData($apiData)
    {
        if (!$apiData) {
            return null;
        }

        return [
            'name' => $apiData['name'] ?? '',
            'iata_code' => $apiData['iata'] ?? '',
            'icao_code' => $apiData['icao'] ?? null,
            'city' => $apiData['city'] ?? '',
            'country' => $apiData['country'] ?? '',
            'latitude' => $apiData['latitude'] ?? 0,
            'longitude' => $apiData['longitude'] ?? 0,
            'timezone' => $apiData['timezone'] ?? null,
            'is_active' => true
        ];
    }

    /**
     * Save or update airport data in database
     */
    public function saveAirport($apiData)
    {
        $formattedData = $this->formatAirportData($apiData);
        
        if (!$formattedData || empty($formattedData['iata_code'])) {
            return null;
        }

        return Airport::updateOrCreate(
            ['iata_code' => $formattedData['iata_code']],
            $formattedData
        );
    }

    /**
     * Get all active airports from database
     */
    public function getActiveAirports()
    {
        return Airport::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Sync multiple airports from API to database
     */
    public function syncAirports(array $iataCodes)
    {
        $results = [];
        foreach ($iataCodes as $iataCode) {
            $apiData = $this->findByIataCode($iataCode);
            if ($apiData) {
                $airport = $this->saveAirport($apiData);
                if ($airport) {
                    $results[] = $airport;
                }
            }
        }
        return $results;
    }
} 