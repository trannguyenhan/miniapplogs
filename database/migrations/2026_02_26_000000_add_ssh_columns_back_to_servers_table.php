<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm lại các cột SSH vào bảng servers.
     * (Đã bị xóa ở migration drop_ssh_columns, nay thêm lại để support SSH connection type)
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            if (! Schema::hasColumn('servers', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('connection_type');
            }
            if (! Schema::hasColumn('servers', 'ssh_port')) {
                $table->integer('ssh_port')->default(22)->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('servers', 'ssh_user')) {
                $table->string('ssh_user')->nullable()->after('ssh_port');
            }
            if (! Schema::hasColumn('servers', 'ssh_password')) {
                $table->text('ssh_password')->nullable()->after('ssh_user');
            }
            if (! Schema::hasColumn('servers', 'ssh_private_key')) {
                $table->text('ssh_private_key')->nullable()->after('ssh_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $cols = ['ip_address', 'ssh_port', 'ssh_user', 'ssh_password', 'ssh_private_key'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('servers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
