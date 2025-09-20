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
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name');
            $table->string('metric_type'); // 'counter', 'gauge', 'histogram'
            $table->json('labels')->nullable(); // For additional metadata
            $table->decimal('value', 15, 4);
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index(['metric_name', 'recorded_at']);
            $table->index('metric_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
