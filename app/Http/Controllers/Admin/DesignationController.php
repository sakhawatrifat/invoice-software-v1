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
use App\Models\Designation;

class DesignationController extends Controller
{
    public function index()
    {
        if (!hasPermission('designation.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $createRoute = hasPermission('designation.create') ? route('admin.designation.create') : '';
        $dataTableRoute = hasPermission('designation.index') ? route('admin.designation.datatable') : '';

        return view('admin.designation.index', get_defined_vars());
    }

    public function datatable()
    {
        $user = Auth::user();
        $query = Designation::with(['creator'])->latest();

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
            ->addColumn('status', function ($row) {
                if (!hasPermission('designation.status')) {
                    // Just return plain status text without toggle
                    return '
                        <span class="' . ($row->status == 1 ? 'text-success' : 'text-danger') . '">'
                            . ($row->status == 1 ? 'Active' : 'Inactive') .
                        '</span>';
                }

                // Toggle status: if 1 then 0 else 1
                $newStatus = $row->status == 1 ? 0 : 1;

                // Generate URL with GET parameters (id and new status)
                $statusUrl = route('admin.designation.status', ['id' => $row->id, 'status' => $newStatus]);

                return '
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input toggle-table-data-status"
                            data-id="' . $row->id . '"
                            data-url="' . $statusUrl . '"
                            ' . ($row->status == 1 ? 'checked' : '') . '>
                    </div>';
            })

            ->addColumn('created_by', function ($row) {
                return $row->creator ? $row->creator->name : 'N/A';
            })
            ->addColumn('action', function ($row) {
                $editUrl   = route('admin.designation.edit', $row->id);
                $deleteUrl = route('admin.designation.destroy', $row->id);

                $buttons = '';

                // Edit button (requires permission)
                if (hasPermission('designation.edit')) {
                    $buttons .= '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                // Delete button (requires permission — add condition if needed)
                if (hasPermission('designation.delete')) {
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
            ->rawColumns(['status', 'action'])
            ->make(true);
    }



    public function create()
    {
        if (!hasPermission('designation.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('designation.index') ? route('admin.designation.index') : '';
        $saveRoute = hasPermission('designation.create') ? route('admin.designation.store') : '';

        return view('admin.designation.addEdit', get_defined_vars());
    }

    public function createAjax(Request $request)
    {
        if (!hasPermission('designation.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('designation.index') ? route('admin.designation.index') : '';
        $saveRoute = hasPermission('designation.create') ? route('admin.designation.store') : '';

        $response = [
            'is_success' => 1,
            'icon' => 'success',
            'view' => view('admin.designation.ajaxForm', get_defined_vars())->render()
        ];

        return $response;
    }

    public function store(Request $request)
    {
        if (!hasPermission('designation.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        if (!hasPermission('designation.create')) {
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
        if (!hasPermission('designation.status')) {
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
        $designation = Designation::where('id', $id)->first();
        if(empty($designation)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        $designation->status = $status;
        $designation->updated_by = $user->id;
        $designation->save();
        
        $statusName = $status == 1 ? 'Active' : 'Inactive';
        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => (getCurrentTranslation()['status_updated'] ?? 'status_updated') . ' (' . $statusName . ')',
        ];
    }

    public function edit($id)
    {
        if (!hasPermission('designation.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('designation.index') ? route('admin.designation.index') : '';
        $saveRoute = hasPermission('designation.edit') ? route('admin.designation.update', $id) : '';

        $editData = Designation::where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }
            
        return view('admin.designation.addEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('designation.edit')) {
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
        if (!hasPermission('designation.delete')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $data = Designation::where('id', $id)->first();
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

        $rules = [
            'name' => 'required|string|max:255|unique:designations,name,' . ($id ?? 'NULL'),
            'status' => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'required' => $messages['required_message'] ?? 'This field is required.',
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            'name.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '255',
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
        $designation = null;
        if (isset($id)) {
            $designation = Designation::where('id', $id)->first();
            if(empty($designation)){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
        }

        if (empty($designation)) {
            $designation = new Designation();
            $designation->created_by = Auth::id();
        } else {
            $designation->updated_by = Auth::id();
        }
        

        DB::beginTransaction();
        try {
            $designation->name = $request->name ?? null;
            $designation->status = $request->status ?? 0;

            $designation->save();


            DB::commit();
            return [
                'is_success' => 1,
                'icon' => 'success',
                'is_ajax_modal' => $request->is_ajax_modal,
                'for_input' => $request->for_input,
                'created_data' => $designation,
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Designation store error', ['error' => $e->getMessage()]);

            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_saving_error'] ?? 'data_saving_error'
            ];
        }
    }
    
}
