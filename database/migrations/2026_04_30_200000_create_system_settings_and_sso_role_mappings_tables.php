<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('sso_role_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('sso_claim_field');   // e.g. "roles", "groups"
            $table->string('sso_claim_value');    // e.g. "admin", "devops"
            $table->string('local_role');          // "admin" or "user"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_role_mappings');
        Schema::dropIfExists('system_settings');
    }
};
