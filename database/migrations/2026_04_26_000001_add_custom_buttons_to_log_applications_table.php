<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_applications', function (Blueprint $table) {
            $table->json('custom_buttons')->nullable()->after('restart_command');
        });
    }

    public function down(): void
    {
        Schema::table('log_applications', function (Blueprint $table) {
            $table->dropColumn('custom_buttons');
        });
    }
};
