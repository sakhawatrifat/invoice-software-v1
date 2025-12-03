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
        Schema::create('hotel_invoices', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->dateTime('invoice_date')->nullable();
            $table->longText('website_name')->nullable();
            $table->longText('invoice_id')->nullable();
            $table->longText('pin_number')->nullable();
            $table->longText('booking_number')->nullable();
            $table->longText('hotel_image')->nullable();
            $table->longText('hotel_name')->nullable();
            $table->longText('hotel_address')->nullable();
            $table->longText('hotel_phone')->nullable();
            $table->longText('hotel_email')->nullable();
            $table->date('check_in_date')->nullable();
            $table->time('check_in_time')->nullable();
            $table->date('check_out_date')->nullable();
            $table->time('check_out_time')->nullable();
            $table->longText('room_type')->nullable();
            $table->integer('total_room')->nullable();
            $table->integer('total_night')->nullable();
            $table->json('guestInfo')->nullable()->comment('name,passport_no, phone, email');
            $table->longText('occupancy_info')->nullable();
            $table->longText('room_info')->nullable();
            $table->longText('meal_info')->nullable();
            $table->longText('room_amenities')->nullable();
            $table->decimal('total_price', 8,2)->nullable()->default(0);
            $table->enum('payment_status', ['Paid', 'Unpaid'])->nullable()->default('Unpaid');
            $table->enum('invoice_status', ['Draft', 'Final'])->nullable()->default('Draft');
            $table->json('cancellationPolicy')->nullable()->comment('date_time, fee');
            $table->longText('policy_note')->nullable();
            $table->longText('contact_info')->nullable();
            $table->bigInteger('mail_sent_count')->nullable();
            
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
        Schema::dropIfExists('hotel_invoices');
    }
};
