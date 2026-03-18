<?php

namespace App\Jobs;

use App\Models\WhatsAppSuppression;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client as TwilioClient;

class SendWhatsAppMarketingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array{
     *   marketing_send_id:int,
     *   ticket_passenger_id:int,
     *   to_e164:string,
     *   content:string,
     *   content_raw:string,
     *   media_url:string,
     *   use_template:bool
     * }
     */
    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function backoff(): array
    {
        // Soft exponential backoff (seconds)
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $sid = \config('services.twilio.sid', \env('TWILIO_ACCOUNT_SID', ''));
        $token = \config('services.twilio.token', \env('TWILIO_AUTH_TOKEN', ''));
        $from = \config('services.twilio.whatsapp_from', \env('TWILIO_WHATSAPP_FROM', ''));
        $contentSidText = (string) \config('services.twilio.whatsapp_content_sid_text', \env('TWILIO_WHATSAPP_CONTENT_SID_TEXT', ''));
        $contentSidMedia = (string) \config('services.twilio.whatsapp_content_sid_media', \env('TWILIO_WHATSAPP_CONTENT_SID_MEDIA', \env('TWILIO_WHATSAPP_CONTENT_SID', '')));

        if (empty($sid) || empty($token) || empty($from)) {
            Log::channel('single')->warning('WhatsApp marketing job skipped: Twilio not configured', [
                'marketing_send_id' => $this->payload['marketing_send_id'] ?? null,
            ]);
            return;
        }

        $toE164 = (string) ($this->payload['to_e164'] ?? '');
        $toKey = preg_replace('/[^0-9+]/', '', $toE164);

        // Suppression (permanent failures like "not a WhatsApp user")
        if (!empty($toKey) && WhatsAppSuppression::isSuppressed($toKey)) {
            Log::channel('single')->info('WhatsApp marketing suppressed', [
                'to' => $toE164,
                'marketing_send_id' => $this->payload['marketing_send_id'] ?? null,
            ]);
            return;
        }

        // Global throttling guard (works with database cache store too)
        $perMinute = (int) \env('WHATSAPP_MARKETING_RATE_PER_MINUTE', 30);
        $perMinute = max(1, min($perMinute, 600));
        $rateKey = 'whatsapp_marketing_global';
        if (RateLimiter::tooManyAttempts($rateKey, $perMinute)) {
            $retryAfter = RateLimiter::availableIn($rateKey);
            $this->release(max(5, (int) $retryAfter));
            return;
        }
        RateLimiter::hit($rateKey, 60);

        $useTemplate = (bool) ($this->payload['use_template'] ?? false);
        $content = (string) ($this->payload['content'] ?? '');
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = trim($content);

        $contentRaw = (string) ($this->payload['content_raw'] ?? '');
        $mediaUrl = (string) ($this->payload['media_url'] ?? '');

        // WhatsApp (post April 2025) does not allow freeform "body" messages for business-initiated (marketing) sends.
        // We must always use Content SID + Content Variables to avoid error 63016.
        $hasMedia = trim($mediaUrl) !== '';
        $params = [];

        if ($contentSidText === '' && $contentSidMedia === '') {
            $this->fail(new \RuntimeException(
                'WhatsApp marketing requires a template. Set TWILIO_WHATSAPP_CONTENT_SID (or TWILIO_WHATSAPP_CONTENT_SID_TEXT and TWILIO_WHATSAPP_CONTENT_SID_MEDIA). Sending with Body is not allowed outside the conversation window (error 63016).'
            ));
            return;
        }

        if ($hasMedia) {
            $sidToUse = $contentSidMedia;
            if ($sidToUse === '') {
                $this->fail(new \RuntimeException('Attachment provided but TWILIO_WHATSAPP_CONTENT_SID_MEDIA (or TWILIO_WHATSAPP_CONTENT_SID) is not set.'));
                return;
            }
            $params = [
                'from' => 'whatsapp:' . $from,
                'contentSid' => $sidToUse,
                'contentVariables' => json_encode([
                    '1' => $content,
                    '2' => $mediaUrl,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
        } else {
            // Text-only: prefer text template; if not set, use media template with only {{1}} (same SID as TWILIO_WHATSAPP_CONTENT_SID).
            $sidToUse = $contentSidText !== '' ? $contentSidText : $contentSidMedia;
            $params = [
                'from' => 'whatsapp:' . $from,
                'contentSid' => $sidToUse,
                'contentVariables' => json_encode([
                    '1' => $content,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
        }

        $twilio = new TwilioClient($sid, $token);

        try {
            $message = $twilio->messages->create('whatsapp:' . $toE164, $params);

            // Log::channel('single')->info('WhatsApp marketing sent', [
            //     'to' => $toE164,
            //     'message_sid' => $message->sid ?? null,
            //     'status' => $message->status ?? null,
            //     'marketing_send_id' => $this->payload['marketing_send_id'] ?? null,
            //     'ticket_passenger_id' => $this->payload['ticket_passenger_id'] ?? null,
            // ]);
        } catch (TwilioException $e) {
            $twilioErrorCode = method_exists($e, 'getCode') ? (int) $e->getCode() : null;
            if (method_exists($e, 'getErrorCode')) {
                $twilioErrorCode = (int) $e->getErrorCode();
            }

            $message = $e->getMessage();

            Log::channel('single')->warning('WhatsApp marketing Twilio error', [
                'to' => $toE164,
                'marketing_send_id' => $this->payload['marketing_send_id'] ?? null,
                'ticket_passenger_id' => $this->payload['ticket_passenger_id'] ?? null,
                'error_code' => $twilioErrorCode,
                'error' => $message,
                'use_template' => $useTemplate,
                'has_media' => trim($mediaUrl) !== '',
                'content_sid_text_set' => $contentSidText !== '',
                'content_sid_media_set' => $contentSidMedia !== '',
                'content_preview' => mb_substr($content, 0, 120),
                'content_variables' => $params['contentVariables'] ?? null,
            ]);

            // Permanent / suppress-worthy failures:
            // 63024 = Invalid message recipient (often: not a valid WhatsApp user)
            // 63032 = WhatsApp limitation (often: user restricted / blocked / opt-out)
            // 63005 = Channel did not accept content (template/content issue) => do not retry
            if (in_array($twilioErrorCode, [63024, 63032], true)) {
                WhatsAppSuppression::suppress($toKey ?: $toE164, $toE164, $twilioErrorCode, $message, 'recipient_not_reachable');
                return;
            }
            if (in_array($twilioErrorCode, [63005], true)) {
                // Template/content issue: retrying won't help.
                $this->fail($e);
                return;
            }

            // 20429 (Too Many Requests) or similar transient errors: allow retry.
            throw $e;
        } catch (\Throwable $e) {
            Log::channel('single')->warning('WhatsApp marketing job error', [
                'to' => $toE164,
                'marketing_send_id' => $this->payload['marketing_send_id'] ?? null,
                'ticket_passenger_id' => $this->payload['ticket_passenger_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

