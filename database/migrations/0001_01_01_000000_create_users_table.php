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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['admin', 'user'])->nullable()->default('user')->comment('admin, user');
            $table->boolean('is_staff')->nullable()->default(0);
            $table->bigInteger('parent_id')->nullable()->comment('For Staff');
            $table->string('uid')->nullable();
            $table->longText('image')->nullable();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->longText('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('otp')->nullable();
            
            
            $table->string('device_token')->nullable();
            $table->string('ip_address')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->nullable()->default('Inactive')->comment('Active, Inactive');
            $table->string('default_language')->nullable()->default('en');
            $table->json('permissions')->nullable();
            $table->rememberToken();
            
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->bigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
