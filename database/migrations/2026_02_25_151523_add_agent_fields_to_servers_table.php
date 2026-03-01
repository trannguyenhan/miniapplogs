<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            // Loại kết nối: 'local' | 'ssh' | 'agent'
            $table->string('connection_type')->default('ssh')->after('use_ssh');

            // Agent HTTP fields
            $table->string('agent_url')->nullable()->after('connection_type');   // e.g. http://192.168.1.x:9876
            $table->text('agent_token')->nullable()->after('agent_url');          // Bearer token
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['connection_type', 'agent_url', 'agent_token']);
        });
    }
};
