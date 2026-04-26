<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_applications', function (Blueprint $table) {
            $table->string('git_pull_role', 10)->default('admin')->after('git_path');
            $table->string('script_role', 10)->default('admin')->after('script_path');
            $table->string('restart_role', 10)->default('admin')->after('restart_command');
        });

        // Migrate existing allowed_roles data to git_pull_role and script_role
        \DB::table('log_applications')->get()->each(function ($app) {
            $roles = array_map('trim', explode(',', $app->allowed_roles ?? 'admin'));
            $role  = in_array('user', $roles) ? 'user' : 'admin';
            \DB::table('log_applications')->where('id', $app->id)->update([
                'git_pull_role' => $role,
                'script_role'   => $role,
            ]);
        });

        Schema::table('log_applications', function (Blueprint $table) {
            $table->dropColumn('allowed_roles');
        });
    }

    public function down(): void
    {
        Schema::table('log_applications', function (Blueprint $table) {
            $table->string('allowed_roles')->default('admin')->after('script_path');
        });

        Schema::table('log_applications', function (Blueprint $table) {
            $table->dropColumn(['git_pull_role', 'script_role', 'restart_role']);
        });
    }
};
