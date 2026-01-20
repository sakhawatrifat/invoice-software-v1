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

use App\Models\User;
use App\Models\IssuedSupplier;

class IssuedSupplierController extends Controller
{
    public function index()
    {
        if (!hasPermission('issuedSupplier')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $createRoute = hasPermission('issuedSupplier') ? route('issuedSupplier.create') : '';
        $dataTableRoute = hasPermission('issuedSupplier') ? route('issuedSupplier.datatable') : '';

        return view('common.setup.issuedSupplier.index', get_defined_vars());
    }

    public function datatable()
    {
        $user = Auth::user();
        $query = IssuedSupplier::latest();

        if (request()->has('search') && $search = request('search')['value']) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status', function ($row) {
                if (!hasPermission('issuedSupplier')) {
                    return '
                        <span class="' . ($row->status == 1 ? 'text-success' : 'text-danger') . '">'
                            . ($row->status == 1 ? 'Active' : 'Inactive') .
                        '</span>';
                }

                $newStatus = $row->status == 1 ? 0 : 1;
                $statusUrl = route('issuedSupplier.status', ['id' => $row->id, 'status' => $newStatus]);

                return '
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input toggle-table-data-status"
                            data-id="' . $row->id . '"
                            data-url="' . $statusUrl . '"
                            ' . ($row->status == 1 ? 'checked' : '') . '>
                    </div>';
            })
            ->addColumn('action', function ($row) {
                $editUrl   = route('issuedSupplier.edit', $row->id);
                $deleteUrl = route('issuedSupplier.destroy', $row->id);

                $buttons = '';

                if (hasPermission('issuedSupplier')) {
                    $buttons .= '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                if (hasPermission('issuedSupplier')) {
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
        if (!hasPermission('issuedSupplier')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('issuedSupplier') ? route('issuedSupplier.index') : '';
        $saveRoute = hasPermission('issuedSupplier') ? route('issuedSupplier.store') : '';

        return view('common.setup.issuedSupplier.addEdit', get_defined_vars());
    }

    public function createAjax(Request $request)
    {
        if (!hasPermission('issuedSupplier')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('issuedSupplier') ? route('issuedSupplier.index') : '';
        $saveRoute = hasPermission('issuedSupplier') ? route('issuedSupplier.store') : '';

        $response = [
            'is_success' => 1,
            'icon' => 'success',
            'view' => view('common.setup.issuedSupplier.ajaxForm', get_defined_vars())->render()
        ];

        return $response;
    }

    public function store(Request $request)
    {
        if (!hasPermission('issuedSupplier')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        if (!hasPermission('issuedSupplier')) {
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
        if (!hasPermission('issuedSupplier')) {
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
        $instructionSource = IssuedSupplier::where('id', $id)->first();
        if(empty($instructionSource)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        $instructionSource->status = $status;
        $instructionSource->save();
        
        $statusName = $status == 1 ? 'Active' : 'Inactive';
        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => (getCurrentTranslation()['status_updated'] ?? 'status_updated') . ' (' . $statusName . ')',
        ];
    }

    public function edit($id)
    {
        if (!hasPermission('issuedSupplier')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('issuedSupplier') ? route('issuedSupplier.index') : '';
        $saveRoute = hasPermission('issuedSupplier') ? route('issuedSupplier.update', $id) : '';

        $editData = IssuedSupplier::where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }

        return view('common.setup.issuedSupplier.addEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('issuedSupplier')) {
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
        if (!hasPermission('issuedSupplier')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $data = IssuedSupplier::where('id', $id)->first();
        if(empty($data)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

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

        $logoMimes = 'heic,jpg,jpeg,png';
        $maxImageSize = 3072;
        $rules = [
            'name' => 'required|string|max:255|unique:airlines,name,' . ($id ?? 'NULL'),
            'status' => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'required' => $messages['required_message'] ?? 'This field is required.',
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            'name.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '255',
            'image' => $messages['image_message'] ?? 'This must be an image.',
            'mimes' => ($messages['mimes_message'] ?? 'The file must be of type') . ' ('.$logoMimes.').',
            'in' => $messages['in_message'] ?? 'The selected value is invalid.',
            'logo.max' => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $user = Auth::user();
        $instructionSource = null;
        if (isset($id)) {
            $instructionSource = IssuedSupplier::where('id', $id)->first();
            if(empty($instructionSource)){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
        }

        if (empty($instructionSource)) {
            $instructionSource = new IssuedSupplier();
        }

        DB::beginTransaction();
        try {
            $instructionSource->name = $request->name ?? null;
            $instructionSource->status = $request->status ?? 0;

            $instructionSource->save();

            DB::commit();
            $response = [
                'is_success' => 1,
                'icon' => 'success',
                'is_ajax_modal' => $request->is_ajax_modal,
                'for_input' => $request->for_input,
                'created_data' => $instructionSource,
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
            
            // Add redirect_url only when creating new data (not updating) and not in ajax modal
            if ((!isset($id) || empty($id)) && !$request->is_ajax_modal) {
                $response['redirect_url'] = route('issuedSupplier.index');
            }
            
            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('IssuedSupplier store error', ['error' => $e->getMessage()]);

            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_saving_error'] ?? 'data_saving_error'
            ];
        }
    }
}
