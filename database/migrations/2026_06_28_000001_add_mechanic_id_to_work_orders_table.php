<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->foreignId('mechanic_id')
                ->nullable()
                ->after('motorcycle_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['team_id', 'mechanic_id']);
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->dropIndex(['team_id', 'mechanic_id']);
            $table->dropConstrainedForeignId('mechanic_id');
        });
    }
};
