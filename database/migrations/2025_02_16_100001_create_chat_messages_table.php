<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->foreignId('reply_to_message_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->enum('type', ['text', 'file'])->default('text');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deleted_for_everyone_at')->nullable();

            $table->index(['sender_id', 'recipient_id']);
            $table->index(['recipient_id', 'sender_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
