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
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        if (!hasPermission('department.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $createRoute = hasPermission('department.create') ? route('admin.department.create') : '';
        $dataTableRoute = hasPermission('department.index') ? route('admin.department.datatable') : '';

        return view('admin.department.index', get_defined_vars());
    }

    public function datatable()
    {
        $user = Auth::user();
        $query = Department::with(['creator'])->latest();

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
                if (!hasPermission('department.status')) {
                    // Just return plain status text without toggle
                    return '
                        <span class="' . ($row->status == 1 ? 'text-success' : 'text-danger') . '">'
                            . ($row->status == 1 ? 'Active' : 'Inactive') .
                        '</span>';
                }

                // Toggle status: if 1 then 0 else 1
                $newStatus = $row->status == 1 ? 0 : 1;

                // Generate URL with GET parameters (id and new status)
                $statusUrl = route('admin.department.status', ['id' => $row->id, 'status' => $newStatus]);

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
                $editUrl   = route('admin.department.edit', $row->id);
                $deleteUrl = route('admin.department.destroy', $row->id);

                $buttons = '';

                // Edit button (requires permission)
                if (hasPermission('department.edit')) {
                    $buttons .= '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                // Delete button (requires permission — add condition if needed)
                if (hasPermission('department.delete')) {
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
        if (!hasPermission('department.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('department.index') ? route('admin.department.index') : '';
        $saveRoute = hasPermission('department.create') ? route('admin.department.store') : '';

        return view('admin.department.addEdit', get_defined_vars());
    }

    public function createAjax(Request $request)
    {
        if (!hasPermission('department.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('department.index') ? route('admin.department.index') : '';
        $saveRoute = hasPermission('department.create') ? route('admin.department.store') : '';

        $response = [
            'is_success' => 1,
            'icon' => 'success',
            'view' => view('admin.department.ajaxForm', get_defined_vars())->render()
        ];

        return $response;
    }

    public function store(Request $request)
    {
        if (!hasPermission('department.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        if (!hasPermission('department.create')) {
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
        if (!hasPermission('department.status')) {
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
        $department = Department::where('id', $id)->first();
        if(empty($department)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        $department->status = $status;
        $department->updated_by = $user->id;
        $department->save();
        
        $statusName = $status == 1 ? 'Active' : 'Inactive';
        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => (getCurrentTranslation()['status_updated'] ?? 'status_updated') . ' (' . $statusName . ')',
        ];
    }

    public function edit($id)
    {
        if (!hasPermission('department.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('department.index') ? route('admin.department.index') : '';
        $saveRoute = hasPermission('department.edit') ? route('admin.department.update', $id) : '';

        $editData = Department::where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }
            
        return view('admin.department.addEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('department.edit')) {
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
        if (!hasPermission('department.delete')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $data = Department::where('id', $id)->first();
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
            'name' => 'required|string|max:255|unique:departments,name,' . ($id ?? 'NULL'),
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
        $department = null;
        if (isset($id)) {
            $department = Department::where('id', $id)->first();
            if(empty($department)){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
        }

        if (empty($department)) {
            $department = new Department();
            $department->created_by = Auth::id();
        } else {
            $department->updated_by = Auth::id();
        }
        

        DB::beginTransaction();
        try {
            $department->name = $request->name ?? null;
            $department->status = $request->status ?? 0;

            $department->save();


            DB::commit();
            return [
                'is_success' => 1,
                'icon' => 'success',
                'is_ajax_modal' => $request->is_ajax_modal,
                'for_input' => $request->for_input,
                'created_data' => $department,
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Department store error', ['error' => $e->getMessage()]);

            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_saving_error'] ?? 'data_saving_error'
            ];
        }
    }
    
}
