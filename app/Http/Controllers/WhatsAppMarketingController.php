<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\TicketPassenger;
use App\Models\MarketingSend;
use App\Jobs\SendWhatsAppMarketingMessage;

class WhatsAppMarketingController extends Controller
{
    /**
     * Normalize phone to digits only.
     */
    protected function normalizePhone($phone)
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Convert phone to E.164 for Twilio/WhatsApp, or null if invalid.
     * - Strips leading zeros (national trunk prefix).
     * - 11 digits starting with 0 (e.g. 01601957104) → Bangladesh +880 + 10 digits (e.g. +8801601957104).
     * - US/Canada (country code 1): must be 11 digits total (1 + 10-digit number).
     * - Other countries: 10–15 digits per E.164.
     */
    protected function toE164OrNull($phone)
    {
        $raw = $this->normalizePhone($phone);
        $digits = ltrim($raw, '0');
        $len = strlen($digits);

        // Bangladesh: 11 digits starting with 0 (e.g. 01601957104) → +8801601957104
        if (strlen($raw) === 11 && isset($raw[0]) && $raw[0] === '0' && $len === 10) {
            return '+880' . $digits;
        }

        if ($len < 10 || $len > 15) {
            return null;
        }
        // US/Canada: country code 1 + 10-digit number = 11 digits
        if ($digits[0] === '1' && $len !== 11) {
            return null;
        }
        return '+' . $digits;
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

    public function form(Request $request)
    {
        if (!hasPermission('send_whatsapp_marketing')) {
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
        if (!hasPermission('send_whatsapp_marketing')) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ]);
        }

        $rules = [
            'content' => 'required|string',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:ticket_passengers,id',
            // Optional attachment: match front-end hint (max 5MB)
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf',
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
        $contentRaw = $request->content;
        // WhatsApp templates behave best with plain text; preserve line breaks.
        $content = strip_tags((string) $contentRaw);
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = trim($content);
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

        // Store attachment in storage if present (for record/details)
        $attachmentPath = null;
        $documentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            if (!$file || !$file->isValid()) {
                $err = $file ? $file->getErrorMessage() : 'Upload failed';
                return response()->json([
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => ($getCurrentTranslation['attachment_upload_failed'] ?? 'Attachment upload failed.') . ' ' . $err,
                ]);
            }
            $ext = strtolower($file->getClientOriginalExtension());
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            if (in_array($ext, $allowed)) {
                $originalName = (string) $file->getClientOriginalName();
                $originalBase = pathinfo($originalName, PATHINFO_FILENAME);

                // Human readable base: replace separators with spaces, remove weird characters.
                $base = preg_replace('/[\-_]+/', ' ', $originalBase);
                $base = preg_replace('/\s+/', ' ', (string) $base);
                $base = trim((string) $base);
                $base = preg_replace('/[^A-Za-z0-9 \(\)\[\]\.]/', '', $base);
                $base = trim((string) $base);
                if ($base === '') {
                    $base = 'Attachment';
                }

                $dt = \now()->format('Y-m-d \a\t h.i.s A');
                $fileName = "{$base} ({$dt}).{$ext}";

                $dir = 'marketing-attachments';
                $disk = Storage::disk('public');
                $disk->makeDirectory($dir);
                $path = $dir . '/' . $fileName;

                // Avoid collisions if same file uploaded within same second
                if ($disk->exists($path)) {
                    $n = 2;
                    do {
                        $fileNameTry = "{$base} ({$dt}) ({$n}).{$ext}";
                        $path = $dir . '/' . $fileNameTry;
                        $n++;
                    } while ($disk->exists($path) && $n < 50);
                    $fileName = basename($path);
                }

                try {
                    $savedPath = $disk->putFileAs($dir, $file, $fileName);
                } catch (\Throwable $e) {
                    $savedPath = false;
                    Log::channel('single')->warning('WhatsApp marketing attachment save exception', [
                        'dir' => $dir,
                        'file' => $fileName,
                        'ext' => $ext,
                        'error' => $e->getMessage(),
                    ]);
                }

                if (!$savedPath) {
                    // Fallback for hosts that block symlinks or storage permissions:
                    // save directly under public/uploads so it is web-accessible.
                    try {
                        $publicDir = public_path('uploads/marketing-attachments');
                        if (!is_dir($publicDir)) {
                            @mkdir($publicDir, 0775, true);
                        }
                        $file->move($publicDir, $fileName);
                        $attachmentPath = 'uploads/marketing-attachments/' . $fileName;
                        $documentName = $fileName;
                    } catch (\Throwable $e) {
                        Log::channel('single')->warning('WhatsApp marketing attachment fallback save failed', [
                            'file' => $fileName,
                            'ext' => $ext,
                            'error' => $e->getMessage(),
                        ]);
                        return response()->json([
                            'is_success' => 0,
                            'icon' => 'error',
                            'message' => $getCurrentTranslation['attachment_save_failed'] ?? 'Failed to save attachment. Please check storage permissions and upload limits.',
                        ]);
                    }
                } else {
                    $attachmentPath = $path;
                    $documentName = $fileName;
                }
            }
        }

        $sid = config('services.twilio.sid', env('TWILIO_ACCOUNT_SID', ''));
        $token = config('services.twilio.token', env('TWILIO_AUTH_TOKEN', ''));
        $from = config('services.twilio.whatsapp_from', env('TWILIO_WHATSAPP_FROM', ''));
        $contentSidText = (string) config('services.twilio.whatsapp_content_sid_text', env('TWILIO_WHATSAPP_CONTENT_SID_TEXT', ''));
        $contentSidMedia = (string) config('services.twilio.whatsapp_content_sid_media', env('TWILIO_WHATSAPP_CONTENT_SID_MEDIA', env('TWILIO_WHATSAPP_CONTENT_SID', '')));

        if (empty($sid) || empty($token) || empty($from)) {
            $msg = getCurrentTranslation()['whatsapp_api_not_configured'] ?? null;
            if ($msg === null || str_contains((string) $msg, 'WHATSAPP_API_URL') || str_contains((string) $msg, 'TWILIO_WHATSAPP_CONTENT_SID')) {
                $msg = 'WhatsApp via Twilio is not configured. Set TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN and TWILIO_WHATSAPP_FROM in .env';
            }
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => $msg,
            ]);
        }

        // Media URL for template {{2}} (when user selects an attachment)
        $mediaUrl = $attachmentPath ? getUploadedUrl($attachmentPath) : '';

        // 1) Save to DB first so it appears in sent-whatsapp-messages list
        $customers = collect($toSend)->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'email' => $p->email ?? null,
                'phone' => $p->phone ?? null,
                'pax_type' => $p->pax_type ?? null,
                'gender' => $p->gender ?? null,
                'date_of_birth' => $p->date_of_birth ?? null,
                'nationality' => $p->nationality ?? null,
            ];
        })->values()->toArray();

        $marketingSend = MarketingSend::create([
            'type' => 'whatsapp',
            'subject' => '',
            'content' => $contentRaw,
            'customers' => $customers,
            'document_path' => $attachmentPath,
            'document_name' => $documentName,
            'sent_date_time' => now(),
            'created_by' => Auth::id(),
        ]);

        // 2) Dispatch per-recipient jobs to queue with throttling + jitter.
        $useTemplate = ($contentSidText !== '' || $contentSidMedia !== '');
        $perMinute = (int) env('WHATSAPP_MARKETING_RATE_PER_MINUTE', 30); // safe default
        $perMinute = max(1, min($perMinute, 600));
        $secondsPerMessage = (int) ceil(60 / $perMinute); // coarse but stable with database queues
        $jitterMax = (int) env('WHATSAPP_MARKETING_JITTER_SECONDS', 2);
        $jitterMax = max(0, min($jitterMax, 10));

        $queued = 0;
        $skipped = 0;
        $errors = [];

        foreach (array_values($toSend) as $i => $passenger) {
            $toE164 = $this->toE164OrNull($passenger->phone);
            if ($toE164 === null) {
                $skipped++;
                $errors[] = $passenger->name . ': ' . (getCurrentTranslation()['whatsapp_invalid_phone'] ?? 'Invalid or incomplete phone number. Use full number with country code (e.g. +16019571040).');
                continue;
            }

            $delaySeconds = ($i * $secondsPerMessage) + ($jitterMax > 0 ? random_int(0, $jitterMax) : 0);

            SendWhatsAppMarketingMessage::dispatch([
                'marketing_send_id' => $marketingSend->id,
                'ticket_passenger_id' => $passenger->id,
                'to_e164' => $toE164,
                'content' => $content,
                'content_raw' => $contentRaw,
                'media_url' => $mediaUrl,
                'use_template' => $useTemplate,
            ])
                ->onQueue('whatsapp-marketing')
                ->delay(now()->addSeconds($delaySeconds));

            $queued++;
        }

        $msg = getCurrentTranslation()['marketing_whatsapp_sent'] ?? 'WhatsApp message queued successfully.';
        $hint = getCurrentTranslation()['whatsapp_check_twilio_logs'] ?? ' You can monitor delivery in Twilio Console > Logs.';
        $extra = " Queued: {$queued}. Skipped: {$skipped}. Rate: {$perMinute}/min.";

        if ($queued > 0) {
            return response()->json([
                'is_success' => 1,
                'icon' => 'success',
                'message' => $msg . $extra . $hint,
            ]);
        }

        return response()->json([
            'is_success' => 0,
            'icon' => 'error',
            'message' => $errors[0] ?? (getCurrentTranslation()['whatsapp_send_failed'] ?? 'WhatsApp send failed'),
        ]);
    }
}
