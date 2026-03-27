<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fresh install: create table without payment_id/meta.
        if (!Schema::hasTable('flight_api_credit_usage')) {
            Schema::create('flight_api_credit_usage', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('credit_used_by')->nullable()->comment('Auth user id');
                $table->dateTime('usage_date_time');
                $table->string('used_for', 64);
                $table->integer('credit_amount');
                $table->timestamps();

                $table->index(['usage_date_time']);
                $table->index(['used_for']);
                $table->index(['credit_used_by']);

                $table->foreign('credit_used_by')->references('id')->on('users')->nullOnDelete();
            });

            return;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_api_credit_usage');
    }
};
