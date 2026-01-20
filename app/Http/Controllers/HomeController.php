<?php

namespace App\Http\Controllers;


use Auth;
use File;
use Image;
use Session;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Models\Homepage;
use App\Models\Language;
use App\Models\Currency;
use App\Models\User;
use App\Models\UserCompany;

class HomeController extends Controller
{
    public function home()
    {
        // if (Auth::check()) {
        //     $user = Auth::user();

        //     if ($user->user_type === 'admin') {
        //         return redirect()->route('admin.dashboard');
        //     } else {
        //         return redirect()->route('user.dashboard');
        //     }
        // }

        // return redirect()->route('login');

        $homepageData = Homepage::with('language')->where('lang', 'en')->first();

        return view('frontend.homepage.homepage', get_defined_vars());
    }


    public function myProfile()
    {
        $user = Auth::user();
        //$listRoute = route('admin.user.index');
        $saveRoute = route('myProfile.update');

        $editData = User::with('company')->where('id', $user->id)->first();
        if(empty($editData)){
            abort(404);
        }
        // if($user->user_type != 'admin' && $editData->user_id != $user->id){
            //     abort(404);
            // }
            
        //dd($editData);

        $languages = Language::orderBy('name', 'asc')->where('status', 1)->get();
        $currencies = Currency::where('status', 'Active')->orderBy('currency_name', 'asc')->get();
        return view('common.profile.addEdit', get_defined_vars());
    }

    public function myProfileUpdate(Request $request)
    {
        $user = Auth::user();
        return $this->saveProfileData($request, $user->id);
    }


    public function saveProfileData(Request $request, $id = null)
    {
        // if ($request->hasFile('image')) {
        //     $file = $request->file('image');

        //     // Get MIME type
        //     dd($file->getMimeType());
        // }
        
        $userId = $id; // or however you determine update
        $isStaff = (Auth::user()?->is_staff == 1) ? 1 : 0;
        
        // $validator = Validator::make($request->all(), [
        //     'name' => 'required|string|max:255',
        //     'image' => 'nullable|mimes:jpg,jpeg,png|max:3072',
        //     'address' => 'nullable|string|max:255',
        //     'phone' => 'nullable|string',
        //     'email' => 'required|email|max:255|unique:users,email,' . ($userId ?? 'NULL'),

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
            'name' => 'required|string|max:255',
            'image' => 'nullable|mimes:' . $logoMimes . '|max:' . $maxImageSize,
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'required|email|max:255|unique:users,email,' . ($userId ?? 'NULL'),

            'company_name' => $isStaff ? 'nullable|string|max:255' : 'required|string|max:255',
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

            'phone_1' => $isStaff ? 'nullable|string|max:20' : 'required|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'email_1' => $isStaff ? 'nullable|string|max:100' : 'required|string|max:100',
            'email_2' => 'nullable|email|max:255',
            'currency_id' => $isStaff ? 'nullable' : 'required|exists:currencies,id',

            'default_language' => 'required|exists:languages,code',
        ], [
            // Required
            'required' => $messages['required_message'] ?? 'This field is required.',
            // Unique
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            // Specific max length for certain fields (overrides generic)
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
            'password.min' => ($messages['min_string_message'] ?? 'This field allowed minimum character length is: ') . ' 8',
            'password.max' => ($messages['min_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
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

        if (isset($request->current_password)) {
            $validator = Validator::make($request->all(), [
                'current_password' => 'nullable|string|min:8|max:255',
            ], [
                'current_password.min' => ($messages['min_string_message'] ?? 'This field allowed minimum character length is: ') . ' 8',
                'current_password.max' => ($messages['min_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'is_success' => 0,
                    'icon' => 'error',
                    'errors' => $validator->errors()
                ]);
            }

            if (!Hash::check($request->current_password, $request->user()->password)) {
                return response()->json([
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['password_mismatch_error'] ?? 'password_mismatch_error'
                ]);
            }

            $validator = Validator::make($request->all(), [
                'new_password' => 'required|min:8|max:255|confirmed',
            ], [
                'required' => $messages['required_message'] ?? 'This field is required.',
                'new_password.min' => ($messages['min_string_message'] ?? 'This field allowed minimum character length is: ') . ' 8',
                'new_password.max' => ($messages['min_string_message'] ?? 'This field allowed maximum character length is: ') . ' 255',
                'confirmed' => $messages['confirmed_message'] ?? 'The confirmation does not match.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'is_success' => 0,
                    'icon' => 'error',
                    'errors' => $validator->errors()
                ]);
            }
        }

        //dd($request->all());

        $user = Auth::user();
        $user = null;
        if (isset($id)) {
            $user = User::with('company')->where('id', $id)->first();
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
            //$user->user_id = $userId;
            $user->name = $request->name ?? null;
            $user->image = $image;
            $user->address = $request->address;
            $user->phone = $request->phone;
            $user->email = $request->email;
            if(isset($request->new_password)){
                $user->password = bcrypt($request->new_password);
            }

            // System Settings
            $user->default_language = $request->default_language;

            $user->save();

            if($isStaff == 0){
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
            }
            

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
}