<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_contact_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // actor who did the action
            $table->foreignId('contact_user_id')->constrained('users')->cascadeOnDelete(); // other user in the 1-to-1
            $table->string('action', 50); // nickname_set, nickname_cleared, my_nickname_set, my_nickname_cleared
            $table->string('extra', 500)->nullable(); // nickname value for set actions
            $table->timestamps();

            $table->index(['user_id', 'contact_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_contact_events');
    }
};
