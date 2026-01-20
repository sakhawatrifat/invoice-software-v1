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
use App\Models\Airline;

class AirlineController extends Controller
{
    public function index()
    {
        if (!hasPermission('airline.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $createRoute = hasPermission('airline.create') ? route('admin.airline.create') : '';
        $dataTableRoute = hasPermission('airline.index') ? route('admin.airline.datatable') : '';

        return view('admin.airline.index', get_defined_vars());
    }

    public function datatable()
    {
        $user = Auth::user();
        $query = Airline::with(['creator'])->latest();

        // Properly grouped global search
        if (request()->has('search') && $search = request('search')['value']) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });

            // Similarly for creator, if needed
            $creatorIds = User::where('name', 'like', "%{$search}%")->pluck('id')->toArray();
            if (!empty($creatorIds)) {
                $query->orWhereIn('created_by', $creatorIds);
            }
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('logo', function ($row) {
                if ($row->logo_url) {
                    return '<img src="' . $row->logo_url . '" alt="Logo" height="40">';
                } else {
                    return 'N/A';
                }
            })
            ->addColumn('status', function ($row) {
                if (!hasPermission('airline.status')) {
                    // Just return plain status text without toggle
                    return '
                        <span class="' . ($row->status == 1 ? 'text-success' : 'text-danger') . '">'
                            . ($row->status == 1 ? 'Active' : 'Inactive') .
                        '</span>';
                }

                // Toggle status: if 1 then 0 else 1
                $newStatus = $row->status == 1 ? 0 : 1;

                // Generate URL with GET parameters (id and new status)
                $statusUrl = route('admin.airline.status', ['id' => $row->id, 'status' => $newStatus]);

                return '
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input toggle-table-data-status"
                            data-id="' . $row->id . '"
                            data-url="' . $statusUrl . '"
                            ' . ($row->status == 1 ? 'checked' : '') . '>
                    </div>';
            })

            // ->addColumn('user_id', function ($row) {
            //     return $row->user ? $row->user->name : 'N/A';
            // })
            ->addColumn('created_by', function ($row) {
                return $row->creator ? $row->creator->name : 'N/A';
            })
            ->addColumn('action', function ($row) {
                $editUrl   = route('admin.airline.edit', $row->id);
                $deleteUrl = route('admin.airline.destroy', $row->id);

                $buttons = '';

                // Edit button (requires permission)
                if (hasPermission('airline.edit')) {
                    $buttons .= '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                // Delete button (requires permission — add condition if needed)
                if (hasPermission('airline.delete')) {
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
            ->rawColumns(['logo', 'status', 'action'])
            ->make(true);
    }



    public function create()
    {
        if (!hasPermission('airline.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('airline.index') ? route('admin.airline.index') : '';
        $saveRoute = hasPermission('airline.create') ? route('admin.airline.store') : '';

        return view('admin.airline.addEdit', get_defined_vars());
    }

    public function createAjax(Request $request)
    {
        if (!hasPermission('airline.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('airline.index') ? route('admin.airline.index') : '';
        $saveRoute = hasPermission('airline.create') ? route('admin.airline.store') : '';

        $response = [
            'is_success' => 1,
            'icon' => 'success',
            'view' => view('admin.airline.ajaxForm', get_defined_vars())->render()
        ];

        return $response;
    }

    public function store(Request $request)
    {
        if (!hasPermission('airline.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        if (!hasPermission('airline.create')) {
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
        if (!hasPermission('airline.status')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        if(!in_array($status, [0,1])){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['status_is_incorrect'] ?? 'status_is_incorrect'
            ];
        }
        
        $user = Auth::user();
        $airline = Airline::where('id', $id)->first();
        if(empty($airline)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }
        // if($user->user_type != 'admin' && $airline->user_id != $user->id){
        //     return [
        //         'is_success' => 0,
        //         'icon' => 'error',
        //         'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
        //     ];
        // }

        $airline->status = $status;
        $airline->updated_by = $user->id;
        $airline->save();
        
        $statusName = $status == 1 ? 'Active' : 'Inactive';
        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => (getCurrentTranslation()['status_updated'] ?? 'status_updated') . ' (' . $statusName . ')',
        ];
    }

    public function edit($id)
    {
        if (!hasPermission('airline.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('airline.index') ? route('admin.airline.index') : '';
        $saveRoute = hasPermission('airline.edit') ? route('admin.airline.update', $id) : '';

        $editData = Airline::where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }
        // if($user->user_type != 'admin' && $editData->user_id != $user->id){
            //     abort(404);
            // }
            
        //dd($editData);
        return view('admin.airline.addEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('airline.edit')) {
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
        if (!hasPermission('airline.delete')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $data = Airline::where('id', $id)->first();
        if(empty($data)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
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



    public function saveData(Request $request, $id = null)
    {
        // $validator = Validator::make($request->all(), [
        //     'name' => 'required|string|max:255|unique:airlines,name,' . ($id ?? 'NULL'),
        //     'logo' => 'nullable|mimes:jpg,jpeg,png|max:3072',
        //     'description' => 'nullable',
        //     'status' => 'required|in:0,1',
        // ]);

        $messages = getCurrentTranslation();

        $logoMimes = 'heic,jpg,jpeg,png';
        $maxImageSize = 3072;
        $rules = [
            'name' => 'required|string|max:255|unique:airlines,name,' . ($id ?? 'NULL'),
            'logo' => 'nullable|mimes:'.$logoMimes.'|max:' . $maxImageSize,
            'description' => 'nullable',
            'status' => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'required' => $messages['required_message'] ?? 'This field is required.',
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            'name.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '255',
            'image' => $messages['image_message'] ?? 'This must be an image.',
            'mimes' => ($messages['mimes_message'] ?? 'The file must be of type') . ' ('.$logoMimes.').',
            'in' => $messages['in_message'] ?? 'The selected value is invalid.',
            // File size validations
            'logo.max' => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
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
        $airline = null;
        if (isset($id)) {
            $airline = Airline::where('id', $id)->first();
            if(empty($airline)){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
            // if($user->user_type != 'admin' && $airline->user_id != $user->id){
            //     return [
            //         'is_success' => 0,
            //         'icon' => 'error',
            //         'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            //     ];
            // }
        }
        
        $logo = null;
        if($request->hasFile('logo')){
            $oldFile = $airline->logo ?? null;
            $logo = handleImageUpload($request->logo, 100, 100, $folderName='airline', 'airline-logo', $oldFile); //$uploadedFile, $resizeMaxWidth, $resizeMaxHeight, $folderName, $fileName ,$oldFile
        }

        if($logo == null && !empty($airline)){
            $logo = $airline->logo;
        }

        $queryForUserId = $request->user_id ?? Auth::id();
        $userId = $request->user_id ?? Auth::id();

        if (empty($airline)) {
            $airline = new Airline();
            $airline->created_by = Auth::id();
        } else {
            $airline->updated_by = Auth::id();
        }
        

        DB::beginTransaction();
        try {
            //$airline->user_id = $userId;
            $airline->name = $request->name ?? null;
            $airline->logo = $logo;
            $airline->description = $request->description ?? 0;
            $airline->status = $request->status ?? 0;

            $airline->save();


            DB::commit();
            $response = [
                'is_success' => 1,
                'icon' => 'success',
                'is_ajax_modal' => $request->is_ajax_modal,
                'for_input' => $request->for_input,
                'created_data' => $airline,
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
            
            // Add redirect_url only when creating new data (not updating) and not in ajax modal
            if ((!isset($id) || empty($id)) && !$request->is_ajax_modal) {
                $response['redirect_url'] = route('admin.airline.index');
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