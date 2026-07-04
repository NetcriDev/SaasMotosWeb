<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('min_quantity', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['inventory_product_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
