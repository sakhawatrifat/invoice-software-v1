<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LeadSource;
use App\Models\User;

class CRMSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Attach default lead sources to the admin/business owner
        $adminUser = User::where('user_type', 'admin')->first();
        $businessId = $adminUser ? $adminUser->id : 1;

        $sources = [
            'Facebook Ads',
            'Google Ads',
            'Website',
            'Referral',
            'Walk-in',
            'Email Campaign',
            'Phone Call',
            'Other',
        ];

        foreach ($sources as $name) {
            $existing = LeadSource::where('name', $name)->first();

            if (!$existing) {
                $source = new LeadSource();
                $source->user_id = $businessId;
                $source->name = $name;
                $source->status = 1;
                $source->created_by = $businessId;
                $source->save();
            }
        }
    }
}

