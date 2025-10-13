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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->enum('document_type', ['ticket', 'invoice', 'quotation'])->nullable()->default('ticket');
            $table->enum('booking_type', ['e-Booking', 'e-Ticket'])->nullable()->default('e-Ticket');
            $table->dateTime('invoice_date')->nullable();
            $table->longText('invoice_id')->nullable();
            $table->longText('reservation_number')->nullable();
            //$table->longText('airlines_pnr')->nullable();
            $table->enum('trip_type', ['One Way', 'Round Trip', 'Multi City'])->nullable();
            $table->enum('ticket_type', ['Economy', 'Premium Economy', 'Business Class', 'First Class'])->nullable();
            $table->enum('booking_status', ['On Hold', 'Processing', 'Confirmed', 'Cancelled'])->nullable();

            $table->enum('bill_to', ['Company', 'Individual'])->nullable();
            $table->longText('bill_to_info')->nullable()->comment('name, phone, email, address');

            $table->longText('footer_title')->nullable();
            $table->longText('footer_text')->nullable();
            $table->longText('bank_details')->nullable();

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
        Schema::dropIfExists('tickets');
    }
};
