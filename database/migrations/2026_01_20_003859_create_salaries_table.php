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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->year('year');
            $table->integer('month'); // 1-12
            $table->decimal('base_salary', 20, 2)->nullable()->default(0);
            $table->decimal('deductions', 20, 2)->nullable()->default(0);
            $table->longText('deduction_note')->nullable();
            $table->decimal('bonus', 20, 2)->nullable()->default(0);
            $table->longText('bonus_note')->nullable();
            $table->decimal('net_salary', 20, 2)->nullable()->default(0);
            $table->enum('payment_status', ['Unpaid', 'Paid', 'Partial'])->nullable()->default('Unpaid');
            $table->decimal('paid_amount', 20, 2)->nullable()->default(0);
            $table->enum('payment_method', ['Bank Transfer', 'Card Payments', 'Cheque', 'bKash', 'Nagad', 'Rocket', 'Upay'])->nullable();
            $table->date('payment_date')->nullable();
            $table->longText('payment_note')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            // Unique constraint for employee, year, month (excluding soft deleted)
            $table->unique(['employee_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
