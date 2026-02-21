<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\TicketPassenger;

class WhatsAppMarketingController extends Controller
{
    /**
     * Normalize phone to digits only for comparison.
     */
    protected function normalizePhone($phone)
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Get ticket passengers unique by phone (first occurrence kept).
     */
    protected function getPassengersUniqueByPhone()
    {
        $rows = TicketPassenger::whereNull('ticket_passengers.deleted_at')
            ->whereNotNull('ticket_passengers.phone')
            ->where('ticket_passengers.phone', '!=', '')
            ->select('ticket_passengers.id', 'ticket_passengers.name', 'ticket_passengers.email', 'ticket_passengers.phone', 'ticket_passengers.pax_type', 'ticket_passengers.gender', 'ticket_passengers.date_of_birth', 'ticket_passengers.nationality')
            ->orderBy('ticket_passengers.phone')
            ->orderBy('ticket_passengers.id')
            ->get();

        $seen = [];
        $list = [];
        foreach ($rows as $row) {
            $key = $this->normalizePhone($row->phone);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $list[] = $row;
        }
        return collect($list)->sortBy('name')->values();
    }

    /**
     * Check which phone numbers have WhatsApp (via Cloud API contacts endpoint).
     * Returns array of normalized phone numbers that are valid (on WhatsApp).
     *
     * @param string $apiUrl Base URL (e.g. https://graph.facebook.com/v18.0)
     * @param string $apiKey Access token
     * @param string $phoneId Phone number ID
     * @param string[] $phones Array of phone numbers (e.g. 15551234567 or +15551234567)
     * @return string[] Normalized phone numbers that have WhatsApp
     */
    protected function checkWhatsAppContacts($apiUrl, $apiKey, $phoneId, array $phones)
    {
        $contacts = array_map(function ($p) {
            $digits = $this->normalizePhone($p);
            return $digits ? '+' . ltrim($digits, '0') : null;
        }, $phones);
        $contacts = array_values(array_filter($contacts));

        if (empty($contacts)) {
            return [];
        }

        $url = rtrim($apiUrl, '/') . '/' . $phoneId . '/contacts';
        $body = [
            'blocking' => 'wait',
            'contacts' => $contacts,
        ];

        try {
            $response = Http::withToken($apiKey)
                ->post($url, $body);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $valid = [];
            foreach ($data['contacts'] ?? [] as $c) {
                if (($c['status'] ?? '') === 'valid' && !empty($c['wa_id'])) {
                    $valid[] = $c['wa_id'];
                }
            }
            return $valid;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function form(Request $request)
    {
        if (!hasPermission('whatsapp_marketing')) {
            if ($request->ajax()) {
                return response()->json([
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
                ]);
            }
            abort(403, getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }

        $users = $this->getPassengersUniqueByPhone();

        $pageTitle = getCurrentTranslation()['whatsapp_marketing'] ?? 'WhatsApp Marketing';
        $submitRoute = route('marketing.whatsapp.send');
        $type = 'whatsapp';

        return view('common.marketing.form', get_defined_vars());
    }

    public function send(Request $request)
    {
        if (!hasPermission('whatsapp_marketing')) {
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
        $subject = $request->subject;
        $content = strip_tags($request->content);
        $bodyText = $subject . "\n\n" . $content;
        $passengers = TicketPassenger::whereIn('id', $passengerIds)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        // Remove duplicate phones (one send per phone)
        $seenPhone = [];
        $toSend = [];
        foreach ($passengers as $p) {
            $key = $this->normalizePhone($p->phone);
            if ($key === '' || isset($seenPhone[$key])) {
                continue;
            }
            $seenPhone[$key] = $p;
            $toSend[] = $p;
        }

        $apiUrl = rtrim(env('WHATSAPP_API_URL', ''), '/');
        $apiKey = env('WHATSAPP_API_KEY', '');
        $phoneId = env('WHATSAPP_PHONE_ID', '');

        if (empty($apiUrl) || empty($apiKey) || empty($phoneId)) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['whatsapp_api_not_configured'] ?? 'WhatsApp API is not configured. Set WHATSAPP_API_URL, WHATSAPP_API_KEY and WHATSAPP_PHONE_ID in .env',
            ]);
        }

        // Check which numbers have WhatsApp before sending
        $phonesToCheck = array_map(function ($p) {
            return $p->phone;
        }, $toSend);
        $validWaIds = $this->checkWhatsAppContacts($apiUrl, $apiKey, $phoneId, $phonesToCheck);

        if (empty($validWaIds)) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['no_whatsapp_accounts_found'] ?? 'None of the selected phone numbers have a WhatsApp account.',
            ]);
        }

        $messagesUrl = $apiUrl . '/' . $phoneId . '/messages';
        $sent = 0;
        $errors = [];

        foreach ($toSend as $passenger) {
            $phone = $this->normalizePhone($passenger->phone);
            $waId = ltrim($phone, '0');
            if (!in_array($waId, $validWaIds) && !in_array($phone, $validWaIds)) {
                continue; // skip: no WhatsApp account for this number
            }
            $toNumber = in_array($waId, $validWaIds) ? $waId : $phone;

            try {
                $payload = [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $toNumber,
                    'type' => 'text',
                    'text' => ['body' => $bodyText],
                ];

                $response = Http::withToken($apiKey)->post($messagesUrl, $payload);

                if ($response->successful()) {
                    $sent++;
                } else {
                    $errors[] = $passenger->name . ': ' . $response->body();
                }
            } catch (\Exception $e) {
                $errors[] = $passenger->name . ': ' . $e->getMessage();
            }
        }

        if ($sent > 0 && empty($errors)) {
            return response()->json([
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['marketing_whatsapp_sent'] ?? 'WhatsApp message sent successfully.',
            ]);
        }
        if ($sent > 0) {
            return response()->json([
                'is_success' => 1,
                'icon' => 'success',
                'message' => (getCurrentTranslation()['marketing_whatsapp_sent'] ?? 'Sent') . ' ' . $sent . '. ' . implode(' ', array_slice($errors, 0, 2)),
            ]);
        }

        return response()->json([
            'is_success' => 0,
            'icon' => 'error',
            'message' => $errors[0] ?? (getCurrentTranslation()['whatsapp_send_failed'] ?? 'WhatsApp send failed'),
        ]);
    }
}
