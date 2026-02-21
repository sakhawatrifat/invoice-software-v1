<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\TicketPassenger;

class EmailMarketingController extends Controller
{
    /**
     * Get ticket passengers unique by email (first occurrence kept).
     */
    protected function getPassengersUniqueByEmail()
    {
        $rows = TicketPassenger::whereNull('ticket_passengers.deleted_at')
            ->whereNotNull('ticket_passengers.email')
            ->where('ticket_passengers.email', '!=', '')
            ->select('ticket_passengers.id', 'ticket_passengers.name', 'ticket_passengers.email', 'ticket_passengers.phone', 'ticket_passengers.pax_type', 'ticket_passengers.gender', 'ticket_passengers.date_of_birth', 'ticket_passengers.nationality')
            ->orderBy('ticket_passengers.email')
            ->orderBy('ticket_passengers.id')
            ->get();

        $seen = [];
        $list = [];
        foreach ($rows as $row) {
            $key = strtolower(trim($row->email));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $list[] = $row;
        }
        return collect($list)->sortBy('name')->values();
    }

    public function form(Request $request)
    {
        if (!hasPermission('email_marketing')) {
            if ($request->ajax()) {
                return response()->json([
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
                ]);
            }
            abort(403, getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }

        $users = $this->getPassengersUniqueByEmail();

        $pageTitle = getCurrentTranslation()['email_marketing'] ?? 'Email Marketing';
        $submitRoute = route('marketing.email.send');
        $type = 'email';

        return view('common.marketing.form', get_defined_vars());
    }

    public function send(Request $request)
    {
        if (!hasPermission('email_marketing')) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ]);
        }

        $rules = [
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:ticket_passengers,id',
        ];
        $validator = Validator::make($request->all(), $rules, [
            'user_ids.required' => getCurrentTranslation()['at_least_one_recipient_required'] ?? 'At least one recipient should be selected.',
            'user_ids.min' => getCurrentTranslation()['at_least_one_recipient_required'] ?? 'At least one recipient should be selected.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => $validator->getMessageBag()->first(),
            ]);
        }

        $passengerIds = $request->user_ids;
        $content = $request->content;
        $passengers = TicketPassenger::whereIn('id', $passengerIds)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        // Remove duplicate emails (send once per email)
        $seenEmail = [];
        $toSend = [];
        foreach ($passengers as $p) {
            $key = strtolower(trim($p->email));
            if (isset($seenEmail[$key])) {
                continue;
            }
            $seenEmail[$key] = true;
            $toSend[] = $p;
        }

        $attachmentPath = null;
        $attachmentIsImage = false;
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $file = $request->file('attachment');
            $ext = strtolower($file->getClientOriginalExtension());
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            if (in_array($ext, $allowed)) {
                $attachmentPath = $file->store('marketing-attachments', 'public');
                $attachmentIsImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
            }
        }

        $fullAttachmentPath = $attachmentPath ? storage_path('app/public/' . $attachmentPath) : null;

        $subject = $request->subject;

        try {
            foreach ($toSend as $passenger) {
                Mail::send([], [], function ($message) use ($passenger, $subject, $content, $fullAttachmentPath, $attachmentIsImage) {
                    $message->to($passenger->email)
                        ->subject($subject);

                    if ($fullAttachmentPath && file_exists($fullAttachmentPath) && $attachmentIsImage) {
                        // Embed image inline under mail content
                        $cid = $message->embed($fullAttachmentPath);
                        $body = $content . '<br><p><img src="' . $cid . '" alt="" style="max-width:100%; height:auto;"></p>';
                        $message->html($body);
                    } elseif ($fullAttachmentPath && file_exists($fullAttachmentPath)) {
                        // PDF: attach as file
                        $message->html($content);
                        $message->attach($fullAttachmentPath);
                    } else {
                        $message->html($content);
                    }
                });
            }
            if ($fullAttachmentPath && file_exists($fullAttachmentPath)) {
                @unlink($fullAttachmentPath);
            }
        } catch (\Exception $e) {
            if ($fullAttachmentPath && file_exists($fullAttachmentPath)) {
                @unlink($fullAttachmentPath);
            }
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['marketing_email_sent'] ?? 'Marketing email sent successfully.',
        ]);
    }
}
