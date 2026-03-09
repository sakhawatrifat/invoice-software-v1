<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * FlightAPI (flightapi.io) integration.
 * Docs: https://docs.flightapi.io/flight-price-api/
 * - One-way: https://docs.flightapi.io/flight-price-api/oneway-trip-api
 * - Round-trip: https://docs.flightapi.io/flight-price-api/round-trip-api
 * - Multi-trip: https://docs.flightapi.io/flight-price-api/multi-trip-api
 * - Flight Tracking: https://docs.flightapi.io/flight-tracking-api
 */
class FlightApiService
{
    private string $apiKey;
    private string $baseUrl;
    private string $region;

    public function __construct()
    {
        $this->apiKey = config('services.flightapi.api_key', '');
        $this->baseUrl = rtrim(config('services.flightapi.base_url', 'https://api.flightapi.io'), '/');
        $this->region = config('services.flightapi.region', 'US');
    }

    /**
     * Check if FlightAPI is configured and should be used.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Search flights (one-way, round-trip, or multi-trip) and return data in app format.
     */
    public function searchCheapestFlights(array $params): array
    {
        $flightType = $params['flight_type'] ?? 'one_way';

        if ($flightType === 'one_way') {
            $raw = $this->onewayTrip($params);
        } elseif ($flightType === 'round_trip') {
            $raw = $this->roundTrip($params);
        } elseif ($flightType === 'multi_city' && !empty($params['flights'])) {
            $raw = $this->multiTrip($params);
        } else {
            $raw = $this->onewayTrip($params);
        }

        return $this->transformResponse($raw, $params);
    }

    /**
     * One-way trip API.
     * GET https://api.flightapi.io/onewaytrip/{api_key}/{dep}/{arr}/{date}/{adults}/{children}/{infants}/{cabin}/{currency}
     */
    public function onewayTrip(array $params): array
    {
        $dep = $this->extractAirportCode($params['origin'] ?? '');
        $arr = $this->extractAirportCode($params['destination'] ?? '');
        $date = $this->formatDate($params['departure_at'] ?? '');
        $adults = (string) ($params['adults'] ?? $params['passenger'] ?? 1);
        $children = (string) ($params['children'] ?? 0);
        $infants = (string) ($params['infants'] ?? 0);
        $cabin = $this->mapCabinClass($params['class'] ?? 'economy');
        $currency = strtoupper($params['currency'] ?? 'JPY');

        if (empty($dep) || empty($arr) || empty($date)) {
            throw new Exception('Origin, destination and departure date are required.');
        }

        $path = "/onewaytrip/{$this->apiKey}/{$dep}/{$arr}/{$date}/{$adults}/{$children}/{$infants}/{$cabin}/{$currency}";
        $url = $this->baseUrl . $path;
        $query = [];
        if (!empty($this->region)) {
            $query['region'] = $this->region;
        }

        return $this->get($url, $query);
    }

    /**
     * Round-trip API.
     * GET https://api.flightapi.io/roundtrip/{api_key}/{dep}/{arr}/{dep_date}/{arr_date}/{adults}/{children}/{infants}/{cabin}/{currency}
     */
    public function roundTrip(array $params): array
    {
        $dep = $this->extractAirportCode($params['origin'] ?? '');
        $arr = $this->extractAirportCode($params['destination'] ?? '');
        $depDate = $this->formatDate($params['departure_at'] ?? '');
        $arrDate = $this->formatDate($params['return_at'] ?? '');
        $adults = (string) ($params['adults'] ?? $params['passenger'] ?? 1);
        $children = (string) ($params['children'] ?? 0);
        $infants = (string) ($params['infants'] ?? 0);
        $cabin = $this->mapCabinClass($params['class'] ?? 'economy');
        $currency = strtoupper($params['currency'] ?? 'JPY');

        if (empty($dep) || empty($arr) || empty($depDate) || empty($arrDate)) {
            throw new Exception('Origin, destination, departure date and return date are required.');
        }

        $path = "/roundtrip/{$this->apiKey}/{$dep}/{$arr}/{$depDate}/{$arrDate}/{$adults}/{$children}/{$infants}/{$cabin}/{$currency}";
        $url = $this->baseUrl . $path;
        $query = [];
        if (!empty($this->region)) {
            $query['region'] = $this->region;
        }

        return $this->get($url, $query);
    }

    /**
     * Multi-trip API (3 to 5 flights).
     * GET https://api.flightapi.io/multitrip/{api_key}?trips=3&arp1=...&arp2=...&date1=...&adults=...&cabinclass=...&currency=...
     */
    public function multiTrip(array $params): array
    {
        $flights = $params['flights'] ?? [];
        $tripsCount = count($flights);
        if ($tripsCount < 3 || $tripsCount > 5) {
            throw new Exception('Multi-trip requires 3 to 5 flights.');
        }

        $adults = (string) ($params['adults'] ?? $params['passenger'] ?? 1);
        $children = (string) ($params['children'] ?? 0);
        $infants = (string) ($params['infants'] ?? 0);
        $cabin = $this->mapCabinClass($params['class'] ?? 'economy');
        $currency = strtoupper($params['currency'] ?? 'JPY');

        $query = [
            'trips' => (string) $tripsCount,
            'adults' => $adults,
            'children' => $children,
            'infants' => $infants,
            'cabinclass' => $cabin,
            'currency' => $currency,
        ];

        foreach ($flights as $i => $flight) {
            $n = $i + 1;
            $query["arp" . (2 * $n - 1)] = $this->extractAirportCode($flight['origin'] ?? '');
            $query["arp" . (2 * $n)] = $this->extractAirportCode($flight['destination'] ?? '');
            $query["date{$n}"] = $this->formatDate($flight['departure_at'] ?? '');
        }

        $url = $this->baseUrl . '/multitrip/' . $this->apiKey;
        return $this->get($url, $query);
    }

    /**
     * GET request and decode JSON.
     * Uses a longer timeout (120s) so slow flight APIs can respond; connection timeout 20s.
     *
     * @param array<string, mixed> $logContext Optional context for error logs (e.g. num, name, date for tracking). API key is never logged.
     */
    private function get(string $url, array $query = [], array $logContext = []): array
    {
        $response = Http::connectTimeout(20)->timeout(120)->get($url, $query);

        if ($response->failed()) {
            $body = $response->body();
            $message = $this->parseFlightApiErrorMessage($body, $response->status());
            $logUrl = $this->redactApiKeyFromUrl($url);
            Log::error('FlightAPI request failed', array_merge(
                ['url' => $logUrl, 'status' => $response->status(), 'body' => $body],
                $logContext
            ));
            throw new Exception($message);
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new Exception('FlightAPI returned invalid response.');
        }
        return $data;
    }

    /**
     * Blocklist of 3-letter tokens that are not IATA airport codes (e.g. from "leaving_from" text).
     * Sending these as depap can cause FlightAPI to return 400.
     */
    private function isLikelyNonAirportCode(string $code): bool
    {
        $bad = [
            'APT', 'TBA', 'NA', 'N/A', 'XXX', 'TBD', 'TBH', 'TBC', 'NIL',
            'INT',  // e.g. from "International" in leaving_from
            'TER', 'ARR', 'DEP', 'DOM',  // terminal/arrival/departure/domestic
        ];
        return in_array($code, $bad, true);
    }

    /**
     * Extract IATA airport code (3-letter) from place/airport text stored in DB.
     * 1) Prefers code in parentheses, e.g. (KIX), (DAC).
     * 2) Then matches against config/airports_iata.php by airport name (e.g. "hazrat shahjalal international airport" → DAC).
     * 3) Fallback: standalone 3-letter word not in blocklist.
     *
     * @param string $place e.g. "Kansai Intl Apt (KIX) T-1", "Beijing Capital Int Apt (PEK) T-3", "hazrat shahjalal international airport"
     * @return string|null 3-letter IATA code (e.g. KIX, PEK, DAC) or null if none found/valid
     */
    public function extractAirportIataFromPlace(string $place): ?string
    {
        $place = trim(preg_replace('/\s+/', ' ', (string) $place));
        if ($place === '') {
            return null;
        }
        // 1) Prefer code in parentheses: (KIX), (PEK), (DAC) – standard IATA format in DB
        if (preg_match('/\(([A-Za-z]{3})\)/', $place, $m)) {
            $code = strtoupper($m[1]);
            if (!$this->isLikelyNonAirportCode($code)) {
                return $code;
            }
        }
        // 2) Match by airport name from config (longest match first) – e.g. "hazrat shahjalal international airport" → DAC
        $placeLower = mb_strtolower($place);
        $nameToIata = $this->getAirportNameToIataMap();
        foreach ($nameToIata as $name => $iata) {
            if ($name === '' || strlen($iata) !== 3) {
                continue;
            }
            if (str_contains($placeLower, $name) || str_contains($name, $placeLower)) {
                return strtoupper($iata);
            }
        }
        // 3) Standalone 3-letter word (all letters) not in blocklist – e.g. "DAC" or "KIX airport"
        if (preg_match_all('/\b([A-Za-z]{3})\b/', $place, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $code = strtoupper($m[1]);
                if (!$this->isLikelyNonAirportCode($code)) {
                    return $code;
                }
            }
        }
        return null;
    }

    /**
     * Build map of airport name (lowercase) => IATA code, sorted by name length descending so longest match wins.
     *
     * @return array<string, string>
     */
    private function getAirportNameToIataMap(): array
    {
        $path = base_path('config/airports_iata.php');
        if (!is_file($path)) {
            return [];
        }
        $list = require $path;
        if (!is_array($list)) {
            return [];
        }
        $flat = [];
        foreach ($list as $entry) {
            $iata = isset($entry['iata']) ? trim((string) $entry['iata']) : '';
            $names = $entry['names'] ?? [];
            if ($iata === '' || !is_array($names)) {
                continue;
            }
            foreach ($names as $name) {
                $name = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $name)));
                if ($name !== '') {
                    $flat[$name] = $iata;
                }
            }
        }
        uksort($flat, static fn ($a, $b) => strlen($b) <=> strlen($a));
        return $flat;
    }

    /**
     * Redact API key from URL for safe logging (e.g. /airline/KEY -> /airline/***).
     */
    private function redactApiKeyFromUrl(string $url): string
    {
        $prefix = $this->baseUrl . '/airline/';
        if (str_starts_with($url, $prefix)) {
            return $prefix . '***';
        }
        return $url;
    }

    /**
     * Transform FlightAPI response to app format.
     * Returns [ 'data' => [ flight, ... ] ] so controller and frontend get data.data as the list.
     */
    private function transformResponse(array $apiResponse, array $searchParams): array
    {
        $flightList = [];

        // Multi-trip API returns different structure (legs, fares, trips, airlines, airports)
        if (isset($apiResponse['fares']) && is_array($apiResponse['fares'])) {
            $flightList = $this->transformMultiTripResponse($apiResponse, $searchParams);
            return [ 'data' => $flightList ];
        }

        // One-way / Round-trip: itineraries, legs, segments, places, carriers
        $itineraries = $apiResponse['itineraries'] ?? [];
        $legs = $this->indexById($apiResponse['legs'] ?? []);
        $segments = $this->indexById($apiResponse['segments'] ?? []);
        $places = $this->buildPlaceCodeMap($apiResponse['places'] ?? []);
        $placeDisplay = $this->buildPlaceDisplayMap($apiResponse['places'] ?? []);
        $carriers = $this->buildCarrierNameMap($apiResponse['carriers'] ?? []);
        $carrierCodes = $this->buildCarrierCodeMap($apiResponse['carriers'] ?? []);

        $origin = $searchParams['origin'] ?? null;
        $destination = $searchParams['destination'] ?? null;
        if (is_string($origin)) {
            $origin = $this->extractAirportCode($origin);
        }
        if (is_string($destination)) {
            $destination = $this->extractAirportCode($destination);
        }

        foreach ($itineraries as $itin) {
            $firstPrice = null;
            $pricingOptions = $itin['pricing_options'] ?? [];
            if (!empty($pricingOptions)) {
                $opt = $pricingOptions[0];
                $firstPrice = $opt['price']['amount'] ?? null;
            }
            $legIds = $itin['leg_ids'] ?? [];
            $firstLeg = null;
            $lastLeg = null;
            $airlineNames = [];
            $flightNumbers = [];
            $allSegmentIds = [];
            foreach ($legIds as $legId) {
                $leg = $legs[$legId] ?? null;
                if (!$leg) {
                    continue;
                }
                if ($firstLeg === null) {
                    $firstLeg = $leg;
                }
                $lastLeg = $leg;
                $segmentIds = $leg['segment_ids'] ?? [];
                foreach ($segmentIds as $segId) {
                    $allSegmentIds[] = $segId;
                    $seg = $segments[$segId] ?? null;
                    if ($seg) {
                        $carrierId = $seg['marketing_carrier_id'] ?? null;
                        if ($carrierId !== null && isset($carriers[$carrierId])) {
                            $airlineNames[$carriers[$carrierId]] = true;
                        }
                        $fn = $seg['marketing_flight_number'] ?? null;
                        if ($fn !== null) {
                            $flightNumbers[] = $fn;
                        }
                    }
                }
            }
            if ($firstLeg === null) {
                continue;
            }

            $departure = $firstLeg['departure'] ?? '';
            $arrival = $lastLeg['arrival'] ?? '';
            $departureAt = $this->formatDateTimeForDisplay($departure);
            $arrivalAt = $this->formatDateTimeForDisplay($arrival);

            // Total fly time: sum of segment durations from API (timezone-correct elapsed flight time)
            $totalFlyTimeMinutes = 0;
            foreach ($legIds as $legId) {
                $leg = $legs[$legId] ?? null;
                if (!$leg) {
                    continue;
                }
                foreach ($leg['segment_ids'] ?? [] as $segId) {
                    $seg = $segments[$segId] ?? null;
                    if ($seg !== null) {
                        $totalFlyTimeMinutes += (int) ($seg['duration'] ?? 0);
                    }
                }
            }

            // Total itinerary duration: prefer sum of leg durations from API (timezone-correct)
            $totalDurationFromApi = 0;
            foreach ($legIds as $legId) {
                $leg = $legs[$legId] ?? null;
                if ($leg !== null && isset($leg['duration'])) {
                    $totalDurationFromApi += (int) $leg['duration'];
                }
            }

            $durationMinutes = $totalDurationFromApi > 0 ? $totalDurationFromApi : 0;
            if ($durationMinutes === 0 && !empty($departure) && !empty($arrival)) {
                try {
                    $depDt = new \DateTime($departure);
                    $arrDt = new \DateTime($arrival);
                    $durationMinutes = (int) (($arrDt->getTimestamp() - $depDt->getTimestamp()) / 60);
                } catch (\Exception $e) {
                    // ignore
                }
            }

            // Total transit (layover) time = total itinerary duration - total fly time
            $totalTransitMinutes = ($durationMinutes > 0 && $totalFlyTimeMinutes >= 0)
                ? max(0, $durationMinutes - $totalFlyTimeMinutes)
                : 0;

            $stops = max(0, count($allSegmentIds) - count($legIds));
            $segmentList = $this->buildSegmentList($legIds, $legs, $segments, $places, $carriers, $placeDisplay, $carrierCodes);
            $transitAirports = $this->transitAirportsFromSegments($segmentList);

            $firstOriginPlaceId = $firstLeg['origin_place_id'] ?? null;
            $lastDestPlaceId = $lastLeg['destination_place_id'] ?? null;
            $originCode = $origin ?? ($places[$firstOriginPlaceId] ?? '');
            $destCode = $destination ?? ($places[$lastDestPlaceId] ?? '');
            $originDisplay = $placeDisplay[$firstOriginPlaceId] ?? $originCode;
            $destinationDisplay = $placeDisplay[$lastDestPlaceId] ?? $destCode;

            $flight = [
                'airline' => implode(', ', array_keys($airlineNames)) ?: 'N/A',
                'flight_number' => implode(', ', $flightNumbers) ?: '',
                'origin' => $originCode,
                'origin_iata' => $originCode,
                'origin_display' => $originDisplay,
                'destination' => $destCode,
                'destination_iata' => $destCode,
                'destination_display' => $destinationDisplay,
                'departure_at' => $departureAt,
                'arrival_at' => $arrivalAt,
                'return_at' => $arrivalAt,
                'departure_time' => $this->formatTimeOnly($departure),
                'arrival_time' => $this->formatArrivalTimeWithDayOffset($departure, $arrival, $durationMinutes),
                'duration_formatted' => $this->formatDurationMinutes($durationMinutes),
                'duration_minutes' => $durationMinutes,
                'total_fly_time_minutes' => $totalFlyTimeMinutes,
                'total_fly_time_formatted' => $this->formatDurationMinutes($totalFlyTimeMinutes),
                'total_transit_minutes' => $totalTransitMinutes,
                'total_transit_formatted' => $this->formatDurationMinutes($totalTransitMinutes),
                'stops' => $stops,
                'transit_airport_codes' => $transitAirports['codes'],
                'transit_airports_display' => $transitAirports['display'],
                'carrier_names' => array_keys($airlineNames),
                'price' => $firstPrice !== null ? round((float) $firstPrice, 2) : null,
                'currency' => $searchParams['currency'] ?? 'JPY',
                'segments' => $segmentList,
                'search_origin' => $searchParams['origin'] ?? null,
                'search_destination' => $searchParams['destination'] ?? null,
                'search_departure_at' => $searchParams['departure_at'] ?? null,
                'search_return_at' => $searchParams['return_at'] ?? null,
                'search_flight_type' => $searchParams['flight_type'] ?? 'one_way',
                'search_class' => $searchParams['class'] ?? 'economy',
                'search_passenger' => $searchParams['passenger'] ?? $searchParams['adults'] ?? 1,
                '_raw_data' => $itin,
            ];

            $flightList[] = $flight;
        }

        return [ 'data' => $flightList ];
    }

    /**
     * Transform multi-trip API response (legs, fares, airlines, airports).
     */
    private function transformMultiTripResponse(array $apiResponse, array $searchParams): array
    {
        $result = [];
        $fares = $apiResponse['fares'] ?? [];
        $legs = $apiResponse['legs'] ?? [];
        $airlines = $this->indexAirlinesByCode($apiResponse['airlines'] ?? []);
        $airports = $this->indexAirportsByCode($apiResponse['airports'] ?? []);

        foreach (array_slice($fares, 0, 50) as $fare) {
            $price = $fare['price']['totalAmount'] ?? $fare['price']['amount'] ?? null;
            $tripId = $fare['tripId'] ?? '';
            $legIds = [];
            foreach ($apiResponse['trips'] ?? [] as $trip) {
                if (($trip['id'] ?? '') === $tripId) {
                    $legIds = $trip['legIds'] ?? [];
                    break;
                }
            }
            $firstLeg = null;
            $lastLeg = null;
            $airlineCodes = [];
            foreach ($legs as $leg) {
                if (in_array($leg['id'] ?? '', $legIds)) {
                    if ($firstLeg === null) {
                        $firstLeg = $leg;
                    }
                    $lastLeg = $leg;
                    foreach ($leg['airlineCodes'] ?? [] as $code) {
                        $airlineCodes[$code] = $airlines[$code] ?? $code;
                    }
                }
            }
            if ($firstLeg === null || $lastLeg === null) {
                continue;
            }

            $firstDep = $firstLeg['departureDateTime'] ?? $firstLeg['departure'] ?? '';
            $lastArr = $lastLeg['arrivalDateTime'] ?? $lastLeg['arrival'] ?? '';
            $departureAt = $this->formatDateTimeForDisplay($firstDep);
            $arrivalAt = $this->formatDateTimeForDisplay($lastArr);

            // Total fly time from segment durations (API, timezone-correct)
            $totalFlyTimeMinutes = 0;
            foreach ($legs as $leg) {
                if (!in_array($leg['id'] ?? '', $legIds)) {
                    continue;
                }
                foreach ($leg['segments'] ?? [] as $seg) {
                    $totalFlyTimeMinutes += (int) ($seg['durationMinutes'] ?? 0);
                }
            }

            $durationMinutes = 0;
            if (!empty($firstDep) && !empty($lastArr)) {
                try {
                    $depDt = new \DateTime($firstDep);
                    $arrDt = new \DateTime($lastArr);
                    $durationMinutes = (int) (($arrDt->getTimestamp() - $depDt->getTimestamp()) / 60);
                } catch (\Exception $e) {
                    // ignore
                }
            }
            $totalTransitMinutes = ($durationMinutes > 0 && $totalFlyTimeMinutes >= 0)
                ? max(0, $durationMinutes - $totalFlyTimeMinutes)
                : 0;

            $segmentList = $this->buildMultiTripSegmentList($legs, $legIds, $airlines, $airports);
            $stops = 0;
            foreach ($legs as $leg) {
                if (in_array($leg['id'] ?? '', $legIds)) {
                    $stops += max(0, count($leg['segments'] ?? []) - 1);
                }
            }
            $transitAirports = $this->transitAirportsFromSegments($segmentList);

            $firstOriginCode = $firstLeg['departureAirportCode'] ?? '';
            $lastDestCode = $lastLeg['arrivalAirportCode'] ?? '';
            $firstOriginDisplay = isset($airports[$firstOriginCode]) && $airports[$firstOriginCode] !== $firstOriginCode
                ? $airports[$firstOriginCode] . ' (' . $firstOriginCode . ')' : $firstOriginCode;
            $lastDestDisplay = isset($airports[$lastDestCode]) && $airports[$lastDestCode] !== $lastDestCode
                ? $airports[$lastDestCode] . ' (' . $lastDestCode . ')' : $lastDestCode;

            $result[] = [
                'airline' => implode(', ', array_unique(array_values($airlineCodes))) ?: 'N/A',
                'flight_number' => '',
                'origin' => $firstOriginCode,
                'origin_iata' => $firstOriginCode,
                'origin_display' => $firstOriginDisplay,
                'destination' => $lastDestCode,
                'destination_iata' => $lastDestCode,
                'destination_display' => $lastDestDisplay,
                'departure_at' => $departureAt,
                'arrival_at' => $arrivalAt,
                'return_at' => $arrivalAt,
                'departure_time' => $this->formatTimeOnly($firstDep),
                'arrival_time' => $this->formatArrivalTimeWithDayOffset($firstDep, $lastArr, $durationMinutes),
                'duration_formatted' => $this->formatDurationMinutes($durationMinutes),
                'duration_minutes' => $durationMinutes,
                'total_fly_time_minutes' => $totalFlyTimeMinutes,
                'total_fly_time_formatted' => $this->formatDurationMinutes($totalFlyTimeMinutes),
                'total_transit_minutes' => $totalTransitMinutes,
                'total_transit_formatted' => $this->formatDurationMinutes($totalTransitMinutes),
                'stops' => $stops,
                'transit_airport_codes' => $transitAirports['codes'],
                'transit_airports_display' => $transitAirports['display'],
                'carrier_names' => array_values(array_unique($airlineCodes)),
                'price' => $price !== null ? round((float) $price, 2) : null,
                'currency' => $fare['price']['currencyCode'] ?? ($searchParams['currency'] ?? 'JPY'),
                'segments' => $segmentList,
                'search_origin' => $searchParams['origin'] ?? null,
                'search_destination' => $searchParams['destination'] ?? null,
                'search_departure_at' => $searchParams['departure_at'] ?? null,
                'search_return_at' => $searchParams['return_at'] ?? null,
                'search_flight_type' => 'multi_city',
                'search_class' => $searchParams['class'] ?? 'economy',
                'search_passenger' => $searchParams['passenger'] ?? $searchParams['adults'] ?? 1,
                '_raw_data' => $fare,
            ];
        }

        return $result;
    }

    /**
     * Build segment list for multi-trip API response (legs have segments with airlineCode, designatorCode, etc.).
     * @param array $airports code => name (optional, for origin_display/destination_display)
     */
    private function buildMultiTripSegmentList(array $legs, array $legIds, array $airlines, array $airports = []): array
    {
        $list = [];
        foreach ($legs as $leg) {
            if (!in_array($leg['id'] ?? '', $legIds)) {
                continue;
            }
            foreach ($leg['segments'] ?? [] as $seg) {
                $dep = $seg['departureDateTime'] ?? $seg['departure'] ?? '';
                $arr = $seg['arrivalDateTime'] ?? $seg['arrival'] ?? '';
                $durationMinutes = (int) ($seg['durationMinutes'] ?? 0);
                $airlineCode = $seg['airlineCode'] ?? '';
                $airlineName = $airlines[$airlineCode] ?? $airlineCode ?: 'N/A';
                $designator = $seg['designatorCode'] ?? '';
                $depCode = $seg['departureAirportCode'] ?? '';
                $arrCode = $seg['arrivalAirportCode'] ?? '';
                $depDisplay = ($airports !== [] && isset($airports[$depCode]) && $airports[$depCode] !== $depCode)
                    ? $airports[$depCode] . ' (' . $depCode . ')' : $depCode;
                $arrDisplay = ($airports !== [] && isset($airports[$arrCode]) && $airports[$arrCode] !== $arrCode)
                    ? $airports[$arrCode] . ' (' . $arrCode . ')' : $arrCode;
                $route = $depCode . ' → ' . $arrCode;
                $routeDisplay = $depDisplay . ' → ' . $arrDisplay;
                $timeStr = $this->formatTimeOnly($dep) . ' - ' . $this->formatArrivalTimeWithDayOffset($dep, $arr, $durationMinutes);
                $list[] = [
                    'airline' => $airlineName,
                    'airline_code' => $airlineCode,
                    'flight_number' => $designator,
                    'route' => $route,
                    'route_display' => $routeDisplay,
                    'time' => $timeStr,
                    'duration' => $this->formatDurationMinutes($durationMinutes),
                    'departure_at' => $this->formatDateTimeForDisplay($dep),
                    'arrival_at' => $this->formatDateTimeForDisplay($arr),
                    'origin_iata' => $depCode,
                    'destination_iata' => $arrCode,
                    'origin_display' => $depDisplay,
                    'destination_display' => $arrDisplay,
                ];
            }
        }
        return $list;
    }

    private function indexById(array $items): array
    {
        $index = [];
        foreach ($items as $item) {
            $id = $item['id'] ?? null;
            if ($id !== null) {
                $index[$id] = $item;
            }
        }
        return $index;
    }

    private function buildPlaceCodeMap(array $places): array
    {
        $map = [];
        foreach ($places as $p) {
            $id = $p['id'] ?? null;
            $code = $p['iata_code'] ?? $p['code'] ?? null;
            if ($id !== null && $code !== null) {
                $map[$id] = $code;
            }
        }
        return $map;
    }

    /**
     * Build place_id => display string: airport name (never city), with optional "Terminal-X" (e.g. "Hazrat Shahjalal International Airport (DAC) Terminal-1").
     * Uses airport name/display_name from API only; appends terminal in passport style: " Terminal-1".
     */
    private function buildPlaceDisplayMap(array $places): array
    {
        $map = [];
        foreach ($places as $p) {
            $id = $p['id'] ?? null;
            $code = $p['iata_code'] ?? $p['code'] ?? '';
            $name = $p['name'] ?? $p['display_name'] ?? '';
            $terminal = $p['terminal'] ?? $p['terminal_id'] ?? '';
            if ($id === null) {
                continue;
            }
            $code = strtoupper(trim((string) $code));
            $name = trim((string) $name);
            $terminal = trim((string) $terminal);
            if ($name !== '') {
                $display = $name . ($code !== '' ? ' (' . $code . ')' : '');
                if ($terminal !== '') {
                    $display .= ' ' . $this->formatTerminalForDisplay($terminal);
                }
                $map[$id] = $display;
            } elseif ($code !== '') {
                $map[$id] = $code;
            }
        }
        return $map;
    }

    /**
     * Format terminal for display: "Terminal-1", "Terminal-2" (passport style, no space before number).
     */
    private function formatTerminalForDisplay(string $terminal): string
    {
        $t = trim($terminal);
        if ($t === '') {
            return '';
        }
        if (preg_match('/^Terminal[- ]*(\d+)$/i', $t, $m)) {
            return 'Terminal-' . $m[1];
        }
        if (preg_match('/^T(\d+)$/i', $t, $m)) {
            return 'Terminal-' . $m[1];
        }
        if (preg_match('/^(\d+)$/', $t, $m)) {
            return 'Terminal-' . $m[1];
        }
        return 'Terminal-' . $t;
    }

    private function buildCarrierNameMap(array $carriers): array
    {
        $map = [];
        foreach ($carriers as $c) {
            $id = $c['id'] ?? null;
            $name = $c['name'] ?? $c['display_name'] ?? null;
            if ($id !== null && $name !== null) {
                $map[$id] = $name;
            }
        }
        return $map;
    }

    /** Build carrier_id => IATA code map for segments (used when creating airline from flight search). */
    private function buildCarrierCodeMap(array $carriers): array
    {
        $map = [];
        foreach ($carriers as $c) {
            $id = $c['id'] ?? null;
            $code = $c['alt_id'] ?? $c['display_code'] ?? $c['code'] ?? $c['iata'] ?? $c['iata_code'] ?? null;
            if ($id !== null && $code !== null) {
                $map[$id] = strtoupper(trim((string) $code));
            }
        }
        return $map;
    }

    private function indexAirlinesByCode(array $airlines): array
    {
        $index = [];
        foreach ($airlines as $a) {
            $code = $a['code'] ?? null;
            $name = $a['name'] ?? '';
            if ($code !== null) {
                $index[$code] = $name;
            }
        }
        return $index;
    }

    private function indexAirportsByCode(array $airports): array
    {
        $index = [];
        foreach ($airports as $a) {
            $code = $a['code'] ?? null;
            if ($code !== null) {
                $index[$code] = $a['name'] ?? $code;
            }
        }
        return $index;
    }

    private function formatDateTimeForDisplay(?string $value): string
    {
        if (empty($value)) {
            return '';
        }
        try {
            $dt = new \DateTime($value);
            return $dt->format('Y-m-d H:i');
        } catch (\Exception $e) {
            return $value;
        }
    }

    private function formatTimeOnly(?string $value): string
    {
        if (empty($value)) {
            return '';
        }
        try {
            $dt = new \DateTime($value);
            return $dt->format('H:i');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format arrival time and add "+N days" when the flight crosses one or more calendar days.
     * Day offset is derived from duration (minutes) so it is timezone-independent and correct.
     * Only shows "+1 day" … "+7 days"; larger values are treated as data errors and omitted.
     */
    private function formatArrivalTimeWithDayOffset(?string $departure, ?string $arrival, ?int $durationMinutes = null): string
    {
        if (empty($arrival)) {
            return '';
        }
        $timeStr = $this->formatTimeOnly($arrival);
        if ($timeStr === '') {
            return '';
        }

        // Prefer duration-based day count (correct and timezone-independent)
        if ($durationMinutes !== null && $durationMinutes >= 0) {
            $daysCrossed = (int) floor($durationMinutes / 1440); // 1440 = 24*60
            if ($daysCrossed <= 0) {
                return $timeStr;
            }
            if ($daysCrossed > 7) {
                return $timeStr;
            }
            if ($daysCrossed === 1) {
                return $timeStr . ' +1 day';
            }
            return $timeStr . ' +' . $daysCrossed . ' days';
        }

        // Fallback: calendar date difference (can be wrong if API uses mixed timezones)
        if (empty($departure)) {
            return $timeStr;
        }
        try {
            $depDt = new \DateTime($departure);
            $arrDt = new \DateTime($arrival);
            $depDay = (int) $depDt->format('Ymd');
            $arrDay = (int) $arrDt->format('Ymd');
            $daysDiff = $arrDay - $depDay;
            if ($daysDiff <= 0 || $daysDiff > 7) {
                return $timeStr;
            }
            if ($daysDiff === 1) {
                return $timeStr . ' +1 day';
            }
            return $timeStr . ' +' . $daysDiff . ' days';
        } catch (\Exception $e) {
            return $timeStr;
        }
    }

    private function formatDurationMinutes(int $minutes): string
    {
        if ($minutes < 0) {
            return '0h 0m';
        }
        $h = (int) floor($minutes / 60);
        $m = $minutes % 60;
        return $h . 'h ' . $m . 'm';
    }

    /**
     * Build list of segment details for display (airline, flight_number, route, time, duration).
     *
     * @param array $legIds
     * @param array $legs   id => leg
     * @param array $segments id => segment
     * @param array $places  place_id => iata_code
     * @param array $carriers carrier_id => name
     * @param array $placeDisplay place_id => display string (name + code + terminal)
     * @param array $carrierCodes carrier_id => IATA code (optional)
     * @return array
     */
    private function buildSegmentList(array $legIds, array $legs, array $segments, array $places, array $carriers, array $placeDisplay = [], array $carrierCodes = []): array
    {
        $list = [];
        $prevArrival = null;
        foreach ($legIds as $legId) {
            $leg = $legs[$legId] ?? null;
            if (!$leg) {
                continue;
            }
            foreach ($leg['segment_ids'] ?? [] as $segId) {
                $seg = $segments[$segId] ?? null;
                if (!$seg) {
                    continue;
                }
                $dep = $seg['departure'] ?? '';
                $arr = $seg['arrival'] ?? '';
                $durationMinutes = (int) ($seg['duration'] ?? 0);
                $oid = $seg['origin_place_id'] ?? null;
                $did = $seg['destination_place_id'] ?? null;
                $originCode = ($oid !== null && isset($places[$oid])) ? $places[$oid] : '';
                $destCode = ($did !== null && isset($places[$did])) ? $places[$did] : '';
                $originDisplay = ($placeDisplay !== [] && $oid !== null && isset($placeDisplay[$oid])) ? $placeDisplay[$oid] : $originCode;
                $destDisplay = ($placeDisplay !== [] && $did !== null && isset($placeDisplay[$did])) ? $placeDisplay[$did] : $destCode;
                $carrierId = $seg['marketing_carrier_id'] ?? null;
                $airlineName = ($carrierId !== null && isset($carriers[$carrierId])) ? $carriers[$carrierId] : 'N/A';
                $airlineCode = ($carrierId !== null && isset($carrierCodes[$carrierId])) ? $carrierCodes[$carrierId] : '';
                $flightNumber = $seg['marketing_flight_number'] ?? '';
                $route = $originCode . ' → ' . $destCode;
                $routeDisplay = $originDisplay . ' → ' . $destDisplay;
                $timeStr = $this->formatTimeOnly($dep) . ' - ' . $this->formatArrivalTimeWithDayOffset($dep, $arr, $durationMinutes);
                $list[] = [
                    'airline' => $airlineName,
                    'airline_code' => $airlineCode,
                    'flight_number' => $flightNumber,
                    'route' => $route,
                    'route_display' => $routeDisplay,
                    'time' => $timeStr,
                    'duration' => $this->formatDurationMinutes($durationMinutes),
                    'departure_at' => $this->formatDateTimeForDisplay($dep),
                    'arrival_at' => $this->formatDateTimeForDisplay($arr),
                    'origin_iata' => $originCode,
                    'destination_iata' => $destCode,
                    'origin_display' => $originDisplay,
                    'destination_display' => $destDisplay,
                ];
            }
        }
        return $list;
    }

    /**
     * Extract transit airport codes and display strings from segment list (intermediate stops).
     * For segments [A→B, B→C, C→D], returns codes [B, C] and display e.g. "Narita (NRT), Honolulu (HNL)".
     */
    private function transitAirportsFromSegments(array $segmentList): array
    {
        $codes = [];
        $displayParts = [];
        $n = count($segmentList);
        for ($i = 0; $i < $n - 1; $i++) {
            $destCode = $segmentList[$i]['destination_iata'] ?? $segmentList[$i]['destination'] ?? '';
            $destDisplay = $segmentList[$i]['destination_display'] ?? $destCode;
            if ($destCode !== '' || $destDisplay !== '') {
                $codes[] = $destCode ?: $destDisplay;
                $displayParts[] = $destDisplay ?: $destCode;
            }
        }
        return [
            'codes' => $codes,
            'display' => implode(', ', $displayParts),
        ];
    }

    private function formatDate(?string $value): string
    {
        if (empty($value)) {
            return date('Y-m-d');
        }
        try {
            $d = new \DateTime($value);
            return $d->format('Y-m-d');
        } catch (\Exception $e) {
            return date('Y-m-d');
        }
    }

    private function extractAirportCode($value): string
    {
        if (is_array($value)) {
            $value = $value['code'] ?? $value['iata'] ?? implode('', $value);
        }
        $s = (string) $value;
        if (preg_match('/\b([A-Z]{3})\b/i', $s, $m)) {
            return strtoupper($m[1]);
        }
        return strtoupper(substr(trim($s), 0, 3));
    }

    private function mapCabinClass(string $class): string
    {
        $map = [
            'economy' => 'Economy',
            'business' => 'Business',
            'first' => 'First',
            'premium_economy' => 'Premium_Economy',
        ];
        return $map[strtolower($class)] ?? 'Economy';
    }

    /**
     * Parse API error body into a short user-facing message.
     */
    private function parseFlightApiErrorMessage(?string $body, int $status): string
    {
        if (empty($body)) {
            return 'FlightAPI error (HTTP ' . $status . ').';
        }
        $decoded = json_decode($body, true);
        if (is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])) {
            $msg = trim($decoded['message']);
            if (stripos($msg, 'date is wrong or airline code') !== false) {
                return 'Live status is not available for this flight. The provider may not support this airline or date — check that the flight date and airline IATA code (e.g. BG, DL) are correct.';
            }
            return $msg;
        }
        return strlen($body) < 500 ? $body : ('FlightAPI error (HTTP ' . $status . ').');
    }

    /**
     * Common ICAO (3-letter) to IATA (2-letter) codes for flight tracking.
     * FlightAPI tracking requires 2-letter IATA; DB may store ICAO.
     */
    private static function trackingAirlineIata(string $code): string
    {
        $raw = strtoupper(trim($code));
        if (strlen($raw) === 2) {
            return $raw;
        }
        $icaoToIata = [
            'ANA' => 'NH', 'JAL' => 'JL', 'UAL' => 'UA', 'DAL' => 'DL', 'AAL' => 'AA',
            'SWA' => 'WN', 'UAE' => 'EK', 'SIA' => 'SQ', 'THA' => 'TG', 'BAW' => 'BA',
            'AFR' => 'AF', 'KLM' => 'KL', 'DLH' => 'LH', 'QTR' => 'QR',
            'CPA' => 'CX', 'CAL' => 'CI', 'EVA' => 'BR', 'CCA' => 'CA', 'CSN' => 'CZ',
        ];
        return $icaoToIata[$raw] ?? substr($raw, 0, 2);
    }

    /**
     * Flight Tracking API: get real-time flight status.
     * @see https://docs.flightapi.io/flight-tracking-api
     *
     * Endpoint: GET https://api.flightapi.io/airline/{api_key}
     * Required query params: num (flight number), name (airline code), date (YYYYMMDD)
     * Optional: depap (departure airport – when two flights with same name/num depart different locations)
     *
     * @param string $airlineCode Airline code – 2-letter IATA (e.g. DL, NH) or 3-letter ICAO (mapped internally)
     * @param string $flightNumber Official flight number only (e.g. 33)
     * @param string $date Flight date – any parseable date; sent as YYYYMMDD
     * @param string|null $departureAirportCode Optional. IATA 3-letter departure airport (e.g. BCN)
     * @return array API response (array of departure/arrival segments or error with success:false)
     */
    public function trackFlight(string $airlineCode, string $flightNumber, string $date, ?string $departureAirportCode = null): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('FlightAPI is not configured.');
        }
        $num = trim((string) $flightNumber);
        $airlineCodeTrim = trim((string) $airlineCode);
        if (empty($airlineCodeTrim)) {
            throw new Exception('Flight number and airline IATA code (2 letters) are required for tracking.');
        }
        $name = self::trackingAirlineIata($airlineCodeTrim);
        if ($num === '' || strlen($name) !== 2) {
            throw new Exception('Flight number and airline IATA code (2 letters) are required for tracking.');
        }
        $dateStr = $this->formatDateForTracking($date);
        $path = '/airline/' . $this->apiKey;
        $url = $this->baseUrl . $path;
        $query = [
            'num' => $num,
            'name' => $name,
            'date' => $dateStr,
        ];
        $depap = '';
        if ($departureAirportCode !== null && trim((string) $departureAirportCode) !== '') {
            $extracted = $this->extractAirportIataFromPlace((string) $departureAirportCode);
            $depap = $extracted !== null ? $extracted : '';
        }
        if ($depap !== '') {
            $query['depap'] = $depap;
        }

        $logContext = ['tracking_request' => ['num' => $num, 'name' => $name, 'date' => $dateStr, 'depap' => $query['depap'] ?? null]];
        $data = $this->get($url, $query, $logContext);

        if (isset($data['success']) && $data['success'] === false) {
            $apiMsg = isset($data['message']) && is_string($data['message']) ? trim($data['message']) : 'Unknown error';
            Log::warning('FlightAPI tracking returned success:false', [
                'request' => ['num' => $num, 'name' => $name, 'date' => $dateStr, 'depap' => $query['depap'] ?? null],
                'message' => $apiMsg,
            ]);
            if (stripos($apiMsg, 'date is wrong or airline code') !== false) {
                throw new Exception(
                    'Live status not available for this flight. Check: (1) Airline code is 2-letter IATA (e.g. NH, JL, DL). '
                    . 'If your airline is stored as 3-letter ICAO, set the airline IATA code in airline settings. '
                    . '(2) The flight date (YYYYMMDD) is correct. Requested: ' . $dateStr . ', airline: ' . $name . ', number: ' . $num . '.'
                );
            }
            throw new Exception('FlightAPI: ' . $apiMsg);
        }

        return $data;
    }

    /**
     * Format date for tracking API: YYYYMMDD.
     */
    private function formatDateForTracking(?string $value): string
    {
        if (empty($value)) {
            return date('Ymd');
        }
        try {
            $d = new \DateTime($value);
            return $d->format('Ymd');
        } catch (\Exception $e) {
            return date('Ymd');
        }
    }

    /**
     * Extract numeric flight number only (for API "num" param).
     * E.g. "DL 33", "33", "DL33" -> "33"
     */
    public static function extractFlightNumberOnly(?string $flightNumber): string
    {
        if (empty($flightNumber)) {
            return '';
        }
        $s = trim(preg_replace('/\s+/', ' ', (string) $flightNumber));
        if (preg_match('/([A-Z]{2})\s*(\d+)/i', $s, $m)) {
            return $m[2];
        }
        if (preg_match('/(\d+)/', $s, $m)) {
            return $m[1];
        }
        return $s;
    }

    /**
     * Extract airline code from flight number string if present (e.g. "DL 33" -> "DL").
     * Returns null if not found (caller should use airline->code).
     */
    public static function extractAirlineCodeFromFlightNumber(?string $flightNumber): ?string
    {
        if (empty($flightNumber)) {
            return null;
        }
        $s = trim((string) $flightNumber);
        if (preg_match('/^([A-Z]{2})\s*\d+/i', $s, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/^([A-Z]{2})/i', $s, $m)) {
            return strtoupper($m[1]);
        }
        return null;
    }
}
