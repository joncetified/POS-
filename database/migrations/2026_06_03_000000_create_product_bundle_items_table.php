<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('is_bundle')->default(false)->after('image_path');
        });

        Schema::create('product_bundle_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bundle_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['bundle_product_id', 'component_product_id'], 'bundle_component_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bundle_items');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('is_bundle');
        });
    }
};
