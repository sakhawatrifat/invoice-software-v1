<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            0 => [
                'currency_name' => 'U.S. Dollar',
                'symbol' => '$',
                'short_name' => 'USD',
                'exchange_rate' => 0
            ],
            1 => [
                'currency_name' => 'Australian Dollar',
                'symbol' => '$',
                'short_name' => 'AUD',
                'exchange_rate' => 0
            ],
            2 => [
                'currency_name' => 'Brazilian Real',
                'symbol' => 'R$',
                'short_name' => 'BRL',
                'exchange_rate' => 0
            ],
            3 => [
                'currency_name' => 'Canadian Dollar',
                'symbol' => '$',
                'short_name' => 'CAD',
                'exchange_rate' => 0
            ],
            4 => [
                'currency_name' => 'Czech Koruna',
                'symbol' => 'Kč',
                'short_name' => 'CZK',
                'exchange_rate' => 0
            ],
            5 => [
                'currency_name' => 'Danish Krone',
                'symbol' => 'kr',
                'short_name' => 'DKK',
                'exchange_rate' => 0
            ],
            6 => [
                'currency_name' => 'Euro',
                'symbol' => '€',
                'short_name' => 'EUR',
                'exchange_rate' => 0
            ],
            7 => [
                'currency_name' => 'Hong Kong Dollar',
                'symbol' => '$',
                'short_name' => 'HKD',
                'exchange_rate' => 0
            ],
            8 => [
                'currency_name' => 'Hungarian Forint',
                'symbol' => 'Ft',
                'short_name' => 'HUF',
                'exchange_rate' => 0
            ],
            9 => [
                'currency_name' => 'Israeli New Sheqel',
                'symbol' => '₪',
                'short_name' => 'ILS',
                'exchange_rate' => 0
            ],
            10 => [
                'currency_name' => 'Japanese Yen',
                'symbol' => '¥',
                'short_name' => 'JPY',
                'exchange_rate' => 0
            ],
            11 => [
                'currency_name' => 'Malaysian Ringgit',
                'symbol' => 'RM',
                'short_name' => 'MYR',
                'exchange_rate' => 0
            ],
            12 => [
                'currency_name' => 'Mexican Peso',
                'symbol' => '$',
                'short_name' => 'MXN',
                'exchange_rate' => 0
            ],
            13 => [
                'currency_name' => 'Norwegian Krone',
                'symbol' => 'kr',
                'short_name' => 'NOK',
                'exchange_rate' => 0
            ],
            14 => [
                'currency_name' => 'New Zealand Dollar',
                'symbol' => '$',
                'short_name' => 'NZD',
                'exchange_rate' => 0
            ],
            15 => [
                'currency_name' => 'Philippine Peso',
                'symbol' => '₱',
                'short_name' => 'PHP',
                'exchange_rate' => 0
            ],
            16 => [
                'currency_name' => 'Polish Zloty',
                'symbol' => 'zł',
                'short_name' => 'PLN',
                'exchange_rate' => 0
            ],
            17 => [
                'currency_name' => 'Pound Sterling',
                'symbol' => '£',
                'short_name' => 'GBP',
                'exchange_rate' => 0
            ],
            18 => [
                'currency_name' => 'Russian Ruble',
                'symbol' => 'руб',
                'short_name' => 'RUB',
                'exchange_rate' => 0
            ],
            19 => [
                'currency_name' => 'Singapore Dollar',
                'symbol' => '$',
                'short_name' => 'SGD',
                'exchange_rate' => 0
            ],
            20 => [
                'currency_name' => 'Swedish Krona',
                'symbol' => 'kr',
                'short_name' => 'SEK',
                'exchange_rate' => 0
            ],
            21 => [
                'currency_name' => 'Swiss Franc',
                'symbol' => 'CHF',
                'short_name' => 'CHF',
                'exchange_rate' => 0
            ],
            22 => [
                'currency_name' => 'Thai Baht',
                'symbol' => '฿',
                'short_name' => 'THB',
                'exchange_rate' => 0
            ],
            23 => [
                'currency_name' => 'Taka',
                'symbol' => '৳',
                'short_name' => 'BDT',
                'exchange_rate' => 0
            ],
            24 => [
                'currency_name' => 'Indian Rupee',
                'symbol' => 'Rs',
                'short_name' => 'Rupee',
                'exchange_rate' => 0
            ]
        ];


        // $currencies = [
        //     0 => [
        //         'currency_name' => 'Japanese Yen',
        //         'symbol' => '¥',
        //         'short_name' => 'JPY',
        //         'exchange_rate' => 0
        //     ],
        //     1 => [
        //         'currency_name' => 'Taka',
        //         'symbol' => '৳',
        //         'short_name' => 'BDT',
        //         'exchange_rate' => 0
        //     ],
        // ];

        Currency::truncate();
        foreach($currencies as $key => $item){
            $myModel = new Currency();
            $myModel->currency_name = $item['currency_name'];
            $myModel->symbol = $item['symbol'];
            $myModel->short_name = $item['short_name'];
            $myModel->exchange_rate = $item['exchange_rate'];
            $myModel->status = 'Active';
            $myModel->save();
        }

    }
}
