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
        Schema::create('marketing_sends', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['email', 'whatsapp']);
            $table->string('subject');
            $table->longText('content')->nullable();
            $table->json('customers')->nullable(); // [{ id, name, email, phone, ... }]
            $table->string('document_path')->nullable(); // storage path e.g. marketing-attachments/xxx.pdf
            $table->string('document_name')->nullable(); // original filename
            $table->dateTime('sent_date_time')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_sends');
    }
};
