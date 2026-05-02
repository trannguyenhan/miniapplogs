<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class SsoRoleMapping extends Model
{
    protected $fillable = ['sso_claim_field', 'sso_claim_value', 'local_role'];

    /**
     * Resolve local role from SSO claims (e.g. userinfo or id_token payload).
     * Returns the highest-priority matched role, or the configured default.
     */
    public static function resolveRole(array $claims, string $defaultRole = 'user'): string
    {
        try {
            $mappings = static::all();
        } catch (\Exception $e) {
            Log::warning('SSO role mapping query failed, fallback to default role.', [
                'default_role' => $defaultRole,
                'error' => $e->getMessage(),
            ]);
            return $defaultRole;
        }

        $matched = $defaultRole;

        Log::debug('Resolving SSO role from claims.', [
            'default_role' => $defaultRole,
            'claim_keys' => array_keys($claims),
            'mapping_count' => $mappings->count(),
        ]);

        foreach ($mappings as $mapping) {
            // Allow mapping multiple claim fields by comma, e.g. "roles,groups"
            $fields = array_values(array_filter(array_map('trim', explode(',', (string) $mapping->sso_claim_field))));
            if (empty($fields)) {
                continue;
            }

            // Allow mapping multiple expected values by comma, e.g. "admin,devops"
            $expectedValues = array_values(array_filter(array_map('trim', explode(',', (string) $mapping->sso_claim_value))));
            if (empty($expectedValues)) {
                continue;
            }

            foreach ($fields as $field) {
                $rawValue = $claims[$field] ?? null;

                if ($rawValue === null) {
                    Log::debug('SSO role mapping: claim field not found.', [
                        'field' => $field,
                        'expected_values' => $expectedValues,
                        'local_role' => $mapping->local_role,
                    ]);
                    continue;
                }

                // Support both array claims (roles: ["admin"]) and scalar claims.
                $values = is_array($rawValue) ? $rawValue : [$rawValue];
                $normalizedValues = array_map(static fn ($v) => trim((string) $v), $values);

                $matchedExpectedValue = null;
                foreach ($expectedValues as $expectedValue) {
                    if (in_array($expectedValue, $normalizedValues, true)) {
                        $matchedExpectedValue = $expectedValue;
                        break;
                    }
                }

                if ($matchedExpectedValue === null) {
                    Log::debug('SSO role mapping: no value match.', [
                        'field' => $field,
                        'claim_values' => $normalizedValues,
                        'expected_values' => $expectedValues,
                        'local_role' => $mapping->local_role,
                    ]);
                    continue;
                }

                Log::debug('SSO role mapping matched.', [
                    'field' => $field,
                    'matched_value' => $matchedExpectedValue,
                    'local_role' => $mapping->local_role,
                ]);

                if ($mapping->local_role === 'admin') {
                    Log::info('SSO role resolved to admin.', [
                        'field' => $field,
                        'matched_value' => $matchedExpectedValue,
                    ]);

                    return 'admin'; // admin is highest priority
                }

                $matched = $mapping->local_role;
            }
        }

        Log::debug('SSO role resolved with fallback/matched role.', [
            'resolved_role' => $matched,
        ]);

        return $matched;
    }
}
