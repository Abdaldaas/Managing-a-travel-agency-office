<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Office Location
    |--------------------------------------------------------------------------
    |
    | The coordinates and address of the travel agency office
    |
    */
    'office' => [
        'latitude' => env('OFFICE_LATITUDE', 31.9539),
        'longitude' => env('OFFICE_LONGITUDE', 35.9106),
        'address' => env('OFFICE_ADDRESS', 'Travel Agency Office, Amman, Jordan'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Airport Location
    |--------------------------------------------------------------------------
    |
    | The coordinates and address of the airport
    |
    */
    'airport' => [
        'latitude' => env('AIRPORT_LATITUDE', 31.7225),
        'longitude' => env('AIRPORT_LONGITUDE', 35.9932),
        'address' => env('AIRPORT_ADDRESS', 'Queen Alia International Airport, Amman, Jordan'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    |
    | Configuration for taxi service pricing
    |
    */
    'pricing' => [
        'base_fare' => env('TAXI_BASE_FARE', 5.00),
        'price_per_km' => env('TAXI_PRICE_PER_KM', 2.50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for finding nearby drivers
    |
    */
    'search' => [
        'max_distance' => env('TAXI_MAX_SEARCH_DISTANCE', 10), // Maximum distance in km to search for drivers
        'max_results' => env('TAXI_MAX_SEARCH_RESULTS', 5), // Maximum number of drivers to return
    ],
]; 