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
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                  ->constrained('apartment_categories')
                  ->onDelete('cascade'); // delete apartments if category deleted
            $table->integer('number_item');
            $table->string('location');
            $table->string('address');
            $table->foreignId('tenant_id')
                  ->nullable()
                  ->constrained('estate_manager_id')
                  ->onDelete('set null'); // keep apartment but remove tenant link if tenant deleted
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
