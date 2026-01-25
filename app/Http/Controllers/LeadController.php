<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class LeadController extends Controller
{
    public function index()
    {
        if (!hasPermission('lead.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'permission_denied',
            ];
        }

        $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
        $getCurrentTranslation = getCurrentTranslation();

        $createRoute = hasPermission('lead.create') ? route('lead.create') : '';
        $dataTableRoute = hasPermission('lead.index') ? route('lead.datatable') : '';

        return view('common.crm.lead.index', compact('layout', 'getCurrentTranslation', 'createRoute', 'dataTableRoute'));
    }

    public function datatable()
    {
        if (!hasPermission('lead.index')) {
            return [
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
            ];
        }

        $user = Auth::user();
        $query = Lead::with(['source', 'assignedUser'])
            ->where('user_id', $user->business_id)
            ->latest();

        if (request()->has('search') && ($search = request('search')['value'])) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_full_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('company_name', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('priority', 'like', '%' . $search . '%');
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('source_name', function (Lead $row) {
                return optional($row->source)->name ?? 'N/A';
            })
            ->addColumn('assigned_name', function (Lead $row) {
                return optional($row->assignedUser)->name ?? 'N/A';
            })
            ->addColumn('status_badge', function (Lead $row) {
                $class = 'badge-light-secondary';

                if ($row->status === 'New') {
                    $class = 'badge-light-primary';
                } elseif ($row->status === 'Contacted') {
                    $class = 'badge-light-info';
                } elseif ($row->status === 'Qualified') {
                    $class = 'badge-light-success';
                } elseif ($row->status === 'Lost') {
                    $class = 'badge-light-danger';
                } elseif ($row->status === 'Converted To Customer') {
                    $class = 'badge-light-success';
                }

                return '<span class="badge ' . $class . '">' . e($row->status ?? 'N/A') . '</span>';
            })
            ->addColumn('action', function (Lead $row) {
                $buttons = '';

                $detailsUrl = route('lead.show', $row->id);

                if (hasPermission('lead.index')) {
                    $buttons .= '<a href="' . $detailsUrl . '" class="btn btn-sm btn-info me-2" title="Details">
                        <i class="fa-solid fa-eye"></i>
                    </a>';
                }

                if (hasPermission('customerHistory')) {
                    $historyUrl = route('customerHistory.index', [
                        'search' => $row->customer_full_name,
                        'lead_id' => $row->id,
                    ]);

                    $buttons .= '<a href="' . $historyUrl . '" class="btn btn-sm btn-secondary me-2" title="History">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </a>';
                }

                if (hasPermission('lead.edit')) {
                    $editUrl = route('lead.edit', $row->id);
                    $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-primary me-2" title="Edit">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>';
                }

                if (hasPermission('lead.delete')) {
                    $deleteUrl = route('lead.delete', $row->id);
                    $buttons .= '<button class="btn btn-sm btn-danger delete-table-data-btn"
                        data-id="' . $row->id . '"
                        data-url="' . $deleteUrl . '"
                        title="Delete">
                        <i class="fa-solid fa-trash"></i>
                    </button>';
                }

                return $buttons ?: 'N/A';
            })
            ->editColumn('created_at', function (Lead $row) {
                return optional($row->created_at)->format('Y-m-d H:i');
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function create()
    {
        if (!hasPermission('lead.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'permission_denied',
            ];
        }

        $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
        $getCurrentTranslation = getCurrentTranslation();

        $listRoute = hasPermission('lead.index') ? route('lead.index') : '';
        $saveRoute = hasPermission('lead.create') ? route('lead.store') : '';

        $leadSources = LeadSource::where('user_id', Auth::user()->business_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $users = User::where('user_type', 'user')->orderBy('name')->get();

        return view('common.crm.lead.addEdit', compact('layout', 'getCurrentTranslation', 'listRoute', 'saveRoute', 'leadSources', 'users'));
    }

    public function store(Request $request)
    {
        if (!hasPermission('lead.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['something_went_wrong'] ?? 'something_went_wrong',
            ];
        }

        return $this->saveData($request);
    }

    public function edit($id)
    {
        if (!hasPermission('lead.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'permission_denied',
            ];
        }

        $user = Auth::user();
        $getCurrentTranslation = getCurrentTranslation();
        $layout = $user->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';

        $lead = Lead::where('id', $id)
            ->where('user_id', $user->business_id)
            ->first();

        if (!$lead) {
            abort(404);
        }

        $leadSources = LeadSource::where('user_id', $user->business_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $users = User::where('user_type', 'user')->orderBy('name')->get();

        $listRoute = hasPermission('lead.index') ? route('lead.index') : '';
        $saveRoute = hasPermission('lead.edit') ? route('lead.update', $id) : '';

        return view('common.crm.lead.addEdit', compact('layout', 'getCurrentTranslation', 'lead', 'leadSources', 'users', 'listRoute', 'saveRoute'));
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('lead.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'permission_denied',
            ];
        }

        return $this->saveData($request, $id);
    }

    public function show($id)
    {
        if (!hasPermission('lead.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'permission_denied',
            ];
        }

        $user = Auth::user();
        $getCurrentTranslation = getCurrentTranslation();
        $layout = $user->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';

        $lead = Lead::with(['source', 'assignedUser'])
            ->where('id', $id)
            ->where('user_id', $user->business_id)
            ->first();

        if (!$lead) {
            abort(404);
        }

        $listRoute = hasPermission('lead.index') ? route('lead.index') : '';
        $editRoute = hasPermission('lead.edit') ? route('lead.edit', $id) : '';
        $customerHistoryUrl = hasPermission('customerHistory')
            ? route('customerHistory.index', ['search' => $lead->customer_full_name, 'lead_id' => $lead->id])
            : null;

        return view(
            'common.crm.lead.show',
            compact('layout', 'getCurrentTranslation', 'lead', 'listRoute', 'editRoute', 'customerHistoryUrl')
        );
    }

    public function destroy($id)
    {
        if (!hasPermission('lead.delete')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'permission_denied',
            ];
        }

        $user = Auth::user();

        $lead = Lead::where('id', $id)
            ->where('user_id', $user->business_id)
            ->first();

        if (!$lead) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found',
            ];
        }

        $lead->delete();

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
            'customer_full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'source_id' => 'nullable|integer|exists:lead_sources,id',
            'status' => 'nullable|in:New,Contacted,Qualified,Lost,Converted To Customer',
            'priority' => 'nullable|in:Low,Medium,High',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'notes' => 'nullable|string',
            'last_contacted_at' => 'nullable|date_format:Y-m-d H:i',
            'converted_customer_at' => 'nullable|date_format:Y-m-d H:i',
        ];

        $validator = Validator::make(
            $request->all(),
            $rules,
            [
                'required' => $messages['required_message'] ?? 'This field is required.',
                'email' => $messages['email_message'] ?? 'Please enter a valid email address.',
                'max' => $messages['max_string_message'] ?? 'This field allowed maximum character length is: ',
                'in' => $messages['in_message'] ?? 'The selected value is invalid.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors(),
            ]);
        }

        $user = Auth::user();

        $lead = $id
            ? Lead::where('id', $id)->where('user_id', $user->business_id)->first()
            : new Lead();

        if ($id && !$lead) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found',
            ];
        }

        $lead->user_id = $user->business_id;
        $lead->customer_full_name = $request->customer_full_name;
        $lead->email = $request->email;
        $lead->phone = $request->phone;
        $lead->company_name = $request->company_name;
        $lead->website = $request->website;
        $lead->source_id = $request->source_id;
        $lead->status = $request->status;
        $lead->priority = $request->priority;
        $lead->assigned_to = $request->assigned_to;
        $lead->notes = $request->notes;
        $lead->last_contacted_at = $request->last_contacted_at
            ? Carbon::createFromFormat('Y-m-d H:i', $request->last_contacted_at)
            : null;
        $lead->converted_customer_at = $request->converted_customer_at
            ? Carbon::createFromFormat('Y-m-d H:i', $request->converted_customer_at)
            : null;

        if (!$lead->exists) {
            $lead->created_by = $user->id;
        } else {
            $lead->updated_by = $user->id;
        }

        $lead->save();

        $response = [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved',
        ];

        if (!$id) {
            $response['redirect_url'] = route('lead.index');
        }

        return $response;
    }
}

