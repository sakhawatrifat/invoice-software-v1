<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\MarketingSend;
use Yajra\DataTables\Facades\DataTables;

class MarketingSendController extends Controller
{
    /**
     * Sent Emails list (type = email).
     */
    public function sentEmailsIndex(Request $request)
    {
        if (!hasPermission('sent_mail_list')) {
            abort(403, getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }
        $type = 'email';
        $pageTitle = getCurrentTranslation()['sent_emails'] ?? 'Sent Emails';
        $listRoute = route('marketing.sent.emails.index');
        $dataTableRoute = route('marketing.sent.emails.datatable');
        $defaultSentDateRange = Carbon::now()->firstOfMonth()->format('Y/m/d') . '-' . Carbon::now()->endOfMonth()->format('Y/m/d');
        return view('common.marketing.sent-index', get_defined_vars());
    }

    /**
     * Sent WhatsApp Messages list (type = whatsapp).
     */
    public function sentWhatsAppIndex(Request $request)
    {
        if (!hasPermission('sent_whatsapp_messages')) {
            abort(403, getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }
        $type = 'whatsapp';
        $pageTitle = getCurrentTranslation()['sent_whatsapp_messages'] ?? 'Sent WhatsApp Messages';
        $listRoute = route('marketing.sent.whatsapp.index');
        $dataTableRoute = route('marketing.sent.whatsapp.datatable');
        $defaultSentDateRange = Carbon::now()->firstOfMonth()->format('Y/m/d') . '-' . Carbon::now()->endOfMonth()->format('Y/m/d');
        return view('common.marketing.sent-index', get_defined_vars());
    }

    /**
     * Datatable for sent emails.
     */
    public function sentEmailsDatatable()
    {
        if (!hasPermission('sent_mail_list')) {
            return response()->json(['data' => []]);
        }
        return $this->sentDatatable('email');
    }

    /**
     * Datatable for sent WhatsApp messages.
     */
    public function sentWhatsAppDatatable()
    {
        if (!hasPermission('sent_whatsapp_messages')) {
            return response()->json(['data' => []]);
        }
        return $this->sentDatatable('whatsapp');
    }

    protected function sentDatatable($type)
    {
        $query = MarketingSend::with('creator')->where('type', $type);

        if (!empty(request()->sent_date_range) && request()->sent_date_range != '0') {
            $parts = explode('-', request()->sent_date_range, 2);
            if (count($parts) === 2) {
                $start = Carbon::createFromFormat('Y/m/d', trim($parts[0]))->startOfDay();
                $end = Carbon::createFromFormat('Y/m/d', trim($parts[1]))->endOfDay();
                $query->whereBetween('sent_date_time', [$start, $end]);
            }
        }

        $query->latest('sent_date_time');

        if (request()->has('search') && $search = request('search')['value']) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $showRouteName = $type === 'email' ? 'marketing.sent.emails.show' : 'marketing.sent.whatsapp.show';

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('subject_short', function ($row) {
                return Str::limit($row->subject, 50);
            })
            ->orderColumn('subject_short', 'subject $1')
            ->addColumn('recipients_count', function ($row) {
                $customers = $row->customers ?? [];
                return is_array($customers) ? count($customers) : 0;
            })
            ->addColumn('sent_date_time_formatted', function ($row) {
                $dt = $row->sent_date_time ?? $row->created_at;
                return $dt ? Carbon::parse($dt)->format('Y-m-d H:i') : '—';
            })
            ->orderColumn('sent_date_time_formatted', 'sent_date_time $1')
            ->addColumn('created_by_name', function ($row) {
                return $row->creator ? $row->creator->name : '—';
            })
            ->addColumn('action', function ($row) use ($showRouteName) {
                $showUrl = route($showRouteName, $row->id);
                return '<a href="' . $showUrl . '" class="btn btn-sm btn-info my-1"><i class="fa-solid fa-eye"></i></a>';
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('Y-m-d H:i');
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show sent email details.
     */
    public function sentEmailShow($id)
    {
        if (!hasPermission('sent_mail_list')) {
            abort(403, getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }
        return $this->sentShow($id, 'email');
    }

    /**
     * Show sent WhatsApp message details.
     */
    public function sentWhatsAppShow($id)
    {
        if (!hasPermission('sent_whatsapp_messages')) {
            abort(403, getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }
        return $this->sentShow($id, 'whatsapp');
    }

    protected function sentShow($id, $type)
    {
        $editData = MarketingSend::with('creator')->where('id', $id)->where('type', $type)->first();
        if (!$editData) {
            abort(404);
        }
        $listRoute = $type === 'email'
            ? route('marketing.sent.emails.index')
            : route('marketing.sent.whatsapp.index');
        $listLabel = $type === 'email'
            ? (getCurrentTranslation()['sent_emails'] ?? 'Sent Emails')
            : (getCurrentTranslation()['sent_whatsapp_messages'] ?? 'Sent WhatsApp Messages');
        $detailsTitle = $type === 'email'
            ? (getCurrentTranslation()['sent_email_details'] ?? 'Sent Email Details')
            : (getCurrentTranslation()['sent_whatsapp_details'] ?? 'Sent WhatsApp Message Details');
        return view('common.marketing.sent-details', get_defined_vars());
    }
}
