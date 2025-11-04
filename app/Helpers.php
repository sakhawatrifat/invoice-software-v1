<?php

use Carbon\Carbon;
use App\Models\Notification;
use App\Models\Homepage;
use App\Models\User;
use App\Models\Country;
use App\Models\Reference;
use App\Models\Ticket;
use App\Models\TicketFlight;
use App\Models\Payment;
use App\Models\Language as LanguageModel;
use App\Models\Translation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

use LanguageDetection\Language;
use Illuminate\Support\Facades\Schema;

// use Kreait\Firebase\Factory;
// use Kreait\Firebase\Messaging\CloudMessage;
// use Kreait\Firebase\Messaging\Notification as FirebaseNotification; // Alias the class


if (!function_exists('homepageData')) {
    function homepageData()
    {   
        $globalHomepageData = Homepage::where('lang', 'en')->first();

        return $globalHomepageData;
    }
}

if (!function_exists('globalData')) {
    function globalData()
    {   
        if (Auth::check()) {
            $globalData = User::with('company')->find(Auth::id());
        } else {
            $globalData = User::with('company')->where('user_type', 'admin')->first();
        }

        return $globalData;
    }
}

if (!function_exists('getCurrentRouteName')) {
    function getCurrentRouteName($route = null)
    {
        return Route::currentRouteName();
    }
}

if (!function_exists('getActiveClass')) {
    function getActiveClass($routeArray, $class = 'active')
    {
        foreach ($routeArray as $route) {
            if (Route::currentRouteName() == $route) {
                return $class;
            }
        }
        return '';
    }
}


if(!function_exists('firstModelData')){
    function firstModelData($model, $withArray = null){
        $class = "\App\Models\\".$model;
        if (class_exists($class)) {
            $data = $class::orderBy('id', 'asc');
            if($withArray != null && is_array($withArray)){
                foreach($withArray as $with){
                    $data->with($with);
                }
            }
            $data = $data->first();
        }else{
            $data = null;
        }
        //dd($data);
        return $data;
    }
}

if(!function_exists('findModelData')){
    function findModelData($model,  $withArray = null, $key = null, $value = null, $selectArray = null, $whereArray = null){
        $class = "\App\Models\\".$model;
        if (class_exists($class)) {
            $data = $class::orderBy('id', 'asc');
            if($withArray != null && is_array($withArray)){
                foreach($withArray as $with){
                    $data->with($with);
                }
            }
            if($selectArray != null && is_array($selectArray)){
                $data->select($selectArray);
            }
            if($whereArray != null && is_array($whereArray)){
                foreach($whereArray as $item){
                    $data->where($item['key'], $item['value']);
                }
            }
            $data = $data->where($key, $value)->first();
        }else{
            $data = null;
        }
        //dd($data);
        return $data;
    }
}

if(!function_exists('getLikeModelData')){
    function getLikeModelData($model, $key, $value, $withArray = null, $limit = null, $extraParamKey = null, $extraParamValue = null){
        $class = "\App\Models\\".$model;
        if (class_exists($class)) {
            $data = $class::where($key, 'like', '%' . strval($value) . '%');
            if($withArray != null && is_array($withArray)){
                foreach($withArray as $with){
                    $data->with($with);
                }
            }
            if($extraParamKey != null && $extraParamValue != null){
                if($extraParamKey = 'WardByPrefecture'){
                    $cities = City::where('prefecture_id',$extraParamValue)->get()->pluck('id')->toArray();
                    if(count($cities) > 0){
                        $wards = Ward::whereIn('city_id',$cities)->get()->pluck('id')->toArray();
                        if(count($wards) > 0){
                            $data->whereIn('ward_id', $wards);
                        }
                    }
                }
            }
            if($limit != null){
                $data = $data->paginate($limit);
            }else{
                $data = $data->get();
            }
        }else{
            $data = [];
        }
        //dd($data);
        return $data;
    }
}

if(!function_exists('getModelData')){
    function getModelData($model, $key, $values, $withArray = null, $limit = null, $customWhereArray = null, $orderByArray = null){

        if(!is_array($values)){
            return $data = [];
        }
        $class = "\App\Models\\".$model;
        if (class_exists($class)) {
            $data = $class::whereIn($key, $values);
            if($withArray != null && is_array($withArray)){
                foreach($withArray as $with){
                    $data->with($with);
                }
            }
            if($customWhereArray != null && is_array($customWhereArray)){
                foreach($customWhereArray as $whereVal){
                    //dd(array_keys($whereVal)[0]);
                    $data->where(array_keys($whereVal)[0], array_values($whereVal)[0]);
                }
            }
            if($orderByArray != null && is_array($orderByArray)){
                $data = $data->orderBy($orderByArray[0], $orderByArray[1]);
            }
            if($limit != null){
                $data = $data->paginate($limit);
            }else{
                $data = $data->get();
            }
        }else{
            $data = [];
        }
        //dd($data);
        return $data;
    }
}

if(!function_exists('getWhereInModelData')){
    function getWhereInModelData($model, $key, $values, $withArray = null, $limit = null, $customWhereArray = null){
        if(!is_array($values)){
            return $data = [];
        }
        $class = "\App\Models\\".$model;
        if (class_exists($class)) {
            $data = $class::whereIn($key, $values);
            if($withArray != null && is_array($withArray)){
                foreach($withArray as $with){
                    $data->with($with);
                }
            }
            if($customWhereArray != null && is_array($customWhereArray)){
                foreach($customWhereArray as $whereVal){
                    //dd(array_keys($whereVal)[0]);
                    $data->whereIn(array_keys($whereVal)[0], array_values($whereVal)[0]);
                }
            }
            if($limit != null){
                $data = $data->paginate($limit);
            }else{
                $data = $data->get();
            }
        }else{
            $data = [];
        }
        //dd($data);
        return $data;
    }
}

if(!function_exists('getAllModelData')){
    function getAllModelData($model, $except = null, $withArray = null, $limit = null){
        $class = "\App\Models\\".$model;
        if (class_exists($class)) {
            $data = $class::query();
            if($withArray != null && is_array($withArray)){
                foreach($withArray as $with){
                    $data->with($with);
                }
            }
            if($except != null && is_array($except)){
                $data->whereNotIn('id', $except);
            }
            if($limit != null){
                $data = $data->paginate($limit);
            }else{
                $data = $data->get();
            }
        }else{
            $data = [];
        }
        //dd($data);
        return $data;
    }
}

if(!function_exists('matchAllRoute')){
    function matchAllRoute($array){
        $matched = 0;
        foreach($array as $arrayItem){
            if(\Request::route()->getName() == $arrayItem){
                $matched += 1;
            }
        }

        $status = false;
        if(count($array) == $matched){
            $status = true;
        }

        return $status;
    }
}

if(!function_exists('matchAnyRoute')){
    function matchAnyRoute($array){
        $matched = 0;
        foreach($array as $arrayItem){
            if( \Request::route() != null && \Request::route()->getName() == $arrayItem){
                $matched += 1;
            }
        }

        $status = false;
        if($matched > 0){
            $status = true;
        }
        //dd($status);

        return $status;
    }
}


if(!function_exists('uploadFile')){
    function uploadFile($file, $nameAs = null, $folder = 'all', $oldFile = null){  
        if($oldFile != null){
            if(file_exists($oldFile)){
                File::delete($oldFile);
            }
        }

        $fullFileUrl = null;
        if(isset($file)){
            $size = $file->getSize();
            $extension = strtolower($file->getClientOriginalExtension());
            $randomDigits = 3;
            $randomNumber = rand(pow(10, $randomDigits-1), pow(10, $randomDigits)-1);

            if($nameAs == null){
                $fileName = str_replace(".$extension", "", str_replace(' ', '-', strtolower($file->getClientOriginalName())));
            }else{
                $fileName = str_replace(".$extension", "", str_replace(' ', '-', strtolower($nameAs)));
            }

            if(file_exists("uploads/$folder/$fileName.$extension")){
                $fileName = "$fileName-$randomNumber.$extension";
            }else{
                $fileName = "$fileName.$extension";
            }
            $destination ="uploads/$folder/";
            $file->move( $destination ,$fileName );
            $fullFileUrl = $destination.$fileName;
        }
        
        return $fullFileUrl;
    }
}


if(!function_exists('fileExists')){
    function fileExists($filePath = null){
        $status = false;
        if($filePath != null){
            if(file_exists($filePath)){
                $status = true;
            }
        }

        return $status;
    }
}


if (!function_exists('getUploadedAsset')) {
    function getUploadedAsset($url)
    {
        return asset($url);
    }
}

if (!function_exists('getStaticFile')) {
    function getStaticFile($folder, $fileName, $ext = '.png', $default = 's',): string
    {
        $path = public_path("assets/images/{$folder}/" . strtolower($fileName) . $ext);
        if (File::exists($path)) {
            return asset("assets/images/{$folder}/" . strtolower($fileName) . $ext);
        }

        // fallback image
        return defaultImage($default);
    }
}

// For Storage
if (!function_exists('handleImageUpload')) {
    function handleImageUpload($uploadedFile, $resizeMaxWidth=null, $resizeMaxHeight=null, $folderName='images', $fileName=null ,$oldFile = null)
    {
        // Define the output directory
        $folderName = $folderName.'/';
        $outputDir = storage_path('app/public/'.$folderName);

        // Ensure the output directory exists
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        //dd($oldFile);
        // Get the file extension and name without extension
        $originalExtension = strtolower($uploadedFile->getClientOriginalExtension());
        $fileNameWithoutExt = $fileName != null ? $fileName. '-' . uniqid() : pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME) . '-' . uniqid();

        // Decide the final extension
        $convertedExtension = $originalExtension === 'heic' ? 'jpg' : $originalExtension;

        // Define the initial output path
        $outputFilePath = $folderName . $fileNameWithoutExt . '.' . $convertedExtension;
        $fullOutputFilePath = $outputDir . $fileNameWithoutExt . '.' . $convertedExtension;

        try { 
            if($oldFile != null){
                deleteUploadedFile($oldFile);
            }

            $tempFileToDelete = null;

            // Handle HEIC conversion
            if ($originalExtension === 'heic') {
                $heicTempPath = $uploadedFile->getPathname();
                $convertedJpgPath = $outputDir . $fileNameWithoutExt . '_converted.jpg';

                Maestroerror\HeicToJpg::convert($heicTempPath)->saveAs($convertedJpgPath);

                // Open the converted JPG with Intervention Image
                $image = Image::make($convertedJpgPath);

                // Mark the temporary file for deletion later
                $tempFileToDelete = $convertedJpgPath;
            } else {
                // Process JPG, JPEG, PNG directly
                $image = Image::make($uploadedFile->getPathname());
            }

            // Resize the image while maintaining the original aspect ratio
            if($resizeMaxWidth != null && $resizeMaxHeight != null){
                $image->resize($resizeMaxWidth, $resizeMaxHeight, function ($constraint) {
                    $constraint->aspectRatio(); // Maintain aspect ratio
                    $constraint->upsize();     // Prevent upsizing beyond the original dimensions
                });
            }

            // Save the resized image
            $image->save($fullOutputFilePath, 90); // Quality 90 for JPG

            // Delete the temporary file if it exists
            if ($tempFileToDelete && file_exists($tempFileToDelete)) {
                unlink($tempFileToDelete);
            }

            return $outputFilePath;
        } catch (\Exception $e) {
            \Log::error('Error on uploading image', ['error' => $e->getMessage()]);
            // Log or handle the error as needed
            return null;
        }
    }
}

// For Storage
function getUploadedUrl($url) {
    // If already a full URL, return as-is
    if (Str::startsWith($url, ['http://', 'https://'])) {
        return $url;
    }

    // 1️⃣ Check storage folder
    $storagePath = 'storage/' . ltrim($url, '/');
    if (file_exists(public_path($storagePath))) {
        return asset($storagePath);
    }

    // 2️⃣ Check public folder
    $publicPath = ltrim($url, '/'); // assume relative to public/
    if (file_exists(public_path($publicPath))) {
        return asset($publicPath);
    }

    // 3️⃣ Not found, optionally return null or placeholder
    return null;
}


// For Storage
function isExistUploadedUrl($url) {
    $relativePath = 'storage/' . ltrim($url, '/');

    if (file_exists(public_path($relativePath))) {
        return asset($relativePath);
    }

    return null;
}

//For Storage
function deleteUploadedFile($path)
{
    // Normalize path (remove leading slash)
    $relativePath = ltrim($path, '/');

    // Possible file locations
    $storagePath = public_path('storage/' . $relativePath);
    $uploadPath = public_path('uploads/' . $relativePath);
    $publicPath = public_path($relativePath);

    // Try deleting from storage folder
    if (File::exists($storagePath)) {
        File::delete($storagePath);
        return true;
    }

    // Try deleting from public/uploads folder
    if (File::exists($uploadPath)) {
        File::delete($uploadPath);
        return true;
    }

    // Try deleting directly from public folder
    if (File::exists($publicPath)) {
        File::delete($publicPath);
        return true;
    }

    // File not found anywhere
    return false;
}



if (!function_exists('isValidDate')) {
    function isValidDate($date)
    {
        // Check if the date matches the format YYYY/MM/DD
        $format = 'Y/m/d';
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}

if (!function_exists('validateDateRange')) {
    function validateDateRange($attribute, $value, $parameters, $validator)
    {
        // Check if the format matches YYYY/MM/DD-YYYY/MM/DD
        if (!preg_match('/^\d{4}\/\d{2}\/\d{2}-\d{4}\/\d{2}\/\d{2}$/', $value)) {
            return false;
        }

        // Split the value by the hyphen to get the two dates
        [$startDate, $endDate] = explode('-', $value);

        // Check if both dates are valid
        return isValidDate($startDate) && isValidDate($endDate);
    }
}


if (!function_exists('updateBanks')) {
    function updateBanks()
    {
        $banks = [
            'AB Bank PLC',
            'Agrani Bank PLC',
            'Al-Arafah Islami Bank PLC',
            'Bangladesh Commerce Bank Limited',
            'Bangladesh Development Bank PLC',
            'Bangladesh Krishi Bank',
            'Bank Al-Falah Limited',
            'Bank Asia PLC.',
            'BASIC Bank Limited',
            'Bengal Commercial Bank PLC.',
            'BRAC Bank PLC',
            'Citibank N.A',
            'Citizens Bank PLC',
            'City Bank PLC',
            'Commercial Bank of Ceylon Limited',
            'Community Bank Bangladesh PLC.',
            'Dhaka Bank PLC',
            'Dutch-Bangla Bank PLC',
            'Eastern Bank PLC',
            'EXIM Bank PLC',
            'First Security Islami Bank PLC',
            'Global Islami Bank PLC',
            'Habib Bank Ltd.',
            'ICB Islamic Bank Ltd.',
            'IFIC Bank PLC',
            'Islami Bank Bangladesh PLC',
            'Jamuna Bank PLC',
            'Janata Bank PLC',
            'Meghna Bank PLC',
            'Mercantile Bank PLC',
            'Midland Bank Limited',
            'Modhumoti Bank PLC',
            'Mutual Trust Bank PLC',
            'Nagad Digital Bank PLC.',
            'National Bank Limited',
            'National Bank of Pakistan',
            'National Credit & Commerce Bank PLC',
            'NRB Bank Limited',
            'NRBC Bank PLC',
            'One Bank PLC',
            'Padma Bank PLC',
            'Prime Bank PLC',
            'Probashi Kollyan Bank',
            'Pubali Bank PLC',
            'Rajshahi Krishi Unnayan Bank',
            'Rupali Bank PLC',
            'SBAC Bank PLC',
            'Shahjalal Islami Bank PLC',
            'Shimanto Bank PLC',
            'Social Islami Bank PLC',
            'Sonali Bank PLC',
            'Southeast Bank PLC',
            'Standard Bank PLC',
            'Standard Chartered Bank',
            'State Bank of India',
            'The Hong Kong and Shanghai Banking Corporation. Ltd.',
            'The Premier Bank PLC',
            'Trust Bank Limited',
            'Union Bank PLC',
            'United Commercial Bank PLC',
            'Uttara Bank PLC',
            'Woori Bank',
        ];

        foreach($banks as $key => $item){
            $bank = Bank::where('name', $item)->first();
            if(empty($bank)){
                $bank = new Bank();
            }
            $bank->name = $item;
            $bank->save();
        }
    }
}

if (!function_exists('getBanks')) {
    function getBanks()
    {
        $banks = Bank::where('status', 'Active')->orderBy('name', 'asc')->get();
        return $banks;
    }
}

if (!function_exists('makeSlug')) {
    function makeSlug($string) 
    {
        // Convert to lowercase
        $slug = strtolower($string);
        
        // Remove any characters that are not alphanumeric, spaces, or dots
        $slug = preg_replace('/[^a-z0-9\s.-]/', '', $slug);
        
        // Replace dots and multiple spaces or hyphens with a single hyphen
        $slug = preg_replace('/[.\s-]+/', '-', $slug);
        
        // Trim hyphens from the start and end of the string
        $slug = trim($slug, '-');
        
        return $slug;
    }
}

if (!function_exists('setEnv')) {
    function setEnv($type, $val)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            //dd($path );
            $val = '"'.trim($val).'"';
            if(is_numeric(strpos(file_get_contents($path), $type)) && strpos(file_get_contents($path), $type) >= 0){
                file_put_contents($path, str_replace(
                    $type.'="'.env($type).'"', $type.'='.$val, file_get_contents($path)
                ));
            }
            else{
                file_put_contents($path, file_get_contents($path)."\r\n".$type.'='.$val);
            }
        }
    }
}


if (!function_exists('generateUniqueCode')) {
    function generateUniqueCode(string $modelName = null, string $column = 'code')
    {
        // Default model if none is provided
        $modelClass = $modelName ?? 'Reference';

        // If only model name (not FQCN) is passed, prepend App\Models\
        if (!class_exists($modelClass)) {
            $modelClass = '\\App\\Models\\' . $modelClass;
        }

        // Securely generate a 6-digit random numeric code
        $code = '';
        while (strlen($code) < 6) {
            $code .= random_int(0, 9);
        }

        // Check if code already exists
        if ($modelClass::where($column, $code)->exists()) {
            return generateUniqueCode($modelName, $column); // Retry
        }

        return $code;
    }
}


function isArrayNotEmpty(array $input): bool
{
    foreach ($input as $value) {
        if (is_array($value)) {
            if (isArrayNotEmpty($value)) {
                return true;
            }
        } else {
            if (!is_null($value) && $value !== '') {
                return true;
            }
        }
    }
    return false;
}


function isNotEmptyFare($fare) {
    return !(
        (is_null($fare['pax_type']) || $fare['pax_type'] === '') &&
        (float)$fare['unit_price'] == 0.00 &&
        (int)$fare['pax_count'] == 0 &&
        (float)$fare['total'] == 0.00
    );
}

if (! function_exists('removeEmptyArrays')) {
    function removeEmptyArrays($data)
    {
        // If not an array, just return it
        if (!is_array($data)) {
            return $data;
        }

        $filtered = [];

        foreach ($data as $key => $value) {
            // Recursively clean nested arrays
            $value = removeEmptyArrays($value);

            if (is_array($value)) {
                // Check if all array values are empty or null
                $allEmpty = true;
                foreach ($value as $v) {
                    if (!empty($v) || $v === 0 || $v === '0') {
                        $allEmpty = false;
                        break;
                    }
                }

                if (! $allEmpty) {
                    $filtered[$key] = $value;
                }
            } else {
                // Keep only non-empty scalar values
                if (!empty($value) || $value === 0 || $value === '0') {
                    $filtered[$key] = $value;
                }
            }
        }

        return $filtered;
    }
}



function defaultImage($type = 's')
{
    $path = asset('/assets/images');
    $file = '';
    if($type = 's'){
        $file = 'placeholder.jpg';
    }else{
        $file = 'placeholder-rect.jpg';
    }

    return $path.'/'.$file;
}


if (!function_exists('diffInHoursAndMinutes')) {
    function diffInHoursAndMinutes(string $startDateTime, string $endDateTime, string $hour = 'h', string $minute = 'm'): string
    {
        $start = Carbon::parse($startDateTime);
        $end = Carbon::parse($endDateTime);

        if ($end->lt($start)) {
            // For default h/m, no plural "s"
            $hourSuffix = ($hour === 'h') ? '' : 's';
            $minuteSuffix = ($minute === 'm') ? '' : 's';

            return sprintf("0%s%s 00%s%s", $hour, $hourSuffix, $minute, $minuteSuffix);
        }

        $diffInMinutes = $start->diffInMinutes($end);
        $hours = intdiv($diffInMinutes, 60);
        $minutes = $diffInMinutes % 60;

        // Decide plural "s" only if not default 'h' and 'm'
        $hourSuffix = ($hour === 'h') ? '' : ($hours === 1 ? '' : 's');
        $minuteSuffix = ($minute === 'm') ? '' : ($minutes === 1 ? '' : 's');

        $hourLabel = $hour . $hourSuffix;
        $minuteLabel = $minute . $minuteSuffix;

        return sprintf('%d%s %02d%s', $hours, $hourLabel, $minutes, $minuteLabel);
    }
}



function extractPrimaryCity($text=null) {
    if (empty($text)) {
        return 'N/A';
    }

    // Step 1: Remove anything in parentheses (e.g., (NRT), (DAC))
    $text = preg_replace('/\s*\([^)]*\)/', '', $text);

    // Step 2: Remove common non-city keywords
    $patterns = [
        '/\b(International|Airport|Apt|Terminal|Station|Railway|Bus|Port|Intl|Airfield|Air\s*Base|City|Departure|Arrival|Domestic|Runway|Flight|Airlines|Transfer|Connection)\b/i'
    ];
    $text = preg_replace($patterns, '', $text);

    // Step 3: Normalize spacing
    $text = trim(preg_replace('/\s+/', ' ', $text));
    $words = explode(' ', $text);

    // Step 4: Lowercase for matching
    $input = strtolower($text);

    // Step 5: Multi-word city list (lowercased)
    $multiWordCities = array_map('strtolower', [
        // Asia
        'Kuala Lumpur', 'Ho Chi Minh', 'Hong Kong', 'Abu Dhabi', 'New Delhi', 'Sri Jayawardenepura Kotte', 'Tel Aviv',
        'Phnom Penh', 'Davao City', 'Islamabad Capital Territory', 'Bandar Seri Begawan',

        // Europe
        'San Marino', 'Vatican City', 'The Hague', 'Belfast City', 'Las Palmas', 'Sankt Petersburg', 'United Kingdom',

        // North America
        'New York', 'Los Angeles', 'San Francisco', 'San Diego', 'San Jose', 'Mexico City', 'Guatemala City', 'Panama City',
        'Kansas City', 'Salt Lake City', 'Oklahoma City', 'Las Vegas', 'Fort Lauderdale', 'San Antonio',

        // South America
        'Buenos Aires', 'Rio de Janeiro', 'São Paulo', 'Santa Cruz', 'La Paz', 'San Juan', 'Porto Alegre', 'Santiago del Estero',

        // Africa
        'Cape Town', 'Port Elizabeth', 'East London', 'Dar es Salaam', 'Abidjan City', 'Addis Ababa', 'Ouagadougou City',

        // Oceania
        'Port Moresby', 'Newcastle City', 'Gold Coast', 'Wellington City',

        // Middle East
        'Riyadh City', 'Mecca City', 'Al Ain', 'Ras Al Khaimah', 'Medina City',

        // Caribbean & Island nations
        'San Juan', 'Port au Prince', 'Saint George’s', 'Kingston City'
    ]);

    // Step 6: Try to match the longest multi-word city name from the start
    for ($i = 4; $i >= 1; $i--) {
        $chunk = implode(' ', array_slice($words, 0, $i));
        if (in_array(strtolower($chunk), $multiWordCities)) {
            return ucwords($chunk);
        }
    }

    // Fallback: return first word as city
    return ucwords($words[0] ?? '');
}

if (!function_exists('getCities')) {
    function getCities() {
        return [
            // North America
            'Atlanta', 'Austin', 'Baltimore', 'Boston', 'Charlotte', 'Chicago', 'Cleveland', 'Dallas',
            'Denver', 'Detroit', 'Houston', 'Las Vegas', 'Los Angeles', 'Miami', 'Minneapolis',
            'Newark', 'New York', 'Orlando', 'Philadelphia', 'Phoenix', 'Portland', 'Salt Lake City',
            'San Diego', 'San Francisco', 'San Jose', 'Seattle', 'Tampa', 'Toronto', 'Vancouver',
            'Montreal', 'Ottawa', 'Calgary', 'Edmonton', 'Winnipeg', 'Quebec City', 'Cancun',
            'Guadalajara', 'Mexico City', 'Monterrey',

            // Central & South America
            'Bogotá', 'Buenos Aires', 'Cordoba', 'Montevideo', 'Lima', 'Santiago', 'Quito',
            'Guayaquil', 'Caracas', 'Panama City', 'San Jose', 'Tegucigalpa', 'San Salvador',
            'Asuncion', 'La Paz', 'Santa Cruz', 'Brasilia', 'Rio de Janeiro', 'São Paulo',
            'Fortaleza', 'Recife', 'Curitiba', 'Manaus',

            // Europe – Western & Central
            'Amsterdam', 'Athens', 'Barcelona', 'Basel', 'Belgrade', 'Bergen', 'Berlin', 'Bilbao',
            'Birmingham', 'Bratislava', 'Brussels', 'Budapest', 'Bucharest', 'Cologne', 'Copenhagen',
            'Dublin', 'Dubrovnik', 'Edinburgh', 'Frankfurt', 'Geneva', 'Glasgow', 'Helsinki',
            'Innsbruck', 'Krakow', 'Lisbon', 'Ljubljana', 'London', 'Luxembourg', 'Lyon', 'Madrid',
            'Manchester', 'Marseille', 'Milan', 'Munich', 'Nice', 'Oslo', 'Paris', 'Porto', 'Poznan',
            'Prague', 'Reykjavik', 'Riga', 'Rome', 'Seville', 'Sofia', 'Split', 'Stockholm',
            'Stuttgart', 'Tallinn', 'Valencia', 'Vienna', 'Vilnius', 'Warsaw', 'Wroclaw', 'Zagreb',
            'Zurich',

            // Mediterranean & Islands
            'Alicante', 'Antalya', 'Bodrum', 'Cagliari', 'Catania', 'Corfu', 'Dalaman', 'Ercan',
            'Faro', 'Funchal', 'Gran Canaria', 'Heraklion', 'Ibiza', 'Izmir', 'Larnaca', 'Malaga',
            'Malta', 'Madeira', 'Mykonos', 'Naples', 'Olbia', 'Palermo', 'Paphos', 'Palma de Mallorca',
            'Rhodes', 'Santorini', 'Tenerife',

            // Middle East
            'Abu Dhabi', 'Amman', 'Baghdad', 'Bahrain', 'Beirut', 'Damascus', 'Doha', 'Dubai',
            'Jeddah', 'Kuwait City', 'Manama', 'Muscat', 'Riyadh', 'Sharjah', 'Tel Aviv',

            // South Asia
            'Ahmedabad', 'Bangalore', 'Chattogram', 'Chennai', 'Cochin', 'Coimbatore', 'Colombo',
            'Delhi', 'Dhaka', 'Hyderabad', 'Islamabad', 'Karachi', 'Kathmandu', 'Kolkata', 'Lahore',
            'Male', 'Mumbai', 'Thiruvananthapuram', 'Trichy', 'Varanasi',

            // East & Southeast Asia
            'Bangkok', 'Beijing', 'Busan', 'Chengdu', 'Chongqing', 'Clark', 'Dalian', 'Davao',
            'Denpasar', 'Fukuoka', 'Guangzhou', 'Hangzhou', 'Hanoi', 'Harbin', 'Ho Chi Minh City',
            'Hong Kong', 'Jakarta', 'Kaohsiung', 'Kota Kinabalu', 'Kuala Lumpur', 'Macau', 'Manado',
            'Manila', 'Medan', 'Nagoya', 'Nanjing', 'Osaka', 'Phnom Penh', 'Qingdao', 'Seoul',
            'Shanghai', 'Shenzhen', 'Singapore', 'Surabaya', 'Taipei', 'Tokyo', 'Ulaanbaatar',
            'Vientiane', 'Xiamen', 'Yangon', 'Yogyakarta', 'Zhuhai', 'Cebu',

            // Central Asia & Caucasus
            'Almaty', 'Ashgabat', 'Astana', 'Baku', 'Bishkek', 'Dushanbe', 'Tashkent', 'Yerevan',
            'Tbilisi',

            // Africa – North
            'Algiers', 'Alexandria', 'Cairo', 'Casablanca', 'Hurghada', 'Khartoum', 'Marrakech',
            'Sharm El Sheikh', 'Tripoli', 'Tunis',

            // Africa – Sub-Saharan
            'Abuja', 'Accra', 'Addis Ababa', 'Antananarivo', 'Bamako', 'Banjul', 'Cape Town',
            'Dar es Salaam', 'Djibouti', 'Douala', 'Entebbe', 'Gaborone', 'Harare', 'Johannesburg',
            'Kampala', 'Kigali', 'Kinshasa', 'Lagos', 'Libreville', 'Lilongwe', 'Lome', 'Luanda',
            'Lusaka', 'Maputo', 'Mombasa', 'Monrovia', 'Moroni', 'Nairobi', 'Ndjamena', 'Nouakchott',
            'Port Louis', 'Victoria', 'Windhoek', 'Zanzibar',

            // Oceania
            'Adelaide', 'Apia', 'Auckland', 'Brisbane', 'Cairns', 'Christchurch', 'Darwin',
            'Gold Coast', 'Hobart', 'Melbourne', 'Nadi', 'Perth', 'Port Moresby', 'Suva', 'Sydney',
            'Wellington', 'Honiara', 'Pago Pago',

            // Pacific & Territories
            'Guam', 'Honolulu', 'Saipan',

            // Caribbean
            'Aruba', 'Barbados', 'Bridgetown', 'Curacao', 'Freeport', 'Georgetown', 'Havana',
            'Kingston', 'Montego Bay', 'Nassau', 'Port of Spain', 'Punta Cana', 'San Juan',
            'Santo Domingo', 'St. Lucia', 'St. Maarten', 'Tortola'
        ];
    }
}


if (!function_exists('detect_language')) {
    function detect_language($text, $simple = true)
    {
        $language = new Language();

        return $simple
            ? $language->detectSimple($text)      // returns 'en', 'ja', etc.
            : $language->detect($text);           // returns array of all possible languages with score
    }
}

if (!function_exists('separateLangTexts')) {
    function separateLangTexts($text) {
        $latin = preg_replace('/[^\x00-\x7F]/', '', $text); // Keep only ASCII
        $nonLatin = preg_replace('/[\x00-\x7F]/', '', $text); // Remove ASCII
        
        return [
            'latin' => trim($latin),
            'non_latin' => trim($nonLatin)
        ];
    }
}


if (!function_exists('language_font')) {
    function language_font($text, $checkPoint = null)
    {
        $separatedTextArr = separateLangTexts($text);

        $language = new Language();
        if (!empty($separatedTextArr['non_latin'])) {
            $langScores = $language->detect($text);
        }else{
            $langScores = $language->detect($text);
        }
        $langScores = json_decode(json_encode($langScores), true);

        if($checkPoint == true){
            //dd($separatedTextArr);
        }

        if(isset($langScores['ja']) && $langScores['ja'] == 0 && isset($langScores['en']) && $langScores['en'] == 0){
            return 'ipaexg';
        }

        // Check if 'ja' (Japanese) exists in the detected languages
        if (isset($langScores['ja']) && $langScores['ja'] > 0) {
            return 'ipaexg';
        }
        // Check if 'zh-Hant' or 'zh-Hans' (Chinese) exists in the detected languages
        if ((isset($langScores['zh-Hant']) && $langScores['zh-Hant'] > 0) || (isset($langScores['zh-Hans']) && $langScores['zh-Hans'] > 0)) {
            return 'ipaexg';
        }

        if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FAF}]/u', $text)) {
            return 'ipaexg';
        }
        
        // Check for other specific character ranges
        if (preg_match('/[\x{AC00}-\x{D7AF}]/u', $text)) {
            return 'ipaexg';
        }
        
        if (preg_match('/[\x{4E00}-\x{9FFF}]/u', $text)) {
            return 'ipaexg';
        }

        //dd($langScores);


        // else{ 
        //     $filtered = array_filter($langScores, function($value) {
        //         return $value > 0;
        //     });

        //     if($checkPoint == true){
        //         dd($filtered);
        //     }
        //     if(count($filtered) >= 2){
        //         return 'ipaexg';
        //     }
        // }
        
        return 'arial';
    }
}

if (!function_exists('generateInvoiceId')) {
    function generateInvoiceId($table = 'tickets')
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $prefix = Auth::user()->company->invoice_prefix ?? '';

        // Get the latest record from DB including soft-deleted
        $latest = DB::table($table)
            ->orderByDesc('id')
            ->first();

        if ($latest) {
            $nextId = $latest->id + 1;

            // Auto-expand as digits grow, start with 3-digit pad
            $numberPart = str_pad($nextId, 3, '0', STR_PAD_LEFT);

            $invoiceId = $prefix . $numberPart;
        } else {
            // First record
            $invoiceId = $prefix . '001';
        }

        return $invoiceId;
    }
}

if (!function_exists('getPreBaggageAllowance')) {
    function getPreBaggageAllowance(): string
    {
        $data = '';
        $data .= "Checked Baggage: Adult, 2x23kg,\n";
        $data .= "Cabin Baggage: Adult, 1x8kg\n\n";
        $data .= "Luggage limit is 23kg,\n";
        $data .= "1 piece of baggage 7kg (56x45x25cm)\n\n";
        $data .= "Adult Check-in: 30 kg\n";
        $data .= "In Hand: Up to 7 KG\n\n";
        $data .= "Check-in: 20kg\n";
        $data .= "In-Hand: 7 kg\n\n";
        $data .= "In Hand: Up to 7kg\n\n";
        $data .= "In Hand: Up to 10kg\n";

        return $data;
    }
}

if (!function_exists('getPreFooterDetails')) {
    function getPreFooterDetails(): string
    {
        return <<<TEXT
            <p><strong>Ticket Notice:</strong></p>
            <p>Carriage and other services provided by the carrier are subject to conditions of carriage which are hereby incorporated by reference. These conditions may be</p>
            <p>obtained from the issuing carrier.</p>
            <p><strong>Passport/Visa/Health :</strong></p>
            <p>Please ensure that you have all the required travel documents for your entire journey - i.e. valid passport &amp;necessary visas - and that you have had the</p>
            <p>recommended inoculations for your destination(s).</p>
            <p><strong>Reconfirmation of flights :</strong></p>
            <p>Please reconfirm all flights at least 72 hours in advance direct with the airline concerned. Failure to do so could result in the cancellation of your reservation</p>
            <p>&nbsp;</p>
            TEXT;
    }
}


if (!function_exists('getCurrentTranslation')) {
    function getCurrentTranslation()
    {
        $lang = Auth::user()->default_language ?? 'en';

        //dd($lang);
        $translations = Translation::where('lang', $lang)->pluck('lang_value', 'lang_key')->toArray();

        return $translations;
    }
}



if (!function_exists('getPermissionList')) {
    function getPermissionList()
    {
        return [
            // [
            //     'title' => 'manage_users',
            //     'for' => 'admin',
            //     'permissions' => [
            //         ['title' => 'list', 'key' => 'user.index'],
            //         ['title' => 'create', 'key' => 'user.create'],
            //         ['title' => 'edit', 'key' => 'user.edit'],
            //         ['title' => 'status', 'key' => 'user.status'],
            //         ['title' => 'delete', 'key' => 'user.delete'],
            //     ],
            // ],
            [
                'title' => 'manage_staffs',
                'for' => 'all_user',
                'permissions' => [
                    ['title' => 'list', 'key' => 'staff.index'],
                    ['title' => 'create', 'key' => 'staff.create'],
                    ['title' => 'edit', 'key' => 'staff.edit'],
                    ['title' => 'status', 'key' => 'staff.status'],
                    ['title' => 'delete', 'key' => 'staff.delete'],
                ],
            ],
            [
                'title' => 'manage_airlines',
                'for' => 'admin',
                'permissions' => [
                    ['title' => 'list', 'key' => 'airline.index'],
                    ['title' => 'create', 'key' => 'airline.create'],
                    ['title' => 'edit', 'key' => 'airline.edit'],
                    ['title' => 'status', 'key' => 'airline.status'],
                    ['title' => 'delete', 'key' => 'airline.delete'],
                ],
            ],
            [
                'title' => 'manage_tickets_and_invoice',
                'for' => 'all_user',
                'permissions' => [
                    ['title' => 'list', 'key' => 'ticket.index'],
                    ['title' => 'create', 'key' => 'ticket.create'],
                    ['title' => 'show', 'key' => 'ticket.show'],
                    ['title' => 'multi_layout', 'key' => 'ticket.multiLayout'],
                    ['title' => 'duplicate', 'key' => 'ticket.duplicate'],
                    ['title' => 'edit', 'key' => 'ticket.edit'],
                    ['title' => 'delete', 'key' => 'ticket.delete'],
                    ['title' => 'mail', 'key' => 'ticket.mail'],
                    ['title' => 'reminder', 'key' => 'ticket.reminder'],
                ],
            ],
            [
                'title' => 'manage_hotel_invoice',
                'for' => 'all_user',
                'permissions' => [
                    ['title' => 'list', 'key' => 'hotel.invoice.index'],
                    ['title' => 'create', 'key' => 'hotel.invoice.create'],
                    ['title' => 'show', 'key' => 'hotel.invoice.show'],
                    ['title' => 'duplicate', 'key' => 'hotel.invoice.duplicate'],
                    ['title' => 'edit', 'key' => 'hotel.invoice.edit'],
                    ['title' => 'status', 'key' => 'hotel.invoice.status'],
                    ['title' => 'delete', 'key' => 'hotel.invoice.delete'],
                    ['title' => 'mail', 'key' => 'hotel.invoice.mail'],
                ],
            ],
            [
                'title' => 'manage_payment',
                'for' => 'admin',
                'permissions' => [
                    ['title' => 'list', 'key' => 'payment.index'],
                    ['title' => 'create', 'key' => 'payment.create'],
                    ['title' => 'show', 'key' => 'payment.show'],
                    ['title' => 'duplicate', 'key' => 'payment.duplicate'],
                    ['title' => 'edit', 'key' => 'payment.edit'],
                    ['title' => 'status', 'key' => 'payment.status'],
                    ['title' => 'delete', 'key' => 'payment.delete'],
                    ['title' => 'mail', 'key' => 'payment.mail'],
                ],
            ],
            [
                'title' => 'manage_payment_setup',
                'for' => 'admin',
                'permissions' => [
                    //['title' => 'country', 'key' => 'country'],
                    ['title' => 'introduction_source', 'key' => 'introductionSource'],
                    ['title' => 'issued_supplier', 'key' => 'issuedSupplier'],
                    ['title' => 'issued_by', 'key' => 'issuedBy'],
                    ['title' => 'transfer_to', 'key' => 'transferTo'],
                    ['title' => 'payment_method', 'key' => 'paymentMethod'],
                    ['title' => 'issued_card_type', 'key' => 'issuedCardType'],
                    ['title' => 'card_owner', 'key' => 'cardOwner'],
                ],
            ],
            [
                'title' => 'manage_dashboard_summary',
                'for' => 'admin',
                'permissions' => [
                    ['title' => 'to_do_list', 'key' => 'toDoList'],
                    ['title' => 'sale_graph', 'key' => 'saleGraph'],
                    ['title' => 'airline_based_graph', 'key' => 'airlineBasedGraph'],
                    ['title' => 'transit_city_based_graph', 'key' => 'transitCityBasedGraph'],
                    ['title' => 'departure_city_based_graph', 'key' => 'departureCityBasedGraph'],
                    ['title' => 'return_city_based_graph', 'key' => 'returnCityBasedGraph'],
                    ['title' => 'introduction_source_based_graph', 'key' => 'introductionSourceBasedGraph'],
                    ['title' => 'issued_supplier_based_graph', 'key' => 'issuedSupplierBasedGraph'],
                    ['title' => 'issued_by_based_graph', 'key' => 'issuedByBasedGraph'],
                    ['title' => 'country_based_graph', 'key' => 'countryBasedGraph'],
                    ['title' => 'transfer_to_based_graph', 'key' => 'transferToBasedGraph'],
                    ['title' => 'payment_method_based_graph', 'key' => 'paymentMethodBasedGraph'],
                    ['title' => 'card_type_based_graph', 'key' => 'cardTypeBasedGraph'],
                    ['title' => 'card_owner_based_graph', 'key' => 'cardOwnerBasedGraph'],
                    ['title' => 'trip_type_based_pie_chart', 'key' => 'tripTypeBasedPieChart'],
                    ['title' => 'payment_method_based_pie_chart', 'key' => 'paymentMethodBasedPieChart'],
                    ['title' => 'issued_card_type_based_pie_chart', 'key' => 'issuedCardTypeBasedPieChart'],
                    ['title' => 'payment_status_based_pie_chart', 'key' => 'paymentStatusBasedPieChart'],
                ],
            ],
            [
                'title' => 'manage_reports',
                'for' => 'admin',
                'permissions' => [
                    ['title' => 'profit_loss_report', 'key' => 'admin.profitLossReport'],
                ],
            ],
            [
                'title' => 'manage_homepage',
                'for' => 'admin',
                'permissions' => [
                    ['title' => 'edit', 'key' => 'homepage.edit'],
                ],
            ],
            [
                'title' => 'manage_language',
                'for' => 'admin',
                'permissions' => [
                    ['title' => 'list', 'key' => 'language.index'],
                    ['title' => 'create', 'key' => 'language.create'],
                    ['title' => 'edit', 'key' => 'language.edit'],
                    ['title' => 'translate', 'key' => 'language.translate'],
                    ['title' => 'status', 'key' => 'language.status'],
                    ['title' => 'delete', 'key' => 'language.delete'],
                ],
            ],
        ];
    }
}


if (!function_exists('hasPermission')) {
    function hasPermission(string $key): bool
    {
        return in_array($key, Auth::user()->permissions ?? []);
    }
}


if (!function_exists('getPassengerDataForMail')) {
    function getPassengerDataForMail($passengers)
    {
        $html = '';

        foreach ($passengers as $index => $passenger) {
            $html .= '<p>';
            $html .= '<b>' . ($index + 1) . '️⃣ ' . e($passenger->name) . ' (' . e($passenger->pax_type) . ')</b><br>';
            $html .= '• <b>Airline PNRs:</b> ' . e(collect($passenger->flights)->pluck('airlines_pnr')->filter()->implode(' / ')) . '<br>';
            $html .= '• <b>E-Ticket Numbers:</b> ' . e(collect($passenger->flights)->pluck('ticket_number')->filter()->implode(' / ')) . '<br>';
            $html .= '• <b>Baggage Allowance:</b> ' . preg_replace(
                '/(\s*<br\s*\/?>\s*|\s*[\r\n]+\s*)+/',
                '<br>',
                $passenger->baggage_allowance
            );
            $html .= '</p>';
        }

        return $html;
    }
}


if (!function_exists('getGuestDataForMail')) {
    function getGuestDataForMail($guests)
    {
        $html = '';

        foreach ($guests as $index => $guest) {
            $html .= '<p>';
            $html .= '<b>Guest ' . ($index + 1) . ': ' . e($guest['name'] ?? 'N/A') . '</b><br>';

            if (!empty($guest['email'])) {
                $html .= '<b>Email:</b> ' . e($guest['email']) . '<br>';
            }

            if (!empty($guest['phone'])) {
                $html .= '<b>Phone:</b> ' . e($guest['phone']) . '<br>';
            }

            if (!empty($guest['passport_number'])) {
                $html .= '<b>Passport Number:</b> ' . e($guest['passport_number']) . '<br>';
            }

            $html .= '</p>';
        }

        return $html;
    }
}



if (!function_exists('getTravelReminderEmailContent')) {
    function getTravelReminderEmailContent()
    {
        $globalData = globalData();

        $lines = [];

        $lines[] = '<p>Dear {passenger_name_here},</p>';
        $lines[] = '<p>We hope you\'re excited for your upcoming journey! As your flight approaches, we wanted to provide some final reminders to ensure everything goes smoothly.</p>';

        $lines[] = '<h3>1. Documents Check:</h3>';
        $lines[] = '<p>Before heading to the airport, please make sure you have all the essential documents:</p>';
        $lines[] = '<ul>';
        $lines[] = '  <li>Passport (valid and up-to-date)</li>';
        $lines[] = '  <li>Flight ticket/boarding pass</li>';
        $lines[] = '  <li>Visa and any other travel documents (if required)</li>';
        $lines[] = '</ul>';

        $lines[] = '<h3>2. Airport Arrival:</h3>';
        $lines[] = '<ul>';
        $lines[] = '  <li><strong>Arrival Time:</strong> It is recommended to arrive at the airport at least 3 hours before your flight for international travel. This will allow enough time for check-in, security, and any potential last-minute issues.</li>';
        $lines[] = '  <li><strong>Transportation:</strong> Plan your way to the airport ahead of time to avoid delays. You can choose:';
        $lines[] = '    <ul>';
        $lines[] = '      <li>Public transportation (trains, buses, etc.)</li>';
        $lines[] = '      <li>Ride-hailing services (taxi, Uber, etc.)</li>';
        $lines[] = '      <li>Personal vehicle (ensure parking arrangements are in place)</li>';
        $lines[] = '    </ul>';
        $lines[] = '  </li>';
        $lines[] = '</ul>';

        $lines[] = '<h3>3. Luggage Allowance:</h3>';
        $lines[] = '<ul>';
        $lines[] = '  <li><strong>Checked Baggage:</strong> Check your airline’s baggage policy for allowed weight and size.</li>';
        $lines[] = '  <li><strong>Carry-On Luggage:</strong> Make sure your hand luggage follows the airline’s size and weight restrictions. Exceeding these limits might result in additional charges or the need to repack at the airport.</li>';
        $lines[] = '</ul>';

        $lines[] = '<h3>4. Health &amp; Safety:</h3>';
        $lines[] = '<p>Don’t forget any necessary health items like face masks or documents related to health regulations or vaccination (if required).</p>';

        $lines[] = '<h3>5. Contact Information:</h3>';
        $lines[] = '<p>If you encounter any issues or have last-minute questions, please do not hesitate to reach out to us for assistance.</p>';

        $lines[] = '<p>We wish you a safe and pleasant flight, and we hope you have a fantastic trip!</p>';

        $lines[] = '<p>Best regards,<br>';
        $lines[] = '<strong>{company_name_here}</strong><br>';
        // $lines[] = 'Website: <a href="' . $globalData->company_data->website_url . '" target="_blank">' . $globalData->company_data->website_url . '</a><br>';
        // $lines[] = 'Email: <a href="mailto:' . $globalData->company_data->email_1 . '" target="_blank">' . $globalData->company_data->email_1 . '</a></p>';
        $lines[] = 'Website: <a href="{company_website_url_here}" target="_blank">{company_website_url_here}</a><br>';
        $lines[] = 'Email: <a href="mailto:{company_mail_here}" target="_blank">{company_mail_here}</a></p>';

        return implode("\n", $lines);
    }
}


if (!function_exists('getPrefillHotelData')) {
    function getPrefillHotelData()
    {
        $globalData = globalData();

        $contactInfo  = 'Global Access Number: ' . ($globalData->company_data->phone_1 ?? '') . "\n";
        $contactInfo .= 'Global Access Email: ' . ($globalData->company_data->email_1 ?? '') . "\n";
        $contactInfo .= 'Flights, hotels, trains, and cars: 24/7' . "\n";
        $contactInfo .= 'Other Bookings: 09:00–02:00 (+1) (GMT+8)';


        $data = [
            'occupancy_info' => 'This room type can accommodate up to 2 guests with a max. of 2 adults',
            'room_info' => '1 king bed or 2 single beds',
            'meal_info' => 'No meals included',
            'room_amenities' => 'Body wash • Shampoo • Conditioner • Soap • Shower cap • Private bathroom • Private toilet • Hair dryer • Shower • Vanity mirror in bathroom • Towels • Bath towels • Hot water (24 hours) • Massage shower head • Shattaf',
            'policy_note' => 'If you fail to check in, a penalty equivalent to the cancellation fee will be charged',
            'contact_info' => $contactInfo,
        ];

        return $data;
    }
}


if (!function_exists('seedCountries')) {
    function seedCountries()
    {
        $countries = [
            ["name" => "Afghanistan", "short_name" => "AF", "phone_code" => "93"],
            ["name" => "Albania", "short_name" => "AL", "phone_code" => "355"],
            ["name" => "Algeria", "short_name" => "DZ", "phone_code" => "213"],
            ["name" => "Andorra", "short_name" => "AD", "phone_code" => "376"],
            ["name" => "Angola", "short_name" => "AO", "phone_code" => "244"],
            ["name" => "Antigua and Barbuda", "short_name" => "AG", "phone_code" => "1268"],
            ["name" => "Argentina", "short_name" => "AR", "phone_code" => "54"],
            ["name" => "Armenia", "short_name" => "AM", "phone_code" => "374"],
            ["name" => "Australia", "short_name" => "AU", "phone_code" => "61"],
            ["name" => "Austria", "short_name" => "AT", "phone_code" => "43"],
            ["name" => "Azerbaijan", "short_name" => "AZ", "phone_code" => "994"],
            ["name" => "Bahamas", "short_name" => "BS", "phone_code" => "1242"],
            ["name" => "Bahrain", "short_name" => "BH", "phone_code" => "973"],
            ["name" => "Bangladesh", "short_name" => "BD", "phone_code" => "880"],
            ["name" => "Barbados", "short_name" => "BB", "phone_code" => "1246"],
            ["name" => "Belarus", "short_name" => "BY", "phone_code" => "375"],
            ["name" => "Belgium", "short_name" => "BE", "phone_code" => "32"],
            ["name" => "Belize", "short_name" => "BZ", "phone_code" => "501"],
            ["name" => "Benin", "short_name" => "BJ", "phone_code" => "229"],
            ["name" => "Bhutan", "short_name" => "BT", "phone_code" => "975"],
            ["name" => "Bolivia", "short_name" => "BO", "phone_code" => "591"],
            ["name" => "Bosnia and Herzegovina", "short_name" => "BA", "phone_code" => "387"],
            ["name" => "Botswana", "short_name" => "BW", "phone_code" => "267"],
            ["name" => "Brazil", "short_name" => "BR", "phone_code" => "55"],
            ["name" => "Brunei Darussalam", "short_name" => "BN", "phone_code" => "673"],
            ["name" => "Bulgaria", "short_name" => "BG", "phone_code" => "359"],
            ["name" => "Burkina Faso", "short_name" => "BF", "phone_code" => "226"],
            ["name" => "Burundi", "short_name" => "BI", "phone_code" => "257"],
            ["name" => "Cabo Verde", "short_name" => "CV", "phone_code" => "238"],
            ["name" => "Cambodia", "short_name" => "KH", "phone_code" => "855"],
            ["name" => "Cameroon", "short_name" => "CM", "phone_code" => "237"],
            ["name" => "Canada", "short_name" => "CA", "phone_code" => "1"],
            ["name" => "Central African Republic", "short_name" => "CF", "phone_code" => "236"],
            ["name" => "Chad", "short_name" => "TD", "phone_code" => "235"],
            ["name" => "Chile", "short_name" => "CL", "phone_code" => "56"],
            ["name" => "China", "short_name" => "CN", "phone_code" => "86"],
            ["name" => "Colombia", "short_name" => "CO", "phone_code" => "57"],
            ["name" => "Comoros", "short_name" => "KM", "phone_code" => "269"],
            ["name" => "Congo", "short_name" => "CG", "phone_code" => "242"],
            ["name" => "Costa Rica", "short_name" => "CR", "phone_code" => "506"],
            ["name" => "Croatia", "short_name" => "HR", "phone_code" => "385"],
            ["name" => "Cuba", "short_name" => "CU", "phone_code" => "53"],
            ["name" => "Cyprus", "short_name" => "CY", "phone_code" => "357"],
            ["name" => "Czechia", "short_name" => "CZ", "phone_code" => "420"],
            ["name" => "Democratic Republic of the Congo", "short_name" => "CD", "phone_code" => "243"],
            ["name" => "Denmark", "short_name" => "DK", "phone_code" => "45"],
            ["name" => "Djibouti", "short_name" => "DJ", "phone_code" => "253"],
            ["name" => "Dominica", "short_name" => "DM", "phone_code" => "1767"],
            ["name" => "Dominican Republic", "short_name" => "DO", "phone_code" => "1809"],
            ["name" => "Ecuador", "short_name" => "EC", "phone_code" => "593"],
            ["name" => "Egypt", "short_name" => "EG", "phone_code" => "20"],
            ["name" => "El Salvador", "short_name" => "SV", "phone_code" => "503"],
            ["name" => "Equatorial Guinea", "short_name" => "GQ", "phone_code" => "240"],
            ["name" => "Eritrea", "short_name" => "ER", "phone_code" => "291"],
            ["name" => "Estonia", "short_name" => "EE", "phone_code" => "372"],
            ["name" => "Eswatini", "short_name" => "SZ", "phone_code" => "268"],
            ["name" => "Ethiopia", "short_name" => "ET", "phone_code" => "251"],
            ["name" => "Fiji", "short_name" => "FJ", "phone_code" => "679"],
            ["name" => "Finland", "short_name" => "FI", "phone_code" => "358"],
            ["name" => "France", "short_name" => "FR", "phone_code" => "33"],
            ["name" => "Gabon", "short_name" => "GA", "phone_code" => "241"],
            ["name" => "Gambia", "short_name" => "GM", "phone_code" => "220"],
            ["name" => "Georgia", "short_name" => "GE", "phone_code" => "995"],
            ["name" => "Germany", "short_name" => "DE", "phone_code" => "49"],
            ["name" => "Ghana", "short_name" => "GH", "phone_code" => "233"],
            ["name" => "Greece", "short_name" => "GR", "phone_code" => "30"],
            ["name" => "Grenada", "short_name" => "GD", "phone_code" => "1473"],
            ["name" => "Guatemala", "short_name" => "GT", "phone_code" => "502"],
            ["name" => "Guinea", "short_name" => "GN", "phone_code" => "224"],
            ["name" => "Guinea-Bissau", "short_name" => "GW", "phone_code" => "245"],
            ["name" => "Guyana", "short_name" => "GY", "phone_code" => "592"],
            ["name" => "Haiti", "short_name" => "HT", "phone_code" => "509"],
            ["name" => "Honduras", "short_name" => "HN", "phone_code" => "504"],
            ["name" => "Hungary", "short_name" => "HU", "phone_code" => "36"],
            ["name" => "Iceland", "short_name" => "IS", "phone_code" => "354"],
            ["name" => "India", "short_name" => "IN", "phone_code" => "91"],
            ["name" => "Indonesia", "short_name" => "ID", "phone_code" => "62"],
            ["name" => "Iran", "short_name" => "IR", "phone_code" => "98"],
            ["name" => "Iraq", "short_name" => "IQ", "phone_code" => "964"],
            ["name" => "Ireland", "short_name" => "IE", "phone_code" => "353"],
            ["name" => "Israel", "short_name" => "IL", "phone_code" => "972"],
            ["name" => "Italy", "short_name" => "IT", "phone_code" => "39"],
            ["name" => "Jamaica", "short_name" => "JM", "phone_code" => "1876"],
            ["name" => "Japan", "short_name" => "JP", "phone_code" => "81"],
            ["name" => "Jordan", "short_name" => "JO", "phone_code" => "962"],
            ["name" => "Kazakhstan", "short_name" => "KZ", "phone_code" => "7"],
            ["name" => "Kenya", "short_name" => "KE", "phone_code" => "254"],
            ["name" => "Kiribati", "short_name" => "KI", "phone_code" => "686"],
            ["name" => "Kuwait", "short_name" => "KW", "phone_code" => "965"],
            ["name" => "Kyrgyzstan", "short_name" => "KG", "phone_code" => "996"],
            ["name" => "Laos", "short_name" => "LA", "phone_code" => "856"],
            ["name" => "Latvia", "short_name" => "LV", "phone_code" => "371"],
            ["name" => "Lebanon", "short_name" => "LB", "phone_code" => "961"],
            ["name" => "Lesotho", "short_name" => "LS", "phone_code" => "266"],
            ["name" => "Liberia", "short_name" => "LR", "phone_code" => "231"],
            ["name" => "Libya", "short_name" => "LY", "phone_code" => "218"],
            ["name" => "Liechtenstein", "short_name" => "LI", "phone_code" => "423"],
            ["name" => "Lithuania", "short_name" => "LT", "phone_code" => "370"],
            ["name" => "Luxembourg", "short_name" => "LU", "phone_code" => "352"],
            ["name" => "Madagascar", "short_name" => "MG", "phone_code" => "261"],
            ["name" => "Malawi", "short_name" => "MW", "phone_code" => "265"],
            ["name" => "Malaysia", "short_name" => "MY", "phone_code" => "60"],
            ["name" => "Maldives", "short_name" => "MV", "phone_code" => "960"],
            ["name" => "Mali", "short_name" => "ML", "phone_code" => "223"],
            ["name" => "Malta", "short_name" => "MT", "phone_code" => "356"],
            ["name" => "Marshall Islands", "short_name" => "MH", "phone_code" => "692"],
            ["name" => "Mauritania", "short_name" => "MR", "phone_code" => "222"],
            ["name" => "Mauritius", "short_name" => "MU", "phone_code" => "230"],
            ["name" => "Mexico", "short_name" => "MX", "phone_code" => "52"],
            ["name" => "Micronesia", "short_name" => "FM", "phone_code" => "691"],
            ["name" => "Moldova", "short_name" => "MD", "phone_code" => "373"],
            ["name" => "Monaco", "short_name" => "MC", "phone_code" => "377"],
            ["name" => "Mongolia", "short_name" => "MN", "phone_code" => "976"],
            ["name" => "Montenegro", "short_name" => "ME", "phone_code" => "382"],
            ["name" => "Morocco", "short_name" => "MA", "phone_code" => "212"],
            ["name" => "Mozambique", "short_name" => "MZ", "phone_code" => "258"],
            ["name" => "Myanmar", "short_name" => "MM", "phone_code" => "95"],
            ["name" => "Namibia", "short_name" => "NA", "phone_code" => "264"],
            ["name" => "Nauru", "short_name" => "NR", "phone_code" => "674"],
            ["name" => "Nepal", "short_name" => "NP", "phone_code" => "977"],
            ["name" => "Netherlands", "short_name" => "NL", "phone_code" => "31"],
            ["name" => "New Zealand", "short_name" => "NZ", "phone_code" => "64"],
            ["name" => "Nicaragua", "short_name" => "NI", "phone_code" => "505"],
            ["name" => "Niger", "short_name" => "NE", "phone_code" => "227"],
            ["name" => "Nigeria", "short_name" => "NG", "phone_code" => "234"],
            ["name" => "North Korea", "short_name" => "KP", "phone_code" => "850"],
            ["name" => "North Macedonia", "short_name" => "MK", "phone_code" => "389"],
            ["name" => "Norway", "short_name" => "NO", "phone_code" => "47"],
            ["name" => "Oman", "short_name" => "OM", "phone_code" => "968"],
            ["name" => "Pakistan", "short_name" => "PK", "phone_code" => "92"],
            ["name" => "Palau", "short_name" => "PW", "phone_code" => "680"],
            ["name" => "Palestine", "short_name" => "PS", "phone_code" => "970"],
            ["name" => "Panama", "short_name" => "PA", "phone_code" => "507"],
            ["name" => "Papua New Guinea", "short_name" => "PG", "phone_code" => "675"],
            ["name" => "Paraguay", "short_name" => "PY", "phone_code" => "595"],
            ["name" => "Peru", "short_name" => "PE", "phone_code" => "51"],
            ["name" => "Philippines", "short_name" => "PH", "phone_code" => "63"],
            ["name" => "Poland", "short_name" => "PL", "phone_code" => "48"],
            ["name" => "Portugal", "short_name" => "PT", "phone_code" => "351"],
            ["name" => "Qatar", "short_name" => "QA", "phone_code" => "974"],
            ["name" => "Romania", "short_name" => "RO", "phone_code" => "40"],
            ["name" => "Russia", "short_name" => "RU", "phone_code" => "7"],
            ["name" => "Rwanda", "short_name" => "RW", "phone_code" => "250"],
            ["name" => "Saint Kitts and Nevis", "short_name" => "KN", "phone_code" => "1869"],
            ["name" => "Saint Lucia", "short_name" => "LC", "phone_code" => "1758"],
            ["name" => "Saint Vincent and the Grenadines", "short_name" => "VC", "phone_code" => "1784"],
            ["name" => "Samoa", "short_name" => "WS", "phone_code" => "685"],
            ["name" => "San Marino", "short_name" => "SM", "phone_code" => "378"],
            ["name" => "Sao Tome and Principe", "short_name" => "ST", "phone_code" => "239"],
            ["name" => "Saudi Arabia", "short_name" => "SA", "phone_code" => "966"],
            ["name" => "Senegal", "short_name" => "SN", "phone_code" => "221"],
            ["name" => "Serbia", "short_name" => "RS", "phone_code" => "381"],
            ["name" => "Seychelles", "short_name" => "SC", "phone_code" => "248"],
            ["name" => "Sierra Leone", "short_name" => "SL", "phone_code" => "232"],
            ["name" => "Singapore", "short_name" => "SG", "phone_code" => "65"],
            ["name" => "Slovakia", "short_name" => "SK", "phone_code" => "421"],
            ["name" => "Slovenia", "short_name" => "SI", "phone_code" => "386"],
            ["name" => "Solomon Islands", "short_name" => "SB", "phone_code" => "677"],
            ["name" => "Somalia", "short_name" => "SO", "phone_code" => "252"],
            ["name" => "South Africa", "short_name" => "ZA", "phone_code" => "27"],
            ["name" => "South Korea", "short_name" => "KR", "phone_code" => "82"],
            ["name" => "South Sudan", "short_name" => "SS", "phone_code" => "211"],
            ["name" => "Spain", "short_name" => "ES", "phone_code" => "34"],
            ["name" => "Sri Lanka", "short_name" => "LK", "phone_code" => "94"],
            ["name" => "Sudan", "short_name" => "SD", "phone_code" => "249"],
            ["name" => "Suriname", "short_name" => "SR", "phone_code" => "597"],
            ["name" => "Sweden", "short_name" => "SE", "phone_code" => "46"],
            ["name" => "Switzerland", "short_name" => "CH", "phone_code" => "41"],
            ["name" => "Syria", "short_name" => "SY", "phone_code" => "963"],
            ["name" => "Taiwan", "short_name" => "TW", "phone_code" => "886"],
            ["name" => "Tajikistan", "short_name" => "TJ", "phone_code" => "992"],
            ["name" => "Tanzania", "short_name" => "TZ", "phone_code" => "255"],
            ["name" => "Thailand", "short_name" => "TH", "phone_code" => "66"],
            ["name" => "Timor-Leste", "short_name" => "TL", "phone_code" => "670"],
            ["name" => "Togo", "short_name" => "TG", "phone_code" => "228"],
            ["name" => "Tonga", "short_name" => "TO", "phone_code" => "676"],
            ["name" => "Trinidad and Tobago", "short_name" => "TT", "phone_code" => "1868"],
            ["name" => "Tunisia", "short_name" => "TN", "phone_code" => "216"],
            ["name" => "Turkey", "short_name" => "TR", "phone_code" => "90"],
            ["name" => "Turkmenistan", "short_name" => "TM", "phone_code" => "993"],
            ["name" => "Tuvalu", "short_name" => "TV", "phone_code" => "688"],
            ["name" => "Uganda", "short_name" => "UG", "phone_code" => "256"],
            ["name" => "Ukraine", "short_name" => "UA", "phone_code" => "380"],
            ["name" => "United Arab Emirates", "short_name" => "AE", "phone_code" => "971"],
            ["name" => "United Kingdom", "short_name" => "GB", "phone_code" => "44"],
            ["name" => "United States", "short_name" => "US", "phone_code" => "1"],
            ["name" => "Uruguay", "short_name" => "UY", "phone_code" => "598"],
            ["name" => "Uzbekistan", "short_name" => "UZ", "phone_code" => "998"],
            ["name" => "Vanuatu", "short_name" => "VU", "phone_code" => "678"],
            ["name" => "Vatican City", "short_name" => "VA", "phone_code" => "379"],
            ["name" => "Venezuela", "short_name" => "VE", "phone_code" => "58"],
            ["name" => "Vietnam", "short_name" => "VN", "phone_code" => "84"],
            ["name" => "Yemen", "short_name" => "YE", "phone_code" => "967"],
            ["name" => "Zambia", "short_name" => "ZM", "phone_code" => "260"],
            ["name" => "Zimbabwe", "short_name" => "ZW", "phone_code" => "263"],
        ];

        Country::truncate();
        foreach ($countries as $item) {
            $country = new Country();
            $country->name = $item['name'];
            $country->short_name = $item['short_name'];
            $country->phone_code = $item['phone_code'];
            $country->save();
        }
    }
}

if (!function_exists('determineRangeType')) {
    function determineRangeType($startDate, $endDate)
    {
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();
        $last7Days = Carbon::now()->subDays(6)->toDateString(); // 7 days includes today
        $last30Days = Carbon::now()->subDays(29)->toDateString();
        $firstDayOfThisMonth = Carbon::now()->firstOfMonth()->toDateString();
        $lastDayOfLastMonth = Carbon::now()->subMonth()->endOfMonth()->toDateString();
        $firstDayOfLastMonth = Carbon::now()->subMonth()->firstOfMonth()->toDateString();

        if ($startDate === $today && $endDate === $today) {
            return 'Today';
        } elseif ($startDate === $yesterday && $endDate === $yesterday) {
            return 'Yesterday';
        } elseif ($startDate === $last7Days && $endDate === $today) {
            return 'Last 7 Days';
        } elseif ($startDate === $last30Days && $endDate === $today) {
            return 'Last 30 Days';
        } elseif ($startDate === $firstDayOfThisMonth && $endDate === $today) {
            return 'This Month';
        } elseif ($startDate === $firstDayOfLastMonth && $endDate === $lastDayOfLastMonth) {
            return 'Last Month';
        } else {
            return 'Custom';
        }
    }
}


if (!function_exists('reportData')) {
    function reportData($date_range = null)
    {
        $startDate = Carbon::now()->subDays(6)->toDateString();
        $endDate = Carbon::today()->toDateString();

        if (isset($date_range)) {
            $dateRange = $date_range;
            list($startDateString, $endDateString) = explode("-", $dateRange);
            $startDate = date("Y-m-d", strtotime($startDateString));
            $endDate = date("Y-m-d 23:59:59", strtotime($endDateString));
        }

        $rangeType = determineRangeType($startDate, $endDate); //keep the commented line

        $totalSale = Payment::selectRaw("
                DATE(pdata.date) as t_date,
                SUM(CAST(pdata.paid_amount AS DECIMAL(10,2))) as total_amount
            ")
            ->from(DB::raw("
                payments,
                JSON_TABLE(
                    payments.paymentData,
                    '$[*]' COLUMNS(
                        paid_amount DECIMAL(10,2) PATH '$.paid_amount',
                        date DATETIME PATH '$.date'
                    )
                ) as pdata
            "))
            ->whereBetween('pdata.date', [$startDate, $endDate])
            ->groupBy('t_date')
            ->orderBy('t_date', 'asc')
            ->get();

        $totalAirline = Payment::selectRaw("
            DATE(payments.invoice_date) as t_date,
            airlines.name as airline_name,
            COUNT(*) as airline_count
        ")
        ->join('airlines', 'airlines.id', '=', 'payments.airline_id')
        ->whereBetween('payments.invoice_date', [$startDate, $endDate])
        ->groupBy('t_date', 'airlines.name')
        ->orderBy('t_date', 'asc')
        ->get()
        ->map(function ($item) {
            return [
                'date_airline' => $item->t_date . "\n(" . $item->airline_name . ")", // ✅ correct newline
                'airline_count' => $item->airline_count,
            ];
        });


        $totalTransitCity = TicketFlight::where('is_transit', 0)
            ->whereNotNull('parent_id')
            ->whereBetween('departure_date_time', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($flight) {
                // Format group key as Y-m-d + city
                $date = date('Y-m-d', strtotime($flight->departure_date_time));
                return $date . "\n(" . $flight->from_city . ")";
            })
            ->map(function ($group, $key) {
                return [
                    'city_date' => $key,
                    'total_transit_city' => $group->count(),
                ];
            })
            // ✅ Sort by date extracted from the key before reindexing
            ->sortBy(function ($item) {
                return strtotime(explode("\n", $item['city_date'])[0]);
            })
            ->values();

        $totalDepartureCity = TicketFlight::where('is_transit', 1)
            ->whereNull('parent_id')
            ->whereBetween('departure_date_time', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($flight) {
                // Group by formatted date + city
                $date = date('Y-m-d', strtotime($flight->departure_date_time));
                return $date . "\n(" . $flight->from_city . ")";
            })
            ->map(function ($group, $key) {
                return [
                    'city_date' => $key,
                    'total_departure_city' => $group->count(),
                ];
            })
            // ✅ Sort by date portion before reindexing
            ->sortBy(function ($item) {
                return strtotime(explode("\n", $item['city_date'])[0]);
            })
            ->values();
        
        $roundTripIds = Ticket::where('trip_type', 'Round Trip')->pluck('id')->toArray();
        $totalReturnCity = TicketFlight::where('is_transit', 1)
            ->whereNull('parent_id')
            ->whereIn('ticket_id', $roundTripIds)
            ->whereBetween('departure_date_time', [$startDate, $endDate])
            ->get()
            ->groupBy('ticket_id') // Group by ticket to get last flight per ticket
            ->map(function ($flights, $ticketId) {
                // Get the last flight (max departure_date_time)
                $lastFlight = $flights->sortByDesc('departure_date_time')->first();

                $date = date('Y-m-d', strtotime($lastFlight->departure_date_time));
                $city = $lastFlight->to_city; // Use accessor to extract only city name

                return [
                    'city_date' => $date . "\n(" . $city . ")",
                    'total_departure_city' => 1, // 1 per ticket
                ];
            })
            ->groupBy('city_date') // Combine same city/date counts
            ->map(function ($group, $key) {
                return [
                    'city_date' => $key,
                    'total_departure_city' => $group->count(),
                ];
            })
            ->sortBy(function ($item) {
                return strtotime(explode("\n", $item['city_date'])[0]);
            })
            ->values();



        $totalIntroductionSource = Payment::selectRaw("
                DATE(payments.invoice_date) as t_date,
                introduction_sources.name as r_title,
                COUNT(*) as total_count
            ")
            ->join('introduction_sources', 'introduction_sources.id', '=', 'payments.introduction_source_id')
            ->whereBetween('payments.invoice_date', [$startDate, $endDate])
            ->groupBy('t_date', 'introduction_sources.name')
            ->orderBy('t_date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'title_date' => $item->t_date . "\n(" . $item->r_title . ")", // ✅ correct newline
                    'total_count' => $item->total_count,
                ];
            });

        $totalIssuedSupplier = Payment::selectRaw("
                DATE(payments.invoice_date) as t_date,
                issued_suppliers.name as r_title,
                COUNT(*) as total_count
            ")
            ->join('issued_suppliers', function ($join) {
                $join->whereRaw("JSON_CONTAINS(payments.issued_supplier_ids, JSON_QUOTE(CAST(issued_suppliers.id AS CHAR)))");
            })
            ->whereBetween('payments.invoice_date', [$startDate, $endDate])
            ->groupBy('t_date', 'issued_suppliers.name')
            ->orderBy('t_date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'title_date' => $item->t_date . "\n(" . $item->r_title . ")", // same newline format
                    'total_count' => $item->total_count,
                ];
            });

        $totalCountry = Payment::selectRaw("
                DATE(payments.invoice_date) as t_date,
                countries.name as r_title,
                COUNT(*) as total_count
            ")
            ->join('countries', 'countries.id', '=', 'payments.customer_country_id')
            ->whereBetween('payments.invoice_date', [$startDate, $endDate])
            ->groupBy('t_date', 'countries.name')
            ->orderBy('t_date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'title_date' => $item->t_date . "\n(" . $item->r_title . ")", // ✅ same structure
                    'total_count' => $item->total_count,
                ];
            });

        $totalIssuedBy = Payment::selectRaw("
                DATE(payments.invoice_date) as t_date,
                issued_bies.name as r_title,
                COUNT(*) as total_count
            ")
            ->join('issued_bies', 'issued_bies.id', '=', 'payments.issued_by_id')
            ->whereBetween('payments.invoice_date', [$startDate, $endDate])
            ->groupBy('t_date', 'issued_bies.name')
            ->orderBy('t_date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'title_date' => $item->t_date . "\n(" . $item->r_title . ")", // same format
                    'total_count' => $item->total_count,
                ];
            });
        
        $totalTransferTo = Payment::selectRaw("
                DATE(payments.invoice_date) as t_date,
                transfer_tos.name as r_title,
                COUNT(*) as total_count
            ")
            ->join('transfer_tos', 'transfer_tos.id', '=', 'payments.transfer_to_id')
            ->whereBetween('payments.invoice_date', [$startDate, $endDate])
            ->groupBy('t_date', 'transfer_tos.name')
            ->orderBy('t_date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'title_date' => $item->t_date . "\n(" . $item->r_title . ")", // ✅ same format
                    'total_count' => $item->total_count,
                ];
            });

        $totalPaymentMethod = Payment::selectRaw("
                DATE(payments.invoice_date) as t_date,
                payment_methods.name as r_title,
                COUNT(*) as total_count
            ")
            ->join('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->whereBetween('payments.invoice_date', [$startDate, $endDate])
            ->groupBy('t_date', 'payment_methods.name')
            ->orderBy('t_date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'title_date' => $item->t_date . "\n(" . $item->r_title . ")", // ✅ same newline formatting
                    'total_count' => $item->total_count,
                ];
            });

        $totalCardType = Payment::selectRaw("
                DATE(payments.invoice_date) as t_date,
                issued_card_types.name as r_title,
                COUNT(*) as total_count
            ")
            ->join('issued_card_types', 'issued_card_types.id', '=', 'payments.issued_card_type_id')
            ->whereBetween('payments.invoice_date', [$startDate, $endDate])
            ->groupBy('t_date', 'issued_card_types.name')
            ->orderBy('t_date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'title_date' => $item->t_date . "\n(" . $item->r_title . ")", // ✅ same newline format
                    'total_count' => $item->total_count,
                ];
            });

        $totalCardOwner = Payment::selectRaw("
                DATE(payments.invoice_date) as t_date,
                card_owners.name as r_title,
                COUNT(*) as total_count
            ")
            ->join('card_owners', 'card_owners.id', '=', 'payments.card_owner_id')
            ->whereBetween('payments.invoice_date', [$startDate, $endDate])
            ->groupBy('t_date', 'card_owners.name')
            ->orderBy('t_date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'title_date' => $item->t_date . "\n(" . $item->r_title . ")", // ✅ correct newline
                    'total_count' => $item->total_count,
                ];
            });

        $totalTripTypePie = Payment::selectRaw("
                trip_type as title,
                COUNT(*) as count
            ")
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->whereIn('trip_type', ['One Way', 'Round Trip', 'Multi City']) // ensure valid types
            ->groupBy('trip_type')
            ->orderByRaw("FIELD(trip_type, 'One Way', 'Round Trip', 'Multi City')")
            ->get();

        $totalPaymentMethodPie = Payment::selectRaw("
                payment_methods.name as title,
                COUNT(*) as count
            ")
            ->join('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->whereBetween('payments.invoice_date', [$startDate, $endDate])
            ->groupBy('payment_methods.name')
            ->orderBy('payment_methods.name', 'asc')
            ->get();

        $totalIssuedCardTypePie = Payment::selectRaw("
                issued_card_types.name as title,
                COUNT(*) as count
            ")
            ->join('issued_card_types', 'issued_card_types.id', '=', 'payments.issued_card_type_id')
            ->whereBetween('payments.invoice_date', [$startDate, $endDate])
            ->groupBy('issued_card_types.name')
            ->orderBy('issued_card_types.name', 'asc')
            ->get();

        $totalPaymentStatusPie = Payment::selectRaw("
                payment_status as title,
                COUNT(*) as count
            ")
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->whereIn('payment_status', ['Unpaid', 'Paid', 'Partial', 'Unknown']) // ensure valid statuses
            ->groupBy('payment_status')
            ->orderByRaw("FIELD(payment_status, 'Unpaid', 'Paid', 'Partial', 'Unknown')")
            ->get();






        return [
            'rangeType' => $rangeType,
            'totalSale' => $totalSale,
            'totalAirline' => $totalAirline,
            'totalTransitCity' => $totalTransitCity,
            'totalDepartureCity' => $totalDepartureCity,
            'totalReturnCity' => $totalReturnCity,
            'totalIntroductionSource' => $totalIntroductionSource,
            'totalIssuedSupplier' => $totalIssuedSupplier,
            'totalCountry' => $totalCountry,
            'totalIssuedBy' => $totalIssuedBy,
            'totalTransferTo' => $totalTransferTo,
            'totalPaymentMethod' => $totalPaymentMethod,
            'totalCardType' => $totalCardType,
            'totalCardOwner' => $totalCardOwner,

            'totalTripTypePie' => $totalTripTypePie,
            'totalPaymentMethodPie' => $totalPaymentMethodPie,
            'totalIssuedCardTypePie' => $totalIssuedCardTypePie,
            'totalPaymentStatusPie' => $totalPaymentStatusPie,
        ];
    }
}


if (!function_exists('toDoListData')) {
    function toDoListData($date_range = null)
    {
        $startDate = Carbon::now()->toDateString();
        $endDate = Carbon::today()->addDays(30)->toDateString();

        if (isset($date_range)) {
            $dateRange = $date_range;
            list($startDateString, $endDateString) = explode("-", $dateRange);
            $startDate = date("Y-m-d", strtotime($startDateString));
            $endDate = date("Y-m-d 23:59:59", strtotime($endDateString));
        }

        $rangeType = determineRangeType($startDate, $endDate); //keep the commented line

        $toDoData = Payment::with('ticket', 'country', 'issuedBy', 'airline')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('departure_date_time', [$startDate, $endDate])
                    ->orWhereBetween('return_date_time', [$startDate, $endDate]);
            })
            ->where(function ($query) {
                $query->where('seat_confirmation', 'Not Chosen')
                    ->orWhereNull('seat_confirmation')

                    ->orWhere('mobility_assistance', 'Not Chosen')
                    ->orWhereNull('mobility_assistance')

                    ->orWhere('transit_visa_application', 'Need To Do')
                    ->orWhereNull('transit_visa_application')

                    ->orWhere('halal_meal_request', 'Need To Do')
                    ->orWhereNull('halal_meal_request')

                    ->orWhere('transit_hotel', 'Need To Do')
                    ->orWhereNull('transit_hotel');
            })
            ->orderByRaw("
                CASE
                    WHEN departure_date_time IS NOT NULL THEN departure_date_time
                    ELSE return_date_time
                END ASC
            ")
            ->get();

        return $toDoData;
    }
}


if (!function_exists('flightListData')) {
    function flightListData($date_range = null)
    {
        $startDate = Carbon::now()->toDateString();
        $endDate = Carbon::today()->addDays(30)->toDateString();

        if (isset($date_range)) {
            $dateRange = $date_range;
            list($startDateString, $endDateString) = explode("-", $dateRange);
            $startDate = date("Y-m-d", strtotime($startDateString));
            $endDate = date("Y-m-d 23:59:59", strtotime($endDateString));
        }

        $rangeType = determineRangeType($startDate, $endDate); //keep the commented line

        $toDoData = Payment::with('ticket', 'country', 'issuedBy', 'airline')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('departure_date_time', [$startDate, $endDate])
                    ->orWhereBetween('return_date_time', [$startDate, $endDate]);
            })
            ->orderByRaw("
                CASE
                    WHEN departure_date_time IS NOT NULL THEN departure_date_time
                    ELSE return_date_time
                END ASC
            ")
            ->get();

        return $toDoData;
    }
}


if (!function_exists('getDateRange')) {
    function getDateRange($day = 6, $type = 'Previous')
    {
        if ($type === 'Previous') {
            $start = Carbon::today()->subDays($day);
            $end = Carbon::today();
        } elseif ($type === 'Next') {
            $start = Carbon::today();
            $end = Carbon::today()->addDays($day);
        } else {
            // Fallback in case of invalid type
            $start = Carbon::today()->subDays($day);
            $end = Carbon::today();
        }

        return $start->format('Y/m/d') . ' - ' . $end->format('Y/m/d');
    }
}

if (!function_exists('generateNotifications')) {
    function generateNotifications()
    {
        if (Auth::check()) {
            $missedPayments = Payment::where(function ($query) {
                    $query->whereRaw("
                        (
                            SELECT COALESCE(SUM(JSON_EXTRACT(value, '$.paid_amount')), 0)
                            FROM JSON_TABLE(paymentData, '$[*]' COLUMNS (value JSON PATH '$')) AS payments
                        ) < total_selling_price
                    ");
                })
                ->where('next_payment_deadline', '<', Carbon::now()->subDay()) // 1+ day passed
                ->get();

            $now = now();
            foreach ($missedPayments as $index => $payment) {
                $url = route('payment.show', $payment->id, false);

                $exists = Notification::where('user_id', Auth::user()->business_id)
                    ->where('url', $url)
                    ->whereDate('deadline', Carbon::parse($payment->next_payment_deadline)->toDateString())
                    ->exists();

                if (!$exists) {
                    $model = new Notification();
                    $model->user_id = Auth::user()->business_id;
                    $model->title = $payment->payment_invoice_id . ' - Missed Payment Deadline!';
                    $model->url = $url;
                    $model->deadline = $payment->next_payment_deadline;

                    // Add small offset per iteration to created_at
                    $model->created_at = $now->copy()->addSeconds($index);
                    $model->updated_at = $now->copy()->addSeconds($index);

                    $model->save();
                }
            }
        }
    }
}


if (!function_exists('getNotifications')) {
    function getNotifications($seenStatus = 'all', $limit = null)
    {
        if (!Auth::check()) {
            return collect(); // return empty collection instead of null
        }

        $query = Notification::where('user_id', Auth::user()->business_id)
            ->orderByDesc('id');

        if ($seenStatus !== 'all') {
            $query->where('read_status', $seenStatus); // use read_status (consistent with your model)
        }

        if (!is_null($limit)) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
