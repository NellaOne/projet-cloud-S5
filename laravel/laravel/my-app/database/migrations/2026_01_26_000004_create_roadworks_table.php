n<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roadworks', function (Blueprint $table) {
            $table->decimal('id', 10, 2)->primary();
            $table->decimal('budget', 10, 2);
            $table->timestamp('finished_at');
            $table->integer('status_id');
            $table->integer('road_id');
            $table->integer('enterprise_id');
            $table->foreign('status_id')->references('id')->on('status');
            $table->foreign('road_id')->references('id')->on('roads');
            $table->foreign('enterprise_id')->references('id')->on('enterprises');
            $table->unique('road_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadworks');
    }
};