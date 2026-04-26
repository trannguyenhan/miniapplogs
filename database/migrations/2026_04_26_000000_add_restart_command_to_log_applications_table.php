<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_applications', function (Blueprint $table) {
            $table->string('restart_command', 500)->nullable()->after('script_path');
        });
    }

    public function down(): void
    {
        Schema::table('log_applications', function (Blueprint $table) {
            $table->dropColumn('restart_command');
        });
    }
};
