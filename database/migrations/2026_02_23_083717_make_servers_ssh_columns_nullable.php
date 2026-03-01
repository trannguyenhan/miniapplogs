<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Khi không dùng SSH (local server), ip_address và ssh_user không cần thiết
     * → cho phép NULL để form không bắt nhập khi tắt SSH
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('ip_address')->nullable()->change();
            $table->string('ssh_user')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('ip_address')->nullable(false)->change();
            $table->string('ssh_user')->nullable(false)->change();
        });
    }
};
