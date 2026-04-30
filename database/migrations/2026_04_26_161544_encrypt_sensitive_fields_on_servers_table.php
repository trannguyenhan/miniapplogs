<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Re-encrypt ssh_password, ssh_private_key, agent_token
     * from plaintext to Laravel's encrypted cast format.
     */
    public function up(): void
    {
        DB::table('servers')->orderBy('id')->each(function ($server) {
            $update = [];

            foreach (['ssh_password', 'ssh_private_key', 'agent_token'] as $field) {
                $value = $server->$field;
                if ($value === null || $value === '') continue;

                // Skip if already encrypted (Laravel encrypted values start with "eyJ")
                if (str_starts_with($value, 'eyJ')) continue;

                try {
                    $update[$field] = Crypt::encryptString($value);
                } catch (\Exception $e) {
                    // Already encrypted or invalid – skip
                }
            }

            if ($update) {
                DB::table('servers')->where('id', $server->id)->update($update);
            }
        });
    }

    public function down(): void
    {
        // Decrypt back to plaintext
        DB::table('servers')->orderBy('id')->each(function ($server) {
            $update = [];

            foreach (['ssh_password', 'ssh_private_key', 'agent_token'] as $field) {
                $value = $server->$field;
                if ($value === null || $value === '') continue;

                try {
                    $update[$field] = Crypt::decryptString($value);
                } catch (\Exception $e) {
                    // Not encrypted, skip
                }
            }

            if ($update) {
                DB::table('servers')->where('id', $server->id)->update($update);
            }
        });
    }
};
