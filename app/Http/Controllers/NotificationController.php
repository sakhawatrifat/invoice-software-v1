<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        $dataTableRoute = route('notification.datatable');
        return view('common.notification.index', get_defined_vars());
    }

    public function datatable()
    {
        $user = Auth::user();
        $query = Notification::where('user_id', $user->business_id);

        if (request()->has('search') && $search = request('search')['value']) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()

            // Make title clickable
            ->editColumn('title', function ($row) {
                $url = route('notification.read', $row->id);
                $title = e($row->title ?? 'No Title');
                return '<a href="' . $url . '" class="fw-semibold text-primary text-decoration-none">' . $title . '</a>';
            })

            // Read / Unread badge
            ->addColumn('status', function ($row) {
                return $row->read_status
                    ? '<span class="badge bg-success">Read</span>'
                    : '<span class="badge bg-warning text-dark">Unread</span>';
            })

            // Details and Delete buttons
            ->addColumn('action', function ($row) {
                $detailsUrl = route('notification.read', $row->id);
                $deleteUrl = route('notification.destroy', $row->id);

                return '
                    <a href="' . $detailsUrl . '" class="btn btn-sm btn-info text-white me-1">
                        <i class="fa-solid fa-pager"></i>
                    </a>
                    <button class="btn btn-sm btn-danger delete-table-data-btn"
                            data-id="' . $row->id . '"
                            data-url="' . $deleteUrl . '">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                ';
            })

            ->editColumn('created_at', function ($row) {
                return \Carbon\Carbon::parse($row->created_at)->format('Y-m-d, H:i');
            })

            // Allow sorting by created_at
            ->orderColumn('created_at', function ($query, $order) {
                $query->orderBy('created_at', $order);
            })

            ->rawColumns(['title', 'status', 'action'])
            ->make(true);

    }



    public function read($id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->business_id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            abort(404);
        }

        // Mark as read
        $notification->read_status = 1;
        $notification->save();

        // Redirect to the target URL
        return redirect()->to($notification->full_url ?? $notification->url);
    }

    public function readAll()
    {
        $user = Auth::user();

        DB::table('notifications')
            ->where('user_id', $user->business_id)
            ->update([
                'read_status' => 1
            ]);

        return redirect()->back()->with('success', getCurrentTranslation()['marks_all_as_read_done'] ?? 'Marks all as read done');
    }


    // ✅ Delete Notification
    public function destroy($id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->business_id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        $notification->delete();

        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['data_deleted'] ?? 'data_deleted'
        ];
    }
}
