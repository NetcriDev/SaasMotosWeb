<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('system')->nullable();
            $table->string('description');
            $table->decimal('service_cost', 10, 2)->default(0);
            $table->boolean('is_billable')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['work_order_id', 'is_billable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_activities');
    }
};
