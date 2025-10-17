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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->dateTime('invoice_date')->nullable();
            $table->longText('payment_invoice_id')->nullable();
            $table->bigInteger('ticket_id')->nullable();
            $table->longText('client_name')->nullable();
            $table->longText('client_phone')->nullable();
            $table->longText('client_email')->nullable();
            $table->bigInteger('introduction_source_id')->nullable();
            $table->bigInteger('customer_country_id')->nullable();
            $table->json('issued_supplier_ids')->nullable();
            //$table->bigInteger('issued_supplier_id')->nullable();
            $table->bigInteger('issued_by_id')->nullable();
            
            $table->enum('trip_type', ['One Way', 'Round Trip', 'Multi City'])->nullable();
            $table->dateTime('departure_date_time')->nullable();
            $table->dateTime('return_date_time')->nullable();
            $table->longText('departure')->nullable();
            $table->longText('destination')->nullable();
            $table->longText('flight_route')->nullable();
            $table->enum('seat_confirmation', ['Window', 'Aisle', 'Not Chosen'])->nullable();
            $table->enum('mobility_assistance', ['Wheelchair', 'Baby Bassinet Seat', 'Meet & Assist', 'Not Chosen'])->nullable();
            $table->bigInteger('airline_id')->nullable();
            $table->enum('transit_visa_application', ['Need To Do', 'Done', 'No Need'])->nullable();
            $table->enum('halal_meal_request', ['Need To Do', 'Done', 'No Need'])->nullable();
            $table->enum('transit_hotel', ['Need To Do', 'Done', 'No Need'])->nullable();
            $table->longText('note')->nullable();

            $table->bigInteger('transfer_to_id')->nullable();
            $table->bigInteger('payment_method_id')->nullable();
            $table->bigInteger('issued_card_type_id')->nullable();
            $table->bigInteger('card_owner_id')->nullable();
            $table->bigInteger('card_digit')->nullable();
            
            $table->decimal('total_purchase_price', 20,2)->nullable()->default(0);
            $table->decimal('total_selling_price', 20,2)->nullable()->default(0);
            $table->json('paymentData')->nullable()->comment('paid_amount', 'date');
            $table->enum('payment_status', ['Unpaid', 'Paid', 'Partial', 'Unknown'])->nullable()->default('Unknown');
            $table->dateTime('next_payment_deadline')->nullable();

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
        Schema::dropIfExists('payments');
    }
};
