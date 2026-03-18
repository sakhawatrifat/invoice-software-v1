<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSuppression extends Model
{
    protected $table = 'whatsapp_suppressions';

    protected $fillable = [
        'phone_key',
        'phone_e164',
        'reason',
        'twilio_error_code',
        'twilio_error_message',
        'suppressed_until',
    ];

    protected $casts = [
        'suppressed_until' => 'datetime',
    ];

    public static function isSuppressed(string $phoneKey): bool
    {
        $row = static::query()
            ->where('phone_key', $phoneKey)
            ->first();

        if (!$row) {
            return false;
        }

        if ($row->suppressed_until === null) {
            return true;
        }

        return \now()->lt($row->suppressed_until);
    }

    public static function suppress(
        string $phoneKey,
        ?string $phoneE164,
        ?int $twilioErrorCode,
        ?string $twilioErrorMessage,
        string $reason,
        $suppressedUntil = null
    ): void {
        static::query()->updateOrCreate(
            ['phone_key' => $phoneKey],
            [
                'phone_e164' => $phoneE164,
                'reason' => $reason,
                'twilio_error_code' => $twilioErrorCode,
                'twilio_error_message' => $twilioErrorMessage,
                'suppressed_until' => $suppressedUntil,
            ]
        );
    }
}

