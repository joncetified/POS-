<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 160);
            $table->string('logo_path')->nullable();
            $table->string('manager_name', 120)->nullable();
            $table->string('contact_email', 160)->nullable();
            $table->string('contact_phone', 80)->nullable();
            $table->string('contact_whatsapp', 80)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
