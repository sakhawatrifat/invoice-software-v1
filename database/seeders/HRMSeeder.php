<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Designation;

class HRMSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Departments
        $departments = [
            [
                'name' => 'Human Resources',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Information Technology',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Finance',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Sales',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Marketing',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Operations',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Customer Service',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Administration',
                'status' => 1,
                'created_by' => 1,
            ],
        ];

        Department::truncate();
        foreach($departments as $department){
            $dept = new Department();
            $dept->name = $department['name'];
            $dept->status = $department['status'];
            $dept->created_by = $department['created_by'];
            $dept->save();
        }

        // Seed Designations
        $designations = [
            [
                'name' => 'Manager',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Senior Manager',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Assistant Manager',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Team Lead',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Senior Executive',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Executive',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Junior Executive',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Associate',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Director',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Senior Director',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Vice President',
                'status' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'President',
                'status' => 1,
                'created_by' => 1,
            ],
        ];

        Designation::truncate();
        foreach($designations as $designation){
            $desig = new Designation();
            $desig->name = $designation['name'];
            $desig->status = $designation['status'];
            $desig->created_by = $designation['created_by'];
            $desig->save();
        }
    }
}
