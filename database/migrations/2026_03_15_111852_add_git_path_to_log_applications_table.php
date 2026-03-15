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
            $table->string('git_path')->nullable()->after('git_branch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_applications', function (Blueprint $table) {
            $table->dropColumn('git_path');
        });
    }
};
