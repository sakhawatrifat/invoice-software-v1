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
        Schema::create('user_companies', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->longText('company_name');
            $table->longText('tagline')->nullable();
            $table->longText('website_url')->nullable();
            $table->longText('invoice_prefix')->nullable();
            $table->longText('company_invoice_id')->nullable();
            $table->longText('light_logo')->nullable();
            $table->longText('dark_logo')->nullable();
            $table->longText('light_icon')->nullable();
            $table->longText('dark_icon')->nullable();
            $table->longText('light_seal')->nullable();
            $table->longText('dark_seal')->nullable();
            $table->longText('address')->nullable();
            $table->longText('phone_1')->nullable();
            $table->longText('phone_2')->nullable();
            $table->longText('email_1')->nullable();
            $table->longText('email_2')->nullable();
            $table->bigInteger('currency_id')->nullable();
            $table->longText('cc_emails')->nullable();
            $table->longText('bcc_emails')->nullable();
            $table->boolean('reminder_mail_content')->nullable();
            
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
        Schema::dropIfExists('user_companies');
    }
};
