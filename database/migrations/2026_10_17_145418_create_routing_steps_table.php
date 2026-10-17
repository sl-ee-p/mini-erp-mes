<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routing_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_center_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('name');
            $table->decimal('planned_minutes_per_unit', 8, 2);
            $table->boolean('consumes_materials')->default(false);
            $table->timestamps();
            $table->unique(['product_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_steps');
    }
};
