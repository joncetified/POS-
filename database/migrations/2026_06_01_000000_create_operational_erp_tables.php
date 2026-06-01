<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position')->default('Staff');
            $table->string('phone')->nullable();
            $table->unsignedInteger('base_salary')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('period', 7);
            $table->unsignedInteger('amount');
            $table->date('paid_at');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('operational_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('description');
            $table->unsignedInteger('amount');
            $table->date('spent_at');
            $table->string('vendor')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('type', 20);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_cost')->default(0);
            $table->unsignedInteger('total_cost')->default(0);
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->string('note')->nullable();
            $table->date('occurred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('operational_expenses');
        Schema::dropIfExists('salary_payments');
        Schema::dropIfExists('employees');
    }
};
