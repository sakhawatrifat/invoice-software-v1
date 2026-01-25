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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();

            $table->string('customer_full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('website')->nullable();

            $table->bigInteger('source_id')->nullable();

            $table->enum('status', [
                'New',
                'Contacted',
                'Qualified',
                'Lost',
                'Converted To Customer',
            ])->nullable();

            $table->enum('priority', ['Low', 'Medium', 'High'])->nullable();

            $table->bigInteger('assigned_to')->nullable();
            $table->longText('notes')->nullable();

            $table->dateTime('last_contacted_at')->nullable();
            $table->dateTime('converted_customer_at')->nullable();

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
        Schema::dropIfExists('leads');
    }
};

