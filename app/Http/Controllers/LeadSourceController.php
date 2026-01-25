<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadSource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class LeadSourceController extends Controller
{
    public function index()
    {
        if (!hasPermission('leadSource')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_disallow'] ?? 'permission_disallow',
            ];
        }

        $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
        $getCurrentTranslation = getCurrentTranslation();

        $createRoute = hasPermission('leadSource') ? route('leadSource.create') : '';
        $dataTableRoute = hasPermission('leadSource') ? route('leadSource.datatable') : '';

        return view('common.crm.leadSource.index', compact('layout', 'getCurrentTranslation', 'createRoute', 'dataTableRoute'));
    }

    public function datatable()
    {
        if (!hasPermission('leadSource')) {
            return [
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
            ];
        }

        $user = Auth::user();
        $query = LeadSource::where('user_id', $user->business_id)->latest();

        if (request()->has('search') && ($search = request('search')['value'])) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status', function ($row) {
                if (!hasPermission('leadSource')) {
                    return '
                        <span class="' . ($row->status ? 'text-success' : 'text-danger') . '">
                            ' . ($row->status ? 'Active' : 'Inactive') . '
                        </span>';
                }

                $newStatus = $row->status ? 0 : 1;
                $statusUrl = route('leadSource.status', ['id' => $row->id, 'status' => $newStatus]);

                return '
                    <div class="form-check form-check-custom form-check-solid form-switch">
                        <input type="checkbox" class="form-check-input toggle-table-data-status"
                               data-id="' . $row->id . '"
                               data-url="' . $statusUrl . '"
                               ' . ($row->status ? 'checked' : '') . '>
                    </div>';
            })
            ->addColumn('action', function ($row) {
                $buttons = '';

                if (hasPermission('leadSource')) {
                    $editUrl = route('leadSource.edit', $row->id);
                    $deleteUrl = route('leadSource.delete', $row->id);

                    $buttons .= '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary me-2">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button class="btn btn-sm btn-danger delete-table-data-btn"
                            data-id="' . $row->id . '"
                            data-url="' . $deleteUrl . '">
                            <i class="fa-solid fa-trash"></i>
                        </button>';
                }

                return $buttons ?: 'N/A';
            })
            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)->format('Y-m-d, H:i');
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function create()
    {
        if (!hasPermission('leadSource')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_disallow'] ?? 'permission_disallow',
            ];
        }

        $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
        $getCurrentTranslation = getCurrentTranslation();

        $listRoute = hasPermission('leadSource') ? route('leadSource.index') : '';
        $saveRoute = hasPermission('leadSource') ? route('leadSource.store') : '';

        return view('common.crm.leadSource.addEdit', compact('layout', 'getCurrentTranslation', 'listRoute', 'saveRoute'));
    }

    public function store(Request $request)
    {
        if (!hasPermission('leadSource')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'permission_denied',
            ];
        }

        return $this->saveData($request);
    }

    public function status($id, $status)
    {
        if (!hasPermission('leadSource')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'permission_denied',
            ];
        }

        if (!in_array((int) $status, [0, 1], true)) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['status_is_invalid'] ?? 'status_is_invalid',
            ];
        }

        $user = Auth::user();
        $leadSource = LeadSource::where('id', $id)
            ->where('user_id', $user->business_id)
            ->first();

        if (!$leadSource) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found',
            ];
        }

        $leadSource->status = (int) $status;
        $leadSource->updated_at = now();
        $leadSource->save();

        $statusName = $leadSource->status ? 'Active' : 'Inactive';

        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => (getCurrentTranslation()['status_updated'] ?? 'status_updated') . ' (' . $statusName . ')',
        ];
    }

    public function edit($id)
    {
        if (!hasPermission('leadSource')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'permission_denied',
            ];
        }

        $user = Auth::user();
        $getCurrentTranslation = getCurrentTranslation();
        $layout = $user->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';

        $listRoute = hasPermission('leadSource') ? route('leadSource.index') : '';
        $saveRoute = hasPermission('leadSource') ? route('leadSource.update', $id) : '';

        $editData = LeadSource::where('id', $id)
            ->where('user_id', $user->business_id)
            ->first();

        if (!$editData) {
            abort(404);
        }

        return view('common.crm.leadSource.addEdit', compact('layout', 'getCurrentTranslation', 'listRoute', 'saveRoute', 'editData'));
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('leadSource')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_deferred'] ?? 'permission_deferred',
            ];
        }

        return $this->saveData($request, $id);
    }

    public function destroy($id)
    {
        if (!hasPermission('leadSource')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_disallow'] ?? 'permission_disallow',
            ];
        }

        $user = Auth::user();
        $leadSource = LeadSource::where('id', $id)
            ->where('user_id', $user->business_id)
            ->first();

        if (!$leadSource) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found',
            ];
        }

        $leadSource->delete();

        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['data_deleted'] ?? 'data_deleted',
        ];
    }

    protected function saveData(Request $request, $id = null)
    {
        $messages = getCurrentTranslation();

        $rules = [
            'name' => 'required|string|max:255|unique:lead_sources,name' . ($id ? ',' . $id : ''),
            'status' => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'required' => $messages['required_message'] ?? 'This field is required.',
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            'name.max' => ($messages['max_string_message'] ?? 'This field allowed maximum length is: ') . '255',
            'in' => $messages['in_message'] ?? 'The selected value is invalid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors(),
            ]);
        }

        $user = Auth::user();

        $leadSource = $id
            ? LeadSource::where('id', $id)->where('user_id', $user->business_id)->first()
            : new LeadSource();

        if ($id && !$leadSource) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found',
            ];
        }

        $leadSource->user_id = $user->business_id;
        $leadSource->name = $request->name;
        $leadSource->status = (int) $request->status;
        if (!$leadSource->exists) {
            $leadSource->created_by = $user->id;
        }
        $leadSource->updated_at = now();

        $leadSource->save();

        $response = [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved',
        ];

        if (!$id) {
            $response['redirect_url'] = route('leadSource.index');
        }

        return $response;
    }
}

