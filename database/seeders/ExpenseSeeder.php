<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Auth;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user for created_by
        $adminUser = \App\Models\User::where('user_type', 'admin')->first();
        $createdBy = $adminUser ? $adminUser->id : 1;

        $expenseCategories = [
            [
                'name' => 'Airline Tickets',
                'description' => 'Expenses related to airline ticket purchases and bookings',
                'status' => 1,
            ],
            [
                'name' => 'Hotel Accommodation',
                'description' => 'Hotel booking and accommodation expenses',
                'status' => 1,
            ],
            [
                'name' => 'Transportation',
                'description' => 'Ground transportation, car rentals, taxi, and other transport costs',
                'status' => 1,
            ],
            [
                'name' => 'Visa & Documentation',
                'description' => 'Visa processing fees, documentation, and related services',
                'status' => 1,
            ],
            [
                'name' => 'Travel Insurance',
                'description' => 'Travel insurance premiums and coverage costs',
                'status' => 1,
            ],
            [
                'name' => 'Meals & Dining',
                'description' => 'Food and dining expenses for business purposes',
                'status' => 1,
            ],
            [
                'name' => 'Marketing & Advertising',
                'description' => 'Marketing campaigns, advertisements, and promotional materials',
                'status' => 1,
            ],
            [
                'name' => 'Office Supplies',
                'description' => 'Office stationery, equipment, and supplies',
                'status' => 1,
            ],
            [
                'name' => 'Utilities',
                'description' => 'Electricity, water, internet, and other utility bills',
                'status' => 1,
            ],
            [
                'name' => 'Staff Salaries',
                'description' => 'Employee salaries and wages',
                'status' => 1,
            ],
            [
                'name' => 'Travel Agent Commission',
                'description' => 'Commissions paid to travel agents and partners',
                'status' => 1,
            ],
            [
                'name' => 'Bank Charges',
                'description' => 'Bank fees, transaction charges, and service fees',
                'status' => 1,
            ],
            [
                'name' => 'Software & Technology',
                'description' => 'Software licenses, IT services, and technology expenses',
                'status' => 1,
            ],
            [
                'name' => 'Training & Development',
                'description' => 'Employee training programs and professional development',
                'status' => 1,
            ],
            [
                'name' => 'Maintenance & Repairs',
                'description' => 'Office maintenance, equipment repairs, and upkeep',
                'status' => 1,
            ],
            [
                'name' => 'Communication',
                'description' => 'Phone bills, internet, and communication services',
                'status' => 1,
            ],
            [
                'name' => 'Professional Services',
                'description' => 'Legal, accounting, consulting, and other professional services',
                'status' => 1,
            ],
            [
                'name' => 'Taxes & Fees',
                'description' => 'Government taxes, licenses, and regulatory fees',
                'status' => 1,
            ],
            [
                'name' => 'Travel & Entertainment',
                'description' => 'Business travel expenses and client entertainment',
                'status' => 1,
            ],
            [
                'name' => 'Rent & Lease',
                'description' => 'Office rent, lease payments, and property expenses',
                'status' => 1,
            ],
            [
                'name' => 'Tour Packages',
                'description' => 'Tour package costs and related expenses',
                'status' => 1,
            ],
            [
                'name' => 'Currency Exchange',
                'description' => 'Currency conversion fees and exchange rate differences',
                'status' => 1,
            ],
            [
                'name' => 'Customer Refunds',
                'description' => 'Refunds issued to customers for cancellations or issues',
                'status' => 1,
            ],
            [
                'name' => 'Equipment Purchase',
                'description' => 'Purchase of office equipment, computers, and machinery',
                'status' => 1,
            ],
            [
                'name' => 'Miscellaneous',
                'description' => 'Other expenses that do not fit into specific categories',
                'status' => 1,
            ],
        ];

        // Check if categories already exist to avoid duplicates
        foreach($expenseCategories as $category){
            $existingCategory = ExpenseCategory::where('name', $category['name'])->first();
            
            if(!$existingCategory){
                $expenseCategory = new ExpenseCategory();
                $expenseCategory->name = $category['name'];
                $expenseCategory->description = $category['description'];
                $expenseCategory->status = $category['status'];
                $expenseCategory->created_by = $createdBy;
                $expenseCategory->save();
            }
        }
    }
}
