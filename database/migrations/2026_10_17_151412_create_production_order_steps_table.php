<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('routing_step_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_center_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('name');
            $table->boolean('consumes_materials')->default(false);
            $table->string('status', 20)->default('pending');
            $table->decimal('planned_minutes', 10, 2);
            $table->unsignedInteger('actual_seconds')->default(0);
            $table->unsignedInteger('input_qty')->nullable();
            $table->unsignedInteger('good_qty')->nullable();
            $table->unsignedInteger('scrap_qty')->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['production_order_id', 'sequence']);
            $table->index(['status', 'work_center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_steps');
    }
};
