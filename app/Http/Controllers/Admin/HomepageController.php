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
use App\Models\Language;
use App\Models\Homepage;

class HomepageController extends Controller
{
    public function index()
    {
        if (!hasPermission('homepage.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $createRoute = hasPermission('homepage.create') ? route('admin.homepage.create') : '';
        $dataTableRoute = hasPermission('homepage.index') ? route('admin.homepage.datatable') : '';

        $homepageExist = Homepage::orderBy('id', 'asc')->first();

        return view('admin.homepage.index', get_defined_vars());
    }

    public function datatable()
    {
        $user = Auth::user();
        $query = Homepage::with(['language', 'creator'])->latest();

        // Properly grouped global search
        if (request()->has('search') && $search = request('search')['value']) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });

            // Similarly for creator, if needed
            $creatorIds = User::where('name', 'like', "%{$search}%")->pluck('id')->toArray();
            if (!empty($creatorIds)) {
                $query->orWhereIn('created_by', $creatorIds);
            }
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('lang', function ($row) {
                return $row->language ? $row->language->name : 'N/A';
            })
            ->addColumn('banner', function ($row) {
                if ($row->banner_url) {
                    return '<img src="' . $row->banner_url . '" alt="Banner" height="40">';
                } else {
                    return 'N/A';
                }
            })
            ->addColumn('created_by', function ($row) {
                return $row->creator ? $row->creator->name : 'N/A';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('admin.homepage.edit', $row->id);
                $editButton = '
                    <a href="' . $editUrl . '" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                ';

                if ($row->id == 1) {
                    // Only show edit button
                    return $editButton;
                }

                $deleteUrl = route('admin.homepage.destroy', $row->id);
                $deleteButton = '
                    <button class="btn btn-sm btn-danger delete-table-data-btn"
                        data-id="' . $row->id . '"
                        data-url="' . $deleteUrl . '">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                ';

                // Show both edit and delete buttons
                return $editButton . ' ' . $deleteButton;
            })

            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('Y-m-d, H:i');
            })
            ->rawColumns(['banner', 'action'])
            ->make(true);
    }


    public function create()
    {
        if (!hasPermission('homepage.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('homepage.index') ? route('admin.homepage.index') : '';
        $saveRoute = hasPermission('homepage.create') ? route('admin.homepage.store') : '';

        $language = Language::where('code', request()->lang)->first();
        return view('admin.homepage.addEdit', get_defined_vars());
    }

    public function store(Request $request)
    {
        if (!hasPermission('homepage.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        return $this->saveData($request);
    }

    public function edit($id)
    {
        if (!hasPermission('homepage.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('homepage.index') ? route('admin.homepage.index') : '';
        $saveRoute = hasPermission('homepage.edit') ? route('admin.homepage.update', $id) : '';

        $editData = Homepage::with('language')->where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }
        $language = $editData->language;
        // if($user->user_type != 'admin' && $editData->user_id != $user->id){
            //     abort(404);
            // }
            
        //dd($editData);
        return view('admin.homepage.addEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('homepage.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        return $this->saveData($request, $id);
    }







    public function saveData(Request $request, $id = null)
    {
        $messages = getCurrentTranslation();

        $imageMimes = 'heic,jpg,jpeg,png';
        $imageSize = 3072;

        $rules = [
            'lang' => 'required|exists:languages,code',
            'banner' => 'nullable|mimes:' . $imageMimes . '|max:' . $imageSize,
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',

            // Validate the feature content array
            'feartureContent' => 'nullable|array',
            'feartureContent.*.image' => 'nullable|mimes:' . $imageMimes . '|max:' . $imageSize,
            'feartureContent.*.title' => 'nullable|string|max:255',
            'feartureContent.*.description' => 'nullable|string',

            // Validate the content array
            'content' => 'nullable|array',
            'content.*.image' => 'nullable|mimes:' . $imageMimes . '|max:' . $imageSize,
            'content.*.title' => 'nullable|string|max:255',
            'content.*.description' => 'nullable|string',

            'is_registration_enabled' => 'required|in:0,1',
            'auth_bg_image' => 'nullable|mimes:' . $imageMimes . '|max:' . $imageSize,
        ];

        $validator = Validator::make($request->all(), $rules, [
            'exists' => $messages['exists_message'] ?? 'The selected value is invalid.',
            'required' => $messages['required_message'] ?? 'This field is required.',
            'banner.image' => $messages['image_message'] ?? 'This must be an image.',
            'banner.max' => $messages['image_message'] ?? 'File size must be ' . ($imageSize / 1024) . ' MB.',
            'banner.mimes' => ($messages['mimes_message'] ?? 'The file must be of type') . ' (' . $imageMimes . ').',
            'content.*.image.image' => $messages['image_message'] ?? 'This must be an image.',
            'content.*.image.max' => $messages['image_message'] ?? 'File size must be ' . ($imageSize / 1024) . ' MB.',
            'content.*.image.mimes' => ($messages['mimes_message'] ?? 'The file must be of type') . ' (' . $imageMimes . ').',
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
        $homepage = null;
        if (isset($id)) {
            $homepage = Homepage::where('id', $id)->first();
            if(empty($homepage)){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
        }
        
        $banner = null;
        if($request->hasFile('banner')){
            $oldFile = $homepage->banner ?? null;
            $banner = handleImageUpload($request->banner, null, null, $folderName='homepage', 'homepage-banner', $oldFile); //$uploadedFile, $resizeMaxWidth, $resizeMaxHeight, $folderName, $fileName ,$oldFile
        }

        if($banner == null && !empty($homepage)){
            $banner = $homepage->banner;
        }
        
        $authBgImage = null;
        if($request->hasFile('auth_bg_image')){
            $oldFile = $homepage->auth_bg_image ?? null;
            $authBgImage = handleImageUpload($request->auth_bg_image, null, null, $folderName='auth', 'auth-banner', $oldFile); //$uploadedFile, $resizeMaxWidth, $resizeMaxHeight, $folderName, $fileName ,$oldFile
        }

        if($authBgImage == null && !empty($homepage)){
            $authBgImage = $homepage->auth_bg_image;
        }
        

        $queryForUserId = $request->user_id ?? Auth::id();
        $userId = $request->user_id ?? Auth::id();

        if (empty($homepage)) {
            $homepage = new Homepage();
            $homepage->created_by = Auth::id();
        } else {
            $homepage->updated_by = Auth::id();
        }

        //dd($request->all());
        

        DB::beginTransaction();
        try {
            //$homepage->user_id = $userId;
            $homepage->lang = $request->lang ?? 'en';
            $homepage->banner = $banner;
            $homepage->title = $request->title ?? null;
            $homepage->subtitle = $request->subtitle ?? null;
            $homepage->description = $request->description ?? null;

            $featureContentData = [];
            if (isset($request->featureContent) && is_array($request->featureContent) && count($request->featureContent)) {
                foreach ($request->featureContent as $key => $item) {
                    if (isArrayNotEmpty($item)) {
                        $oldFile = $item['old_image_url'] ?? null;
                        $featureContentData[$key]['image'] = !empty($item['image'])
                            ? handleImageUpload($item['image'], 300, 300, 'homepage', 'homepage-image', $oldFile)
                            : $oldFile;

                        $featureContentData[$key]['title'] = $item['title'] ?? null;
                        $featureContentData[$key]['details'] = $item['details'] ?? null;
                    }
                }
            }
            $homepage->featureContent = $featureContentData;

            $contentData = [];
            if (isset($request->content) && is_array($request->content) && count($request->content)) {
                foreach ($request->content as $key => $item) {
                    if (isArrayNotEmpty($item)) {
                        $oldFile = $item['old_image_url'] ?? null;
                        $contentData[$key]['image'] = !empty($item['image'])
                            ? handleImageUpload($item['image'], null, null, 'homepage', 'homepage-image', $oldFile)
                            : $oldFile;

                        $contentData[$key]['title'] = $item['title'] ?? null;
                        $contentData[$key]['details'] = $item['details'] ?? null;
                    }
                }
            }
            $homepage->content = $contentData;
            $homepage->is_registration_enabled = $request->is_registration_enabled;
            $homepage->auth_bg_image = $authBgImage;

            $homepage->save();

            //dd($homepage);
            DB::commit();
            $response = [
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
            
            // Add redirect_url only when creating new data (not updating)
            if (!isset($id) || empty($id)) {
                $response['redirect_url'] = route('admin.homepage.index');
            }
            
            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Airline store error', ['error' => $e->getMessage()]);

            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_saving_error'] ?? 'data_saving_error'
            ];
        }
    }

}