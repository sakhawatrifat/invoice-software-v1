<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_passengers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('ticket_id')->nullable();

            $table->longText('name')->nullable();
            $table->longText('email')->nullable();
            $table->longText('phone')->nullable();
            $table->enum('pax_type', ['Adult', 'Child', 'Infant'])->nullable();
            //$table->longText('ticket_number')->nullable();
            $table->longText('ticket_price')->nullable();
            $table->date('date_of_birth')->nullable();
            // $table->enum('gender', [
            //     'Male',
            //     'Female',
            //     'Transgender',
            //     'Non-binary',
            //     'Genderqueer',
            //     'Genderfluid',
            //     'Agender',
            //     'Bigender',
            //     'Two-Spirit',
            //     'Other',
            //     'Prefer not to say'
            // ])->nullable();
            // $table->longText('nationality')->nullable();
            // $table->enum('type', ['Adult', 'Child', 'Infant'])->nullable();
            $table->longText('passport_number')->nullable();
            // $table->longText('passport_expiry_date')->nullable();
            $table->longText('baggage_allowance')->nullable();
            $table->boolean('reminder_status')->nullable()->default(1);
            $table->boolean('mail_sent_status')->nullable()->default(0);

            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->bigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_passengers');
    }
};
