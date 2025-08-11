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
        Schema::create('landlord_agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique()->index();
            $table->string('id_card')->nullable();
            $table->string('selfie_photo')->nullable();
            $table->string('cac')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_state')->nullable();
            $table->string('business_lga')->nullable();
            $table->string('about_business')->nullable();
            $table->string('business_services')->nullable();
            $table->string('business_address')->nullable();
            $table->string('logo')->nullable();
            $table->enum('verified', ['yes','no'])->default('no')->nullable();
            $table->timestamps();


            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landlord_agents');
    }
};
