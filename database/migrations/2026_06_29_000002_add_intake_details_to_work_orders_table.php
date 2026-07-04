<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->text('intake_reason')->nullable()->after('maintenance_type_id');
            $table->json('affected_systems')->nullable()->after('intake_reason');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->dropColumn(['intake_reason', 'affected_systems']);
        });
    }
};
