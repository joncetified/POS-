<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'face_descriptor')) {
                $table->dropColumn('face_descriptor');
            }

            if (Schema::hasColumn('users', 'face_registered_at')) {
                $table->dropColumn('face_registered_at');
            }

            if (! Schema::hasColumn('users', 'biometric_credential_id')) {
                $table->string('biometric_credential_id', 512)->nullable()->after('avatar_path')->unique();
            }

            if (! Schema::hasColumn('users', 'biometric_registered_at')) {
                $table->timestamp('biometric_registered_at')->nullable()->after('biometric_credential_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'biometric_registered_at')) {
                $table->dropColumn('biometric_registered_at');
            }

            if (Schema::hasColumn('users', 'biometric_credential_id')) {
                $table->dropUnique(['biometric_credential_id']);
                $table->dropColumn('biometric_credential_id');
            }
        });
    }
};
