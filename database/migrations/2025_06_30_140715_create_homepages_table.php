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
        Schema::create('homepages', function (Blueprint $table) {
            $table->id();
            $table->string('lang')->nullable()->default('en');
            $table->longText('banner')->nullable();
            $table->json('bannerData')->comment('image,title,details,link')->nullable();
            $table->json('featureContent')->comment('image,title,details')->nullable();
            $table->longText('title')->nullable();
            $table->longText('subtitle')->nullable();
            $table->longText('description')->nullable();
            $table->json('content')->comment('image,title,details')->nullable();


            $table->boolean('is_registration_enabled')->nullable()->default(1);
            $table->longText('auth_bg_image')->nullable();
            
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
        Schema::dropIfExists('homepages');
    }
};
