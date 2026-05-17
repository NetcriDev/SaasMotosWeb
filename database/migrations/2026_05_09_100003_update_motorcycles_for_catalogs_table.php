<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->foreignId('brand_id')->after('license_plate')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('motorcycle_model_id')->after('brand_id')->nullable()->constrained('motorcycle_models')->nullOnDelete();
        });

        Schema::table('motorcycles', function (Blueprint $table) {
            $table->dropColumn(['brand', 'model']);
        });
    }

    public function down(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['motorcycle_model_id']);
        });

        Schema::table('motorcycles', function (Blueprint $table) {
            $table->dropColumn(['brand_id', 'motorcycle_model_id']);
            $table->string('brand')->after('license_plate');
            $table->string('model')->after('brand');
        });
    }
};
