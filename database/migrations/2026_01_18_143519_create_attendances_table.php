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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('date');
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->decimal('total_hours', 8, 2)->nullable();
            $table->json('attendance_timeline')->nullable();
            $table->enum('status', ['Present', 'Late', 'Absent', 'Half-day', 'On Leave', 'Work From Home'])->default('Present');
            $table->string('ip_address')->nullable();
            $table->string('device_browser')->nullable();
            $table->longText('overtime_task_description')->nullable();
            $table->boolean('forgot_clock_out')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['employee_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
