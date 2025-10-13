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

use App\Models\Language;
use App\Models\Currency;
use App\Models\User;
use App\Models\UserCompany;

class UserController extends Controller
{
    public function index()
    {
        $createRoute = route('admin.user.create');
        $dataTableRoute = route('admin.user.datatable');

        return view('admin.user.index', get_defined_vars());
    }

    public function datatable()
    {
        $user = Auth::user();
        $query = User::with(['company', 'creator'])->where('id', '!=', 1)->where('is_staff', 0)->latest();

        if($user->user_type != 'admin'){
            $query->where('user_id', $user->id);
        }

        // Properly grouped global search
        if (request()->has('search') && $search = request('search')['value']) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });

            // Search users by company and filter by their IDs
            $companyUserIds = UserCompany::where(function ($q2) use ($search) {
                $q2->where('company_name', 'like', "%{$search}%")
                    ->orWhere('tagline', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('phone_1', 'like', "%{$search}%")
                    ->orWhere('phone_2', 'like', "%{$search}%")
                    ->orWhere('email_1', 'like', "%{$search}%")
                    ->orWhere('email_2', 'like', "%{$search}%");
            })->pluck('user_id')->toArray();
            //dd($companyUserIds);
            if (!empty($companyUserIds)) {
                $query->orWhereIn('id', $companyUserIds);
            }

            // Similarly for creator, if needed
            $creatorIds = User::where('name', 'like', "%{$search}%")->pluck('id')->toArray();
            if (!empty($creatorIds)) {
                $query->orWhereIn('created_by', $creatorIds);
            }
        }

        return DataTables::of($query)
            ->addIndexColumn()
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
                if ($row->id != 1) {
                    // Determine the new status value to toggle to
                    $newStatus = $row->status === 'Active' ? 'Inactive' : 'Active';

                    // Generate the route URL with ID and new status
                    $statusUrl = route('admin.user.status', ['id' => $row->id, 'status' => $newStatus]);

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
                        <small class="text-warning">' . $mailVerifyText . '</small>
                    ';
                }
            })

            // ->addColumn('user_id', function ($row) {
            //     return $row->user ? $row->user->name : 'N/A';
            // })
            ->addColumn('created_by', function ($row) {
                return $row->creator ? $row->creator->name : 'N/A';
            })
            ->addColumn('action', function ($row) {
                $deleteUrl = route('admin.user.destroy', $row->id);
                $editUrl = route('admin.user.edit', $row->id);

                $deleteButton = '';
                if ($row->id != 1) {
                    $deleteButton = '
                        <button class="btn btn-sm btn-danger delete-table-data-btn"
                            data-id="' . $row->id . '"
                            data-url="' . $deleteUrl . '">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    ';
                }

                return '
                    <a href="' . $editUrl . '" class="btn btn-sm btn-primary"><i class="fa-solid fa-pen-to-square"></i></a>
                    ' . $deleteButton . '
                ';
            })

            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('Y-m-d, H:i');
            })
            ->rawColumns(['company_id', 'status', 'action'])
            ->make(true);
    }



    public function create()
    {
        $listRoute = route('admin.user.index');
        $saveRoute = route('admin.user.store');

        $languages = Language::orderBy('name', 'asc')->where('status', 1)->get();
        $currencies = Currency::where('status', 'Active')->orderBy('currency_name', 'asc')->get();
        return view('admin.user.addEdit', get_defined_vars());
    }

    public function store(Request $request)
    {
        return $this->saveData($request);
    }

    public function status($id, $status)
    {
        if(!in_array($status, ['Active','Inactive'])){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['status_is_incorrect'] ?? 'status_is_incorrect'
            ];
        }
        
        $user = Auth::user();
        $editData = User::where('id', $id)->where('is_staff', 0)->first();
        if(empty($editData)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        if($editData->user_type == 'admin'){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['you_cant_update_admin_status'] ?? 'you_cant_update_admin_status'
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

    public function edit($id)
    {
        if($id == 1){
            abort(404);
        }

        $user = Auth::user();
        $listRoute = route('admin.user.index');
        $saveRoute = route('admin.user.update', $id);

        $editData = User::with('company')->where('is_staff', 0)->where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }
        // if($user->user_type != 'admin' && $editData->user_id != $user->id){
            //     abort(404);
            // }
            
        //dd($editData);

        $languages = Language::orderBy('name', 'asc')->where('status', 1)->get();
        $currencies = Currency::where('status', 'Active')->orderBy('currency_name', 'asc')->get();
        return view('admin.user.addEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        return $this->saveData($request, $id);
    }

    public function destroy($id)
    {
        if($id == 1){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        $authUser = Auth::user(); // renamed to $authUser for clarity
        $data = User::where('id', $id)->where('is_staff', 0)->first();
        if (empty($data)) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        // if($authUser->user_type != 'admin' && $data->user_id != $authUser->id){
        //     return [
        //         'is_success' => 0,
        //         'icon' => 'error',
        //         'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
        //     ];
        // }

        // Wrap in DB transaction to avoid partial deletions
        DB::beginTransaction();
        try {
            // Recursive deletion closure
            $deleteUser = function ($userData, $deletedBy) use (&$deleteUser) {
                if (!$userData) {
                    return;
                }

                // Delete logo if exists
                if (!empty($userData->image)) {
                    deleteUploadedFile($userData->image);
                }

                // Delete related UserCompany + files
                $companies = UserCompany::where('user_id', $userData->id)->get();
                foreach ($companies as $company) {
                    foreach (['light_logo', 'dark_logo', 'light_icon', 'dark_icon', 'light_seal', 'dark_seal'] as $field) {
                        if (!empty($company->$field)) {
                            deleteUploadedFile($company->$field);
                        }
                    }
                    $company->delete();
                }

                // Delete child staff users recursively
                $staffUsers = User::where('parent_id', $userData->id)->get();
                foreach ($staffUsers as $staff) {
                    $deleteUser($staff, $deletedBy);
                }

                // Mark deleted_by and delete
                $userData->deleted_by = $deletedBy;
                $userData->save();
                $userData->delete();
            };

            // Start with main user (should delete $data, not $authUser)
            $deleteUser($data, $authUser->id);

            DB::commit();

            return [
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['delete_success'] ?? 'Deleted successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('User delete error', ['error' => $e->getMessage()]);

            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_deleting_error'] ?? 'data_deleting_error'
            ];
        }
    }



    public function saveData(Request $request, $id = null)
    {
        $userId = $id; // or however you determine update

        // $messages = getCurrentTranslation();
        // $validator = Validator::make($request->all(), [
        //     'name' => 'required|string|max:255',
        //     'image' => 'nullable|mimes:jpg,jpeg,png|max:3072',
        //     'address' => 'nullable|string|max:255',
        //     'phone' => 'nullable|string',
        //     'email' => 'required|email|max:255|unique:users,email,' . ($userId ?? 'NULL'),
        //     'email_verified_at' => 'nullable|date',
        //     'password' => $userId ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',
        //     'status' => 'required|in:Active,Inactive',

        //     'company_name' => 'required|string|max:255',
        //     'tagline' => 'nullable|string|max:255',
        //     'invoice_prefix' => 'nullable|string|max:255',
        //     'company_invoice_id' => 'nullable|string|max:255',

        //     'light_logo' => 'nullable|mimes:jpg,jpeg,png|max:3072',
        //     'dark_logo' => 'nullable|mimes:jpg,jpeg,png|max:3072',
        //     'light_icon' => 'nullable|mimes:jpg,jpeg,png|max:3072',
        //     'dark_icon' => 'nullable|mimes:jpg,jpeg,png|max:3072',
        //     'light_seal' => 'nullable|mimes:jpg,jpeg,png|max:3072',
        //     'dark_seal' => 'nullable|mimes:jpg,jpeg,png|max:3072',

        //     'phone_1' => 'required|string|max:20',
        //     'phone_2' => 'nullable|string|max:20',
        //     'email_1' => 'required|email|max:255',
        //     'email_2' => 'nullable|email|max:255',
        //     'currency_id' => 'required|exists:currencies,id',

        //     'default_language' => 'required|exists:languages,code',
        // ]);

        $messages = getCurrentTranslation();

        $logoMimes = 'heic,jpg,jpeg,png';
        $maxImageSize = 3072;

        $validator = Validator::make($request->all(), [
            'admin' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'image' => 'nullable|mimes:' . $logoMimes . '|max:' . $maxImageSize,
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'required|email|max:255|unique:users,email,' . ($userId ?? 'NULL'),
            'email_verified_at' => 'nullable|date',
            'password' => $userId ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',
            'status' => 'required|in:Active,Inactive',

            'company_name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'website_url' => 'nullable|string|max:255',
            'invoice_prefix' => 'nullable|string|max:255',
            'company_invoice_id' => 'nullable|string|max:255',

            'light_logo' => 'nullable|mimes:' . $logoMimes . '|max:' . $maxImageSize,
            'dark_logo' => 'nullable|mimes:' . $logoMimes . '|max:' . $maxImageSize,
            'light_icon' => 'nullable|mimes:' . $logoMimes . '|max:' . $maxImageSize,
            'dark_icon' => 'nullable|mimes:' . $logoMimes . '|max:' . $maxImageSize,
            'light_seal' => 'nullable|mimes:' . $logoMimes . '|max:' . $maxImageSize,
            'dark_seal' => 'nullable|mimes:' . $logoMimes . '|max:' . $maxImageSize,

            'phone_1' => 'required|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'email_1' => 'required|email|max:255',
            'email_2' => 'nullable|email|max:255',
            'currency_id' => 'required|exists:currencies,id',

            'default_language' => 'required|exists:languages,code',
        ], [
            // Required
            'required' => $messages['required_message'] ?? 'This field is required.',
            // Unique
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            // Specific max length for certain fields (overrides generic)
            'designation.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'name.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'address.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'company_name.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'tagline.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'website_url.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'invoice_prefix.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'company_invoice_id.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'phone_1.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 20',
            'phone_2.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 20',
            'email.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'email_1.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            'email_2.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            // File size validations
            'image.max' => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
            'light_logo.max' => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
            'dark_logo.max' => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
            'light_icon.max' => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
            'dark_icon.max' => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
            'light_seal.max' => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
            'dark_seal.max' => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
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

        $user = Auth::user();
        $user = null;
        if (isset($id)) {
            $user = User::with('company')->where('is_staff', 0)->where('id', $id)->first();
            if(empty($user)){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
            // if($user->user_type != 'admin' && $user->user_id != $user->id){
            //     return [
            //         'is_success' => 0,
            //         'icon' => 'error',
            //         'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            //     ];
            // }
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
        

        DB::beginTransaction();
        try {

            $userType = 'user';
            $submittedPermissions = $request->permissions ?? [];
            $validPermissions = $this->filterValidPermissions($submittedPermissions, $userType);
            $user->permissions = $validPermissions;

            //$user->user_id = $userId;
            $user->name = $request->name ?? null;
            $user->designation = $request->designation ?? null;
            $user->image = $image;
            $user->address = $request->address;
            $user->phone = $request->phone;
            $user->email = $request->email;
            $user->email_verified_at = $request->email_verified_at;
            if(isset($request->password)){
                $user->password = bcrypt($request->password);
            }
            if($user->user_type != 'admin' && $user->id != 1){
                $user->status = $request->status;
            }
            
            // System Settings
            $user->default_language = $request->default_language;
            
            $user->save();

            // Update staff permissions
            $staffs = User::where('parent_id', $user->id)->get();
            foreach ($staffs as $staff) {
                $staff->permissions = array_values(array_intersect(
                    $staff->permissions ?? [], 
                    $user->permissions ?? []
                ));
                $staff->save();
            }

            $company = UserCompany::where('user_id', $user->id)->first();
            if(empty($company)){
                $company = new UserCompany();
            }else{

            }

            $light_logo = null;
            if($request->hasFile('light_logo')){
                $oldFile = $company->light_logo ?? null;
                $light_logo = handleImageUpload($request->light_logo, 500, 500, $folderName='company', 'light_logo', $oldFile); //$uploadedFile, $resizeMaxWidth, $resizeMaxHeight, $folderName, $fileName ,$oldFile
            }
            if($light_logo == null && !empty($company)){
                $light_logo = $company->light_logo;
            }

            $dark_logo = null;
            if($request->hasFile('dark_logo')){
                $oldFile = $company->dark_logo ?? null;
                $dark_logo = handleImageUpload($request->dark_logo, 500, 500, $folderName='company', 'dark_logo', $oldFile); //$uploadedFile, $resizeMaxWidth, $resizeMaxHeight, $folderName, $fileName ,$oldFile
            }
            if($dark_logo == null && !empty($company)){
                $dark_logo = $company->dark_logo;
            }

            $light_icon = null;
            if($request->hasFile('light_icon')){
                $oldFile = $company->light_icon ?? null;
                $light_icon = handleImageUpload($request->light_icon, 500, 500, $folderName='company', 'light_icon', $oldFile); //$uploadedFile, $resizeMaxWidth, $resizeMaxHeight, $folderName, $fileName ,$oldFile
            }
            if($light_icon == null && !empty($company)){
                $light_icon = $company->light_icon;
            }

            $dark_icon = null;
            if($request->hasFile('dark_icon')){
                $oldFile = $company->dark_icon ?? null;
                $dark_icon = handleImageUpload($request->dark_icon, 500, 500, $folderName='company', 'dark_icon', $oldFile); //$uploadedFile, $resizeMaxWidth, $resizeMaxHeight, $folderName, $fileName ,$oldFile
            }
            if($dark_icon == null && !empty($company)){
                $dark_icon = $company->dark_icon;
            }

            $light_seal = null;
            if($request->hasFile('light_seal')){
                $oldFile = $company->light_seal ?? null;
                $light_seal = handleImageUpload($request->light_seal, 500, 500, $folderName='company', 'light_seal', $oldFile); //$uploadedFile, $resizeMaxWidth, $resizeMaxHeight, $folderName, $fileName ,$oldFile
            }
            if($light_seal == null && !empty($company)){
                $light_seal = $company->light_seal;
            }

            $dark_seal = null;
            if($request->hasFile('dark_seal')){
                $oldFile = $company->dark_seal ?? null;
                $dark_seal = handleImageUpload($request->dark_seal, 500, 500, $folderName='company', 'dark_seal', $oldFile); //$uploadedFile, $resizeMaxWidth, $resizeMaxHeight, $folderName, $fileName ,$oldFile
            }
            if($dark_seal == null && !empty($company)){
                $dark_seal = $company->dark_seal;
            }

            $company->user_id = $user->id;
            $company->company_name = $request->company_name;
            $company->tagline = $request->tagline;
            $company->website_url = $request->website_url;
            $company->invoice_prefix = $request->invoice_prefix;
            $company->company_invoice_id = $request->company_invoice_id;
            $company->light_logo = $light_logo;
            $company->dark_logo = $dark_logo;
            $company->light_icon = $light_icon;
            $company->dark_icon = $dark_icon;
            $company->light_seal = $light_seal;
            $company->dark_seal = $dark_seal;
            $company->address = $request->address;
            $company->phone_1 = $request->phone_1;
            $company->phone_2 = $request->phone_2;
            $company->email_1 = $request->email_1;
            $company->email_2 = $request->email_2;
            $company->currency_id = $request->currency_id;
            $company->save();
            

            DB::commit();
            return [
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('User store error', ['error' => $e->getMessage()]);

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