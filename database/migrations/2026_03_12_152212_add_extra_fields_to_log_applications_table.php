<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('log_applications', function (Blueprint $table) {
            $table->string('log_type')->default('file')->after('log_path'); // file, pattern
            $table->string('script_path')->nullable()->after('log_type');
            $table->string('allowed_roles')->default('admin')->after('script_path'); // comma separated roles
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_applications', function (Blueprint $table) {
            $table->dropColumn(['log_type', 'script_path', 'allowed_roles']);
        });
    }
};
