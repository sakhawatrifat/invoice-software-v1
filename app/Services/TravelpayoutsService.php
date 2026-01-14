<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class TravelpayoutsService
{
    private string $apiToken;
    private string $marker;
    private string $websiteUrl;
    private string $baseUrl = 'https://api.travelpayouts.com';
    private string $searchBaseUrl = 'https://tickets-api.travelpayouts.com';

    // Valid airport codes cache
    private array $validAirportCodes = [];

    public function __construct()
    {
        $this->apiToken = config('services.travelpayouts.token');
        $this->marker = config('services.travelpayouts.marker');
        $this->websiteUrl = config('services.travelpayouts.website_url', config('app.url'));
    }

    /**
     * Search for cheapest flights with validation
     * Supports: one_way, round_trip, multi_city
     * Uses the new Flight Search API (available from November 1, 2025)
     */
    public function searchCheapestFlights(array $params, ?string $userIp = null, ?string $realHost = null)
    {
        try {
            // Get user IP and host from request if not provided
            if (empty($userIp)) {
                $userIp = request()->ip();
                // Replace localhost IPs (prohibited by API)
                if (str_starts_with($userIp, '127.')) {
                    $userIp = '8.8.8.8'; // Fallback to a valid IP
                }
            }
            
            if (empty($realHost)) {
                $realHost = $this->getRealHost();
            }

            // Handle multi-city flights
            if (isset($params['flight_type']) && $params['flight_type'] === 'multi_city' && isset($params['flights'])) {
                // Check if new API is enabled
                $useNewApi = config('services.travelpayouts.use_new_api', false);
                if ($useNewApi) {
                    try {
                        return $this->searchFlightsNewApi($params, $userIp, $realHost);
                    } catch (Exception $e) {
                        // Fallback to old API for multi-city (searches each segment)
                        if (str_contains($e->getMessage(), 'access denied') || str_contains($e->getMessage(), 'Access denied')) {
                            Log::warning('New Flight Search API access denied for multi-city, using old API', [
                                'error' => $e->getMessage(),
                            ]);
                            return $this->searchMultiCityFlights($params);
                        }
                        throw $e;
                    }
                } else {
                    // Use old API for multi-city (searches each segment separately)
                    return $this->searchMultiCityFlights($params);
                }
            }

            // Validate airport codes before making API call
            if (!isset($params['origin']) || !isset($params['destination'])) {
                throw new Exception('Origin and destination airports are required');
            }

            $this->validateAirportCode($params['origin'], 'Origin');
            $this->validateAirportCode($params['destination'], 'Destination');

            // Check if new API is enabled in config
            $useNewApi = config('services.travelpayouts.use_new_api', false);
            
            if ($useNewApi) {
                // Try new Flight Search API first, fallback to old API if access denied
                try {
                    return $this->searchFlightsNewApi($params, $userIp, $realHost);
                } catch (Exception $e) {
                    // If access denied (403), fallback to old API
                    if (str_contains($e->getMessage(), 'access denied') || str_contains($e->getMessage(), 'Access denied')) {
                        Log::warning('New Flight Search API access denied, falling back to old API', [
                            'error' => $e->getMessage(),
                        ]);
                        return $this->searchFlightsOldApi($params);
                    }
                    // Re-throw other errors
                    throw $e;
                }
            } else {
                // Use old API by default (was working before)
                return $this->searchFlightsOldApi($params);
            }

        } catch (Exception $e) {
            Log::error('TravelpayoutsService::searchCheapestFlights error: ' . $e->getMessage(), [
                'params' => $params,
            ]);
            throw $e;
        }
    }

    /**
     * Search flights using the new Flight Search API
     */
    private function searchFlightsNewApi(array $params, string $userIp, string $realHost)
    {
        try {
            // Step 1: Start search
            $searchResponse = $this->startFlightSearch($params, $userIp, $realHost);
            
            if (!isset($searchResponse['search_id']) || !isset($searchResponse['results_url'])) {
                throw new Exception('Failed to start flight search. Invalid response from API.');
            }

            $searchId = $searchResponse['search_id'];
            $resultsUrl = $searchResponse['results_url'];

            // Step 2: Get results (poll until is_over = true)
            $allTickets = [];
            $allFlightLegs = [];
            $allAgents = [];
            $allAirlines = [];
            $lastUpdateTimestamp = 0;
            $maxAttempts = 30; // Maximum polling attempts
            $attempt = 0;
            $isOver = false;

            // Wait a bit before first request (API needs 30-60 seconds)
            sleep(2);

            while (!$isOver && $attempt < $maxAttempts) {
                $resultsResponse = $this->getFlightSearchResults($resultsUrl, $searchId, $lastUpdateTimestamp, $userIp, $realHost);
                
                // Collect data
                if (isset($resultsResponse['tickets']) && is_array($resultsResponse['tickets'])) {
                    $allTickets = array_merge($allTickets, $resultsResponse['tickets']);
                }
                
                if (isset($resultsResponse['flight_legs']) && is_array($resultsResponse['flight_legs'])) {
                    $allFlightLegs = array_merge($allFlightLegs, $resultsResponse['flight_legs']);
                }
                
                if (isset($resultsResponse['agents']) && is_array($resultsResponse['agents'])) {
                    $allAgents = array_merge($allAgents, $resultsResponse['agents']);
                }
                
                if (isset($resultsResponse['airlines']) && is_array($resultsResponse['airlines'])) {
                    $allAirlines = array_merge($allAirlines, $resultsResponse['airlines']);
                }

                // Update timestamp for next request
                if (isset($resultsResponse['last_update_timestamp'])) {
                    $lastUpdateTimestamp = $resultsResponse['last_update_timestamp'];
                }

                // Check if search is complete
                $isOver = isset($resultsResponse['is_over']) && $resultsResponse['is_over'] === true;

                if (!$isOver) {
                    // Wait before next request
                    sleep(2);
                    $attempt++;
                }
            }

            // Step 3: Transform response to match expected format
            return $this->transformNewApiResponse([
                'tickets' => $allTickets,
                'flight_legs' => $allFlightLegs,
                'agents' => $allAgents,
                'airlines' => $allAirlines,
                'search_id' => $searchId,
                'results_url' => $resultsUrl,
            ], $params);

        } catch (Exception $e) {
            Log::error('TravelpayoutsService::searchFlightsNewApi error: ' . $e->getMessage(), [
                'params' => $params,
            ]);
            throw $e;
        }
    }

    /**
     * Start flight search (Step 1)
     */
    private function startFlightSearch(array $params, string $userIp, string $realHost)
    {
        // Validate API credentials
        if (empty($this->apiToken)) {
            throw new Exception('Travelpayouts API token is not configured. Please set TRAVELPAYOUTS_TOKEN in your .env file.');
        }
        
        if (empty($this->marker)) {
            throw new Exception('Travelpayouts marker is not configured. Please set TRAVELPAYOUTS_MARKER in your .env file.');
        }
        
        // Build directions array based on flight type
        $directions = [];
        
        if (isset($params['flight_type']) && $params['flight_type'] === 'multi_city' && isset($params['flights'])) {
            // Multi-city: use flights array
            foreach ($params['flights'] as $flight) {
                $directions[] = [
                    'origin' => strtoupper($flight['origin']),
                    'destination' => strtoupper($flight['destination']),
                    'date' => $flight['departure_at'],
                ];
            }
        } elseif (isset($params['return_at']) && !empty($params['return_at'])) {
            // Round trip
            $directions[] = [
                'origin' => strtoupper($params['origin']),
                'destination' => strtoupper($params['destination']),
                'date' => $params['departure_at'],
            ];
            $directions[] = [
                'origin' => strtoupper($params['destination']),
                'destination' => strtoupper($params['origin']),
                'date' => $params['return_at'],
            ];
        } else {
            // One way
            $directions[] = [
                'origin' => strtoupper($params['origin']),
                'destination' => strtoupper($params['destination']),
                'date' => $params['departure_at'] ?? now()->addDays(7)->format('Y-m-d'),
            ];
        }

        // Map class to API format
        $tripClass = 'Y'; // Default: Economy
        if (isset($params['class'])) {
            $classMap = [
                'economy' => 'Y',
                'business' => 'C',
                'first' => 'F',
                'comfort' => 'W',
            ];
            $tripClass = $classMap[strtolower($params['class'])] ?? 'Y';
        }

        // Build request body
        // Ensure passenger counts are integers (API requires integers, not strings)
        $adults = (int)($params['adults'] ?? $params['passenger'] ?? 1);
        $children = (int)($params['children'] ?? 0);
        $infants = (int)($params['infants'] ?? 0);

        $body = [
            'marker' => $this->marker,
            'locale' => $params['locale'] ?? 'en-us',
            'currency_code' => $params['currency'] ?? 'USD',
            'market_code' => $params['market_code'] ?? 'US',
            'search_params' => [
                'trip_class' => $tripClass,
                'passengers' => [
                    'adults' => $adults,
                    'children' => $children,
                    'infants' => $infants,
                ],
                'directions' => $directions,
            ],
        ];

        // Generate signature (must be calculated before adding to body)
        $signature = $this->generateSignature($body);
        $body['signature'] = $signature;

        // Make request
        $url = $this->searchBaseUrl . '/search/affiliate/start';
        
        // Log request details for debugging (remove sensitive data in production)
        Log::debug('Travelpayouts API Request', [
            'url' => $url,
            'headers' => [
                'x-real-host' => $realHost,
                'x-user-ip' => $userIp,
                'x-affiliate-user-id' => substr($this->apiToken, 0, 10) . '...', // Partial token for logging
            ],
            'body_keys' => array_keys($body),
            'marker' => substr($this->marker, 0, 10) . '...', // Partial marker for logging
        ]);
        
        $response = Http::timeout(60)
            ->withHeaders($this->getSearchApiHeaders($userIp, $realHost, $signature))
            ->post($url, $body);

        if (!$response->successful()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['error'] ?? $errorBody['message'] ?? $response->body() ?? 'Unknown error';
            
            // Log full error response for debugging
            Log::error('Travelpayouts API Error Response', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response_body' => $response->body(),
                'real_host' => $realHost,
                'configured_website_url' => $this->websiteUrl,
            ]);
            
            // Provide more helpful error message for access denied
            if ($response->status() === 403 || str_contains(strtolower($errorMessage), 'access denied')) {
                $helpfulMessage = 'Access denied. Please verify: ' .
                    '1) Your API token and marker are correct in .env file, ' .
                    '2) The website URL "' . $realHost . '" matches exactly what is registered in your Travelpayouts account, ' .
                    '3) Your account has been approved for Flight Search API access (contact support@travelpayouts.com if needed).';
                throw new Exception($helpfulMessage);
            }
            
            throw new Exception('Failed to start search: ' . $errorMessage);
        }

        return $response->json();
    }

    /**
     * Get flight search results (Step 2)
     */
    private function getFlightSearchResults(string $resultsUrl, string $searchId, int $lastUpdateTimestamp, string $userIp, string $realHost)
    {
        // Build results URL
        $url = rtrim($resultsUrl, '/') . '/search/affiliate/results';

        // Build request body
        $body = [
            'search_id' => $searchId,
            'limit' => 200, // Maximum allowed
            'last_update_timestamp' => $lastUpdateTimestamp,
        ];

        // Generate signature for results request
        $signature = $this->generateSignature($body);

        // Make request
        $response = Http::timeout(60)
            ->withHeaders($this->getSearchApiHeaders($userIp, $realHost, $signature))
            ->post($url, $body);

        if ($response->status() === 304) {
            // No new results
            return ['is_over' => false, 'tickets' => [], 'flight_legs' => []];
        }

        if (!$response->successful()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['error'] ?? $errorBody['message'] ?? $response->body() ?? 'Unknown error';
            throw new Exception('Failed to get search results: ' . $errorMessage);
        }

        return $response->json();
    }

    /**
     * Generate signature for API requests
     * Signature = MD5(token:marker:sorted_params)
     * Parameters are sorted alphabetically and values are joined with colons
     * Based on Travelpayouts documentation: signature is composed of token, marker, and all request parameter values
     */
    private function generateSignature(array $params): string
    {
        // Remove signature from params if present (to avoid recursion)
        unset($params['signature']);
        
        // Flatten and sort all parameters for signature calculation
        $flatParams = $this->flattenParamsForSignature($params);
        ksort($flatParams);
        
        // Build signature string: token:marker:key1:value1:key2:value2...
        $signatureString = $this->apiToken . ':' . $this->marker;
        foreach ($flatParams as $key => $value) {
            if ($value !== null && $value !== '') {
                $signatureString .= ':' . $key . ':' . $value;
            }
        }
        
        // Log signature calculation details for debugging
        Log::debug('Travelpayouts Signature Calculation', [
            'signature_string_length' => strlen($signatureString),
            'params_count' => count($flatParams),
            'flat_params_keys' => array_keys($flatParams),
            'signature_preview' => substr($signatureString, 0, 50) . '...' . substr($signatureString, -20),
        ]);
        
        // Return MD5 hash
        return md5($signatureString);
    }

    /**
     * Flatten parameters for signature calculation
     * Converts nested arrays to dot notation keys
     * For arrays, we flatten recursively to create dot-notation keys
     */
    private function flattenParamsForSignature(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                // Check if it's a numeric array (list) or associative array
                if (array_keys($value) === range(0, count($value) - 1)) {
                    // Numeric array - sort nested objects if they're associative arrays, then JSON encode
                    $sortedValue = $value;
                    // If array contains associative arrays, sort each one
                    if (!empty($value) && is_array($value[0]) && !array_key_exists(0, $value[0])) {
                        // Contains associative arrays - sort each one
                        foreach ($sortedValue as &$item) {
                            if (is_array($item)) {
                                ksort($item);
                            }
                        }
                        unset($item);
                    }
                    // Sort the array itself if it contains objects (by first key of first object)
                    if (!empty($sortedValue) && is_array($sortedValue[0])) {
                        usort($sortedValue, function($a, $b) {
                            if (!is_array($a) || !is_array($b)) return 0;
                            $keysA = array_keys($a);
                            $keysB = array_keys($b);
                            if (empty($keysA) || empty($keysB)) return 0;
                            return strcmp($keysA[0], $keysB[0]);
                        });
                    }
                    $result[$newKey] = json_encode($sortedValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    // Associative array - recursively flatten
                    $result = array_merge($result, $this->flattenParamsForSignature($value, $newKey));
                }
            } else {
                // For primitive values, convert to string
                $result[$newKey] = (string)$value;
            }
        }
        
        return $result;
    }

    /**
     * Build signature string from parameters (recursive)
     * Travelpayouts expects: key:value format for nested structures
     */
    private function buildSignatureString($value): string
    {
        if (is_array($value)) {
            ksort($value);
            $parts = [];
            foreach ($value as $key => $val) {
                // Skip null and empty string values
                if ($val === null || $val === '') {
                    continue;
                }
                
                // For arrays, recursively build the string
                if (is_array($val)) {
                    $nestedString = $this->buildSignatureString($val);
                    if (!empty($nestedString)) {
                        $parts[] = $key . ':' . $nestedString;
                    }
                } else {
                    // For primitive values, use key:value format
                    $parts[] = $key . ':' . (string)$val;
                }
            }
            return implode(':', $parts);
        } else {
            return (string)$value;
        }
    }

    /**
     * Get real host for API requests
     * Returns the hostname (domain) without protocol
     * Travelpayouts documentation says: "the address of your website"
     * Most APIs expect just the domain name in headers
     */
    private function getRealHost(): string
    {
        $websiteUrl = $this->websiteUrl;
        
        // Extract hostname from URL (remove protocol, path, etc.)
        if (filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($websiteUrl);
            $hostname = $parsed['host'] ?? $websiteUrl;
            
            // Remove 'www.' prefix if present (some APIs don't want it)
            $hostname = preg_replace('/^www\./', '', $hostname);
            
            return $hostname;
        }
        
        // If it's already just a hostname, clean it up
        if (!empty($websiteUrl)) {
            // Remove protocol if present
            $hostname = preg_replace('/^https?:\/\//', '', $websiteUrl);
            // Remove trailing slash
            $hostname = rtrim($hostname, '/');
            // Remove www. prefix
            $hostname = preg_replace('/^www\./', '', $hostname);
            return $hostname;
        }
        
        // Fallback to request host or app URL
        $appUrl = config('app.url');
        if (filter_var($appUrl, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($appUrl);
            return $parsed['host'] ?? request()->getHost();
        }
        
        return request()->getHost() ?: 'localhost';
    }

    /**
     * Get headers for Flight Search API requests
     */
    private function getSearchApiHeaders(string $userIp, string $realHost, string $signature): array
    {
        return [
            'x-real-host' => $realHost,
            'x-user-ip' => $userIp,
            'x-signature' => $signature,
            'x-affiliate-user-id' => $this->apiToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Transform new API response to match expected format
     */
    private function transformNewApiResponse(array $apiResponse, array $searchParams): array
    {
        $tickets = $apiResponse['tickets'] ?? [];
        $flightLegs = $apiResponse['flight_legs'] ?? [];
        $agents = $apiResponse['agents'] ?? [];
        $airlines = $apiResponse['airlines'] ?? [];

        // Create lookup maps
        // flight_legs is an array, and segments.flights contains indices into this array
        $legsArray = $flightLegs; // Keep as array for index-based access

        $agentsMap = [];
        foreach ($agents as $agent) {
            $agentsMap[$agent['id'] ?? ''] = $agent;
        }

        $airlinesMap = [];
        foreach ($airlines as $airline) {
            $airlinesMap[$airline['iata'] ?? ''] = $airline;
        }

        // Transform tickets to expected format
        $transformedData = [];
        
        foreach ($tickets as $ticket) {
            $segments = $ticket['segments'] ?? [];
            $proposals = $ticket['proposals'] ?? [];
            
            if (empty($proposals)) {
                continue; // Skip tickets without proposals
            }

            // Get the cheapest proposal
            $cheapestProposal = null;
            $cheapestPrice = PHP_INT_MAX;
            
            foreach ($proposals as $proposal) {
                $price = $proposal['price']['value'] ?? PHP_INT_MAX;
                if ($price < $cheapestPrice) {
                    $cheapestPrice = $price;
                    $cheapestProposal = $proposal;
                }
            }

            if (!$cheapestProposal) {
                continue;
            }

            // Build flight segments from ticket segments
            $flightSegments = [];
            $firstLeg = null;
            $lastLeg = null;
            $allLegs = [];

            foreach ($segments as $segment) {
                $flights = $segment['flights'] ?? []; // Array of indices into flight_legs
                $segmentLegs = [];
                
                foreach ($flights as $legIndex) {
                    // legIndex is the index in the flight_legs array
                    if (isset($legsArray[$legIndex])) {
                        $leg = $legsArray[$legIndex];
                        $segmentLegs[] = $leg;
                        $allLegs[] = $leg;
                        
                        if ($firstLeg === null) {
                            $firstLeg = $leg;
                        }
                        $lastLeg = $leg;
                    }
                }
                
                if (!empty($segmentLegs)) {
                    $flightSegments[] = [
                        'legs' => $segmentLegs,
                        'transfers' => $segment['transfers'] ?? [],
                    ];
                }
            }

            // Use first and last leg for overall flight info
            $firstSegment = $firstLeg;
            $lastSegment = $lastLeg;

            // Skip if no valid segments found
            if ($firstSegment === null || $lastSegment === null) {
                continue;
            }

            // Convert Unix timestamps to datetime strings
            $departureAt = '';
            if (isset($firstSegment['local_departure_date_time'])) {
                $departureAt = $firstSegment['local_departure_date_time'];
            } elseif (isset($firstSegment['departure_unix_timestamp'])) {
                $departureAt = date('Y-m-d H:i:s', $firstSegment['departure_unix_timestamp']);
            }

            $arrivalAt = '';
            if (isset($lastSegment['local_arrival_date_time'])) {
                $arrivalAt = $lastSegment['local_arrival_date_time'];
            } elseif (isset($lastSegment['arrival_unix_timestamp'])) {
                $arrivalAt = date('Y-m-d H:i:s', $lastSegment['arrival_unix_timestamp']);
            }

            // Extract airline code from operating_carrier_designator (format: "XX1234")
            $operatingCarrier = $firstSegment['operating_carrier_designator'] ?? '';
            $airlineCode = '';
            if (preg_match('/^([A-Z]{2})/', $operatingCarrier, $matches)) {
                $airlineCode = $matches[1];
            }

            // Calculate total transfers (number of segments - 1)
            $totalTransfers = max(0, count($segments) - 1);

            // Build flight data
            $flightData = [
                'id' => $ticket['id'] ?? $ticket['signature'] ?? '',
                'price' => $cheapestProposal['price']['value'] ?? 0,
                'currency' => $cheapestProposal['price']['currency'] ?? ($searchParams['currency'] ?? 'USD'),
                'origin' => $firstSegment['origin'] ?? $searchParams['origin'] ?? '',
                'origin_iata' => $firstSegment['origin'] ?? $searchParams['origin'] ?? '',
                'destination' => $lastSegment['destination'] ?? $searchParams['destination'] ?? '',
                'destination_iata' => $lastSegment['destination'] ?? $searchParams['destination'] ?? '',
                'departure_at' => $departureAt,
                'arrival_at' => $arrivalAt,
                'airline' => $airlineCode,
                'airline_name' => $airlinesMap[$airlineCode]['name'] ?? '',
                'flight_number' => $operatingCarrier,
                'transfers' => $totalTransfers,
                'stops' => $totalTransfers,
                'segments' => $flightSegments,
                'proposals' => $proposals,
                'search_id' => $apiResponse['search_id'] ?? '',
                'results_url' => $apiResponse['results_url'] ?? '',
            ];

            // Add agent information
            $agentId = $cheapestProposal['agent_id'] ?? null;
            if ($agentId && isset($agentsMap[$agentId])) {
                $agent = $agentsMap[$agentId];
                $flightData['agent_id'] = $agentId;
                $flightData['agent_name'] = $agent['label'] ?? '';
                $flightData['gate_name'] = $agent['gate_name'] ?? '';
            }

            $transformedData[] = $flightData;
        }

        return [
            'success' => true,
            'data' => $transformedData,
            'search_id' => $apiResponse['search_id'] ?? '',
            'results_url' => $apiResponse['results_url'] ?? '',
        ];
    }

    /**
     * Get booking link for a proposal
     * Must be called only when user clicks "Buy" button
     * 
     * @param string $resultsUrl The results URL from search response
     * @param string $searchId The search ID from search response
     * @param string $proposalId The proposal ID from ticket proposals array
     * @param string|null $userIp User's IP address
     * @param string|null $realHost Website host
     * @return array Booking link data with URL, method, and expiration
     */
    public function getBookingLink(string $resultsUrl, string $searchId, string $proposalId, ?string $userIp = null, ?string $realHost = null)
    {
        try {
            // Get user IP and host from request if not provided
            if (empty($userIp)) {
                $userIp = request()->ip();
                if (str_starts_with($userIp, '127.')) {
                    $userIp = '8.8.8.8';
                }
            }
            
            if (empty($realHost)) {
                $realHost = $this->getRealHost();
            }

            // Build URL
            $url = rtrim($resultsUrl, '/') . '/searches/' . $searchId . '/clicks/' . $proposalId;

            // For GET requests, signature is typically not required in body but may be in headers
            // Build minimal signature for headers
            $signature = md5($this->apiToken . ':' . $this->marker);

            // Make request
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-real-host' => $realHost,
                    'x-user-ip' => $userIp,
                    'x-signature' => $signature,
                    'x-affiliate-user-id' => $this->apiToken,
                    'x-marker' => $this->marker,
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error'] ?? $errorBody['message'] ?? $response->body() ?? 'Unknown error';
                throw new Exception('Failed to get booking link: ' . $errorMessage);
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('TravelpayoutsService::getBookingLink error: ' . $e->getMessage(), [
                'search_id' => $searchId,
                'proposal_id' => $proposalId,
            ]);
            throw $e;
        }
    }

    /**
     * Search flights using the old API (fallback when new API access is denied)
     * Uses /aviasales/v3/prices_for_dates endpoint
     */
    private function searchFlightsOldApi(array $params)
    {
        try {
            Log::info('Using old Travelpayouts API (fallback)', [
                'origin' => $params['origin'] ?? null,
                'destination' => $params['destination'] ?? null,
            ]);

            // Use the old detailed flight search endpoint
            $endpoint = '/aviasales/v3/prices_for_dates';
            
            $queryParams = [
                'origin' => strtoupper($params['origin']),
                'destination' => strtoupper($params['destination']),
                'departure_at' => $params['departure_at'] ?? now()->addDays(7)->format('Y-m-d'),
                'return_at' => $params['return_at'] ?? null,
                'currency' => $params['currency'] ?? 'USD',
                'limit' => $params['limit'] ?? 30,
                'token' => $this->apiToken,
                'sorting' => 'price',
            ];

            // Add airline if provided (convert name to IATA code if needed)
            if (isset($params['airline']) && !empty($params['airline'])) {
                $airlineCode = $this->getAirlineIataCode($params['airline']);
                if ($airlineCode) {
                    $queryParams['airline'] = $airlineCode;
                }
            }

            // Add class if provided
            if (isset($params['class'])) {
                $queryParams['class'] = $params['class'];
            }

            // Add adults/passengers if provided
            if (isset($params['adults']) || isset($params['passenger'])) {
                $queryParams['adults'] = (int)($params['adults'] ?? $params['passenger'] ?? 1);
            }

            // Remove null values
            $queryParams = array_filter($queryParams, function($value) {
                return $value !== null && $value !== '';
            });

            $response = $this->makeRequest('GET', $endpoint, $queryParams);
            
            // Log success
            $flightCount = isset($response['data']) && is_array($response['data']) ? count($response['data']) : 0;
            Log::info('Old Travelpayouts API call successful', [
                'flights_found' => $flightCount,
            ]);
            
            // Enhance response with detailed flight information
            return $this->enhanceFlightResponse($response, $params);

        } catch (Exception $e) {
            Log::error('TravelpayoutsService::searchFlightsOldApi error: ' . $e->getMessage(), [
                'params' => $params,
            ]);
            throw $e;
        }
    }

    /**
     * Search multi-city flights (deprecated - now handled by new API)
     * Kept for backward compatibility
     */
    private function searchMultiCityFlights(array $params)
    {
        try {
            $flights = $params['flights'] ?? [];
            
            if (count($flights) < 2) {
                throw new Exception('At least 2 flights are required for multi-city search');
            }

            // Validate all airport codes
            foreach ($flights as $index => $flight) {
                if (!isset($flight['origin']) || !isset($flight['destination'])) {
                    throw new Exception("Flight " . ($index + 1) . ": Origin and destination are required");
                }
                $this->validateAirportCode($flight['origin'], "Flight " . ($index + 1) . " Origin");
                $this->validateAirportCode($flight['destination'], "Flight " . ($index + 1) . " Destination");
            }

            // Search each flight segment
            $results = [];
            $currency = $params['currency'] ?? 'USD';
            $limit = $params['limit'] ?? 30;
            $class = $params['class'] ?? 'economy';
            $adults = $params['adults'] ?? $params['passenger'] ?? 1;
            $airline = $params['airline'] ?? null;

            foreach ($flights as $index => $flight) {
                try {
                    $endpoint = '/aviasales/v3/prices_for_dates';
                    
                    $queryParams = [
                        'origin' => strtoupper($flight['origin']),
                        'destination' => strtoupper($flight['destination']),
                        'departure_at' => $flight['departure_at'],
                        'currency' => $currency,
                        'limit' => $limit,
                        'class' => $class,
                        'adults' => $adults,
                        'token' => $this->apiToken,
                        'sorting' => 'price',
                    ];

                    // Add airline if provided (convert name to IATA code if needed)
                    if (!empty($airline)) {
                        $airlineCode = $this->getAirlineIataCode($airline);
                        if ($airlineCode) {
                            $queryParams['airline'] = $airlineCode;
                        }
                    }

                    $segmentResults = $this->makeRequest('GET', $endpoint, $queryParams);
                    
                    // Enhance segment results with detailed information
                    $segmentResults = $this->enhanceFlightResponse($segmentResults, [
                        'origin' => $flight['origin'],
                        'destination' => $flight['destination'],
                        'departure_at' => $flight['departure_at'],
                        'currency' => $currency,
                        'class' => $class,
                    ]);
                    
                    // Add segment index to results
                    if (isset($segmentResults['data'])) {
                        foreach ($segmentResults['data'] as &$flightData) {
                            $flightData['segment'] = $index + 1;
                            $flightData['segment_origin'] = $flight['origin'];
                            $flightData['segment_destination'] = $flight['destination'];
                            $flightData['segment_origin_iata'] = strtoupper($flight['origin']);
                            $flightData['segment_destination_iata'] = strtoupper($flight['destination']);
                        }
                    }
                    
                    $results[] = [
                        'segment' => $index + 1,
                        'origin' => $flight['origin'],
                        'destination' => $flight['destination'],
                        'departure_at' => $flight['departure_at'],
                        'data' => $segmentResults['data'] ?? [],
                    ];

                } catch (Exception $e) {
                    Log::warning("Multi-city segment search failed for segment " . ($index + 1) . ": " . $e->getMessage());
                    // Continue with other segments even if one fails
                    $results[] = [
                        'segment' => $index + 1,
                        'origin' => $flight['origin'],
                        'destination' => $flight['destination'],
                        'departure_at' => $flight['departure_at'],
                        'data' => [],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Combine all results
            $combinedData = [];
            foreach ($results as $result) {
                if (!empty($result['data'])) {
                    $combinedData = array_merge($combinedData, $result['data']);
                }
            }
            
            return [
                'success' => true,
                'data' => $combinedData,
                'segments' => $results,
                'total_segments' => count($flights),
            ];

        } catch (Exception $e) {
            Log::error('TravelpayoutsService::searchMultiCityFlights error: ' . $e->getMessage(), [
                'params' => $params,
            ]);
            throw $e;
        }
    }

    /**
     * Validate airport code
     */
    private function validateAirportCode(string $code, string $field = 'Airport')
    {
        // Check format
        if (!preg_match('/^[A-Z]{3}$/', strtoupper($code))) {
            throw new Exception("{$field} code must be exactly 3 uppercase letters");
        }

        // Check against known invalid codes
        $invalidCodes = ['TOK', 'NYC', 'LON', 'PAR', 'MIL', 'BER', 'ROM', 'OSA'];
        
        if (in_array(strtoupper($code), $invalidCodes)) {
            throw new Exception("{$field} code '{$code}' is a city code. Please use a specific airport code (e.g., use 'HND' or 'NRT' instead of 'TOK')");
        }
    }

    /**
     * Get list of valid airports for autocomplete
     */
    public function getAirportsList()
    {
        $cacheKey = 'travelpayouts_airports_list';
        
        return Cache::remember($cacheKey, 604800, function () { // Cache for 1 week
            try {
                $endpoint = '/data/en/airports.json';
                $airports = $this->makeRequest('GET', $endpoint);
                
                // Format for easier use
                return collect($airports)->map(function($airport) {
                    return [
                        'code' => $airport['code'] ?? '',
                        'name' => $airport['name'] ?? '',
                        'city' => $airport['city_name'] ?? '',
                        'country' => $airport['country_name'] ?? '',
                        'display' => ($airport['code'] ?? '') . ' - ' . ($airport['name'] ?? '') . ', ' . ($airport['city_name'] ?? ''),
                    ];
                })->toArray();
            } catch (Exception $e) {
                return [];
            }
        });
    }

    /**
     * Get list of cities
     */
    public function getCitiesList()
    {
        $cacheKey = 'travelpayouts_cities_list';
        
        return Cache::remember($cacheKey, 604800, function () {
            try {
                $endpoint = '/data/en/cities.json';
                return $this->makeRequest('GET', $endpoint);
            } catch (Exception $e) {
                return [];
            }
        });
    }

    /**
     * Search airports by query (city name, airport name, or code)
     */
    public function searchAirports(string $query)
    {
        $airports = $this->getAirportsList();
        
        if (empty($airports)) {
            // Fallback to common airports if API fails
            return $this->searchCommonAirports($query);
        }
        
        $query = strtolower(trim($query));

        $results = collect($airports)->filter(function($airport) use ($query) {
            $code = strtolower($airport['code'] ?? '');
            $name = strtolower($airport['name'] ?? '');
            $city = strtolower($airport['city'] ?? '');
            $country = strtolower($airport['country'] ?? '');
            
            return str_contains($code, $query) ||
                   str_contains($name, $query) ||
                   str_contains($city, $query) ||
                   str_contains($country, $query);
        })->take(15)->values()->toArray();
        
        // If no results from API, search common airports
        if (empty($results)) {
            return $this->searchCommonAirports($query);
        }
        
        return $results;
    }
    
    /**
     * Search common airports (fallback)
     */
    private function searchCommonAirports(string $query)
    {
        $commonAirports = $this->getCommonAirports();
        $query = strtolower(trim($query));
        
        return collect($commonAirports)->filter(function($airport) use ($query) {
            $code = strtolower($airport['code']);
            $name = strtolower($airport['name']);
            $city = strtolower($airport['city']);
            
            return str_contains($code, $query) ||
                   str_contains($name, $query) ||
                   str_contains($city, $query);
        })->map(function($airport) {
            return [
                'code' => $airport['code'],
                'name' => $airport['name'],
                'city' => $airport['city'],
                'country' => $this->getCountryFromCity($airport['city']),
                'display' => $airport['code'] . ' - ' . $airport['name'] . ', ' . $airport['city'],
            ];
        })->values()->toArray();
    }
    
    /**
     * Get country from city (helper)
     */
    private function getCountryFromCity(string $city)
    {
        $cityCountryMap = [
            'New York' => 'United States',
            'Los Angeles' => 'United States',
            'Chicago' => 'United States',
            'Miami' => 'United States',
            'San Francisco' => 'United States',
            'London' => 'United Kingdom',
            'Paris' => 'France',
            'Frankfurt' => 'Germany',
            'Amsterdam' => 'Netherlands',
            'Rome' => 'Italy',
            'Dubai' => 'UAE',
            'Singapore' => 'Singapore',
            'Hong Kong' => 'Hong Kong',
            'Tokyo' => 'Japan',
            'New Delhi' => 'India',
            'Mumbai' => 'India',
            'Dhaka' => 'Bangladesh',
            'Doha' => 'Qatar',
            'Abu Dhabi' => 'UAE',
            'Sydney' => 'Australia',
            'Melbourne' => 'Australia',
        ];
        
        return $cityCountryMap[$city] ?? '';
    }

    /**
     * Get popular destinations from origin
     */
    public function getPopularDestinations(string $origin, array $options = [])
    {
        $endpoint = '/v1/city-directions';
        
        $params = array_merge([
            'origin' => strtoupper($origin),
            'currency' => 'USD',
        ], $options);

        return $this->makeRequest('GET', $endpoint, $params);
    }

    /**
     * Get flight prices calendar
     */
    public function getPricesCalendar(string $origin, string $destination, array $options = [])
    {
        $endpoint = '/v2/prices/month-matrix';
        
        $params = array_merge([
            'origin' => strtoupper($origin),
            'destination' => strtoupper($destination),
            'show_to_affiliates' => true,
            'currency' => 'USD',
        ], $options);

        return $this->makeRequest('GET', $endpoint, $params);
    }

    /**
     * Make HTTP request to Travelpayouts API
     */
    private function makeRequest(string $method, string $endpoint, array $params = [])
    {
        try {
            $url = $this->baseUrl . $endpoint;

            // Validate API token
            if (empty($this->apiToken)) {
                throw new Exception('Travelpayouts API token is not configured. Please check your configuration.');
            }

            $response = Http::timeout(30)
                ->retry(2, 1000) // Retry twice with 1 second delay
                ->withHeaders([
                    'X-Access-Token' => $this->apiToken,
                    'Accept' => 'application/json',
                ])
                ->$method($url, $params);

            // Handle different response statuses
            if ($response->successful()) {
                $data = $response->json();
                
                // Check if response indicates an error
                if (isset($data['error'])) {
                    throw new Exception($this->formatErrorMessage($data['error']));
                }
                
                return $data;
            }

            // Handle specific HTTP status codes
            if ($response->status() === 401) {
                throw new Exception('Authentication failed. Please check your API token.');
            } elseif ($response->status() === 403) {
                throw new Exception('Access forbidden. Please check your API permissions.');
            } elseif ($response->status() === 404) {
                throw new Exception('The requested endpoint was not found.');
            } elseif ($response->status() === 429) {
                throw new Exception('Rate limit exceeded. Please try again later.');
            } elseif ($response->status() >= 500) {
                throw new Exception('The flight search service is temporarily unavailable. Please try again later.');
            }

            // Parse error message from response
            $errorBody = $response->json();
            $errorMessage = $errorBody['error'] ?? $errorBody['message'] ?? $response->body() ?? 'Unknown error occurred';
            
            throw new Exception($this->formatErrorMessage($errorMessage));

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Travelpayouts API Connection Error: ' . $e->getMessage());
            throw new Exception('Unable to connect to the flight search service. Please check your internet connection and try again.');
            
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Travelpayouts API Request Error: ' . $e->getMessage());
            throw new Exception('Request to flight search service failed. Please try again.');
            
        } catch (\Exception $e) {
            // If it's already a formatted exception, re-throw it
            if (str_contains($e->getMessage(), 'Travelpayouts') || 
                str_contains($e->getMessage(), 'airport') ||
                str_contains($e->getMessage(), 'flight')) {
                throw $e;
            }
            
            Log::error('Travelpayouts API Error: ' . $e->getMessage(), [
                'endpoint' => $endpoint,
                'method' => $method,
            ]);
            
            throw new Exception('An error occurred while searching for flights. Please try again later.');
        }
    }

    /**
     * Format error messages to be more user-friendly
     */
    private function formatErrorMessage(string $errorMessage): string
    {
        // Make error message more user-friendly
        if (str_contains($errorMessage, 'not flightable')) {
            preg_match('/airport ([A-Z]{3})/', $errorMessage, $matches);
            $code = $matches[1] ?? 'unknown';
            return "The airport code '{$code}' is not valid for flight searches. Please use a specific airport code instead of a city code.";
        }
        
        if (str_contains($errorMessage, 'timeout')) {
            return 'The request timed out. Please try again.';
        }
        
        if (str_contains($errorMessage, 'connection')) {
            return 'Unable to connect to the flight search service. Please try again later.';
        }
        
        if (str_contains($errorMessage, 'rate limit')) {
            return 'Too many requests. Please wait a moment and try again.';
        }
        
        return "Travelpayouts API Error: " . $errorMessage;
    }

    /**
     * Build search URL for Aviasales
     */
    public function buildSearchUrl(array $params): string
    {
        $baseUrl = 'https://www.aviasales.com/search';
        
        $searchParams = [
            'origin_iata' => strtoupper($params['origin']),
            'destination_iata' => strtoupper($params['destination']),
            'departure_at' => $params['departure_at'],
            'return_at' => $params['return_at'] ?? null,
            'adults' => $params['adults'] ?? 1,
            'children' => $params['children'] ?? 0,
            'infants' => $params['infants'] ?? 0,
            'marker' => $this->marker,
        ];

        return $baseUrl . '?' . http_build_query(array_filter($searchParams));
    }

    /**
     * Get common airport codes (helper)
     */
    public function getCommonAirports(): array
    {
        return [
            // USA - New York
            ['code' => 'JFK', 'name' => 'John F. Kennedy International Airport', 'city' => 'New York'],
            ['code' => 'LGA', 'name' => 'LaGuardia Airport', 'city' => 'New York'],
            ['code' => 'EWR', 'name' => 'Newark Liberty International Airport', 'city' => 'New York'],
            
            // USA - Other Cities
            ['code' => 'LAX', 'name' => 'Los Angeles International Airport', 'city' => 'Los Angeles'],
            ['code' => 'ORD', 'name' => "O'Hare International Airport", 'city' => 'Chicago'],
            ['code' => 'MIA', 'name' => 'Miami International Airport', 'city' => 'Miami'],
            ['code' => 'SFO', 'name' => 'San Francisco International Airport', 'city' => 'San Francisco'],
            ['code' => 'SEA', 'name' => 'Seattle-Tacoma International Airport', 'city' => 'Seattle'],
            ['code' => 'LAS', 'name' => 'Harry Reid International Airport', 'city' => 'Las Vegas'],
            ['code' => 'ATL', 'name' => 'Hartsfield-Jackson Atlanta International Airport', 'city' => 'Atlanta'],
            ['code' => 'DFW', 'name' => 'Dallas/Fort Worth International Airport', 'city' => 'Dallas'],
            ['code' => 'IAH', 'name' => 'George Bush Intercontinental Airport', 'city' => 'Houston'],
            ['code' => 'BOS', 'name' => 'Logan International Airport', 'city' => 'Boston'],
            
            // Europe - London
            ['code' => 'LHR', 'name' => 'London Heathrow Airport', 'city' => 'London'],
            ['code' => 'LGW', 'name' => 'London Gatwick Airport', 'city' => 'London'],
            ['code' => 'STN', 'name' => 'London Stansted Airport', 'city' => 'London'],
            
            // Europe - Other Cities
            ['code' => 'CDG', 'name' => 'Charles de Gaulle Airport', 'city' => 'Paris'],
            ['code' => 'ORY', 'name' => 'Paris Orly Airport', 'city' => 'Paris'],
            ['code' => 'FRA', 'name' => 'Frankfurt Airport', 'city' => 'Frankfurt'],
            ['code' => 'AMS', 'name' => 'Amsterdam Airport Schiphol', 'city' => 'Amsterdam'],
            ['code' => 'FCO', 'name' => 'Leonardo da Vinci-Fiumicino Airport', 'city' => 'Rome'],
            ['code' => 'MAD', 'name' => 'Adolfo Suárez Madrid-Barajas Airport', 'city' => 'Madrid'],
            ['code' => 'BCN', 'name' => 'Barcelona-El Prat Airport', 'city' => 'Barcelona'],
            ['code' => 'MUC', 'name' => 'Munich Airport', 'city' => 'Munich'],
            ['code' => 'ZRH', 'name' => 'Zurich Airport', 'city' => 'Zurich'],
            
            // Middle East
            ['code' => 'DXB', 'name' => 'Dubai International Airport', 'city' => 'Dubai'],
            ['code' => 'DWC', 'name' => 'Al Maktoum International Airport', 'city' => 'Dubai'],
            ['code' => 'AUH', 'name' => 'Abu Dhabi International Airport', 'city' => 'Abu Dhabi'],
            ['code' => 'DOH', 'name' => 'Hamad International Airport', 'city' => 'Doha'],
            ['code' => 'JED', 'name' => 'King Abdulaziz International Airport', 'city' => 'Jeddah'],
            ['code' => 'RUH', 'name' => 'King Khalid International Airport', 'city' => 'Riyadh'],
            
            // Asia - Japan
            ['code' => 'NRT', 'name' => 'Narita International Airport', 'city' => 'Tokyo'],
            ['code' => 'HND', 'name' => 'Tokyo Haneda Airport', 'city' => 'Tokyo'],
            
            // Asia - Other
            ['code' => 'SIN', 'name' => 'Singapore Changi Airport', 'city' => 'Singapore'],
            ['code' => 'HKG', 'name' => 'Hong Kong International Airport', 'city' => 'Hong Kong'],
            ['code' => 'ICN', 'name' => 'Incheon International Airport', 'city' => 'Seoul'],
            ['code' => 'BKK', 'name' => 'Suvarnabhumi Airport', 'city' => 'Bangkok'],
            ['code' => 'KUL', 'name' => 'Kuala Lumpur International Airport', 'city' => 'Kuala Lumpur'],
            
            // South Asia
            ['code' => 'DEL', 'name' => 'Indira Gandhi International Airport', 'city' => 'New Delhi'],
            ['code' => 'BOM', 'name' => 'Chhatrapati Shivaji Maharaj International Airport', 'city' => 'Mumbai'],
            ['code' => 'BLR', 'name' => 'Kempegowda International Airport', 'city' => 'Bangalore'],
            ['code' => 'DAC', 'name' => 'Hazrat Shahjalal International Airport', 'city' => 'Dhaka'],
            ['code' => 'CGP', 'name' => 'Shah Amanat International Airport', 'city' => 'Chittagong'],
            ['code' => 'ISB', 'name' => 'Islamabad International Airport', 'city' => 'Islamabad'],
            ['code' => 'KHI', 'name' => 'Jinnah International Airport', 'city' => 'Karachi'],
            
            // Australia & Oceania
            ['code' => 'SYD', 'name' => 'Sydney Kingsford Smith Airport', 'city' => 'Sydney'],
            ['code' => 'MEL', 'name' => 'Melbourne Airport', 'city' => 'Melbourne'],
            ['code' => 'BNE', 'name' => 'Brisbane Airport', 'city' => 'Brisbane'],
            ['code' => 'AKL', 'name' => 'Auckland Airport', 'city' => 'Auckland'],
            
            // China
            ['code' => 'PEK', 'name' => 'Beijing Capital International Airport', 'city' => 'Beijing'],
            ['code' => 'PVG', 'name' => 'Shanghai Pudong International Airport', 'city' => 'Shanghai'],
            ['code' => 'CAN', 'name' => 'Guangzhou Baiyun International Airport', 'city' => 'Guangzhou'],
        ];
    }

    /**
     * Convert airline name to IATA code
     * Travelpayouts API requires airline IATA codes (2-letter codes) instead of full names
     */
    private function getAirlineIataCode(string $airlineName): ?string
    {
        // Comprehensive map of airline names to their IATA codes
        $airlineMap = [
            // North America - USA
            'United Airlines' => 'UA',
            'United' => 'UA',
            'American Airlines' => 'AA',
            'American' => 'AA',
            'Delta Air Lines' => 'DL',
            'Delta' => 'DL',
            'Southwest Airlines' => 'WN',
            'Southwest' => 'WN',
            'JetBlue Airways' => 'B6',
            'JetBlue' => 'B6',
            'Alaska Airlines' => 'AS',
            'Alaska' => 'AS',
            'Spirit Airlines' => 'NK',
            'Spirit' => 'NK',
            'Frontier Airlines' => 'F9',
            'Frontier' => 'F9',
            'Hawaiian Airlines' => 'HA',
            'Hawaiian' => 'HA',
            'Allegiant Air' => 'G4',
            'Allegiant' => 'G4',
            'Sun Country Airlines' => 'SY',
            'Sun Country' => 'SY',
            'Virgin America' => 'VX',
            
            // North America - Canada
            'Air Canada' => 'AC',
            'WestJet' => 'WS',
            'Air Transat' => 'TS',
            'Porter Airlines' => 'PD',
            'Flair Airlines' => 'F8',
            
            // North America - Mexico
            'Aeromexico' => 'AM',
            'Aeroméxico' => 'AM',
            'Volaris' => 'Y4',
            'Interjet' => '4O',
            'VivaAerobus' => 'VB',
            
            // Europe - UK & Ireland
            'British Airways' => 'BA',
            'Virgin Atlantic' => 'VS',
            'Virgin Atlantic Airways' => 'VS',
            'EasyJet' => 'U2',
            'easyJet' => 'U2',
            'Ryanair' => 'FR',
            'Aer Lingus' => 'EI',
            'Jet2' => 'LS',
            'TUI Airways' => 'BY',
            'Thomas Cook Airlines' => 'MT',
            
            // Europe - Germany
            'Lufthansa' => 'LH',
            'Eurowings' => 'EW',
            'Condor' => 'DE',
            'Germanwings' => '4U',
            
            // Europe - France
            'Air France' => 'AF',
            'Transavia France' => 'TO',
            'Corsair International' => 'SS',
            'French Bee' => 'BF',
            
            // Europe - Netherlands
            'KLM' => 'KL',
            'KLM Royal Dutch Airlines' => 'KL',
            'Transavia' => 'HV',
            
            // Europe - Spain
            'Iberia' => 'IB',
            'Vueling' => 'VY',
            'Air Europa' => 'UX',
            'Volotea' => 'V7',
            
            // Europe - Italy
            'Alitalia' => 'AZ',
            'ITA Airways' => 'AZ',
            'Ryanair' => 'FR',
            'Volotea' => 'V7',
            'Neos' => 'NO',
            
            // Europe - Switzerland
            'Swiss International Air Lines' => 'LX',
            'Swiss' => 'LX',
            'Edelweiss Air' => 'WK',
            
            // Europe - Austria
            'Austrian Airlines' => 'OS',
            'Austrian' => 'OS',
            
            // Europe - Scandinavia
            'Scandinavian Airlines' => 'SK',
            'SAS' => 'SK',
            'Finnair' => 'AY',
            'Norwegian Air Shuttle' => 'DY',
            'Norwegian' => 'DY',
            'Icelandair' => 'FI',
            'Wizz Air' => 'W6',
            
            // Europe - Eastern Europe
            'Aeroflot' => 'SU',
            'Aeroflot Russian Airlines' => 'SU',
            'LOT Polish Airlines' => 'LO',
            'LOT' => 'LO',
            'Czech Airlines' => 'OK',
            'CSA' => 'OK',
            'TAROM' => 'RO',
            'Bulgaria Air' => 'FB',
            'Croatia Airlines' => 'OU',
            'Adria Airways' => 'JP',
            'Air Serbia' => 'JU',
            
            // Europe - Turkey
            'Turkish Airlines' => 'TK',
            'Turkish' => 'TK',
            'Pegasus Airlines' => 'PC',
            'Pegasus' => 'PC',
            'SunExpress' => 'XQ',
            
            // Europe - Greece
            'Aegean Airlines' => 'A3',
            'Aegean' => 'A3',
            'Olympic Air' => 'OA',
            
            // Europe - Portugal
            'TAP Air Portugal' => 'TP',
            'TAP' => 'TP',
            
            // Europe - Belgium
            'Brussels Airlines' => 'SN',
            
            // Middle East
            'Emirates' => 'EK',
            'Qatar Airways' => 'QR',
            'Qatar' => 'QR',
            'Etihad Airways' => 'EY',
            'Etihad' => 'EY',
            'Saudia' => 'SV',
            'Saudi Arabian Airlines' => 'SV',
            'Oman Air' => 'WY',
            'Royal Jordanian' => 'RJ',
            'Middle East Airlines' => 'ME',
            'MEA' => 'ME',
            'Gulf Air' => 'GF',
            'Kuwait Airways' => 'KU',
            'EgyptAir' => 'MS',
            'El Al' => 'LY',
            'El Al Israel Airlines' => 'LY',
            'Flydubai' => 'FZ',
            'Air Arabia' => 'G9',
            
            // Asia - Japan
            'Japan Airlines' => 'JL',
            'JAL' => 'JL',
            'All Nippon Airways' => 'NH',
            'ANA' => 'NH',
            'Air Japan' => 'NQ',
            'Jetstar Japan' => 'GK',
            'Peach Aviation' => 'MM',
            'Vanilla Air' => 'JW',
            'StarFlyer' => '7G',
            
            // Asia - China
            'Air China' => 'CA',
            'China Eastern Airlines' => 'MU',
            'China Eastern' => 'MU',
            'China Southern Airlines' => 'CZ',
            'China Southern' => 'CZ',
            'Hainan Airlines' => 'HU',
            'Xiamen Airlines' => 'MF',
            'Shenzhen Airlines' => 'ZH',
            'Sichuan Airlines' => '3U',
            'Shanghai Airlines' => 'FM',
            'Juneyao Airlines' => 'HO',
            'Spring Airlines' => '9C',
            
            // Asia - South Korea
            'Korean Air' => 'KE',
            'Asiana Airlines' => 'OZ',
            'Asiana' => 'OZ',
            'Jeju Air' => '7C',
            'Jin Air' => 'LJ',
            'T\'way Air' => 'TW',
            'Tway Air' => 'TW',
            
            // Asia - Southeast Asia
            'Singapore Airlines' => 'SQ',
            'SIA' => 'SQ',
            'Scoot' => 'TR',
            'Jetstar Asia' => '3K',
            'Thai Airways' => 'TG',
            'Thai Airways International' => 'TG',
            'Bangkok Airways' => 'PG',
            'Thai AirAsia' => 'FD',
            'Nok Air' => 'DD',
            'Malaysia Airlines' => 'MH',
            'AirAsia' => 'AK',
            'Malindo Air' => 'OD',
            'Batik Air' => 'ID',
            'Lion Air' => 'JT',
            'Garuda Indonesia' => 'GA',
            'Citilink' => 'QG',
            'Wings Air' => 'IW',
            'Vietnam Airlines' => 'VN',
            'VietJet Air' => 'VJ',
            'Bamboo Airways' => 'QH',
            'Jetstar Pacific' => 'BL',
            'Philippine Airlines' => 'PR',
            'PAL' => 'PR',
            'Cebu Pacific' => '5J',
            'Philippines AirAsia' => 'Z2',
            'Myanmar Airways International' => '8M',
            'Cambodia Angkor Air' => 'K6',
            'Lao Airlines' => 'QV',
            'Royal Brunei Airlines' => 'BI',
            
            // Asia - South Asia
            'Air India' => 'AI',
            'IndiGo' => '6E',
            'SpiceJet' => 'SG',
            'GoAir' => 'G8',
            'Go First' => 'G8',
            'Vistara' => 'UK',
            'AirAsia India' => 'I5',
            'Alliance Air' => '9I',
            'Jet Airways' => '9W',
            'Pakistan International Airlines' => 'PK',
            'PIA' => 'PK',
            'Airblue' => 'PA',
            'Serene Air' => 'ER',
            'Biman Bangladesh Airlines' => 'BG',
            'US-Bangla Airlines' => 'BS',
            'Nepal Airlines' => 'RA',
            'Yeti Airlines' => 'YT',
            'Buddha Air' => 'U4',
            'SriLankan Airlines' => 'UL',
            'Mihin Lanka' => 'MJ',
            'Maldivian' => 'Q2',
            
            // Asia - Central Asia
            'Uzbekistan Airways' => 'HY',
            'Air Astana' => 'KC',
            'Kyrgyzstan Airlines' => 'QH',
            'Turkmenistan Airlines' => 'T5',
            
            // Asia - Taiwan & Hong Kong
            'EVA Air' => 'BR',
            'China Airlines' => 'CI',
            'Mandarin Airlines' => 'AE',
            'Tigerair Taiwan' => 'IT',
            'Cathay Pacific' => 'CX',
            'Cathay' => 'CX',
            'Hong Kong Airlines' => 'HX',
            'HK Express' => 'UO',
            
            // Oceania
            'Qantas' => 'QF',
            'Jetstar' => 'JQ',
            'Virgin Australia' => 'VA',
            'Regional Express' => 'ZL',
            'Air New Zealand' => 'NZ',
            'Fiji Airways' => 'FJ',
            'Air Pacific' => 'FJ',
            'Air Calin' => 'SB',
            'Papua New Guinea Airlines' => 'PX',
            
            // Africa
            'Ethiopian Airlines' => 'ET',
            'Ethiopian' => 'ET',
            'South African Airways' => 'SA',
            'SAA' => 'SA',
            'Kenya Airways' => 'KQ',
            'EgyptAir' => 'MS',
            'Royal Air Maroc' => 'AT',
            'Tunisair' => 'TU',
            'Air Algérie' => 'AH',
            'Nigerian Airways' => 'WT',
            'Arik Air' => 'W3',
            'Air Mauritius' => 'MK',
            'Air Seychelles' => 'HM',
            
            // South America
            'LATAM Airlines' => 'LA',
            'LATAM' => 'LA',
            'LATAM Brasil' => 'JJ',
            'GOL Linhas Aéreas' => 'G3',
            'GOL' => 'G3',
            'Azul Brazilian Airlines' => 'AD',
            'Azul' => 'AD',
            'Aerolíneas Argentinas' => 'AR',
            'Aerolineas Argentinas' => 'AR',
            'Avianca' => 'AV',
            'Avianca Brasil' => 'O6',
            'Copa Airlines' => 'CM',
            'Copa' => 'CM',
            'LACSA' => 'LR',
            'TACA' => 'TA',
            'LAN Airlines' => 'LA',
            'TAM Airlines' => 'JJ',
            'Sky Airline' => 'H2',
            'JetSMART' => 'JA',
            
            // Low-Cost Carriers - Europe
            'Wizz Air' => 'W6',
            'Wizz Air Hungary' => 'W6',
            'Wizz Air UK' => 'W9',
            'Wizz Air Abu Dhabi' => '5W',
            'Eurowings' => 'EW',
            'Vueling' => 'VY',
            'Norwegian' => 'DY',
            'Transavia' => 'HV',
            'Transavia France' => 'TO',
            'Volotea' => 'V7',
            'Blue Air' => '0B',
            'Ryanair' => 'FR',
            
            // Low-Cost Carriers - Asia
            'AirAsia' => 'AK',
            'AirAsia X' => 'D7',
            'Thai AirAsia' => 'FD',
            'Indonesia AirAsia' => 'QZ',
            'Philippines AirAsia' => 'Z2',
            'AirAsia India' => 'I5',
            'Jetstar' => 'JQ',
            'Jetstar Asia' => '3K',
            'Jetstar Japan' => 'GK',
            'Jetstar Pacific' => 'BL',
            'Scoot' => 'TR',
            'Tigerair' => 'TR',
            'Tigerair Taiwan' => 'IT',
            'Cebu Pacific' => '5J',
            'IndiGo' => '6E',
            'SpiceJet' => 'SG',
            'GoAir' => 'G8',
            'Go First' => 'G8',
            'Lion Air' => 'JT',
            'Wings Air' => 'IW',
            'Citilink' => 'QG',
            'Batik Air' => 'ID',
            'VietJet Air' => 'VJ',
            'Jeju Air' => '7C',
            'Jin Air' => 'LJ',
            'T\'way Air' => 'TW',
            'Tway Air' => 'TW',
            'Eastar Jet' => 'ZE',
            'Peach Aviation' => 'MM',
            'Vanilla Air' => 'JW',
            'Spring Airlines' => '9C',
            'Juneyao Airlines' => 'HO',
            
            // Low-Cost Carriers - Americas
            'Spirit Airlines' => 'NK',
            'Frontier Airlines' => 'F9',
            'Allegiant Air' => 'G4',
            'JetBlue' => 'B6',
            'Southwest' => 'WN',
            'Volaris' => 'Y4',
            'VivaAerobus' => 'VB',
            'Interjet' => '4O',
            'GOL' => 'G3',
            'Azul' => 'AD',
            'JetSMART' => 'JA',
            'Sky Airline' => 'H2',
            
            // Regional & Charter Airlines
            'Alaska Horizon' => 'QX',
            'SkyWest Airlines' => 'OO',
            'ExpressJet' => 'XE',
            'Republic Airways' => 'YX',
            'Mesa Airlines' => 'YV',
            'Envoy Air' => 'MQ',
            'Piedmont Airlines' => 'PT',
            'PSA Airlines' => 'OH',
            'Endeavor Air' => '9E',
            'Compass Airlines' => 'CP',
            'GoJet Airlines' => 'G7',
            'Trans States Airlines' => 'AX',
            'Cape Air' => '9K',
            'Silver Airways' => '3M',
            'Ravn Alaska' => '7H',
            'PenAir' => 'KS',
            'Boutique Air' => '4B',
            
            // Cargo Airlines
            'FedEx' => 'FX',
            'FedEx Express' => 'FX',
            'UPS Airlines' => '5X',
            'UPS' => '5X',
            'DHL Aviation' => 'D0',
            'Cargolux' => 'CV',
            'Atlas Air' => '5Y',
            'Kalitta Air' => 'K4',
            'Polar Air Cargo' => 'PO',
            
            // Other Notable Airlines
            'Aer Lingus' => 'EI',
            'Aeromexico' => 'AM',
            'Aeroméxico' => 'AM',
            'Air Astana' => 'KC',
            'Air Baltic' => 'BT',
            'Air Europa' => 'UX',
            'Air Malta' => 'KM',
            'Air Mauritius' => 'MK',
            'Air Seychelles' => 'HM',
            'Alaska Airlines' => 'AS',
            'Allegiant Air' => 'G4',
            'Arik Air' => 'W3',
            'Asiana Airlines' => 'OZ',
            'Austrian Airlines' => 'OS',
            'Avianca' => 'AV',
            'Azul' => 'AD',
            'Bangkok Airways' => 'PG',
            'Biman Bangladesh Airlines' => 'BG',
            'Brussels Airlines' => 'SN',
            'Bulgaria Air' => 'FB',
            'Cathay Pacific' => 'CX',
            'China Airlines' => 'CI',
            'Copa Airlines' => 'CM',
            'Croatia Airlines' => 'OU',
            'Czech Airlines' => 'OK',
            'Edelweiss Air' => 'WK',
            'El Al' => 'LY',
            'Ethiopian Airlines' => 'ET',
            'EVA Air' => 'BR',
            'Fiji Airways' => 'FJ',
            'Finnair' => 'AY',
            'Flydubai' => 'FZ',
            'Frontier Airlines' => 'F9',
            'Garuda Indonesia' => 'GA',
            'GOL' => 'G3',
            'Gulf Air' => 'GF',
            'Hainan Airlines' => 'HU',
            'Hawaiian Airlines' => 'HA',
            'Hong Kong Airlines' => 'HX',
            'Icelandair' => 'FI',
            'IndiGo' => '6E',
            'ITA Airways' => 'AZ',
            'Japan Airlines' => 'JL',
            'JetBlue' => 'B6',
            'JetSMART' => 'JA',
            'Kenya Airways' => 'KQ',
            'Kuwait Airways' => 'KU',
            'LATAM' => 'LA',
            'LOT Polish Airlines' => 'LO',
            'Malaysia Airlines' => 'MH',
            'Middle East Airlines' => 'ME',
            'Nepal Airlines' => 'RA',
            'Norwegian' => 'DY',
            'Oman Air' => 'WY',
            'Pakistan International Airlines' => 'PK',
            'Philippine Airlines' => 'PR',
            'Qantas' => 'QF',
            'Qatar Airways' => 'QR',
            'Royal Air Maroc' => 'AT',
            'Royal Brunei Airlines' => 'BI',
            'Royal Jordanian' => 'RJ',
            'SAS' => 'SK',
            'Saudia' => 'SV',
            'Shanghai Airlines' => 'FM',
            'Shenzhen Airlines' => 'ZH',
            'Sichuan Airlines' => '3U',
            'Singapore Airlines' => 'SQ',
            'Sky Airline' => 'H2',
            'South African Airways' => 'SA',
            'Southwest Airlines' => 'WN',
            'SpiceJet' => 'SG',
            'Spirit Airlines' => 'NK',
            'SriLankan Airlines' => 'UL',
            'Sun Country Airlines' => 'SY',
            'SunExpress' => 'XQ',
            'Swiss' => 'LX',
            'TAP Air Portugal' => 'TP',
            'Thai Airways' => 'TG',
            'Tunisair' => 'TU',
            'Turkish Airlines' => 'TK',
            'US-Bangla Airlines' => 'BS',
            'Uzbekistan Airways' => 'HY',
            'Vietnam Airlines' => 'VN',
            'Virgin Atlantic' => 'VS',
            'Virgin Australia' => 'VA',
            'Vistara' => 'UK',
            'Volaris' => 'Y4',
            'Volotea' => 'V7',
            'Vueling' => 'VY',
            'WestJet' => 'WS',
            'Wizz Air' => 'W6',
            'Xiamen Airlines' => 'MF',
        ];

        // Normalize the airline name for matching (case-insensitive, trim whitespace)
        $normalizedName = trim(strtolower($airlineName));
        
        // Try exact match first
        foreach ($airlineMap as $name => $code) {
            if (strtolower(trim($name)) === $normalizedName) {
                return $code;
            }
        }
        
        // Try partial match (in case of variations)
        foreach ($airlineMap as $name => $code) {
            if (str_contains($normalizedName, strtolower($name)) || 
                str_contains(strtolower($name), $normalizedName)) {
                return $code;
            }
        }
        
        // If it's already a 2-letter uppercase code, return it as is
        if (preg_match('/^[A-Z]{2}$/', strtoupper(trim($airlineName)))) {
            return strtoupper(trim($airlineName));
        }
        
        // If no match found, return null (API will ignore the parameter)
        Log::warning("Airline IATA code not found for: " . $airlineName);
        return null;
    }

    /**
     * Search hotels (stub - to be implemented)
     */
    public function searchHotels(array $params)
    {
        throw new Exception('Hotel search functionality is not yet implemented');
    }

    /**
     * Get hotel prices (stub - to be implemented)
     */
    public function getHotelPrices(int $hotelId, array $params)
    {
        throw new Exception('Hotel prices functionality is not yet implemented');
    }

    /**
     * Enhance flight response with detailed information
     * Processes the API response to extract and structure all available flight details
     */
    private function enhanceFlightResponse(array $response, array $searchParams): array
    {
        if (!isset($response['data']) || !is_array($response['data'])) {
            return $response;
        }

        // Process each flight in the response
        foreach ($response['data'] as &$flight) {
            // Extract and structure detailed flight information
            
            // 1. Airport information - ensure we have IATA codes
            // Check multiple possible field names for origin
            if (!isset($flight['origin_iata'])) {
                if (isset($flight['origin'])) {
                    $flight['origin_iata'] = strtoupper($flight['origin']);
                } elseif (isset($searchParams['origin'])) {
                    $flight['origin_iata'] = strtoupper($searchParams['origin']);
                }
            }
            
            // Check multiple possible field names for destination
            if (!isset($flight['destination_iata'])) {
                if (isset($flight['destination'])) {
                    $flight['destination_iata'] = strtoupper($flight['destination']);
                } elseif (isset($searchParams['destination'])) {
                    $flight['destination_iata'] = strtoupper($searchParams['destination']);
                }
            }
            
            // 2. Extract origin and destination from various possible fields
            if (!isset($flight['origin']) && isset($flight['origin_iata'])) {
                $flight['origin'] = $flight['origin_iata'];
            }
            if (!isset($flight['destination']) && isset($flight['destination_iata'])) {
                $flight['destination'] = $flight['destination_iata'];
            }
            
            // Check for transfers/stops information
            $transfers = $flight['transfers'] ?? $flight['stops'] ?? $flight['number_of_changes'] ?? 0;
            $hasTransit = ($transfers > 0);
            
            // Store transfers count
            $flight['transfers'] = $transfers;
            $flight['stops'] = $transfers;
            
            // 3. Process route information if available
            // The API might return route as a string like "JFK-DXB-BKK" or as an array
            // Also check for 'via' field which might contain transit airports
            $routeAirports = [];
            
            if (isset($flight['route'])) {
                if (is_string($flight['route'])) {
                    // Convert string route to array: "JFK-DXB-BKK" => ["JFK", "DXB", "BKK"]
                    $routeAirports = array_filter(explode('-', $flight['route']));
                } elseif (is_array($flight['route'])) {
                    $routeAirports = $flight['route'];
                }
            }
            
            // Check for 'via' field (transit airports)
            if (isset($flight['via']) && !empty($flight['via'])) {
                $viaAirports = is_array($flight['via']) ? $flight['via'] : [$flight['via']];
                // Build route from origin -> via -> destination
                if (!empty($routeAirports)) {
                    $routeAirports = array_merge([$routeAirports[0]], $viaAirports, [end($routeAirports)]);
                } else {
                    $routeAirports = array_merge(
                        [$flight['origin_iata'] ?? $flight['origin'] ?? ''],
                        $viaAirports,
                        [$flight['destination_iata'] ?? $flight['destination'] ?? '']
                    );
                }
            }
            
            // If we have route information, process it
            if (!empty($routeAirports)) {
                $flight['route'] = array_values(array_filter($routeAirports));
                
                // If route has more than 2 airports, it means there are transits
                if (count($flight['route']) > 2) {
                    // Create segments from route
                    $flight['segments'] = [];
                    for ($i = 0; $i < count($flight['route']) - 1; $i++) {
                        $segment = [
                            'origin_iata' => strtoupper($flight['route'][$i]),
                            'destination_iata' => strtoupper($flight['route'][$i + 1]),
                            'origin' => strtoupper($flight['route'][$i]),
                            'destination' => strtoupper($flight['route'][$i + 1]),
                            'airline' => $flight['airline'] ?? null,
                            'airline_name' => $flight['airline'] ?? null,
                            'flight_number' => $flight['flight_number'] ?? $flight['number'] ?? '',
                        ];
                        
                        // Calculate segment times
                        if ($i === 0) {
                            // First segment uses main flight departure
                            $segment['departure_at'] = $flight['departure_at'] ?? $searchParams['departure_at'] ?? null;
                        } else {
                            // Subsequent segments - estimate based on previous arrival + transit time
                            $prevSegment = $flight['segments'][$i - 1] ?? null;
                            if ($prevSegment && isset($prevSegment['arrival_at'])) {
                                $prevArrival = new \DateTime($prevSegment['arrival_at']);
                                $prevArrival->modify('+2 hours'); // Default 2 hour transit
                                $segment['departure_at'] = $prevArrival->format('Y-m-d H:i:s');
                            }
                        }
                        
                        // Last segment uses main flight arrival
                        if ($i === count($flight['route']) - 2) {
                            $segment['arrival_at'] = $flight['return_at'] ?? $flight['arrival_at'] ?? null;
                        } else {
                            // Estimate arrival for intermediate segments
                            if (isset($segment['departure_at'])) {
                                $departure = new \DateTime($segment['departure_at']);
                                $departure->modify('+3 hours'); // Default 3 hour flight
                                $segment['arrival_at'] = $departure->format('Y-m-d H:i:s');
                            }
                        }
                        
                        $flight['segments'][] = $segment;
                    }
                }
            }
            
            // 4. Process segments if available in response
            // The API might return segments in various formats
            if (isset($flight['segments']) && is_array($flight['segments']) && count($flight['segments']) > 0) {
                // Process each segment
                foreach ($flight['segments'] as $index => &$segment) {
                    // Ensure all segment fields are properly set
                    if (!isset($segment['origin_iata']) && isset($segment['origin'])) {
                        $segment['origin_iata'] = strtoupper($segment['origin']);
                    }
                    if (!isset($segment['destination_iata']) && isset($segment['destination'])) {
                        $segment['destination_iata'] = strtoupper($segment['destination']);
                    }
                    
                    // Extract airport codes from various field names
                    if (!isset($segment['origin']) && isset($segment['origin_iata'])) {
                        $segment['origin'] = $segment['origin_iata'];
                    }
                    if (!isset($segment['destination']) && isset($segment['destination_iata'])) {
                        $segment['destination'] = $segment['destination_iata'];
                    }
                    
                    // Ensure airline information is available
                    if (!isset($segment['airline']) && isset($flight['airline'])) {
                        $segment['airline'] = $flight['airline'];
                    }
                    if (!isset($segment['airline_name']) && isset($segment['airline'])) {
                        if (strlen($segment['airline']) == 2) {
                            $segment['airline_name'] = $this->getAirlineNameFromCode($segment['airline']) ?? $segment['airline'];
                        } else {
                            $segment['airline_name'] = $segment['airline'];
                        }
                    }
                    
                    // Ensure flight number is available
                    if (!isset($segment['flight_number']) && !isset($segment['number'])) {
                        $segment['flight_number'] = $flight['flight_number'] ?? $flight['number'] ?? '';
                        $segment['number'] = $segment['flight_number'];
                    }
                    
                    // Ensure date/time fields are set
                    if (!isset($segment['departure_at']) && isset($segment['departure'])) {
                        $segment['departure_at'] = $segment['departure'];
                    }
                    if (!isset($segment['arrival_at']) && isset($segment['arrival'])) {
                        $segment['arrival_at'] = $segment['arrival'];
                    }
                    
                    // Calculate transit time for segments after the first one
                    if ($index > 0 && isset($flight['segments'][$index - 1])) {
                        $prevSegment = $flight['segments'][$index - 1];
                        if (isset($prevSegment['arrival_at']) && isset($segment['departure_at'])) {
                            try {
                                $prevArrival = new \DateTime($prevSegment['arrival_at']);
                                $currDeparture = new \DateTime($segment['departure_at']);
                                $transitInterval = $prevArrival->diff($currDeparture);
                                $transitHours = $transitInterval->h + ($transitInterval->days * 24);
                                $transitMinutes = $transitInterval->i;
                                $segment['transit_time'] = $transitHours . 'h ' . $transitMinutes . 'm';
                                $segment['total_transit_time'] = $segment['transit_time'];
                            } catch (\Exception $e) {
                                // Ignore date parsing errors
                            }
                        }
                    }
                }
            }
            
            // 5. If we have transfers but no segments, try to infer from route
            $hasSegments = isset($flight['segments']) && is_array($flight['segments']) && count($flight['segments']) > 0;
            if ($hasTransit && !$hasSegments) {
                if (isset($flight['route']) && is_array($flight['route']) && count($flight['route']) > 2) {
                    // Create segments from route (this was already done above, but ensure segments array exists)
                    if (!$hasSegments) {
                        // Re-process route to create segments if not already done
                        $flight['segments'] = [];
                        for ($i = 0; $i < count($flight['route']) - 1; $i++) {
                            $segment = [
                                'origin_iata' => strtoupper($flight['route'][$i]),
                                'destination_iata' => strtoupper($flight['route'][$i + 1]),
                                'origin' => strtoupper($flight['route'][$i]),
                                'destination' => strtoupper($flight['route'][$i + 1]),
                                'airline' => $flight['airline'] ?? null,
                                'airline_name' => $flight['airline_name'] ?? $flight['airline'] ?? null,
                                'flight_number' => $flight['flight_number'] ?? $flight['number'] ?? '',
                            ];
                            
                            // Calculate segment times
                            if ($i === 0) {
                                $segment['departure_at'] = $flight['departure_at'] ?? $searchParams['departure_at'] ?? null;
                            } else {
                                $prevSegment = $flight['segments'][$i - 1] ?? null;
                                if ($prevSegment && isset($prevSegment['arrival_at'])) {
                                    $prevArrival = new \DateTime($prevSegment['arrival_at']);
                                    $prevArrival->modify('+2 hours'); // Default 2 hour transit
                                    $segment['departure_at'] = $prevArrival->format('Y-m-d H:i:s');
                                }
                            }
                            
                            if ($i === count($flight['route']) - 2) {
                                $segment['arrival_at'] = $flight['return_at'] ?? $flight['arrival_at'] ?? null;
                            } else {
                                if (isset($segment['departure_at'])) {
                                    $departure = new \DateTime($segment['departure_at']);
                                    $departure->modify('+3 hours'); // Default 3 hour flight
                                    $segment['arrival_at'] = $departure->format('Y-m-d H:i:s');
                                }
                            }
                            
                            $flight['segments'][] = $segment;
                        }
                    }
                }
            }
            
            // 5. Extract airline information - ensure we have both code and name
            if (isset($flight['airline']) && strlen($flight['airline']) == 2) {
                // If airline is an IATA code, try to get full name
                $airlineName = $this->getAirlineNameFromCode($flight['airline']);
                if ($airlineName) {
                    $flight['airline_name'] = $airlineName;
                } else {
                    $flight['airline_name'] = $flight['airline'];
                }
            } elseif (isset($flight['airline']) && !isset($flight['airline_name'])) {
                // If airline is already a name, use it
                $flight['airline_name'] = $flight['airline'];
            }
            
            // 6. Ensure we have proper date/time fields
            if (isset($flight['departure_at']) && !isset($flight['departure'])) {
                $flight['departure'] = $flight['departure_at'];
            }
            if (isset($flight['return_at']) && !isset($flight['arrival_at'])) {
                // For one-way flights, return_at might actually be arrival_at
                $flight['arrival_at'] = $flight['return_at'];
                $flight['arrival'] = $flight['return_at'];
            }
            
            // 7. Add search context for easier processing
            $flight['search_origin'] = $searchParams['origin'] ?? null;
            $flight['search_destination'] = $searchParams['destination'] ?? null;
            $flight['search_departure_at'] = $searchParams['departure_at'] ?? null;
            $flight['search_return_at'] = $searchParams['return_at'] ?? null;
        }

        return $response;
    }

    /**
     * Get airline full name from IATA code (reverse lookup)
     */
    private function getAirlineNameFromCode(string $iataCode): ?string
    {
        // Reverse mapping - get full name from IATA code
        $codeToNameMap = [
            'UA' => 'United Airlines',
            'AA' => 'American Airlines',
            'DL' => 'Delta Air Lines',
            'WN' => 'Southwest Airlines',
            'B6' => 'JetBlue Airways',
            'AS' => 'Alaska Airlines',
            'NK' => 'Spirit Airlines',
            'F9' => 'Frontier Airlines',
            'HA' => 'Hawaiian Airlines',
            'G4' => 'Allegiant Air',
            'SY' => 'Sun Country Airlines',
            'VX' => 'Virgin America',
            'AC' => 'Air Canada',
            'WS' => 'WestJet',
            'TS' => 'Air Transat',
            'PD' => 'Porter Airlines',
            'F8' => 'Flair Airlines',
            'AM' => 'Aeromexico',
            'Y4' => 'Volaris',
            '4O' => 'Interjet',
            'VB' => 'VivaAerobus',
            'BA' => 'British Airways',
            'VS' => 'Virgin Atlantic',
            'U2' => 'EasyJet',
            'FR' => 'Ryanair',
            'EI' => 'Aer Lingus',
            'LS' => 'Jet2',
            'BY' => 'TUI Airways',
            'MT' => 'Thomas Cook Airlines',
            'LH' => 'Lufthansa',
            'EW' => 'Eurowings',
            'DE' => 'Condor',
            '4U' => 'Germanwings',
            'AF' => 'Air France',
            'TO' => 'Transavia France',
            'SS' => 'Corsair International',
            'BF' => 'French Bee',
            'KL' => 'KLM Royal Dutch Airlines',
            'HV' => 'Transavia',
            'IB' => 'Iberia',
            'VY' => 'Vueling',
            'UX' => 'Air Europa',
            'V7' => 'Volotea',
            'AZ' => 'ITA Airways',
            'NO' => 'Neos',
            'LX' => 'Swiss International Air Lines',
            'WK' => 'Edelweiss Air',
            'OS' => 'Austrian Airlines',
            'SK' => 'Scandinavian Airlines',
            'AY' => 'Finnair',
            'DY' => 'Norwegian Air Shuttle',
            'FI' => 'Icelandair',
            'W6' => 'Wizz Air',
            'SU' => 'Aeroflot Russian Airlines',
            'LO' => 'LOT Polish Airlines',
            'OK' => 'Czech Airlines',
            'RO' => 'TAROM',
            'FB' => 'Bulgaria Air',
            'OU' => 'Croatia Airlines',
            'JP' => 'Adria Airways',
            'JU' => 'Air Serbia',
            'TK' => 'Turkish Airlines',
            'PC' => 'Pegasus Airlines',
            'XQ' => 'SunExpress',
            'A3' => 'Aegean Airlines',
            'OA' => 'Olympic Air',
            'TP' => 'TAP Air Portugal',
            'SN' => 'Brussels Airlines',
            'EK' => 'Emirates',
            'QR' => 'Qatar Airways',
            'EY' => 'Etihad Airways',
            'SV' => 'Saudia',
            'WY' => 'Oman Air',
            'RJ' => 'Royal Jordanian',
            'ME' => 'Middle East Airlines',
            'GF' => 'Gulf Air',
            'KU' => 'Kuwait Airways',
            'MS' => 'EgyptAir',
            'LY' => 'El Al Israel Airlines',
            'FZ' => 'Flydubai',
            'G9' => 'Air Arabia',
            'JL' => 'Japan Airlines',
            'NH' => 'All Nippon Airways',
            'NQ' => 'Air Japan',
            'GK' => 'Jetstar Japan',
            'MM' => 'Peach Aviation',
            'JW' => 'Vanilla Air',
            '7G' => 'StarFlyer',
            'CA' => 'Air China',
            'MU' => 'China Eastern Airlines',
            'CZ' => 'China Southern Airlines',
            'HU' => 'Hainan Airlines',
            'MF' => 'Xiamen Airlines',
            'ZH' => 'Shenzhen Airlines',
            '3U' => 'Sichuan Airlines',
            'FM' => 'Shanghai Airlines',
            'HO' => 'Juneyao Airlines',
            '9C' => 'Spring Airlines',
            'KE' => 'Korean Air',
            'OZ' => 'Asiana Airlines',
            '7C' => 'Jeju Air',
            'LJ' => 'Jin Air',
            'TW' => 'T\'way Air',
            'SQ' => 'Singapore Airlines',
            'TR' => 'Scoot',
            '3K' => 'Jetstar Asia',
            'TG' => 'Thai Airways International',
            'PG' => 'Bangkok Airways',
            'FD' => 'Thai AirAsia',
            'DD' => 'Nok Air',
            'MH' => 'Malaysia Airlines',
            'AK' => 'AirAsia',
            'OD' => 'Malindo Air',
            'ID' => 'Batik Air',
            'JT' => 'Lion Air',
            'GA' => 'Garuda Indonesia',
            'QG' => 'Citilink',
            'IW' => 'Wings Air',
            'VN' => 'Vietnam Airlines',
            'VJ' => 'VietJet Air',
            'QH' => 'Bamboo Airways',
            'BL' => 'Jetstar Pacific',
            'PR' => 'Philippine Airlines',
            '5J' => 'Cebu Pacific',
            'Z2' => 'Philippines AirAsia',
            '8M' => 'Myanmar Airways International',
            'K6' => 'Cambodia Angkor Air',
            'QV' => 'Lao Airlines',
            'BI' => 'Royal Brunei Airlines',
            'AI' => 'Air India',
            '6E' => 'IndiGo',
            'SG' => 'SpiceJet',
            'G8' => 'Go First',
            'UK' => 'Vistara',
            'I5' => 'AirAsia India',
            '9I' => 'Alliance Air',
            '9W' => 'Jet Airways',
            'PK' => 'Pakistan International Airlines',
            'PA' => 'Airblue',
            'ER' => 'Serene Air',
            'BG' => 'Biman Bangladesh Airlines',
            'BS' => 'US-Bangla Airlines',
            'RA' => 'Nepal Airlines',
            'YT' => 'Yeti Airlines',
            'U4' => 'Buddha Air',
            'UL' => 'SriLankan Airlines',
            'MJ' => 'Mihin Lanka',
            'Q2' => 'Maldivian',
            'HY' => 'Uzbekistan Airways',
            'KC' => 'Air Astana',
            'T5' => 'Turkmenistan Airlines',
            'BR' => 'EVA Air',
            'CI' => 'China Airlines',
            'AE' => 'Mandarin Airlines',
            'IT' => 'Tigerair Taiwan',
            'CX' => 'Cathay Pacific',
            'HX' => 'Hong Kong Airlines',
            'UO' => 'HK Express',
            'QF' => 'Qantas',
            'JQ' => 'Jetstar',
            'VA' => 'Virgin Australia',
            'ZL' => 'Regional Express',
            'NZ' => 'Air New Zealand',
            'FJ' => 'Fiji Airways',
            'SB' => 'Air Calin',
            'PX' => 'Papua New Guinea Airlines',
            'ET' => 'Ethiopian Airlines',
            'SA' => 'South African Airways',
            'KQ' => 'Kenya Airways',
            'AT' => 'Royal Air Maroc',
            'TU' => 'Tunisair',
            'AH' => 'Air Algérie',
            'WT' => 'Nigerian Airways',
            'W3' => 'Arik Air',
            'MK' => 'Air Mauritius',
            'HM' => 'Air Seychelles',
            'LA' => 'LATAM Airlines',
            'JJ' => 'LATAM Brasil',
            'G3' => 'GOL Linhas Aéreas',
            'AD' => 'Azul Brazilian Airlines',
            'AR' => 'Aerolíneas Argentinas',
            'AV' => 'Avianca',
            'O6' => 'Avianca Brasil',
            'CM' => 'Copa Airlines',
            'LR' => 'LACSA',
            'TA' => 'TACA',
            'H2' => 'Sky Airline',
            'JA' => 'JetSMART',
        ];
        
        $code = strtoupper(trim($iataCode));
        return $codeToNameMap[$code] ?? null;
    }
}