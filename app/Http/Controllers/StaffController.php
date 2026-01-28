<?php

namespace App\Http\Controllers;


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

use App\Models\Language;
use App\Models\Currency;
use App\Models\User;
use App\Models\UserCompany;
use App\Models\Department;
use App\Models\Designation;
use App\Models\UserDocument;

class StaffController extends Controller
{
    public function index()
    {
        if (!hasPermission('staff.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        $createRoute = hasPermission('staff.create') ? route('staff.create') : '';
        $dataTableRoute = hasPermission('staff.index') ? route('staff.datatable') : '';

        return view('common.staff.index', get_defined_vars());
    }

    public function datatable()
    {
        $user = Auth::user();

        $query = User::with(['parent', 'company', 'creator', 'designation', 'department'])->where('is_staff', 1)->latest();

        return DataTables::of($query)
            ->filter(function ($query) use ($user) {
                // Restrict by user (non-admin sees only their staff)
                if ($user->user_type != 'admin') {
                    $query->where('parent_id', $user->business_id);
                }

                // Global search: users table + designation, department, parent, company, creator
                if (request()->has('search') && $search = request('search')['value']) {
                    $searchTerm = '%' . $search . '%';
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('users.name', 'like', $searchTerm)
                            ->orWhere('users.email', 'like', $searchTerm)
                            ->orWhere('users.phone', 'like', $searchTerm)
                            ->orWhereHas('designation', function ($sub) use ($searchTerm) {
                                $sub->where('name', 'like', $searchTerm);
                            })
                            ->orWhereHas('department', function ($sub) use ($searchTerm) {
                                $sub->where('name', 'like', $searchTerm);
                            })
                            ->orWhereHas('parent', function ($sub) use ($searchTerm) {
                                $sub->where('name', 'like', $searchTerm);
                            })
                            ->orWhereHas('company', function ($sub) use ($searchTerm) {
                                $sub->where('company_name', 'like', $searchTerm)
                                    ->orWhere('tagline', 'like', $searchTerm)
                                    ->orWhere('address', 'like', $searchTerm)
                                    ->orWhere('phone_1', 'like', $searchTerm)
                                    ->orWhere('phone_2', 'like', $searchTerm)
                                    ->orWhere('email_1', 'like', $searchTerm)
                                    ->orWhere('email_2', 'like', $searchTerm);
                            });
                        // Creator name search
                        $creatorIds = User::where('name', 'like', $searchTerm)->pluck('id')->toArray();
                        if (!empty($creatorIds)) {
                            $q->orWhereIn('users.created_by', $creatorIds);
                        }
                        // Company (user_id) search for staff linked to that company
                        $companyUserIds = UserCompany::where(function ($q2) use ($searchTerm) {
                            $q2->where('company_name', 'like', $searchTerm)
                                ->orWhere('tagline', 'like', $searchTerm)
                                ->orWhere('address', 'like', $searchTerm)
                                ->orWhere('phone_1', 'like', $searchTerm)
                                ->orWhere('phone_2', 'like', $searchTerm)
                                ->orWhere('email_1', 'like', $searchTerm)
                                ->orWhere('email_2', 'like', $searchTerm);
                        })->pluck('user_id')->toArray();
                        if (!empty($companyUserIds)) {
                            $q->orWhereIn('users.id', $companyUserIds);
                        }
                    });
                }
            })
            ->addIndexColumn()
            ->editColumn('name', function ($row) {
                $designation = is_object($row->designation) && isset($row->designation->name) ? $row->designation->name : (is_string($row->designation) ? $row->designation : 'N/A');
                $department = is_object($row->department) && isset($row->department->name) ? $row->department->name : (is_string($row->department) ? $row->department : 'N/A');
                $sub = trim($designation . ' · ' . $department);
                if ($sub === 'N/A · N/A') {
                    $sub = 'N/A';
                }
                return '<strong>' . e($row->name) . '</strong><br><small class="text-muted">' . e($sub) . '</small>';
            })
            ->addColumn('parent_id', function ($row) {
                $p = $row->parent;
                return (is_object($p) && isset($p->name)) ? $p->name : 'N/A';
            })
            ->addColumn('user_type', function ($row) {
                $badgeClass = $row->user_type === 'admin'
                    ? 'badge badge-success'
                    : 'badge badge-info';

                return '<span class="' . $badgeClass . '">' . e(ucfirst($row->user_type)) . '</span>';
            })
            ->addColumn('company_id', function ($row) {
                if ($row->company_data) {
                    $logo = $row->company_data->dark_logo_url 
                        ? '<img src="' . $row->company_data->dark_logo_url . '" alt="Logo" height="30" style="margin-right: 10px;"><br>' 
                        : '';
                    $name = $row->company_data->company_name ?? 'N/A';

                    return '<div class="text-center">' . $logo . '<span>' . $name . '</span></div>';
                } else {
                    return 'N/A';
                }
            })
            ->addColumn('status', function ($row) {
                if (!hasPermission('staff.status') || Auth::user()->id == $row->id) {
                    $newStatus      = $row->status === 'Active' ? 'Inactive' : 'Active';
                    $mailVerifyText = !empty($row->email_verified_at)
                                    ? 'Mail is verified'
                                    : 'Mail is not verified';

                    return
                        '<div>'
                    .     '<span class="'
                    .         ($row->status === 'Active' ? 'text-success' : 'text-danger')
                    .     '">'
                    .         $row->status
                    .     '</span>'
                    .     '<br>'
                    .     '<small class="text-info">'
                    .         $mailVerifyText
                    .     '</small>'
                    . '</div>';
                }


                if ($row->id != 1 && Auth::user()->id != $row->id) {
                    // Determine the new status value to toggle to
                    $newStatus = $row->status === 'Active' ? 'Inactive' : 'Active';

                    // Generate the route URL with ID and new status
                    $statusUrl = route('staff.status', ['id' => $row->id, 'status' => $newStatus]);

                    // Mail verification text
                    $mailVerifyText = !empty($row->email_verified_at) ? 'Mail is verified' : 'Mail is not verified';

                    // Return the switch input HTML with mail verification text
                    return '
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input toggle-table-data-status"
                                data-id="' . $row->id . '"
                                data-url="' . $statusUrl . '"
                                ' . ($row->status === 'Active' ? 'checked' : '') . '>
                        </div>
                        <small class="text-info">' . $mailVerifyText . '</small>
                    ';
                }
            })

            // ->addColumn('user_id', function ($row) {
            //     return $row->user ? $row->user->name : 'N/A';
            // })
            ->addColumn('created_by', function ($row) {
                $c = $row->creator;
                return (is_object($c) && isset($c->name)) ? $c->name : 'N/A';
            })
            ->addColumn('action', function ($row) {
                $showUrl   = route('staff.show', $row->id);
                $editUrl   = route('staff.edit', $row->id);
                $deleteUrl = route('staff.destroy', $row->id);

                $buttons = '';

                // View button (requires permission)
                if (hasPermission('staff.index')) {
                    $buttons .= '
                        <a href="' . $showUrl . '" class="btn btn-sm btn-info" title="View">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    ';
                }

                // Edit button (requires permission)
                if (hasPermission('staff.edit') && Auth::user()->id != $row->id) {
                    $buttons .= '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                // Delete button (requires permission and some conditions)
                if (
                    $row->id != 1 &&
                    Auth::user()->id != $row->id &&
                    hasPermission('staff.delete')
                ) {
                    $buttons .= '
                        <button class="btn btn-sm btn-danger delete-table-data-btn"
                            data-id="' . $row->id . '"
                            data-url="' . $deleteUrl . '"
                            title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    ';
                }

                return !empty($buttons) ? $buttons : 'N/A';
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('Y-m-d, H:i');
            })
            ->rawColumns(['user_type', 'name', 'company_id', 'status', 'action'])
            ->make(true);
    }



    public function create()
    {
        if (!hasPermission('staff.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('staff.index') ? route('staff.index') : '';
        $saveRoute = hasPermission('staff.create') ? route('staff.store') : '';

        $languages = Language::orderBy('name', 'asc')->where('status', 1)->get();
        $users = User::with(['company', 'designation'])->orderBy('name', 'asc')->where('is_staff', 0)->where('status', 1)->get();
        $currencies = Currency::where('status', 'Active')->orderBy('currency_name', 'asc')->get();
        $departments = Department::where('status', 1)->orderBy('name', 'asc')->get();
        $designations = Designation::where('status', 1)->orderBy('name', 'asc')->get();
        return view('common.staff.addEdit', get_defined_vars());
    }

    public function store(Request $request)
    {
        if (!hasPermission('staff.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        return $this->saveData($request);
    }

    public function status($id, $status)
    {
        if (!hasPermission('staff.status')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        if(!in_array($status, ['Active','Inactive'])){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['status_is_incorrect'] ?? 'status_is_incorrect'
            ];
        }
        
        $user = Auth::user();
        $query = User::with('company')->where('id', $id)->where('is_staff', 1);
        if(Auth::user()->user_type == 'user'){
            $query->where('parent_id', $user->business_id);
        }
        $editData = $query->first();

        if(empty($editData)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        if($editData->id == 1){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['You_cant_update_admin_status'] ?? 'You_cant_update_admin_status'
            ];
        }
        // if($user->user_type != 'admin' && $editData->id != $user->id){
        //     return [
        //         'is_success' => 0,
        //         'icon' => 'error',
        //         'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
        //     ];
        // }

        $editData->status = $status;
        $editData->updated_by = $user->id;
        $editData->save();
        
        $statusName = $status == 1 ? 'Active' : 'Inactive';
        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => (getCurrentTranslation()['status_updated'] ?? 'status_updated') . ' (' . $statusName . ')',
        ];
    }

    public function show($id)
    {
        if (!hasPermission('staff.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('staff.index') ? route('staff.index') : '';
        $editRoute = hasPermission('staff.edit') ? route('staff.edit', $id) : '';

        $query = User::with(['company', 'parent', 'department', 'designation', 'documents', 'creator'])->where('id', $id)->where('is_staff', 1);
        if(Auth::user()->user_type == 'user'){
            $query->where('parent_id', $user->business_id);
        }
        $editData = $query->first();
        
        if(empty($editData)){
            abort(404);
        }

        // Get language name if default_language exists
        $languageName = null;
        if($editData->default_language){
            $language = Language::where('code', $editData->default_language)->first();
            $languageName = $language ? $language->name : null;
        }

        return view('common.staff.details', get_defined_vars());
    }

    public function edit($id)
    {
        if (!hasPermission('staff.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('staff.index') ? route('staff.index') : '';
        $saveRoute = hasPermission('staff.edit') ? route('staff.update', $id) : '';

        $query = User::with('company')->where('id', $id)->where('is_staff', 1);
        if(Auth::user()->user_type == 'user'){
            $query->where('parent_id', $user->business_id);
        }
        $editData = $query->first();
        
        if(empty($editData)){
            abort(404);
        }
        // if($user->user_type != 'admin' && $editData->user_id != $user->id){
            //     abort(404);
            // }
            
        //dd($editData);

        $languages = Language::orderBy('name', 'asc')->where('status', 1)->get();
        $users = User::with(['company', 'designation'])->orderBy('name', 'asc')->where('is_staff', 0)->where('status', 1)->get();
        $currencies = Currency::where('status', 'Active')->orderBy('currency_name', 'asc')->get();
        $departments = Department::where('status', 1)->orderBy('name', 'asc')->get();
        $designations = Designation::where('status', 1)->orderBy('name', 'asc')->get();
        $userDocuments = UserDocument::where('user_id', $id)->get();
        return view('common.staff.addEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('staff.edit')) {
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
        if (!hasPermission('staff.delete')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $query = User::with('company')->where('id', $id)->where('is_staff', 1);
        if(Auth::user()->user_type == 'user'){
            $query->where('parent_id', $user->business_id);
        }
        $data = $query->first();

        if(empty($data)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        // Delete user documents
        $userDocuments = UserDocument::where('user_id', $data->id)->get();
        foreach($userDocuments as $doc){
            deleteUploadedFile($doc->document_file);
            $doc->deleted_by = $user->id;
            $doc->save();
            $doc->delete();
        }
        // if($user->user_type != 'admin' && $data->user_id != $user->id){
        //     return [
        //         'is_success' => 0,
        //         'icon' => 'error',
        //         'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
        //     ];
        // }

        if($data->logo != null){
            deleteUploadedFile($data->logo);
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



    public function loadPermissions(Request $request)
    {
        // Validate parentId
        if (!$request->has('parentId')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        // Get parent user
        $parent = User::find($request->parentId);
        if (!$parent) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        // Get user if userId exists
        $user = null;
        if ($request->filled('userId')) {
            $user = User::find($request->userId);
            if (!$user) {
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
        }

        // Decode permissions (stored as JSON in DB usually)
        $parentPermissions = is_string($parent->permissions) 
            ? json_decode($parent->permissions, true) 
            : ($parent->permissions ?? []);

        $userPermissions = $user 
            ? (is_string($user->permissions) 
                ? json_decode($user->permissions, true) 
                : ($user->permissions ?? [])) 
            : [];


            //dd($userPermissions);
        // Render view
        $viewPage = view('common.staff.permissionList', [
            'parent' => $parent,
            'user' => $user,
            'parentPermissions' => $parentPermissions,
            'userPermissions' => $userPermissions,
        ])->render();

        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['data_found'] ?? 'data_found',
            'view_page' => $viewPage
        ];
    }



    public function saveData(Request $request, $id = null)
    {
        $userId = $id; // or however you determine update

        $messages = getCurrentTranslation();

        $logoMimes = 'heic,jpg,jpeg,png';
        $maxImageSize = 3072;

        $documentMimes = 'pdf,png,jpg,jpeg';
        $maxDocumentSize = 5120; // 5MB

        $validator = Validator::make($request->all(), [
            'parent_id' => 'nullable|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|integer|exists:departments,id',
            'designation_id' => 'nullable|integer|exists:designations,id',
            'joining_date' => 'nullable|date',
            'employment_type' => 'nullable|in:Full-time,Part-time,Contract',
            'salary_amount' => 'nullable|numeric|min:0',
            'image' => 'nullable|mimes:' . $logoMimes . '|max:' . $maxImageSize,
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'required|email|max:255|unique:users,email,' . ($userId ?? 'NULL'),
            'email_verified_at' => 'nullable|date',
            'password' => $userId ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',
            'status' => 'required|in:Active,Inactive',
            'documents.*.document_name' => 'nullable|string|max:255',
            'documents.*.document_file' => 'nullable|mimes:' . $documentMimes . '|max:' . $maxDocumentSize,
            'documents.*.description' => 'nullable|string',
        ], [
            // Required
            'required' => $messages['required_message'] ?? 'This field is required.',
            // Unique
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            // Specific max length for certain fields (overrides generic)
            'name.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'documents.*.document_name.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'address.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'company_name.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'tagline.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'invoice_prefix.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'company_invoice_id.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'phone_1.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 20',
            'phone_2.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 20',
            'email.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'email_1.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'email_2.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            // File size validations
            'image.max' => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
            'documents.*.document_file.max' => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxDocumentSize / 1024) . ' MB',
            // Image validations
            'image' => $messages['image_message'] ?? 'This must be an image.',
            // Mimes validation with mime types injected
            'mimes' => ($messages['mimes_message'] ?? 'The file must be of type') . ' (' . $logoMimes . ').',
            // In validation
            'in' => $messages['in_message'] ?? 'The selected value is invalid.',
            // Email
            'email' => $messages['email_message'] ?? 'Please enter a valid email address.',
            // Min length (for password)
            'password.min' => ($messages['max_string_message'] ?? 'This field allowed minimum character length is: ') . ' 8',
            'password.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            // Confirmed
            'confirmed' => $messages['confirmed_message'] ?? 'The confirmation does not match.',
            // Date
            'date' => $messages['date_message'] ?? 'Please enter a valid date.',
            // Exists
            'exists' => $messages['exists_message'] ?? 'The selected value is invalid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        //dd($request->all());

        $authUser = Auth::user();
        $user = null;
        if (isset($id)) {
            $query = User::with('company')->where('id', $id)->where('is_staff', 1);
            if(Auth::user()->user_type == 'user'){
                $query->where('parent_id', $authUser->business_id);
            }
            $user = $query->first();
            if(empty($user)){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
        }
        
        $image = null;
        if($request->hasFile('image')){
            $oldFile = $user->image ?? null;
            $image = handleImageUpload($request->image, 500, 500, $folderName='user', 'user-image', $oldFile); //$uploadedFile, $resizeMaxWidth, $resizeMaxHeight, $folderName, $fileName ,$oldFile
        }
        if($image == null && !empty($user)){
            $image = $user->image;
        }

        $queryForUserId = $request->user_id ?? Auth::id();
        $userId = $request->user_id ?? Auth::id();

        if (empty($user)) {
            $user = new User();
            $user->created_by = Auth::id();
        } else {
            $user->updated_by = Auth::id();
        }

        if (Auth::user()->user_type === 'admin' && $request->select_user_type == 'user' && empty($request->user_id)) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => [
                    'user_id' => [getCurrentTranslation()['this_field_is_required'] ?? 'This field is required.'],
                ],
            ]);
        }else{
            if(Auth::user()->user_type === 'admin' && Auth::user()->is_staff == 0){
                $user->parent_id = $request->user_id ?? Auth::user()->id;
            }else if(Auth::user()->user_type === 'admin' && Auth::user()->is_staff == 1){
                $user->parent_id = Auth::user()->business_id;
            }else{
                $user->parent_id = Auth::id();
            }
        }

        DB::beginTransaction();
        try {
            //$user->user_id = $userId;
            $parentData = User::where('id', $user->parent_id)->first();
            $parentPermissions = $parentData->permissions ?? []; // assume array/json decoded

            $userType = Auth::user()->user_type;
            $submittedPermissions = $request->permissions ?? [];

            // first filter based on your function
            $validPermissions = $this->filterValidPermissions($submittedPermissions, $userType);

            // ensure only parent's permissions are allowed
            $validPermissions = array_intersect($validPermissions, $parentPermissions);

            // save back
            $user->permissions = array_values($validPermissions);
            

            if(Auth::user()->user_type == 'admin'){
                $user->user_type = $request->user_type;
            }else{
                $user->user_type = 'user';
                
            }
            $user->is_staff = 1;
            $user->name = $request->name ?? null;
            $user->department_id = $request->department_id ?? null;
            $user->designation_id = $request->designation_id ?? null;
            $user->joining_date = $request->joining_date ?? null;
            $user->employment_type = $request->employment_type ?? null;
            $user->salary_amount = $request->salary_amount ?? 0;
            $user->image = $image;
            $user->address = $request->address;
            $user->phone = $request->phone;
            $user->email = $request->email;
            $user->email_verified_at = date('Y-m-d');
            if(isset($request->password)){
                $user->password = bcrypt($request->password);
            }
            $user->status = $request->status;

            // System Settings
            $user->default_language = $request->default_language;
            
            $user->save();

            // Handle User Documents
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
                        $userDocument = UserDocument::where('id', $documentId)->where('user_id', $user->id)->first();
                        if($userDocument){
                            if($documentFile){
                                // New file uploaded - replace existing file
                                $documentExtension = strtolower($documentFile->getClientOriginalExtension());
                                $documentName = $docData['document_name'] ?? $userDocument->document_name ?? 'document-' . ($docIndex + 1);
                                $documentPath = uploadFile($documentFile, $documentName, 'user-documents', $userDocument->document_file);
                                
                                if($documentPath){
                                    $userDocument->document_file = $documentPath;
                                    $userDocument->document_type = $documentExtension;
                                }
                            }
                            // If no new file, keep existing file (don't update document_file)
                            
                            $userDocument->document_name = $docData['document_name'] ?? $userDocument->document_name;
                            $userDocument->description = $docData['description'] ?? $userDocument->description;
                            $userDocument->updated_by = Auth::id();
                            $userDocument->save();
                            // Always add to currentDocumentIds to prevent deletion
                            $currentDocumentIds[] = $userDocument->id;
                        }
                    } else if($documentFile){
                        // Create new document
                        $documentExtension = strtolower($documentFile->getClientOriginalExtension());
                        $documentName = $docData['document_name'] ?? 'document-' . ($docIndex + 1);
                        $documentPath = uploadFile($documentFile, $documentName, 'user-documents', null);
                        
                        if($documentPath){
                            $userDocument = new UserDocument();
                            $userDocument->user_id = $user->id;
                            $userDocument->document_name = $docData['document_name'] ?? null;
                            $userDocument->document_file = $documentPath;
                            $userDocument->document_type = $documentExtension;
                            $userDocument->description = $docData['description'] ?? null;
                            $userDocument->created_by = Auth::id();
                            $userDocument->save();
                            $currentDocumentIds[] = $userDocument->id;
                        }
                    }
                }
            }
            
            // Delete documents that were removed
            if(isset($id) && $id){
                UserDocument::where('user_id', $user->id)
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
                $response['redirect_url'] = route('admin.staff.index');
            }
            
            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Staff store error', ['error' => $e->getMessage()]);

            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_saving_error'] ?? 'data_saving_error'
            ];
        }
    }



    private function filterValidPermissions(array $submittedPermissions, string $actingUserType): array
    {
        $allPermissions = getPermissionList();

        // Filter permission groups based on acting user's type
        $filteredPermissions = collect($allPermissions)->filter(function ($group) use ($actingUserType) {
            return $actingUserType === 'admin' || $group['for'] === 'all_user';
        });

        // Extract all allowed permission keys
        $allowedPermissionKeys = $filteredPermissions
            ->pluck('permissions')
            ->flatten(1)
            ->pluck('key')
            ->toArray();

        // Return only the allowed permissions from the submitted ones
        return array_values(array_intersect($submittedPermissions, $allowedPermissionKeys));
    }

    
}