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
        Schema::create('sticky_notes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable()->comment('Owner / business');
            $table->string('note_title')->nullable();
            $table->longText('note_description')->nullable();
            $table->dateTime('deadline')->nullable();
            $table->dateTime('reminder_datetime')->nullable();
            $table->dateTime('reminder_mail_sent_at')->nullable();
            $table->string('status', 50)->nullable()->default('Pending')->comment('Pending, In Progress, Completed, Cancelled');
            $table->boolean('read_status')->nullable()->default(0)->comment('0/1');
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->bigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('sticky_note_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sticky_note_id')->constrained('sticky_notes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['sticky_note_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sticky_note_user');
        Schema::dropIfExists('sticky_notes');
    }
};
