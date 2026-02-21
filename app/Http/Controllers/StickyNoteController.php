<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

use App\Models\StickyNote;
use App\Models\StickyNoteActivity;
use App\Models\User;

class StickyNoteController extends Controller
{
    public function index()
    {
        if (!hasPermission('sticky_note.index')) {
            return redirect()->route(Auth::user()->user_type === 'admin' ? 'admin.dashboard' : 'user.dashboard')
                ->with('error', getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }

        $createRoute = hasPermission('sticky_note.create') ? route('sticky_note.create') : '';
        $dataTableRoute = route('sticky_note.datatable');

        return view('common.sticky-note.index', get_defined_vars());
    }

    public function datatable()
    {
        $user = Auth::user();
        $query = StickyNote::with('user', 'creator', 'updater', 'assignedUsers')->visibleToUser($user)->latest();

        return DataTables::of($query)
            ->filter(function ($query) {
                $search = request('search')['value'] ?? null;
                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('note_title', 'like', "%{$search}%")
                            ->orWhere('note_description', 'like', "%{$search}%");
                    });
                    $creatorIds = User::where('name', 'like', "%{$search}%")->pluck('id')->toArray();
                    if (!empty($creatorIds)) {
                        $query->orWhereIn('created_by', $creatorIds);
                    }
                }
            })
            ->addIndexColumn()
            ->addColumn('note_title', function ($row) {
                return $row->note_title ?? '—';
            })
            ->addColumn('deadline', function ($row) {
                return $row->deadline ? $row->deadline->format('Y-m-d H:i') : '—';
            })
            ->addColumn('reminder_datetime', function ($row) {
                return $row->reminder_datetime ? $row->reminder_datetime->format('Y-m-d H:i') : '—';
            })
            ->addColumn('status', function ($row) {
                $status = $row->status ?? 'Pending';
                $badge = match ($status) {
                    'Completed' => 'success',
                    'In Progress' => 'primary',
                    'Cancelled' => 'secondary',
                    default => 'warning',
                };
                return '<span class="badge badge-light-' . $badge . '">' . e($status) . '</span>';
            })
            ->addColumn('assigned_users', function ($row) {
                if ($row->assignedUsers->isEmpty()) {
                    return '—';
                }
                return $row->assignedUsers->pluck('name')->take(3)->join(', ') . ($row->assignedUsers->count() > 3 ? '...' : '');
            })
            ->addColumn('action', function ($row) {
                $getCurrentTranslation = getCurrentTranslation();
                $user = Auth::user();
                $canDelete = hasPermission('sticky_note.delete') && (
                    ($user->user_type === 'admin' && $user->is_staff != 1) || (int) $row->created_by === (int) $user->id
                );
                $html = '';
                if (hasPermission('sticky_note.show')) {
                    $html .= '<a href="' . route('sticky_note.show', $row->id) . '" class="btn btn-sm btn-icon btn-light-info"><i class="fa-solid fa-eye"></i></a> ';
                }
                if (hasPermission('sticky_note.edit')) {
                    $html .= '<a href="' . route('sticky_note.edit', $row->id) . '" class="btn btn-sm btn-icon btn-light-primary"><i class="fa-solid fa-pen"></i></a> ';
                }
                if ($canDelete) {
                    $html .= '<button type="button" class="btn btn-sm btn-icon btn-light-danger delete-table-data-btn" data-url="' . route('sticky_note.destroy', $row->id) . '" title="' . ($getCurrentTranslation['delete'] ?? 'delete') . '"><i class="fa-solid fa-trash"></i></button>';
                }
                return $html ?: '—';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function create()
    {
        if (!hasPermission('sticky_note.create')) {
            return redirect()->route('sticky_note.index')
                ->with('error', getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }

        $editData = null;
        $listRoute = route('sticky_note.index');
        $saveRoute = route('sticky_note.store');
        $assignableUsers = $this->getAssignableUsers();

        return view('common.sticky-note.addEdit', get_defined_vars());
    }

    public function store(Request $request)
    {
        if (!hasPermission('sticky_note.create')) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ]);
        }

        $rules = [
            'note_title' => 'required|string|max:255',
            'note_description' => 'nullable|string',
            'deadline' => 'required|date',
            'reminder_datetime' => 'required|date',
            'status' => 'nullable|in:Pending,In Progress,Completed,Cancelled',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'exists:users,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => $validator->errors()->first(),
            ]);
        }

        $user = Auth::user();
        $note = new StickyNote();
        $note->user_id = $user->business_id;
        $note->note_title = $request->note_title;
        $note->note_description = $request->note_description;
        $note->deadline = $request->deadline ? Carbon::parse($request->deadline) : null;
        $note->reminder_datetime = $request->reminder_datetime ? Carbon::parse($request->reminder_datetime) : null;
        $note->status = $request->status ?? 'Pending';
        $note->created_by = $user->id;
        $note->updated_by = $user->id;
        $note->save();

        $assignedIds = $request->assigned_user_ids ?? [];
        $note->assignedUsers()->sync(array_filter($assignedIds));

        $this->logStickyNoteActivity($note, StickyNoteActivity::ACTION_CREATE, $this->buildCreateChanges($note), $request);

        return response()->json([
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['data_saved'] ?? 'Data saved',
            'redirect_url' => route('sticky_note.index'),
        ]);
    }

    public function show($id)
    {
        if (!hasPermission('sticky_note.show')) {
            return redirect()->route('sticky_note.index')
                ->with('error', getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }

        $user = Auth::user();
        $note = StickyNote::with('user', 'creator', 'assignedUsers')
            ->with(['activities' => fn ($q) => $q->with('user')->latest()])
            ->visibleToUser($user)->where('id', $id)->first();
        if (!$note) {
            abort(404);
        }

        $note->update(['read_status' => true]);

        $listRoute = route('sticky_note.index');
        $editRoute = hasPermission('sticky_note.edit') ? route('sticky_note.edit', $id) : '';

        return view('common.sticky-note.details', get_defined_vars());
    }

    public function edit($id)
    {
        if (!hasPermission('sticky_note.edit')) {
            return redirect()->route('sticky_note.index')
                ->with('error', getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }

        $user = Auth::user();
        $editData = StickyNote::visibleToUser($user)->where('id', $id)->first();
        if (!$editData) {
            abort(404);
        }

        $listRoute = route('sticky_note.index');
        $saveRoute = route('sticky_note.update', $id);
        $assignableUsers = $this->getAssignableUsers();

        return view('common.sticky-note.addEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('sticky_note.edit')) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ]);
        }

        $user = Auth::user();
        $note = StickyNote::visibleToUser($user)->where('id', $id)->first();
        if (!$note) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'Data not found',
            ]);
        }

        $rules = [
            'note_title' => 'required|string|max:255',
            'note_description' => 'nullable|string',
            'deadline' => 'required|date',
            'reminder_datetime' => 'required|date',
            'status' => 'nullable|in:Pending,In Progress,Completed,Cancelled',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'exists:users,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => $validator->errors()->first(),
            ]);
        }

        $oldSnapshot = $this->noteSnapshot($note);

        $note->note_title = $request->note_title;
        $note->note_description = $request->note_description;
        $note->deadline = $request->deadline ? Carbon::parse($request->deadline) : null;
        $note->reminder_datetime = $request->reminder_datetime ? Carbon::parse($request->reminder_datetime) : null;
        $note->status = $request->status ?? $note->status;
        $note->updated_by = $user->id;
        $note->save();

        $assignedIds = $request->assigned_user_ids ?? [];
        $note->assignedUsers()->sync(array_filter($assignedIds));

        $updateChanges = $this->buildUpdateChanges($note, $oldSnapshot);
        if ($updateChanges !== null) {
            $this->logStickyNoteActivity($note, StickyNoteActivity::ACTION_UPDATE, $updateChanges, $request);
        }

        return response()->json([
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['data_updated'] ?? 'Data updated',
            'redirect_url' => route('sticky_note.index'),
        ]);
    }

    public function destroy($id)
    {
        if (!hasPermission('sticky_note.delete')) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ]);
        }

        $user = Auth::user();
        $note = StickyNote::visibleToUser($user)->where('id', $id)->first();
        if (!$note) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'Data not found',
            ]);
        }

        $isAdmin = $user->user_type === 'admin' && $user->is_staff != 1;
        $isCreator = (int) $note->created_by === (int) $user->id;
        if (!$isAdmin && !$isCreator) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['only_admin_or_owner_can_delete_note'] ?? getCurrentTranslation()['permission_denied'] ?? 'Only admin or owner can delete this note.',
            ]);
        }

        $note->deleted_by = $user->id;
        $note->save();

        $this->logStickyNoteActivity($note, StickyNoteActivity::ACTION_DELETE, $this->buildDeleteChanges($note), request());

        $note->delete();

        return response()->json([
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['data_deleted'] ?? 'Data deleted',
        ]);
    }

    /**
     * Update only the status of a sticky note (for AJAX acknowledge in reminder popup).
     */
    public function updateStatus(Request $request, $id)
    {
        if (!hasPermission('sticky_note.edit')) {
            return response()->json([
                'is_success' => 0,
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ]);
        }

        $user = Auth::user();
        $note = StickyNote::visibleToUser($user)->where('id', $id)->first();
        if (!$note) {
            return response()->json([
                'is_success' => 0,
                'message' => getCurrentTranslation()['data_not_found'] ?? 'Data not found',
            ]);
        }

        $status = $request->input('status');
        $allowed = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
        if (!in_array($status, $allowed)) {
            return response()->json([
                'is_success' => 0,
                'message' => 'Invalid status',
            ]);
        }

        $oldStatus = $note->status;
        $note->status = $status;
        $note->read_status = true;
        $note->updated_by = $user->id;
        $note->save();

        $this->logStickyNoteActivity($note, StickyNoteActivity::ACTION_STATUS, $this->buildStatusChanges($note, $oldStatus, $status), $request);

        return response()->json([
            'is_success' => 1,
            'message' => getCurrentTranslation()['data_updated'] ?? 'Data updated',
        ]);
    }

    /**
     * Return upcoming sticky notes for drawer refresh (count + list html).
     */
    public function upcomingDrawerData()
    {
        if (!Auth::check() || !function_exists('hasPermission') || !hasPermission('sticky_note.index')) {
            return response()->json(['count' => 0, 'html' => '', 'count_text' => '']);
        }
        $now = Carbon::now();
        $end = Carbon::now()->addDays(7);
        $upcomingStickyNotes = StickyNote::visibleToUser(Auth::user())
            ->where(function ($q) use ($now, $end) {
                $q->whereBetween('reminder_datetime', [$now, $end])
                    ->orWhereBetween('deadline', [$now, $end]);
            })
            ->whereNotIn('status', ['Cancelled', 'Completed'])
            ->orderByRaw('COALESCE(reminder_datetime, deadline) ASC')
            ->limit(20)
            ->get();
        $unreadCount = $upcomingStickyNotes->where('read_status', 0)->count();
        $totalCount = $upcomingStickyNotes->count();
        $getCurrentTranslation = getCurrentTranslation();
        $countText = str_replace(':count', (string) $totalCount, $getCurrentTranslation['you_have_count_notes'] ?? 'You have :count notes.');
        $html = view('common._partials.sticky-notes-drawer-list', compact('upcomingStickyNotes'))->render();
        return response()->json([
            'count' => $unreadCount,
            'html' => $html,
            'count_text' => $countText,
        ]);
    }

    protected function getAssignableUsers()
    {
        $user = Auth::user();
        if ($user->user_type === 'admin' && $user->is_staff != 1) {
            return User::whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'email']);
        }
        $bid = $user->business_id;
        return User::whereNull('deleted_at')
            ->where(function ($q) use ($bid) {
                $q->where('id', $bid)->orWhere('parent_id', $bid);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /**
     * Get actor role for activity: Admin, Owner, or Assigned user.
     */
    protected function stickyNoteActorRole(StickyNote $note): string
    {
        $user = Auth::user();
        if ($user->user_type === 'admin' && $user->is_staff != 1) {
            return 'Admin';
        }
        if ((int) $note->user_id === (int) $user->id) {
            return 'Owner';
        }
        return 'Assigned user';
    }

    /**
     * Build a snapshot of note data for diffing (dates as ISO strings).
     */
    protected function noteSnapshot(StickyNote $note): array
    {
        $note->load('assignedUsers');
        return [
            'note_title' => $note->note_title,
            'note_description' => $note->note_description,
            'deadline' => $note->deadline?->toIso8601String(),
            'reminder_datetime' => $note->reminder_datetime?->toIso8601String(),
            'status' => $note->status,
            'assigned_user_ids' => $note->assignedUsers->pluck('id')->values()->all(),
        ];
    }

    protected function formatValue($value): string
    {
        if (is_array($value)) {
            return implode(', ', $value) ?: 'none';
        }
        return $value === null || $value === '' ? '—' : (string) $value;
    }

    /** Format a value for activity display (e.g. ISO dates as readable, assigned users as Name (employee_uid)). */
    protected function formatValueForDisplay($value, string $key): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if ($key === 'assigned_user_ids' && is_array($value)) {
            return $this->formatAssignedUsersForDisplay($value);
        }
        if (is_array($value)) {
            return implode(', ', $value) ?: 'none';
        }
        $str = (string) $value;
        if (in_array($key, ['deadline', 'reminder_datetime'], true)) {
            try {
                return Carbon::parse($str)->format('d M Y, H:i');
            } catch (\Throwable $e) {
                return $str;
            }
        }
        return $str;
    }

    /** Resolve assigned user IDs to "Name (employee_uid)" for activity display. */
    protected function formatAssignedUsersForDisplay(array $userIds): string
    {
        if ($userIds === []) {
            return 'none';
        }
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'employee_uid'])->keyBy('id');
        $parts = [];
        foreach ($userIds as $id) {
            $u = $users->get($id);
            if ($u) {
                $employee_uid = $u->employee_uid ?? '';
                $parts[] = $employee_uid !== '' ? $u->name . ' (' . $employee_uid . ')' : $u->name;
            }
        }
        return $parts === [] ? 'none' : implode(', ', $parts);
    }

    protected function buildCreateChanges(StickyNote $note): string
    {
        $role = $this->stickyNoteActorRole($note);
        $snap = $this->noteSnapshot($note);
        $parts = [];
        $parts[] = "{$role} created note.";
        foreach (['note_title' => 'title', 'status' => 'status', 'deadline' => 'deadline', 'reminder_datetime' => 'reminder'] as $key => $label) {
            $v = $snap[$key] ?? null;
            if ($v !== null && $v !== '') {
                $parts[] = "{$label}: " . $this->formatValue($v);
            }
        }
        return implode(' ', $parts);
    }

    /**
     * Build update changes text. Returns null when nothing changed (so no activity is logged).
     */
    protected function buildUpdateChanges(StickyNote $note, array $oldSnapshot): ?string
    {
        $newSnapshot = $this->noteSnapshot($note);
        $labels = [
            'note_title' => 'Note Title',
            'note_description' => 'Note Description',
            'deadline' => 'Deadline',
            'reminder_datetime' => 'Reminder Datetime',
            'status' => 'Status',
            'assigned_user_ids' => 'Assigned Users',
        ];
        $lines = [];
        foreach ($labels as $key => $label) {
            $old = $oldSnapshot[$key] ?? null;
            $new = $newSnapshot[$key] ?? null;
            if ($old != $new) {
                $oldStr = $this->formatValueForDisplay($old, $key);
                $newStr = $this->formatValueForDisplay($new, $key);
                $lines[] = '"' . $label . '" data from `' . $oldStr . '` to `' . $newStr . '`';
            }
        }
        if ($lines === []) {
            return null;
        }
        $role = $this->stickyNoteActorRole($note);
        $numbered = array_map(function ($line, $i) {
            return sprintf('%02d. %s', $i + 1, $line);
        }, $lines, array_keys($lines));
        return $role . " Changed:\n" . implode("\n", $numbered);
    }

    protected function buildStatusChanges(StickyNote $note, string $oldStatus, string $newStatus): string
    {
        $role = $this->stickyNoteActorRole($note);
        return "{$role} changed status from " . $this->formatValue($oldStatus) . " to " . $this->formatValue($newStatus) . '.';
    }

    protected function buildDeleteChanges(StickyNote $note): string
    {
        $role = $this->stickyNoteActorRole($note);
        return "{$role} deleted note (title: " . $this->formatValue($note->note_title) . ').';
    }

    /**
     * Log sticky note activity (create, update, status, delete).
     */
    protected function logStickyNoteActivity(StickyNote $note, string $action, string $changes, $request = null): void
    {
        $req = $request instanceof Request ? $request : request();
        StickyNoteActivity::create([
            'sticky_note_id' => $note->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'changes' => $changes,
            'ip_address' => $req->ip(),
            'user_agent' => $req->userAgent(),
        ]);
    }
}
