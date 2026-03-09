<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_group_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('chat_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // who did the action
            $table->string('action', 50); // member_added, member_removed, admin_added, admin_removed, nickname_set
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('extra', 500)->nullable(); // e.g. nickname value or target display name for nickname_set
            $table->timestamps();

            $table->index(['group_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_group_events');
    }
};
