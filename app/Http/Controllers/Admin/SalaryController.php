<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Salary;
use App\Models\User;

class SalaryController extends Controller
{
    /**
     * Show salary generation page
     */
    public function index()
    {
        if (!hasPermission('admin.salary.index')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();
        
        // Get all active users (both staff and non-staff)
        $employees = User::where('status', 'Active')
        ->with('designation')
        ->orderBy('name')
        ->get();
        
        return view('admin.salary.generateSalary', compact('employees'));
    }

    /**
     * Generate salary sheet
     */
    public function generate(Request $request)
    {
        if (!hasPermission('admin.salary.generate')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();
        
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:users,id',
        ]);

        $month = $request->month;
        $year = $request->year;
        $employeeIds = $request->employee_ids;

        // Check for existing salaries before generating
        $existingSalaries = Salary::whereIn('employee_id', $employeeIds)
            ->where('year', $year)
            ->where('month', $month)
            ->with('employee')
            ->get();
        
        if ($existingSalaries->count() > 0) {
            $existingEmployeeNames = $existingSalaries->pluck('employee.name')->toArray();
            $existingEmployeeNamesStr = implode(', ', $existingEmployeeNames);
            
            return back()
                ->withInput()
                ->with('error', 'Salary already exists for the selected month/year for the following employees: ' . $existingEmployeeNamesStr . '. Please remove them from selection or edit existing salaries.');
        }

        DB::beginTransaction();
        try {
            $generatedSalaries = [];
            $newEmployeeIds = [];
            
            foreach ($employeeIds as $employeeId) {
                // Double check (should not reach here if validation above passed, but safety check)
                $existingSalary = Salary::where('employee_id', $employeeId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();
                
                if ($existingSalary) {
                    continue; // Skip if already exists
                }
                
                $employee = User::findOrFail($employeeId);
                
                // Get base salary from employee
                $baseSalary = $employee->salary_amount ?? 0;
                
                // Calculate net salary (base - deductions + bonus)
                $netSalary = $baseSalary;
                
                $salary = Salary::create([
                    'employee_id' => $employeeId,
                    'year' => $year,
                    'month' => $month,
                    'base_salary' => $baseSalary,
                    'deductions' => 0,
                    'bonus' => 0,
                    'net_salary' => $netSalary,
                    'payment_status' => 'Unpaid',
                    'created_by' => $user->id,
                ]);
                
                $generatedSalaries[] = $salary;
                $newEmployeeIds[] = $employeeId;
            }
            
            if (count($generatedSalaries) === 0) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->with('error', 'No new salaries were generated. All selected employees already have salaries for the selected month/year.');
            }
            
            DB::commit();
            
            $successMessage = 'Salary sheet generated successfully for ' . count($generatedSalaries) . ' employee(s).';
            
            return redirect()->route('admin.salary.list', [
                'month' => $month,
                'year' => $year
            ])->with('success', $successMessage);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Salary generation error: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Failed to generate salary sheet. Please try again.');
        }
    }

    /**
     * List salary sheet
     */
    public function list(Request $request)
    {
        if (!hasPermission('admin.salary.index')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();
        
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;
        $employeeId = $request->employee_id;
        
        $salaries = Salary::with(['employee.designation', 'creator'])
            ->where('year', $year)
            ->where('month', $month)
            ->when($employeeId, function($query) use ($employeeId) {
                return $query->where('employee_id', $employeeId);
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get all active users (both staff and non-staff) for dropdown
        $employees = User::where('status', 'Active')
            ->with('designation')
            ->orderBy('name')
            ->get();
        
        return view('admin.salary.list', compact('salaries', 'month', 'year', 'employeeId', 'employees'));
    }

    /**
     * Update salary
     */
    public function update(Request $request, $id)
    {
        if (!hasPermission('admin.salary.update')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();
        
        $rules = [
            'base_salary' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'deduction_note' => 'nullable|string',
            'bonus' => 'nullable|numeric|min:0',
            'bonus_note' => 'nullable|string',
            'payment_status' => 'nullable|in:Unpaid,Paid,Partial',
            'payment_note' => 'nullable|string',
        ];
        
        // Make payment_date required if payment_status is not Unpaid
        if ($request->payment_status && $request->payment_status != 'Unpaid') {
            $rules['payment_date'] = 'required|date';
        } else {
            $rules['payment_date'] = 'nullable|date';
        }
        
        $request->validate($rules);
        
        $salary = Salary::findOrFail($id);
        
        $baseSalary = $request->base_salary ?? $salary->base_salary;
        $deductions = $request->deductions ?? 0;
        $bonus = $request->bonus ?? 0;
        
        // Calculate net salary
        $netSalary = $baseSalary - $deductions + $bonus;
        
        $salary->update([
            'base_salary' => $baseSalary,
            'deductions' => $deductions,
            'deduction_note' => $request->deduction_note,
            'bonus' => $bonus,
            'bonus_note' => $request->bonus_note,
            'net_salary' => $netSalary,
            'payment_status' => $request->payment_status ?? $salary->payment_status,
            'payment_date' => $request->payment_date,
            'payment_note' => $request->payment_note,
            'updated_by' => $user->id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Salary updated successfully.',
            'data' => $salary->fresh(['employee.designation']),
        ]);
    }

    /**
     * Check for duplicate salaries (AJAX)
     */
    public function checkDuplicates(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:users,id',
        ]);

        $month = $request->month;
        $year = $request->year;
        $employeeIds = $request->employee_ids;

        $existingSalaries = Salary::whereIn('employee_id', $employeeIds)
            ->where('year', $year)
            ->where('month', $month)
            ->with('employee')
            ->get();

        $duplicates = $existingSalaries->map(function($salary) {
            return [
                'employee_id' => $salary->employee_id,
                'employee_name' => $salary->employee->name ?? 'N/A',
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'duplicates' => $duplicates,
        ]);
    }

    /**
     * Get salary details for edit modal
     */
    public function getDetails($id)
    {
        $salary = Salary::with(['employee.designation', 'creator'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $salary,
        ]);
    }

    /**
     * Delete salary record
     */
    public function destroy($id)
    {
        if (!hasPermission('admin.salary.destroy')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }
        try {
            $salary = Salary::findOrFail($id);
            $employeeName = $salary->employee->name ?? 'N/A';
            
            $salary->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Salary record deleted successfully.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Salary deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete salary record. ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Staff salary list (for is_staff == 1)
     */
    public function staffSalaryList(Request $request)
    {
        $user = Auth::user();
        
        if ($user->is_staff != 1) {
            abort(403, 'Unauthorized action.');
        }
        
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;
        
        $salaries = Salary::where('employee_id', $user->id)
            ->where('year', $year)
            ->when($month, function($query) use ($month) {
                return $query->where('month', $month);
            })
            ->with('employee.designation')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
        
        $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        
        return view('common.staff.salary.list', compact('salaries', 'month', 'year', 'monthNames'));
    }
}
