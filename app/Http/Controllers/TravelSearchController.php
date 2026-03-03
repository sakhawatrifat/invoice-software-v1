<?php

namespace App\Http\Controllers;


use Auth;
use File;
use Image;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth as AuthFacade;

use App\Services\TravelpayoutsService;
use App\Services\FlightApiService;
use Illuminate\Http\JsonResponse;

use App\Models\Language;
use App\Models\Currency;
use App\Models\User;
use App\Models\Airline;

class TravelSearchController extends Controller
{
    protected TravelpayoutsService $travelpayouts;

    public function __construct(TravelpayoutsService $travelpayouts)
    {
        $this->travelpayouts = $travelpayouts;
    }

    public function ticketSearchForm()
    {
        if (!hasPermission('ticket.search.form')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('ticket.index') ? route('ticket.index') : '';
        $createRoute = hasPermission('ticket.create') ? route('ticket.create') : '';
        $searchImportRoute = hasPermission('ticket.search.form') ? route('ticket.search.import') : '';
        $saveRoute = hasPermission('ticket.create') ? route('ticket.store') : '';
        $clearFlightSearchRoute = hasPermission('ticket.search.form') ? route('ticket.search.clear') : '';

        //$languages = Language::orderBy('name', 'asc')->where('status', 1)->get();
        $currencies = Currency::where('status', 'Active')->orderBy('currency_name', 'asc')->get();
        $airlines = Airline::where('status', '1')->orderBy('name', 'asc')->get();

        // Last flight search from session (for reload / reduce API hits)
        $lastFlightSearch = Session::get('flight_search_last');

        return view('common.ticket.ticketSearchForm', get_defined_vars());
    }


    public function ticketSearchImport(Request $request): JsonResponse
    {
        if (!hasPermission('ticket.search.form')) {
            return response()->json([
                'success' => false,
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ], 403);
        }

        try {
            $flightType = $request->input('flight_type', 'one_way');
            $validated = [];
            $searchParams = [];

            // Validate and prepare data based on flight type
            if ($flightType === 'one_way') {
                $validated = $request->validate([
                    'one_way.origin' => 'required|string|size:3|regex:/^[A-Z]{3}$/i',
                    'one_way.destination' => 'required|string|size:3|regex:/^[A-Z]{3}$/i',
                    'one_way.departure_at' => 'required|date|after:today',
                    'airline_name' => 'nullable|string|max:255',
                    'class' => 'nullable|string|in:economy,business,first',
                    'passenger' => 'nullable|integer|min:1|max:9',
                ], [
                    'one_way.origin.required' => 'Origin airport is required',
                    'one_way.origin.size' => 'Origin must be a 3-letter airport code',
                    'one_way.destination.required' => 'Destination airport is required',
                    'one_way.destination.size' => 'Destination must be a 3-letter airport code',
                    'one_way.departure_at.required' => 'Departure date is required',
                    'one_way.departure_at.after' => 'Departure date must be in the future',
                ]);

                $searchParams = [
                    'origin' => strtoupper($validated['one_way']['origin']),
                    'destination' => strtoupper($validated['one_way']['destination']),
                    'departure_at' => $validated['one_way']['departure_at'],
                    'return_at' => null,
                ];

            } elseif ($flightType === 'round_trip') {
                $validated = $request->validate([
                    'round_trip.origin' => 'required|string|size:3|regex:/^[A-Z]{3}$/i',
                    'round_trip.destination' => 'required|string|size:3|regex:/^[A-Z]{3}$/i',
                    'round_trip.departure_at' => 'required|date|after:today',
                    'round_trip.return_at' => 'required|date|after:round_trip.departure_at',
                    'airline_name' => 'nullable|string|max:255',
                    'class' => 'nullable|string|in:economy,business,first',
                    'passenger' => 'nullable|integer|min:1|max:9',
                ], [
                    'round_trip.origin.required' => 'Origin airport is required',
                    'round_trip.origin.size' => 'Origin must be a 3-letter airport code',
                    'round_trip.destination.required' => 'Destination airport is required',
                    'round_trip.destination.size' => 'Destination must be a 3-letter airport code',
                    'round_trip.departure_at.required' => 'Departure date is required',
                    'round_trip.departure_at.after' => 'Departure date must be in the future',
                    'round_trip.return_at.required' => 'Return date is required',
                    'round_trip.return_at.after' => 'Return date must be after departure date',
                ]);

                $searchParams = [
                    'origin' => strtoupper($validated['round_trip']['origin']),
                    'destination' => strtoupper($validated['round_trip']['destination']),
                    'departure_at' => $validated['round_trip']['departure_at'],
                    'return_at' => $validated['round_trip']['return_at'],
                ];

            } elseif ($flightType === 'multi_city') {
                $validated = $request->validate([
                    'multi_city' => 'required|array|min:2',
                    'multi_city.*.origin' => 'required|string|size:3|regex:/^[A-Z]{3}$/i',
                    'multi_city.*.destination' => 'required|string|size:3|regex:/^[A-Z]{3}$/i',
                    'multi_city.*.departure_at' => 'required|date|after:today',
                    'airline_name' => 'nullable|string|max:255',
                    'class' => 'nullable|string|in:economy,business,first',
                    'passenger' => 'nullable|integer|min:1|max:9',
                ], [
                    'multi_city.required' => 'At least 2 flights are required for multi-city search',
                    'multi_city.min' => 'At least 2 flights are required for multi-city search',
                    'multi_city.*.origin.required' => 'Origin airport is required for all flights',
                    'multi_city.*.origin.size' => 'Origin must be a 3-letter airport code',
                    'multi_city.*.destination.required' => 'Destination airport is required for all flights',
                    'multi_city.*.destination.size' => 'Destination must be a 3-letter airport code',
                    'multi_city.*.departure_at.required' => 'Departure date is required for all flights',
                    'multi_city.*.departure_at.after' => 'Departure date must be in the future',
                ]);

                // Validate departure dates are in order
                $multiCityFlights = $validated['multi_city'];
                $previousDate = null;
                foreach ($multiCityFlights as $index => $flight) {
                    $currentDate = \Carbon\Carbon::parse($flight['departure_at']);
                    if ($previousDate && $currentDate->lt($previousDate)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "multi_city.{$index}.departure_at" => "Departure date must be after the previous flight's departure date"
                        ]);
                    }
                    $previousDate = $currentDate;
                }

                $searchParams = [
                    'flight_type' => 'multi_city',
                    'flights' => array_map(function($flight) {
                        return [
                            'origin' => strtoupper($flight['origin']),
                            'destination' => strtoupper($flight['destination']),
                            'departure_at' => $flight['departure_at'],
                        ];
                    }, $multiCityFlights),
                ];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid flight type specified',
                ], 422);
            }

            // Add common parameters with defaults
            $searchParams['flight_type'] = $flightType; // Add flight type
            $searchParams['currency'] = 'JPY'; // Default currency for flight search API
            $searchParams['limit'] = 30; // Default limit
            $searchParams['result_type'] = 'general'; // Default result type
            
            if (isset($validated['airline_name']) && !empty($validated['airline_name'])) {
                $searchParams['airline'] = $validated['airline_name'];
            }
            if (isset($validated['class'])) {
                $searchParams['class'] = $validated['class'];
            }
            if (isset($validated['passenger'])) {
                $searchParams['adults'] = $validated['passenger'];
                $searchParams['passenger'] = $validated['passenger'];
            }

            // Use FlightAPI when configured, otherwise InnoTravelTech via TravelpayoutsService
            $flightApi = app(FlightApiService::class);
            if ($flightApi->isConfigured()) {
                $flights = $flightApi->searchCheapestFlights($searchParams);
            } else {
                $flights = $this->travelpayouts->searchCheapestFlights($searchParams);
            }
            
            // Check if we have results
            if (empty($flights) || (isset($flights['data']) && empty($flights['data']))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No flights found for the selected route and dates. Please try different dates or airports.',
                ], 404);
            }

            // Enhance response with search parameters for form auto-fill
            $responseData = $flights;
            if (isset($flights['data']) && is_array($flights['data'])) {
                // Add search context to each flight for easier processing
                foreach ($flights['data'] as &$flight) {
                    $flight['search_origin'] = $searchParams['origin'] ?? null;
                    $flight['search_destination'] = $searchParams['destination'] ?? null;
                    $flight['search_departure_at'] = $searchParams['departure_at'] ?? null;
                    $flight['search_return_at'] = $searchParams['return_at'] ?? null;
                    $flight['search_flight_type'] = $flightType;
                    $flight['search_class'] = $validated['class'] ?? 'economy';
                    $flight['search_passenger'] = $validated['passenger'] ?? 1;
                }
            }
            
            $searchParamsForSession = [
                'flight_type' => $flightType,
                'origin' => $searchParams['origin'] ?? null,
                'destination' => $searchParams['destination'] ?? null,
                'departure_at' => $searchParams['departure_at'] ?? null,
                'return_at' => $searchParams['return_at'] ?? null,
                'class' => $validated['class'] ?? 'economy',
                'passenger' => $validated['passenger'] ?? 1,
            ];

            // Store in session to show on reload and reduce API over-hit
            Session::put('flight_search_last', [
                'data' => $responseData,
                'search_params' => $searchParamsForSession,
                'at' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $responseData,
                'search_params' => $searchParamsForSession,
                'message' => 'Flights retrieved successfully',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            // Log the error
            Log::error('Flight search error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = $e->getMessage();
            $isTimeout = str_contains(strtolower($errorMessage), 'timeout') || str_contains(strtolower($errorMessage), 'timed out');
            $isConnection = str_contains(strtolower($errorMessage), 'connection') || str_contains(strtolower($errorMessage), 'connect');

            if (str_contains($errorMessage, 'not flightable')) {
                $errorMessage = 'One of the airport codes is not valid. Please use specific airport codes (e.g., JFK, LAX) instead of city codes (e.g., NYC, LON).';
            } elseif ($isTimeout) {
                $errorMessage = 'The flight search took too long and timed out. Please try again.';
            } elseif ($isConnection) {
                $errorMessage = 'Unable to connect to the flight search service. Please check your connection and try again.';
            } elseif (!str_contains($errorMessage, 'Validation failed')) {
                $errorMessage = 'An error occurred while searching for flights. Please try again.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'retry' => true,
            ], 500);
        }
    }

    /**
     * Clear last flight search from session (user clicks "Clear response data").
     */
    public function clearLastFlightSearch(): JsonResponse
    {
        if (!hasPermission('ticket.search.form')) {
            return response()->json([
                'success' => false,
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ], 403);
        }

        Session::forget('flight_search_last');

        return response()->json([
            'success' => true,
            'message' => 'Flight search results cleared.',
        ]);
    }

    public function getAirportSuggestions(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        try {
            $travelpayouts = app(TravelpayoutsService::class);
            $airports = $travelpayouts->searchAirports($query);
            
            return response()->json([
                'success' => true,
                'data' => $airports,
            ]);
        } catch (\Exception $e) {
            Log::error('Airport search error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch airport suggestions',
                'data' => [],
            ]);
        }
    }

    public function getCommonAirports(): JsonResponse
    {
        try {
            $travelpayouts = app(TravelpayoutsService::class);
            $airports = $travelpayouts->getCommonAirports();
            
            // Format for consistency
            $formatted = collect($airports)->map(function($airport) {
                return [
                    'code' => $airport['code'],
                    'name' => $airport['name'],
                    'city' => $airport['city'],
                    'country' => $this->getCountryFromCity($airport['city']),
                ];
            })->toArray();
            
            return response()->json([
                'success' => true,
                'data' => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch common airports',
                'data' => [],
            ]);
        }
    }

    private function getCountryFromCity(string $city)
    {
        $map = [
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
        ];
        
        return $map[$city] ?? '';
    }

    /**
     * Process selected flight data and return formatted data for ticket form
     */
    public function processFlightData(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'flight_data' => 'required|array',
                'search_params' => 'required|array',
            ]);

            $flightData = $validated['flight_data'];
            $searchParams = $validated['search_params'];

            // Process and structure flight data
            $formattedData = $this->formatFlightDataForTicketForm($flightData, $searchParams);

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'message' => 'Flight data processed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Process flight data error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process flight data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format flight data for ticket form
     */
    private function formatFlightDataForTicketForm(array $flightData, array $searchParams): array
    {
        $formatted = [
            'trip_type' => $this->mapFlightTypeToTripType($searchParams['flight_type'] ?? 'one_way'),
            'ticket_type' => $this->mapClassToTicketType($searchParams['class'] ?? 'economy'),
            'ticket_flight_info' => [],
        ];

        $flightType = $searchParams['flight_type'] ?? 'one_way';

        if ($flightType === 'one_way') {
            $flight = $this->processSingleFlight($flightData, $searchParams);
            $formatted['ticket_flight_info'][] = $flight;
        } elseif ($flightType === 'round_trip') {
            // Outbound flight
            $outboundFlight = $this->processSingleFlight($flightData, $searchParams, 'outbound');
            $formatted['ticket_flight_info'][] = $outboundFlight;

            // Return flight (if return date is provided)
            if (isset($searchParams['return_at']) && !empty($searchParams['return_at'])) {
                $returnFlight = $this->processReturnFlight($flightData, $searchParams);
                $formatted['ticket_flight_info'][] = $returnFlight;
            }
        } elseif ($flightType === 'multi_city') {
            // Process multi-city segments
            if (isset($flightData['segments']) && is_array($flightData['segments'])) {
                foreach ($flightData['segments'] as $segment) {
                    $flight = $this->processSingleFlight($segment, $searchParams);
                    $formatted['ticket_flight_info'][] = $flight;
                }
            }
        }

        // Collect all airline IDs used (main + transit) so frontend can ensure dropdown options exist and show logos
        $airlineIds = [];
        foreach ($formatted['ticket_flight_info'] as $info) {
            if (!empty($info['airline_id'])) {
                $airlineIds[] = $info['airline_id'];
            }
            foreach ($info['transit'] ?? [] as $transit) {
                if (!empty($transit['airline_id'])) {
                    $airlineIds[] = $transit['airline_id'];
                }
            }
        }
        $airlineIds = array_values(array_unique(array_filter($airlineIds)));
        $airlinesUsed = [];
        if (!empty($airlineIds)) {
            $airlines = Airline::whereIn('id', $airlineIds)->get();
            foreach ($airlines as $a) {
                $airlinesUsed[] = [
                    'id' => $a->id,
                    'name' => $a->name,
                    'logo_url' => $a->logo_url ?? null,
                ];
            }
        }
        $formatted['airlines_used'] = $airlinesUsed;
        $formatted['default_airline_logo'] = function_exists('defaultImage') ? defaultImage('s') : '';

        return $formatted;
    }

    /**
     * Process a single flight with transit handling.
     * When segments exist: main flight = first segment only (one airline, one flight number);
     * subsequent segments = separate transit rows (each with its own airline, e.g. Spring Japan then China Eastern).
     */
    private function processSingleFlight(array $flightData, array $searchParams, string $direction = 'outbound'): array
    {
        $segments = $flightData['segments'] ?? null;
        $hasSegments = is_array($segments) && count($segments) > 0;

        if ($hasSegments && count($segments) > 1) {
            // Main flight = first segment only. Leaving From = journey start; Going To = first segment destination (transit hub), NOT final destination.
            $firstSeg = $segments[0];
            $originCode = $firstSeg['origin_iata'] ?? $firstSeg['origin'] ?? $flightData['origin_iata'] ?? $searchParams['origin'] ?? '';
            $destinationCode = $firstSeg['destination_iata'] ?? $firstSeg['destination'] ?? '';
            if ($destinationCode === '' && isset($segments[1])) {
                $destinationCode = $segments[1]['origin_iata'] ?? $segments[1]['origin'] ?? '';
            }
            if ($destinationCode === '') {
                $destinationCode = $flightData['destination_iata'] ?? $searchParams['destination'] ?? '';
            }
            $origin = !empty($firstSeg['origin_display']) ? $firstSeg['origin_display'] : $this->getAirportFullName($originCode);
            $destination = !empty($firstSeg['destination_display']) ? $firstSeg['destination_display'] : $this->getAirportFullName($destinationCode);
            $airlineName = $firstSeg['airline'] ?? $firstSeg['airline_name'] ?? $flightData['airline'] ?? null;
            $airlineCode = $firstSeg['airline_code'] ?? $flightData['airline_code'] ?? null;
            $airlineId = $this->findOrCreateAirline($airlineName, $airlineCode);
            $departureAt = $firstSeg['departure_at'] ?? $firstSeg['departure'] ?? '';
            $arrivalAt = $firstSeg['arrival_at'] ?? $firstSeg['arrival'] ?? '';
            $departureDateTime = $this->formatDateTime($departureAt);
            $arrivalDateTime = $this->formatDateTime($arrivalAt);
            if (!empty($departureDateTime) && !empty($arrivalDateTime)) {
                $dep = new \DateTime($departureDateTime);
                $arr = new \DateTime($arrivalDateTime);
                if ($arr <= $dep) {
                    $arr->modify('+1 hour');
                    $arrivalDateTime = $arr->format('Y-m-d H:i');
                }
            }
            $totalFlyTime = $this->calculateDuration($departureDateTime, $arrivalDateTime);
            $mainFlightNumber = $firstSeg['flight_number'] ?? $firstSeg['marketing_flight_number'] ?? $flightData['flight_number'] ?? '';

            $flight = [
                'airline_id' => $airlineId,
                'flight_number' => $mainFlightNumber,
                'leaving_from' => $origin,
                'going_to' => $destination,
                'departure_date_time' => $departureDateTime,
                'arrival_date_time' => $arrivalDateTime,
                'total_fly_time' => $totalFlyTime,
                'is_transit' => 1,
                'transit' => $this->processTransitsFromSegments($segments, $searchParams),
            ];
            return $flight;
        }

        // No segments or single segment: build one main flight from flightData
        $originCode = $flightData['origin_iata'] ?? $flightData['search_origin'] ?? $flightData['origin'] ?? $searchParams['origin'] ?? '';
        $destinationCode = $flightData['destination_iata'] ?? $flightData['search_destination'] ?? $flightData['destination'] ?? $searchParams['destination'] ?? '';
        $origin = !empty($flightData['origin_display']) ? $flightData['origin_display'] : $this->getAirportFullName($originCode);
        $destination = !empty($flightData['destination_display']) ? $flightData['destination_display'] : $this->getAirportFullName($destinationCode);
        $airlineName = $flightData['airline'] ?? $flightData['airline_name'] ?? null;
        $airlineCode = $flightData['airline_code'] ?? (isset($segments[0]) ? ($segments[0]['airline_code'] ?? null) : null);
        $airlineId = $this->findOrCreateAirline($airlineName, $airlineCode);
        $departureAt = $flightData['departure_at'] ?? $flightData['search_departure_at'] ?? $searchParams['departure_at'] ?? '';
        $arrivalAt = $flightData['arrival_at'] ?? $flightData['arrival_date'] ?? null;
        if (empty($arrivalAt) && isset($flightData['return_at'])) {
            $departureDate = new \DateTime($departureAt);
            $returnDate = new \DateTime($flightData['return_at']);
            $diff = $returnDate->diff($departureDate);
            if ($diff->days < 2 && $returnDate > $departureDate) {
                $arrivalAt = $flightData['return_at'];
            }
        }
        $departureDateTime = $this->formatDateTime($departureAt);
        $arrivalDateTime = $this->formatDateTime($arrivalAt);
        if (!empty($departureDateTime) && !empty($arrivalDateTime)) {
            $dep = new \DateTime($departureDateTime);
            $arr = new \DateTime($arrivalDateTime);
            if ($arr <= $dep) {
                $arr->modify('+1 hour');
                $arrivalDateTime = $arr->format('Y-m-d H:i');
            }
        }
        $totalFlyTime = $this->calculateDuration($departureDateTime, $arrivalDateTime);
        $mainFlightNumber = $flightData['flight_number'] ?? $flightData['number'] ?? '';
        if ($hasSegments && count($segments) === 1) {
            $mainFlightNumber = $segments[0]['flight_number'] ?? $segments[0]['marketing_flight_number'] ?? $mainFlightNumber;
        }

        $flight = [
            'airline_id' => $airlineId,
            'flight_number' => $mainFlightNumber,
            'leaving_from' => $origin,
            'going_to' => $destination,
            'departure_date_time' => $departureDateTime,
            'arrival_date_time' => $arrivalDateTime,
            'total_fly_time' => $totalFlyTime,
            'is_transit' => 0,
            'transit' => [],
        ];

        if (isset($flightData['route']) && is_array($flightData['route']) && count($flightData['route']) > 2) {
            $transits = $this->processTransitsFromRoute($flightData, $searchParams, $departureDateTime, $arrivalDateTime);
            if (!empty($transits)) {
                $flight['is_transit'] = 1;
                $flight['transit'] = $transits;
            }
        }

        return $flight;
    }

    /**
     * Process return flight
     */
    private function processReturnFlight(array $flightData, array $searchParams): array
    {
        // Extract airport codes for return flight (reversed)
        $originCode = $flightData['destination_iata'] ?? $flightData['search_destination'] ?? $flightData['destination'] ?? $searchParams['destination'] ?? '';
        $destinationCode = $flightData['origin_iata'] ?? $flightData['search_origin'] ?? $flightData['origin'] ?? $searchParams['origin'] ?? '';
        
        // Return flight: origin = main destination, destination = main origin; prefer API display when available
        $origin = !empty($flightData['destination_display']) ? $flightData['destination_display'] : $this->getAirportFullName($originCode);
        $destination = !empty($flightData['origin_display']) ? $flightData['origin_display'] : $this->getAirportFullName($destinationCode);

        // Extract airline name for return flight
        $airlineName = $flightData['return_airline'] ?? $flightData['return_airline_name'] ?? $flightData['airline'] ?? $flightData['airline_name'] ?? null;
        $airlineCode = $flightData['return_airline_code'] ?? $flightData['airline_code'] ?? null;
        $airlineId = $this->findOrCreateAirline($airlineName, $airlineCode);

        // Extract return flight dates
        $departureDateTime = $this->formatDateTime($flightData['return_at'] ?? $flightData['return_departure_at'] ?? $searchParams['return_at'] ?? '');
        $arrivalDateTime = $this->formatDateTime($flightData['return_arrival_at'] ?? $flightData['return_arrival'] ?? '');
        
        // Ensure arrival is after departure
        if (!empty($departureDateTime) && !empty($arrivalDateTime)) {
            $dep = new \DateTime($departureDateTime);
            $arr = new \DateTime($arrivalDateTime);
            if ($arr <= $dep) {
                // If arrival is not after departure, add minimum flight time (1 hour)
                $arr->modify('+1 hour');
                $arrivalDateTime = $arr->format('Y-m-d H:i');
            }
        }

        $totalFlyTime = $this->calculateDuration($departureDateTime, $arrivalDateTime);

        $returnFlight = [
            'airline_id' => $airlineId,
            'flight_number' => $flightData['return_flight_number'] ?? $flightData['return_number'] ?? $flightData['flight_number'] ?? '',
            'leaving_from' => $origin,
            'going_to' => $destination,
            'departure_date_time' => $departureDateTime,
            'arrival_date_time' => $arrivalDateTime,
            'total_fly_time' => $totalFlyTime,
            'is_transit' => 0,
            'transit' => [],
        ];
        
        // Check if return flight has transit/segments - ONLY set transit if there are actual transit segments
        if (isset($flightData['return_segments']) && is_array($flightData['return_segments']) && count($flightData['return_segments']) > 1) {
            $transits = $this->processTransitsFromSegments($flightData['return_segments'], $searchParams);
            // Only set is_transit = 1 if there are actual transit segments
            if (!empty($transits)) {
                $returnFlight['is_transit'] = 1;
                $returnFlight['transit'] = $transits;
            }
        }

        return $returnFlight;
    }

    /**
     * Process transit flights from segments array (preferred method)
     */
    private function processTransitsFromSegments(array $segments, array $searchParams): array
    {
        $transits = [];
        
        // Skip first segment (main flight) and process rest as transits
        // If segments array has more than 1 item, items 1+ are transits
        for ($i = 1; $i < count($segments); $i++) {
            $segment = $segments[$i];
            
            // Extract airport codes (support multiple key names from API)
            $originCode = $segment['origin_iata'] ?? $segment['origin'] ?? '';
            $destinationCode = $segment['destination_iata'] ?? $segment['destination'] ?? '';
            if (($originCode === '' || $destinationCode === '') && !empty($segment['route'])) {
                $route = preg_replace('/\s+/', ' ', trim($segment['route']));
                if (preg_match('/^([A-Z]{3})\s*[→\-]\s*([A-Z]{3})/i', $route, $m)) {
                    if ($originCode === '') {
                        $originCode = strtoupper($m[1]);
                    }
                    if ($destinationCode === '') {
                        $destinationCode = strtoupper($m[2]);
                    }
                }
            }
            
            // Prefer API display string (name + code + terminal) when available; else resolve full name
            $transitOrigin = !empty($segment['origin_display']) ? $segment['origin_display'] : $this->getAirportFullName($originCode);
            $transitDestination = !empty($segment['destination_display']) ? $segment['destination_display'] : $this->getAirportFullName($destinationCode);
            if ($transitOrigin === '' && $originCode !== '') {
                $transitOrigin = $originCode;
            }
            if ($transitDestination === '' && $destinationCode !== '') {
                $transitDestination = $destinationCode;
            }
            
            // Extract airline
            $airlineName = $segment['airline'] ?? $segment['airline_name'] ?? null;
            $airlineCode = $segment['airline_code'] ?? null;
            $airlineId = $this->findOrCreateAirline($airlineName, $airlineCode);
            
            // Extract dates
            $transitDeparture = $this->formatDateTime($segment['departure_at'] ?? $segment['departure'] ?? '');
            $transitArrival = $this->formatDateTime($segment['arrival_at'] ?? $segment['arrival'] ?? '');
            
            // Ensure arrival is after departure
            if (!empty($transitDeparture) && !empty($transitArrival)) {
                $dep = new \DateTime($transitDeparture);
                $arr = new \DateTime($transitArrival);
                if ($arr <= $dep) {
                    $arr->modify('+1 hour');
                    $transitArrival = $arr->format('Y-m-d H:i');
                }
            }
            
            // Calculate transit flight time
            $transitFlyTime = $this->calculateDuration($transitDeparture, $transitArrival);
            
            // Calculate transit time (layover) - time between previous arrival and this departure
            $transitTime = '';
            if ($i > 0 && isset($segments[$i - 1])) {
                $prevSegment = $segments[$i - 1];
                $prevArrival = $this->formatDateTime($prevSegment['arrival_at'] ?? $prevSegment['arrival'] ?? '');
                if (!empty($prevArrival) && !empty($transitDeparture)) {
                    $transitTime = $this->calculateDuration($prevArrival, $transitDeparture);
                }
            }
            
            $transits[] = [
                'airline_id' => $airlineId,
                'flight_number' => $segment['flight_number'] ?? $segment['number'] ?? '',
                'leaving_from' => $transitOrigin,
                'going_to' => $transitDestination,
                'departure_date_time' => $transitDeparture,
                'arrival_date_time' => $transitArrival,
                'total_fly_time' => $transitFlyTime,
                'total_transit_time' => $transitTime,
            ];
        }
        
        return $transits;
    }
    
    /**
     * Process transit flights from route array (fallback method)
     */
    private function processTransitsFromRoute(array $flightData, array $searchParams, string $mainDeparture, string $mainArrival): array
    {
        $transits = [];

        if (isset($flightData['route']) && is_array($flightData['route']) && count($flightData['route']) > 2) {
            $route = $flightData['route'];
            $previousArrival = $mainDeparture;

            for ($i = 1; $i < count($route) - 1; $i++) {
                $transitAirport = $route[$i];
                $nextAirport = $route[$i + 1] ?? $flightData['search_destination'] ?? $flightData['destination_iata'] ?? '';

                $transitOrigin = $this->getAirportFullName($transitAirport);
                $transitDestination = $this->getAirportFullName($nextAirport);

                // Calculate transit times - add minimum layover time
                $transitDeparture = $this->addHoursToDateTime($previousArrival, 2); // Default 2 hour transit
                $transitArrival = $this->addHoursToDateTime($transitDeparture, 3); // Default 3 hour flight

                $transitFlyTime = $this->calculateDuration($transitDeparture, $transitArrival);
                $transitTime = $this->calculateDuration($previousArrival, $transitDeparture);

                $airlineName = $flightData['airline'] ?? $flightData['airline_name'] ?? null;
                $airlineCode = $flightData['airline_code'] ?? null;
                $transits[] = [
                    'airline_id' => $this->findOrCreateAirline($airlineName, $airlineCode),
                    'flight_number' => $flightData['flight_number'] ?? '',
                    'leaving_from' => $transitOrigin,
                    'going_to' => $transitDestination,
                    'departure_date_time' => $transitDeparture,
                    'arrival_date_time' => $transitArrival,
                    'total_fly_time' => $transitFlyTime,
                    'total_transit_time' => $transitTime,
                ];

                $previousArrival = $transitArrival;
            }
        }

        return $transits;
    }
    
    /**
     * Calculate total transit time for main flight
     */
    private function calculateTotalTransitTime(string $mainDeparture, string $mainArrival, array $transits): string
    {
        if (empty($transits)) {
            return '';
        }
        
        $totalMinutes = 0;
        $previousArrival = $mainDeparture;
        
        foreach ($transits as $transit) {
            $transitDeparture = $transit['departure_date_time'] ?? '';
            if (!empty($transitDeparture) && !empty($previousArrival)) {
                $dep = new \DateTime($previousArrival);
                $arr = new \DateTime($transitDeparture);
                $diff = $arr->diff($dep);
                $totalMinutes += ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            }
            $previousArrival = $transit['arrival_date_time'] ?? $previousArrival;
        }
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        return $hours . 'h ' . $minutes . 'm';
    }

    /**
     * Find existing airline by actual flight data (name or code), or create new one if not available.
     * Uses exact match, case-insensitive, normalized name (without "Airlines"/"Airways"), then creates.
     * When creating, saves airline code (IATA) when provided.
     */
    private function findOrCreateAirline(?string $airlineName, ?string $airlineCode = null): ?int
    {
        if ($airlineName === null || $airlineName === '') {
            return null;
        }

        $airlineName = trim(preg_replace('/\s+/', ' ', $airlineName));
        $originalName = $airlineName;
        $airlineCode = $airlineCode !== null && $airlineCode !== '' ? strtoupper(trim(substr((string) $airlineCode, 0, 10))) : null;

        // Do not create placeholder / unknown names
        $placeholders = ['n/a', 'na', '-', '—', 'unknown', 'null'];
        if (in_array(strtolower($airlineName), $placeholders, true) || strlen($airlineName) < 2) {
            return null;
        }

        // If it's an IATA code (2 letters), resolve to full name for lookup/display
        if (strlen($airlineName) === 2 && ctype_upper($airlineName)) {
            $fullName = $this->getAirlineFullName($airlineName);
            if ($fullName) {
                $airlineName = $fullName;
            }
            if ($airlineCode === null) {
                $airlineCode = $originalName;
            }
        }

        // 0) If we have a code, try match by code first
        if ($airlineCode !== null && $airlineCode !== '') {
            $airline = Airline::whereRaw('UPPER(TRIM(code)) = ?', [$airlineCode])->first();
            if ($airline) {
                return $airline->id;
            }
        }

        // 1) Try exact name
        $airline = Airline::where('name', $airlineName)->first();
        if ($airline) {
            return $airline->id;
        }

        // 2) Try case-insensitive
        $airline = Airline::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($airlineName)])->first();
        if ($airline) {
            return $airline->id;
        }

        // 3) Try normalized "core" name (strip common suffixes so "China Eastern" matches "China Eastern Airlines")
        $coreName = $this->normalizeAirlineNameForMatch($airlineName);
        if ($coreName !== '') {
            $like = '%' . addcslashes($coreName, '%_\\') . '%';
            $airline = Airline::where('status', 1)
                ->where(function ($q) use ($coreName, $like) {
                    $q->whereRaw('LOWER(TRIM(name)) = ?', [$coreName])
                        ->orWhereRaw("LOWER(TRIM(REPLACE(REPLACE(REPLACE(name, ' Airlines', ''), ' Airways', ''), ' Air', ''))) = ?", [$coreName])
                        ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
                })
                ->first();
            if ($airline) {
                return $airline->id;
            }
        }

        // 4) Try partial / LIKE match (DB name contains our name or vice versa)
        $airline = Airline::where('name', 'LIKE', '%' . addcslashes($airlineName, '%_\\') . '%')->first();
        if ($airline) {
            return $airline->id;
        }
        $airline = Airline::where('name', 'LIKE', '%' . addcslashes(substr($airlineName, 0, 15), '%_\\') . '%')->first();
        if ($airline) {
            return $airline->id;
        }

        // 5) Not found: create new airline so actual flight data is always stored (with code when available)
        try {
            $newAirline = new Airline();
            $newAirline->name = $airlineName;
            if ($airlineCode !== null && $airlineCode !== '') {
                $newAirline->code = $airlineCode;
            }
            $newAirline->status = 1;
            $newAirline->created_by = AuthFacade::id();
            $newAirline->save();

            Log::info('Created new airline for flight data', [
                'airline_id' => $newAirline->id,
                'airline_name' => $airlineName,
                'airline_code' => $airlineCode,
                'original_input' => $originalName,
            ]);
            return $newAirline->id;
        } catch (\Exception $e) {
            Log::error('Failed to create airline', [
                'airline_name' => $airlineName,
                'airline_code' => $airlineCode,
                'original_input' => $originalName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Normalize airline name for matching (lowercase, strip common suffixes).
     */
    private function normalizeAirlineNameForMatch(string $name): string
    {
        $name = trim(strtolower($name));
        if ($name === '') {
            return '';
        }
        $suffixes = [' airlines', ' airline', ' airways', ' air ways', ' air line', ' air lines', ' aviation'];
        foreach ($suffixes as $suffix) {
            if (str_ends_with($name, $suffix)) {
                $name = trim(substr($name, 0, -strlen($suffix)));
                break;
            }
        }
        return preg_replace('/\s+/', ' ', $name);
    }
    
    /**
     * Get airline full name from IATA code (reverse lookup)
     */
    private function getAirlineFullName(string $iataCode): ?string
    {
        // Reverse mapping from IATA code to full airline name
        // This is the reverse of the mapping in TravelpayoutsService
        $iataToNameMap = [
            // North America - USA
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
            
            // North America - Canada
            'AC' => 'Air Canada',
            'WS' => 'WestJet',
            'TS' => 'Air Transat',
            'PD' => 'Porter Airlines',
            'F8' => 'Flair Airlines',
            
            // North America - Mexico
            'AM' => 'Aeromexico',
            'Y4' => 'Volaris',
            '4O' => 'Interjet',
            'VB' => 'VivaAerobus',
            
            // Europe - UK & Ireland
            'BA' => 'British Airways',
            'VS' => 'Virgin Atlantic',
            'U2' => 'EasyJet',
            'FR' => 'Ryanair',
            'EI' => 'Aer Lingus',
            'LS' => 'Jet2',
            'BY' => 'TUI Airways',
            'MT' => 'Thomas Cook Airlines',
            
            // Europe - Germany
            'LH' => 'Lufthansa',
            'EW' => 'Eurowings',
            'DE' => 'Condor',
            '4U' => 'Germanwings',
            
            // Europe - France
            'AF' => 'Air France',
            'TO' => 'Transavia France',
            'SS' => 'Corsair International',
            'BF' => 'French Bee',
            
            // Europe - Netherlands
            'KL' => 'KLM Royal Dutch Airlines',
            'HV' => 'Transavia',
            
            // Europe - Spain
            'IB' => 'Iberia',
            'VY' => 'Vueling',
            'UX' => 'Air Europa',
            'V7' => 'Volotea',
            
            // Europe - Italy
            'AZ' => 'ITA Airways',
            'NO' => 'Neos',
            
            // Europe - Switzerland
            'LX' => 'Swiss International Air Lines',
            'WK' => 'Edelweiss Air',
            
            // Europe - Austria
            'OS' => 'Austrian Airlines',
            
            // Europe - Scandinavia
            'SK' => 'Scandinavian Airlines',
            'AY' => 'Finnair',
            'DY' => 'Norwegian Air Shuttle',
            'FI' => 'Icelandair',
            'W6' => 'Wizz Air',
            
            // Europe - Eastern Europe
            'SU' => 'Aeroflot Russian Airlines',
            'LO' => 'LOT Polish Airlines',
            'OK' => 'Czech Airlines',
            'RO' => 'TAROM',
            'FB' => 'Bulgaria Air',
            'OU' => 'Croatia Airlines',
            'JP' => 'Adria Airways',
            'JU' => 'Air Serbia',
            
            // Europe - Turkey
            'TK' => 'Turkish Airlines',
            'PC' => 'Pegasus Airlines',
            'XQ' => 'SunExpress',
            
            // Europe - Greece
            'A3' => 'Aegean Airlines',
            'OA' => 'Olympic Air',
            
            // Europe - Portugal
            'TP' => 'TAP Air Portugal',
            
            // Europe - Belgium
            'SN' => 'Brussels Airlines',
            
            // Middle East
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
            
            // Asia - Japan
            'JL' => 'Japan Airlines',
            'NH' => 'All Nippon Airways',
            'NQ' => 'Air Japan',
            'GK' => 'Jetstar Japan',
            'MM' => 'Peach Aviation',
            'JW' => 'Vanilla Air',
            '7G' => 'StarFlyer',
            
            // Asia - China
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
            
            // Asia - South Korea
            'KE' => 'Korean Air',
            'OZ' => 'Asiana Airlines',
            '7C' => 'Jeju Air',
            'LJ' => 'Jin Air',
            'TW' => 'T\'way Air',
            
            // Asia - Southeast Asia
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
            
            // Asia - South Asia
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
            
            // Asia - Central Asia
            'HY' => 'Uzbekistan Airways',
            'KC' => 'Air Astana',
            'T5' => 'Turkmenistan Airlines',
            
            // Asia - Taiwan & Hong Kong
            'BR' => 'EVA Air',
            'CI' => 'China Airlines',
            'AE' => 'Mandarin Airlines',
            'IT' => 'Tigerair Taiwan',
            'CX' => 'Cathay Pacific',
            'HX' => 'Hong Kong Airlines',
            'UO' => 'HK Express',
            
            // Oceania
            'QF' => 'Qantas',
            'JQ' => 'Jetstar',
            'VA' => 'Virgin Australia',
            'ZL' => 'Regional Express',
            'NZ' => 'Air New Zealand',
            'FJ' => 'Fiji Airways',
            'SB' => 'Air Calin',
            'PX' => 'Papua New Guinea Airlines',
            
            // Africa
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
            
            // South America
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
        return $iataToNameMap[$code] ?? null;
    }

    /**
     * Get airport full name from code
     */
    private function getAirportFullName(string $airportCode): string
    {
        if (empty($airportCode)) {
            return '';
        }

        $originalInput = $airportCode;
        
        // If already contains full name format (has parentheses with code), return as is
        if (preg_match('/.+\(([A-Z]{3})\)/', $airportCode, $matches)) {
            // Already has full name format, return as is
            return $airportCode;
        }

        // Extract airport code - check multiple patterns
        $code = strtoupper(trim($airportCode));
        
        // Pattern 1: Extract from parentheses: "Name (ABC)"
        if (preg_match('/\(([A-Z]{3})\)/', $code, $matches)) {
            $code = $matches[1];
        } 
        // Pattern 2: Extract 3-letter code at start: "ABC" or "ABC - Name"
        elseif (preg_match('/^([A-Z]{3})(\s|$|-)/', $code, $matches)) {
            $code = $matches[1];
        }
        // Pattern 3: If it's exactly 3 uppercase letters, use it
        elseif (preg_match('/^[A-Z]{3}$/', $code)) {
            // Already a code, use it
        }
        // Pattern 4: Try to find 3-letter code anywhere in the string
        elseif (preg_match('/([A-Z]{3})/', $code, $matches)) {
            $code = $matches[1];
        }
        // If no code found, return original (might already be a name)
        else {
            // If it looks like a name (has spaces, longer than 3 chars), return as is
            if (strlen($code) > 3 && strpos($code, ' ') !== false) {
                return $airportCode; // Return original, might already be a full name
            }
            // Otherwise, try to use as code
        }

        // Now try to get full name for the extracted code
        // Try to get from service
        try {
            $airports = $this->travelpayouts->getAirportsList();
            $airport = collect($airports)->firstWhere('code', $code);

            if ($airport && isset($airport['name']) && !empty($airport['name'])) {
                return $airport['name'] . ' (' . $airport['code'] . ')';
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get airport name from service: ' . $e->getMessage(), ['code' => $code]);
        }

        // Fallback to common airports
        try {
            $commonAirports = $this->travelpayouts->getCommonAirports();
            $airport = collect($commonAirports)->firstWhere('code', $code);

            if ($airport && isset($airport['name']) && !empty($airport['name'])) {
                return $airport['name'] . ' (' . $airport['code'] . ')';
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get airport name from common airports: ' . $e->getMessage(), ['code' => $code]);
        }

        // If we couldn't find full name, but have a valid code, return code
        // Otherwise return original input (might be a name already)
        if (preg_match('/^[A-Z]{3}$/', $code)) {
            Log::warning('Airport full name not found, using code', ['code' => $code, 'original_input' => $originalInput]);
            return $code;
        }
        
        return $originalInput;
    }

    /**
     * Format date time
     */
    private function formatDateTime(?string $dateStr): string
    {
        if (empty($dateStr)) {
            return '';
        }

        try {
            $date = new \DateTime($dateStr);
            return $date->format('Y-m-d H:i');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Calculate duration between two dates
     */
    private function calculateDuration(string $start, string $end): string
    {
        if (empty($start) || empty($end)) {
            return '';
        }

        try {
            $startDate = new \DateTime($start);
            $endDate = new \DateTime($end);
            $diff = $startDate->diff($endDate);

            $hours = $diff->h + ($diff->days * 24);
            $minutes = $diff->i;

            return $hours . 'h ' . $minutes . 'm';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Add hours to datetime
     */
    private function addHoursToDateTime(string $dateTime, int $hours): string
    {
        try {
            $date = new \DateTime($dateTime);
            $date->modify('+' . $hours . ' hours');
            return $date->format('Y-m-d H:i');
        } catch (\Exception $e) {
            return $dateTime;
        }
    }

    /**
     * Map flight type to trip type
     */
    private function mapFlightTypeToTripType(string $flightType): string
    {
        $map = [
            'one_way' => 'One Way',
            'round_trip' => 'Round Trip',
            'multi_city' => 'Multi City',
        ];

        return $map[$flightType] ?? 'One Way';
    }

    /**
     * Map class to ticket type
     */
    private function mapClassToTicketType(string $class): string
    {
        $map = [
            'economy' => 'Economy',
            'business' => 'Business Class',
            'first' => 'First Class',
        ];

        return $map[$class] ?? 'Economy';
    }

    /**
     * Get flight calendar prices
     */
    public function getFlightCalendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin' => 'required|string|size:3',
            'destination' => 'required|string|size:3',
            'currency' => 'nullable|string|size:3',
        ]);

        try {
            $calendar = $this->travelpayouts->getPricesCalendar(
                $validated['origin'],
                $validated['destination'],
                ['currency' => $validated['currency'] ?? 'USD']
            );
            
            return response()->json([
                'success' => true,
                'data' => $calendar,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get popular destinations
     */
    public function getPopularDestinations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin' => 'required|string|size:3',
            'currency' => 'nullable|string|size:3',
        ]);

        try {
            $destinations = $this->travelpayouts->getPopularDestinations(
                $validated['origin'],
                ['currency' => $validated['currency'] ?? 'USD']
            );
            
            return response()->json([
                'success' => true,
                'data' => $destinations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search hotels
     */
    public function searchHotels(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
            'lang' => 'nullable|string|size:2',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $hotels = $this->travelpayouts->searchHotels($validated);
            
            return response()->json([
                'success' => true,
                'data' => $hotels,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get hotel prices
     */
    public function getHotelPrices(Request $request, int $hotelId): JsonResponse
    {
        $validated = $request->validate([
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'nullable|integer|min:1|max:10',
            'children' => 'nullable|integer|min:0|max:10',
            'currency' => 'nullable|string|size:3',
        ]);

        try {
            $prices = $this->travelpayouts->getHotelPrices($hotelId, $validated);
            
            return response()->json([
                'success' => true,
                'data' => $prices,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get booking URL (legacy method - builds Aviasales search URL)
     */
    public function getBookingUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin' => 'required|string|size:3',
            'destination' => 'required|string|size:3',
            'departure_at' => 'required|date',
            'return_at' => 'nullable|date',
            'adults' => 'nullable|integer|min:1',
        ]);

        try {
            $url = $this->travelpayouts->buildSearchUrl($validated);
            
            return response()->json([
                'success' => true,
                'booking_url' => $url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get booking link for a flight proposal
     * Must be called only when user clicks "Buy" button
     */
    public function getBookingLink(Request $request): JsonResponse
    {
        if (!hasPermission('ticket.search.form')) {
            return response()->json([
                'success' => false,
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ], 403);
        }

        $validated = $request->validate([
            'results_url' => 'required|string|url',
            'search_id' => 'required|string',
            'proposal_id' => 'required|string',
        ]);

        try {
            $userIp = $request->ip();
            // Use configured website URL from config
            $websiteUrl = config('services.travelpayouts.website_url', config('app.url'));
            $realHost = parse_url($websiteUrl, PHP_URL_HOST) ?: $request->getHost();

            $bookingLink = $this->travelpayouts->getBookingLink(
                $validated['results_url'],
                $validated['search_id'],
                $validated['proposal_id'],
                $userIp,
                $realHost
            );
            
            return response()->json([
                'success' => true,
                'data' => $bookingLink,
                'message' => 'Booking link retrieved successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Get booking link error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}