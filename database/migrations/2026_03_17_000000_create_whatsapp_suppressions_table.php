<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('phone_key', 32)->unique(); // usually "+8801..." or normalized digits
            $table->string('phone_e164', 32)->nullable();
            $table->string('reason', 64)->nullable();
            $table->unsignedInteger('twilio_error_code')->nullable();
            $table->text('twilio_error_message')->nullable();
            $table->timestamp('suppressed_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_suppressions');
    }
};

