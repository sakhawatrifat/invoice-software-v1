<?php

namespace App\Http\Controllers\Admin;

use Auth;
use File;
use Image;
use Session;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

use App\Models\User;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseDocument;

class ExpenseController extends Controller
{
    public function index()
    {
        if (!hasPermission('expense.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $createRoute = hasPermission('expense.create') ? route('admin.expense.create') : '';
        $dataTableRoute = hasPermission('expense.index') ? route('admin.expense.datatable') : '';

        return view('admin.expense.index', get_defined_vars());
    }

    public function datatable()
    {
        $user = Auth::user();
        $query = Expense::with(['category', 'forUser', 'creator'])->latest();

        // Properly grouped global search
        if (request()->has('search') && $search = request('search')['value']) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });

            // Search by category name
            $categoryIds = ExpenseCategory::where('name', 'like', "%{$search}%")->pluck('id')->toArray();
            if (!empty($categoryIds)) {
                $query->orWhereIn('expense_category_id', $categoryIds);
            }

            // Search by user name (for_user_id)
            $userIds = User::where('name', 'like', "%{$search}%")->pluck('id')->toArray();
            if (!empty($userIds)) {
                $query->orWhereIn('for_user_id', $userIds);
            }

            // Search by creator
            $creatorIds = User::where('name', 'like', "%{$search}%")->pluck('id')->toArray();
            if (!empty($creatorIds)) {
                $query->orWhereIn('created_by', $creatorIds);
            }
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('category_name', function ($row) {
                return $row->category ? $row->category->name : 'N/A';
            })
            ->addColumn('for_user_name', function ($row) {
                if ($row->forUser) {
                    $name = $row->forUser->name;
                    $name .= ' (' . ($row->forUser->designation?->name ?? 'N/A') . ')';
                    if ($row->forUser->is_staff == 0) {
                        $name .= ' - ' . (getCurrentTranslation()['non_staff'] ?? 'Non Staff');
                    }
                    return $name;
                }
                return 'N/A';
            })
            ->addColumn('amount_formatted', function ($row) {
                $currency = Auth::user()->company_data->currency->short_name ?? '';
                return number_format($row->amount, 2) . ' (' . $currency . ')';
            })
            ->addColumn('expense_date_formatted', function ($row) {
                return $row->expense_date ? Carbon::parse($row->expense_date)->format('Y-m-d') : 'N/A';
            })
            ->addColumn('payment_status', function ($row) {
                $badgeClass = $row->payment_status == 'Paid' ? 'badge-success' : 'badge-danger';
                return '<span class="badge ' . $badgeClass . '">' . $row->payment_status . '</span>';
            })
            ->addColumn('created_by', function ($row) {
                return $row->creator ? $row->creator->name : 'N/A';
            })
            ->addColumn('action', function ($row) {
                $showUrl   = route('admin.expense.show', $row->id);
                $editUrl   = route('admin.expense.edit', $row->id);
                $deleteUrl = route('admin.expense.destroy', $row->id);

                $buttons = '';

                if (hasPermission('expense.index')) {
                    $buttons .= '
                        <a href="' . $showUrl . '" class="btn btn-sm btn-info">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    ';
                }

                if (hasPermission('expense.edit')) {
                    $buttons .= '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                if (hasPermission('expense.delete')) {
                    $buttons .= '
                        <button class="btn btn-sm btn-danger delete-table-data-btn"
                            data-id="' . $row->id . '"
                            data-url="' . $deleteUrl . '">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    ';
                }

                return !empty(trim($buttons)) ? $buttons : 'N/A';
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('Y-m-d, H:i');
            })
            ->rawColumns(['payment_status', 'action'])
            ->make(true);
    }

    public function create()
    {
        if (!hasPermission('expense.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('expense.index') ? route('admin.expense.index') : '';
        $saveRoute = route('admin.expense.store');

        // Get all active users for "for" dropdown
        $users = User::where('status', 'Active')
            ->with('designation')
            ->orderBy('name')
            ->get();

        // Get all active expense categories
        $categories = ExpenseCategory::where('status', 1)
            ->orderBy('name')
            ->get();

        // Get expense documents (empty collection for new expense)
        $expenseDocuments = collect();

        // Set editData to null for create
        $editData = null;

        return view('admin.expense.addEdit', get_defined_vars());
    }

    public function store(Request $request)
    {
        if (!hasPermission('expense.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        return $this->saveData($request);
    }


    public function show($id)
    {
        if (!hasPermission('expense.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('expense.index') ? route('admin.expense.index') : '';
        $editRoute = hasPermission('expense.edit') ? route('admin.expense.edit', $id) : '';

        $editData = Expense::with(['category', 'forUser', 'forUser.designation', 'creator', 'documents'])
            ->where('id', $id)
            ->first();
            
        if(empty($editData)){
            abort(404);
        }
            
        return view('admin.expense.details', get_defined_vars());
    }

    public function edit($id)
    {
        if (!hasPermission('expense.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('expense.index') ? route('admin.expense.index') : '';
        $saveRoute = hasPermission('expense.edit') ? route('admin.expense.update', $id) : '';

        $editData = Expense::where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }

        // Get all active users for "for" dropdown
        $users = User::where('status', 'Active')
            ->with('designation')
            ->orderBy('name')
            ->get();

        // Get all active expense categories
        $categories = ExpenseCategory::where('status', 1)
            ->orderBy('name')
            ->get();

        // Get expense documents
        $expenseDocuments = ExpenseDocument::where('expense_id', $id)->get();
            
        return view('admin.expense.addEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('expense.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        return $this->saveData($request, $id);
    }

    public function destroy($id)
    {
        if (!hasPermission('expense.delete')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $data = Expense::where('id', $id)->first();
        if(empty($data)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        $data->deleted_by = $user->id;
        $data->save();

        $data->delete();

        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['data_deleted'] ?? 'data_deleted'
        ];
    }

    public function saveData(Request $request, $id = null)
    {
        $messages = getCurrentTranslation();

        // Normalize "None" (0) to null so validation and save handle it correctly
        if ($request->has('for_user_id') && ($request->for_user_id === '0' || $request->for_user_id === 0 || $request->for_user_id === '')) {
            $request->merge(['for_user_id' => null]);
        }

        $rules = [
            'expense_category_id' => 'required|exists:expense_categories,id',
            'for_user_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'payment_status' => 'required|in:Paid,Unpaid',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'required' => $messages['required_message'] ?? 'This field is required.',
            'exists' => $messages['exists_message'] ?? 'The selected value is invalid.',
            'numeric' => $messages['numeric_message'] ?? 'This field must be a number.',
            'min' => $messages['min_message'] ?? 'The minimum value is :min.',
            'date' => $messages['date_message'] ?? 'This field must be a valid date.',
            'max' => $messages['max_string_message'] ?? 'This field allowed maximum character length is: :max',
            'in' => $messages['in_message'] ?? 'The selected value is invalid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $user = Auth::user();
        $expense = null;
        if (isset($id)) {
            $expense = Expense::where('id', $id)->first();
            if(empty($expense)){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
        }

        if (empty($expense)) {
            $expense = new Expense();
            $expense->created_by = Auth::id();
        } else {
            $expense->updated_by = Auth::id();
        }
        

        DB::beginTransaction();
        try {
            $expense->expense_category_id = $request->expense_category_id ?? null;
            // Convert "0" to null for for_user_id (None option)
            $expense->for_user_id = ($request->for_user_id == '0' || $request->for_user_id === null || $request->for_user_id === '') ? null : $request->for_user_id;
            $expense->title = $request->title ?? null;
            $expense->description = $request->description ?? null;
            $expense->amount = $request->amount ?? 0;
            $expense->expense_date = $request->expense_date ?? null;
            $expense->payment_method = $request->payment_method ?? null;
            $expense->reference_number = $request->reference_number ?? null;
            $expense->notes = $request->notes ?? null;
            $expense->payment_status = $request->payment_status ?? 'Unpaid';

            $expense->save();

            // Handle Expense Documents
            $currentDocumentIds = [];
            if($request->has('documents') && is_array($request->documents)){
                foreach($request->documents as $docIndex => $docData){
                    $documentId = $docData['id'] ?? null;
                    $documentFile = null;
                    
                    // Check if file is being uploaded
                    if($request->hasFile("documents.$docIndex.document_file")){
                        $documentFile = $request->file("documents.$docIndex.document_file");
                    }
                    
                    if($documentId){
                        // Update existing document
                        $expenseDocument = ExpenseDocument::where('id', $documentId)->where('expense_id', $expense->id)->first();
                        if($expenseDocument){
                            if($documentFile){
                                // New file uploaded - replace existing file
                                $documentExtension = strtolower($documentFile->getClientOriginalExtension());
                                $documentName = $docData['document_name'] ?? $expenseDocument->document_name ?? 'document-' . ($docIndex + 1);
                                $documentPath = uploadFile($documentFile, $documentName, 'expense-documents', $expenseDocument->document_file);
                                
                                if($documentPath){
                                    $expenseDocument->document_file = $documentPath;
                                    $expenseDocument->document_type = $documentExtension;
                                }
                            }
                            // If no new file, keep existing file (don't update document_file)
                            
                            $expenseDocument->document_name = $docData['document_name'] ?? $expenseDocument->document_name;
                            $expenseDocument->description = $docData['description'] ?? $expenseDocument->description;
                            $expenseDocument->updated_by = Auth::id();
                            $expenseDocument->save();
                            // Always add to currentDocumentIds to prevent deletion
                            $currentDocumentIds[] = $expenseDocument->id;
                        }
                    } else if($documentFile){
                        // Create new document
                        $documentExtension = strtolower($documentFile->getClientOriginalExtension());
                        $documentName = $docData['document_name'] ?? 'document-' . ($docIndex + 1);
                        $documentPath = uploadFile($documentFile, $documentName, 'expense-documents', null);
                        
                        if($documentPath){
                            $expenseDocument = new ExpenseDocument();
                            $expenseDocument->expense_id = $expense->id;
                            $expenseDocument->document_name = $docData['document_name'] ?? null;
                            $expenseDocument->document_file = $documentPath;
                            $expenseDocument->document_type = $documentExtension;
                            $expenseDocument->description = $docData['description'] ?? null;
                            $expenseDocument->created_by = Auth::id();
                            $expenseDocument->save();
                            $currentDocumentIds[] = $expenseDocument->id;
                        }
                    }
                }
            }
            
            // Delete documents that were removed
            if(isset($id) && $id){
                ExpenseDocument::where('expense_id', $expense->id)
                    ->whereNotIn('id', $currentDocumentIds)
                    ->each(function($oldDoc){
                        deleteUploadedFile($oldDoc->document_file);
                        $oldDoc->deleted_by = Auth::id();
                        $oldDoc->save();
                        $oldDoc->delete();
                    });
            }

            DB::commit();
            $response = [
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
            
            // Add redirect_url only when creating new data (not updating)
            if (!isset($id) || empty($id)) {
                $response['redirect_url'] = route('admin.expense.index');
            }
            
            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Expense store error', ['error' => $e->getMessage()]);

            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_saving_error'] ?? 'data_saving_error'
            ];
        }
    }

    /**
     * Expense report
     */
    public function report(Request $request)
    {
        if (!hasPermission('expense.index')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();
        
        // Default to current month if no date range is provided
        $dateRange = $request->date_range;
        $categoryId = $request->category_id;
        $forUserId = $request->for_user_id;
        $paymentStatus = $request->payment_status;
        
        // If no date range provided, default to current month
        if (empty($dateRange) || $dateRange == 0 || $dateRange == '0') {
            $startDate = Carbon::now()->firstOfMonth()->startOfDay();
            $endDate = Carbon::now()->endOfMonth()->endOfDay();
        } else {
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();
        }
        
        $expenses = Expense::with(['category', 'forUser', 'creator'])
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->when($categoryId && $categoryId !== 'all' && $categoryId !== '', function($query) use ($categoryId) {
                return $query->where('expense_category_id', $categoryId);
            })
            ->when($forUserId && $forUserId !== 'all' && $forUserId !== '', function($query) use ($forUserId) {
                return $query->where('for_user_id', $forUserId);
            })
            ->when($paymentStatus && $paymentStatus !== 'all' && $paymentStatus !== '', function($query) use ($paymentStatus) {
                return $query->where('payment_status', $paymentStatus);
            })
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Calculate summary statistics
        $totalAmount = $expenses->sum('amount');
        $totalPaid = $expenses->where('payment_status', 'Paid')->sum('amount');
        $totalUnpaid = $expenses->where('payment_status', 'Unpaid')->sum('amount');
        $totalCount = $expenses->count();
        $paidCount = $expenses->where('payment_status', 'Paid')->count();
        $unpaidCount = $expenses->where('payment_status', 'Unpaid')->count();
        
        // Get all active expense categories for dropdown
        $categories = ExpenseCategory::where('status', 1)
            ->orderBy('name')
            ->get();
        
        // Get all active users for dropdown
        $users = User::where('status', 'Active')
            ->with('designation')
            ->orderBy('name')
            ->get();
        
        // Get default date range for filter (current month)
        $defaultDateRange = $dateRange ?? ($startDate->format('Y/m/d') . '-' . $endDate->format('Y/m/d'));
        
        return view('admin.report.expense', compact('expenses', 'dateRange', 'categoryId', 'forUserId', 'paymentStatus', 'categories', 'users', 'totalAmount', 'totalPaid', 'totalUnpaid', 'totalCount', 'paidCount', 'unpaidCount', 'defaultDateRange'));
    }

    /**
     * Export expense report as PDF
     */
    public function exportPdf(Request $request, \App\Services\PdfService $pdfService)
    {
        if (!hasPermission('expense.index')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();

        $dateRange = $request->date_range;
        $categoryId = $request->category_id;
        $forUserId = $request->for_user_id;
        $paymentStatus = $request->payment_status;

        if (empty($dateRange) || $dateRange == 0 || $dateRange == '0') {
            $startDate = Carbon::now()->firstOfMonth()->startOfDay();
            $endDate = Carbon::now()->endOfMonth()->endOfDay();
        } else {
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();
        }

        $expenses = Expense::with(['category', 'forUser', 'creator'])
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->when($categoryId && $categoryId !== 'all' && $categoryId !== '', function($query) use ($categoryId) {
                return $query->where('expense_category_id', $categoryId);
            })
            ->when($forUserId && $forUserId !== 'all' && $forUserId !== '', function($query) use ($forUserId) {
                return $query->where('for_user_id', $forUserId);
            })
            ->when($paymentStatus && $paymentStatus !== 'all' && $paymentStatus !== '', function($query) use ($paymentStatus) {
                return $query->where('payment_status', $paymentStatus);
            })
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalAmount = $expenses->sum('amount');
        $totalPaid = $expenses->where('payment_status', 'Paid')->sum('amount');
        $totalUnpaid = $expenses->where('payment_status', 'Unpaid')->sum('amount');
        $totalCount = $expenses->count();
        $paidCount = $expenses->where('payment_status', 'Paid')->count();
        $unpaidCount = $expenses->where('payment_status', 'Unpaid')->count();

        $getCurrentTranslation = getCurrentTranslation();
        $dateRangeStr = $dateRange ?? ($startDate->format('Y/m/d') . '-' . $endDate->format('Y/m/d'));
        
        $html = view('admin.report.expense-pdf', compact('expenses', 'dateRangeStr', 'totalAmount', 'totalPaid', 'totalUnpaid', 'totalCount', 'paidCount', 'unpaidCount', 'getCurrentTranslation', 'startDate', 'endDate'))->render();
        
        $filename = 'Expense_Report_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.pdf';
        
        return $pdfService->generatePdf(null, $html, $filename, 'I');
    }
}
