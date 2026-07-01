<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit', 30)->default('unidad');
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'sku']);
            $table->unique(['team_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_products');
    }
};
