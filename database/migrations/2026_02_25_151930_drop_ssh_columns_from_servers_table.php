<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $cols = ['use_ssh', 'ip_address', 'ssh_port', 'ssh_user', 'ssh_password', 'ssh_private_key'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('servers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('use_ssh')->default(false)->after('name');
            $table->string('ip_address')->nullable()->after('use_ssh');
            $table->integer('ssh_port')->default(22)->nullable()->after('ip_address');
            $table->string('ssh_user')->nullable()->after('ssh_port');
            $table->text('ssh_password')->nullable()->after('ssh_user');
            $table->text('ssh_private_key')->nullable()->after('ssh_password');
        });
    }
};
