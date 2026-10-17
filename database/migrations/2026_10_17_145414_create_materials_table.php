<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 40)->unique();
            $table->string('name')->index();
            $table->string('type', 20)->index();
            $table->string('unit', 10);
            $table->decimal('stock_qty', 12, 3)->default(0);
            $table->decimal('min_stock', 12, 3)->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
