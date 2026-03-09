<?php

/**
 * Airport name / city variations mapped to IATA codes for flight status API.
 * Used when DB stores full names (e.g. "hazrat shahjalal international airport") without (DAC).
 * Add more entries as needed; names are matched case-insensitive; longest match wins.
 *
 * @return array<int, array{iata: string, names: array<string>}>
 */
return [
    // Bangladesh
    ['iata' => 'DAC', 'names' => ['hazrat shahjalal international airport', 'shahjalal international airport', 'shahjalal international', 'dhaka international airport', 'dhaka international', 'dhaka intl apt', 'dhaka', 'zia international', 'zia international airport']],
    ['iata' => 'CGP', 'names' => ['shah amanat international', 'chittagong international', 'chittagong', 'patenga']],

    // Japan
    ['iata' => 'NRT', 'names' => ['narita international', 'narita', 'tokyo narita', 'tokyo narita international airport', 'new tokyo international']],
    ['iata' => 'HND', 'names' => ['haneda airport', 'haneda', 'tokyo haneda', 'tokyo international airport']],
    ['iata' => 'KIX', 'names' => ['kansai international', 'kansai intl apt', 'kansai', 'osaka kansai', 'osaka kansai international', 'kansai international airport']],
    ['iata' => 'NGO', 'names' => ['chubu centrair', 'nagoya chubu', 'chubu international', 'centrair']],
    ['iata' => 'FUK', 'names' => ['fukuoka airport', 'fukuoka']],
    ['iata' => 'CTS', 'names' => ['new chitose', 'chitose', 'sapporo new chitose', 'sapporo']],

    // China
    ['iata' => 'PEK', 'names' => ['beijing capital', 'beijing capital international', 'beijing capital int apt', 'beijing int apt', 'beijing intl apt', 'capital international airport', 'beijing']],
    ['iata' => 'PVG', 'names' => ['shanghai pudong', 'pudong international', 'pudong', 'shanghai pudong international']],
    ['iata' => 'SHA', 'names' => ['shanghai hongqiao', 'hongqiao', 'shanghai hongqiao international']],
    ['iata' => 'CAN', 'names' => ['guangzhou baiyun', 'baiyun international', 'guangzhou', 'baiyun']],
    ['iata' => 'CTU', 'names' => ['chengdu shuangliu', 'chengdu tianfu', 'chengdu', 'shuangliu']],
    ['iata' => 'SZX', 'names' => ['shenzhen baoan', 'shenzhen', 'baoan international']],
    ['iata' => 'KMG', 'names' => ['kunming changshui', 'kunming', 'changshui']],
    ['iata' => 'XIY', 'names' => ['xi an xianyang', 'xian xianyang', 'xian', 'xi an', 'xianyang']],
    ['iata' => 'CKG', 'names' => ['chongqing jiangbei', 'chongqing', 'jiangbei']],

    // India
    ['iata' => 'DEL', 'names' => ['indira gandhi international', 'delhi international', 'new delhi', 'delhi', 'igi airport']],
    ['iata' => 'BOM', 'names' => ['chhatrapati shivaji maharaj international', 'mumbai international', 'mumbai', 'sahar airport']],
    ['iata' => 'MAA', 'names' => ['chennai international', 'chennai', 'meenambakkam']],
    ['iata' => 'BLR', 'names' => ['kempegowda international', 'bengaluru international', 'bangalore', 'bengaluru']],
    ['iata' => 'HYD', 'names' => ['rajiv gandhi international', 'hyderabad international', 'hyderabad', 'shamshabad']],
    ['iata' => 'CCU', 'names' => ['netaji subhas chandra bose', 'kolkata international', 'kolkata', 'calcutta', 'dum dum']],

    // Middle East
    ['iata' => 'DXB', 'names' => ['dubai international', 'dubai', 'dubai intl']],
    ['iata' => 'AUH', 'names' => ['abu dhabi international', 'abu dhabi', 'abu dhabi intl']],
    ['iata' => 'DOH', 'names' => ['hamad international', 'doha international', 'doha', 'doha hamad']],
    ['iata' => 'BAH', 'names' => ['bahrain international', 'bahrain', 'muharraq']],
    ['iata' => 'RUH', 'names' => ['king khalid international', 'riyadh king khalid', 'riyadh', 'king khalid']],
    ['iata' => 'JED', 'names' => ['king abdulaziz international', 'jeddah', 'king abdulaziz']],
    ['iata' => 'TLV', 'names' => ['ben gurion', 'tel aviv ben gurion', 'tel aviv', 'ben gurion international']],
    ['iata' => 'IST', 'names' => ['istanbul airport', 'istanbul new airport', 'istanbul']],
    ['iata' => 'SAW', 'names' => ['sabiha gokcen', 'istanbul sabiha gokcen', 'sabiha gokcen international']],

    // Southeast Asia
    ['iata' => 'SIN', 'names' => ['singapore changi', 'changi airport', 'changi', 'singapore', 'singapore changi international']],
    ['iata' => 'BKK', 'names' => ['suvarnabhumi', 'bangkok suvarnabhumi', 'bangkok international', 'bangkok']],
    ['iata' => 'KUL', 'names' => ['klia', 'kuala lumpur international', 'kuala lumpur', 'klia2', 'klia 2']],
    ['iata' => 'CGK', 'names' => ['soekarno hatta', 'jakarta soekarno hatta', 'jakarta', 'soekarno-hatta']],
    ['iata' => 'MNL', 'names' => ['ninoy aquino international', 'manila ninoy aquino', 'manila', 'naia']],
    ['iata' => 'SGN', 'names' => ['tan son nhat', 'ho chi minh city', 'ho chi minh', 'saigon', 'tan son nhat international']],
    ['iata' => 'HAN', 'names' => ['noi bai international', 'hanoi', 'noi bai']],
    ['iata' => 'RGN', 'names' => ['yangon international', 'yangon', 'mingaladon']],
    ['iata' => 'PNH', 'names' => ['phnom penh international', 'phnom penh']],

    // Europe – UK & Ireland
    ['iata' => 'LHR', 'names' => ['london heathrow', 'heathrow', 'heathrow airport', 'london heathrow airport']],
    ['iata' => 'LGW', 'names' => ['london gatwick', 'gatwick', 'london gatwick airport']],
    ['iata' => 'STN', 'names' => ['london stansted', 'stansted', 'stansted airport']],
    ['iata' => 'MAN', 'names' => ['manchester airport', 'manchester international', 'manchester']],
    ['iata' => 'DUB', 'names' => ['dublin airport', 'dublin', 'dublin international']],
    ['iata' => 'EDI', 'names' => ['edinburgh airport', 'edinburgh']],
    ['iata' => 'BHX', 'names' => ['birmingham airport', 'birmingham international', 'birmingham uk']],
    ['iata' => 'GLA', 'names' => ['glasgow international', 'glasgow', 'glasgow airport']],

    // Europe – Western
    ['iata' => 'CDG', 'names' => ['paris charles de gaulle', 'charles de gaulle', 'paris cdg', 'roissy', 'paris charles de gaulle airport']],
    ['iata' => 'ORY', 'names' => ['paris orly', 'orly', 'orly airport', 'paris orly airport']],
    ['iata' => 'FRA', 'names' => ['frankfurt airport', 'frankfurt', 'frankfurt am main', 'frankfurt international']],
    ['iata' => 'MUC', 'names' => ['munich airport', 'munich', 'munich franz josef strauss', 'franz josef strauss']],
    ['iata' => 'AMS', 'names' => ['amsterdam schiphol', 'schiphol', 'amsterdam', 'amsterdam schiphol airport']],
    ['iata' => 'MAD', 'names' => ['madrid barajas', 'adolfo suarez madrid', 'madrid', 'barajas', 'madrid barajas airport']],
    ['iata' => 'BCN', 'names' => ['barcelona el prat', 'barcelona', 'el prat', 'barcelona el prat airport']],
    ['iata' => 'FCO', 'names' => ['rome fiumicino', 'fiumicino', 'rome', 'leonardo da vinci fiumicino', 'rome fiumicino airport']],
    ['iata' => 'MXP', 'names' => ['milan malpensa', 'malpensa', 'milan malpensa airport', 'milan']],
    ['iata' => 'ZRH', 'names' => ['zurich airport', 'zurich', 'zurich international']],
    ['iata' => 'VIE', 'names' => ['vienna international', 'vienna', 'vienna schwechat', 'schwechat']],
    ['iata' => 'BRU', 'names' => ['brussels airport', 'brussels national', 'brussels', 'brussels zaventem', 'zaventem']],
    ['iata' => 'CPH', 'names' => ['copenhagen airport', 'copenhagen', 'kastrup', 'copenhagen kastrup']],
    ['iata' => 'ARN', 'names' => ['stockholm arlanda', 'arlanda', 'stockholm', 'stockholm arlanda airport']],
    ['iata' => 'OSL', 'names' => ['oslo gardermoen', 'oslo', 'gardermoen', 'oslo airport']],
    ['iata' => 'HEL', 'names' => ['helsinki vantaa', 'helsinki', 'vantaa', 'helsinki vantaa airport']],
    ['iata' => 'LIS', 'names' => ['lisbon portela', 'lisbon', 'portela', 'lisbon airport']],

    // Europe – Eastern
    ['iata' => 'SVO', 'names' => ['sheremetyevo', 'moscow sheremetyevo', 'moscow', 'sheremetyevo international']],
    ['iata' => 'DME', 'names' => ['domodedovo', 'moscow domodedovo', 'domodedovo international']],
    ['iata' => 'WAW', 'names' => ['warsaw chopin', 'warsaw', 'chopin airport', 'warsaw chopin airport']],
    ['iata' => 'PRG', 'names' => ['prague vaclav havel', 'prague', 'vaclav havel', 'prague airport']],
    ['iata' => 'BUD', 'names' => ['budapest ferenc liszt', 'budapest', 'ferenc liszt', 'budapest airport']],
    ['iata' => 'ATH', 'names' => ['athens international', 'athens', 'eleftherios venizelos', 'athens airport']],

    // Americas – US
    ['iata' => 'JFK', 'names' => ['john f kennedy', 'jfk', 'new york jfk', 'kennedy international', 'new york kennedy']],
    ['iata' => 'EWR', 'names' => ['newark liberty', 'newark', 'newark liberty international', 'newark airport']],
    ['iata' => 'LAX', 'names' => ['los angeles international', 'los angeles', 'lax', 'la international']],
    ['iata' => 'ORD', 'names' => ["chicago o'hare", 'ohare', 'chicago ohare', 'o hare', 'chicago international']],
    ['iata' => 'MIA', 'names' => ['miami international', 'miami', 'miami airport']],
    ['iata' => 'SFO', 'names' => ['san francisco international', 'san francisco', 'sfo']],
    ['iata' => 'SEA', 'names' => ['seattle tacoma', 'seattle', 'sea-tac', 'tacoma international']],
    ['iata' => 'DFW', 'names' => ['dallas fort worth', 'dallas', 'fort worth', 'dfw airport']],
    ['iata' => 'ATL', 'names' => ['hartsfield jackson atlanta', 'atlanta', 'hartsfield jackson', 'atlanta international']],
    ['iata' => 'BOS', 'names' => ['boston logan', 'boston', 'logan international', 'boston logan international']],
    ['iata' => 'IAD', 'names' => ['washington dulles', 'dulles', 'washington dulles international']],
    ['iata' => 'DCA', 'names' => ['reagan national', 'washington reagan', 'washington national']],
    ['iata' => 'DEN', 'names' => ['denver international', 'denver', 'dia']],
    ['iata' => 'LAS', 'names' => ['harry reid international', 'las vegas', 'mccarran', 'las vegas international']],
    ['iata' => 'PHX', 'names' => ['phoenix sky harbor', 'phoenix', 'sky harbor', 'phoenix sky harbor international']],
    ['iata' => 'IAH', 'names' => ['george bush intercontinental', 'houston george bush', 'houston intercontinental', 'houston']],
    ['iata' => 'MCO', 'names' => ['orlando international', 'orlando', 'orlando airport']],
    ['iata' => 'MSP', 'names' => ['minneapolis saint paul', 'minneapolis', 'minneapolis st paul', 'st paul international']],
    ['iata' => 'DTW', 'names' => ['detroit metropolitan', 'detroit', 'detroit metro', 'wayne county']],
    ['iata' => 'PHL', 'names' => ['philadelphia international', 'philadelphia', 'philadelphia airport']],
    ['iata' => 'CLT', 'names' => ['charlotte douglas', 'charlotte', 'charlotte douglas international']],
    ['iata' => 'BWI', 'names' => ['baltimore washington international', 'bwi', 'baltimore', 'baltimore washington']],
    ['iata' => 'SLC', 'names' => ['salt lake city international', 'salt lake city', 'salt lake city airport']],
    ['iata' => 'TPA', 'names' => ['tampa international', 'tampa', 'tampa airport']],
    ['iata' => 'SAN', 'names' => ['san diego international', 'san diego', 'san diego airport', 'lindbergh field']],
    ['iata' => 'PDX', 'names' => ['portland international', 'portland', 'portland oregon', 'pdx']],

    // Americas – Canada
    ['iata' => 'YYZ', 'names' => ['toronto pearson', 'pearson international', 'toronto', 'toronto pearson international']],
    ['iata' => 'YVR', 'names' => ['vancouver international', 'vancouver', 'vancouver airport']],
    ['iata' => 'YUL', 'names' => ['montreal trudeau', 'montreal', 'pierre elliott trudeau', 'montreal trudeau international']],
    ['iata' => 'YYC', 'names' => ['calgary international', 'calgary', 'calgary airport']],

    // Americas – Latin America & Caribbean
    ['iata' => 'MEX', 'names' => ['mexico city international', 'mexico city', 'benito juarez', 'mexico city benito juarez']],
    ['iata' => 'GRU', 'names' => ['sao paulo guarulhos', 'guarulhos', 'sao paulo', 'guarulhos international']],
    ['iata' => 'EZE', 'names' => ['ezeiza', 'buenos aires ezeiza', 'buenos aires', 'ministro pistarini']],
    ['iata' => 'BOG', 'names' => ['el dorado', 'bogota', 'bogota el dorado', 'bogota international']],
    ['iata' => 'LIM', 'names' => ['jorge chavez', 'lima', 'lima international', 'jorge chavez international']],
    ['iata' => 'SCL', 'names' => ['santiago international', 'santiago', 'arturo merino benitez', 'santiago de chile']],
    ['iata' => 'PTY', 'names' => ['tocumen international', 'panama city', 'tocumen', 'panama']],
    ['iata' => 'HAV', 'names' => ['jose marti international', 'havana', 'havana jose marti']],
    ['iata' => 'SJU', 'names' => ['luis munoz marin', 'san juan', 'san juan international', 'puerto rico']],
    ['iata' => 'CUN', 'names' => ['cancun international', 'cancun', 'cancun airport']],

    // Africa
    ['iata' => 'JNB', 'names' => ['or tambo international', 'johannesburg', 'johannesburg international', 'or tambo']],
    ['iata' => 'CPT', 'names' => ['cape town international', 'cape town', 'cape town airport']],
    ['iata' => 'ADD', 'names' => ['addis ababa bole', 'addis ababa', 'bole international', 'addis ababa bole international']],
    ['iata' => 'NBO', 'names' => ['jomo kenyatta international', 'nairobi', 'nairobi jomo kenyatta', 'jomo kenyatta']],
    ['iata' => 'CAI', 'names' => ['cairo international', 'cairo', 'cairo airport']],
    ['iata' => 'CMN', 'names' => ['mohammed v international', 'casablanca', 'casablanca mohammed v', 'mohammed v']],
    ['iata' => 'LOS', 'names' => ['murtala muhammed international', 'lagos', 'lagos murtala muhammed', 'murtala muhammed']],
    ['iata' => 'ACC', 'names' => ['kotoka international', 'accra', 'accra kotoka', 'accra international']],
    ['iata' => 'DAR', 'names' => ['julius nyerere international', 'dar es salaam', 'dar es salaam international', 'julius nyerere']],

    // Oceania
    ['iata' => 'SYD', 'names' => ['sydney kingsford smith', 'sydney', 'sydney airport', 'kingsford smith']],
    ['iata' => 'MEL', 'names' => ['melbourne airport', 'melbourne', 'melbourne international', 'tullamarine']],
    ['iata' => 'BNE', 'names' => ['brisbane airport', 'brisbane', 'brisbane international']],
    ['iata' => 'AKL', 'names' => ['auckland airport', 'auckland', 'auckland international']],
    ['iata' => 'WLG', 'names' => ['wellington airport', 'wellington', 'wellington international']],
    ['iata' => 'NAN', 'names' => ['nadi international', 'nadi', 'nadi airport', 'fiji']],
    ['iata' => 'PPT', 'names' => ['faaa international', 'papeete', 'tahiti', 'papeete tahiti']],

    // Additional common / regional
    ['iata' => 'HKG', 'names' => ['hong kong international', 'hong kong', 'chek lap kok', 'hong kong airport']],
    ['iata' => 'ICN', 'names' => ['incheon international', 'seoul incheon', 'incheon', 'seoul', 'seoul incheon international']],
    ['iata' => 'GMP', 'names' => ['gimpo international', 'seoul gimpo', 'gimpo', 'gimpo airport']],
    ['iata' => 'TPE', 'names' => ['taoyuan international', 'taiwan taoyuan', 'taipei', 'taiwan taoyuan international', 'taoyuan']],
    ['iata' => 'KHH', 'names' => ['kaohsiung international', 'kaohsiung', 'kaohsiung airport']],
    ['iata' => 'MLE', 'names' => ['velana international', 'male', 'malé', 'maldives', 'male international', 'ibrahim nasir international']],
    ['iata' => 'CMB', 'names' => ['bandaranaike international', 'colombo', 'colombo bandaranaike', 'bandaranaike']],
    ['iata' => 'KTM', 'names' => ['tribhuvan international', 'kathmandu', 'kathmandu tribhuvan', 'tribhuvan']],
    ['iata' => 'ISB', 'names' => ['islamabad international', 'islamabad', 'benazir bhutto international', 'new islamabad']],
    ['iata' => 'KHI', 'names' => ['jinnah international', 'karachi', 'karachi jinnah', 'jinnah']],
    ['iata' => 'LHE', 'names' => ['allama iqbal international', 'lahore', 'lahore allama iqbal', 'allama iqbal']],
    ['iata' => 'DMM', 'names' => ['king fahd international', 'dammam', 'dammam king fahd', 'king fahd']],
    ['iata' => 'MCT', 'names' => ['muscat international', 'muscat', 'seeb international', 'oman']],
    ['iata' => 'KWI', 'names' => ['kuwait international', 'kuwait', 'kuwait airport']],
    ['iata' => 'LCA', 'names' => ['larnaca international', 'larnaca', 'cyprus']],
    ['iata' => 'PFO', 'names' => ['paphos international', 'paphos', 'paphos airport']],
    ['iata' => 'SSH', 'names' => ['sharm el sheikh', 'sharm el sheikh international', 'sharm el-sheikh']],
    ['iata' => 'HRG', 'names' => ['hurghada international', 'hurghada', 'hurghada airport']],
    ['iata' => 'DUR', 'names' => ['king shaka international', 'durban', 'durban king shaka']],
    ['iata' => 'GBE', 'names' => ['sir seretse khama international', 'gaborone', 'gaborone international']],
    ['iata' => 'LUN', 'names' => ['kenneth kaunda international', 'lusaka', 'lusaka international']],
    ['iata' => 'HRE', 'names' => ['robert gabriel mugabe international', 'harare', 'harare international']],
];
