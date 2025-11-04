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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->longText('title')->nullable();
            $table->date('deadline')->nullable()->comment('For Payment');
            $table->longText('url')->nullable();
            $table->boolean('read_status')->nullable()->default(0)->comment('0/1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
