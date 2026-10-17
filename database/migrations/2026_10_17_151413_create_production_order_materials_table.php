<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_per_unit', 12, 3);
            $table->decimal('required_qty', 12, 3);
            $table->decimal('consumed_qty', 12, 3)->default(0);
            $table->timestamps();
            $table->unique(['production_order_id', 'material_id'], 'prod_order_materials_order_material_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_materials');
    }
};
