<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('password_temporal')->nullable();
            $table->timestamp('password_temporal_vence')->nullable();
            $table->timestamp('cambio_password_hasta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['password_temporal', 'password_temporal_vence', 'cambio_password_hasta']);
        });
    }
};
