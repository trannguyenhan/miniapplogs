<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    public const DEFAULT_SSO_PROFILE_URL = 'https://sso.techvanguard.vn/account';
    public const DEFAULT_SSO_LOGOUT_URL = 'https://sso.techvanguard.vn/connect/logout?client_id=devops-app&post_logout_redirect_uri=http%3A%2F%2Flocalhost%3A8000%2Fsso%2Fcallback&state=optional-state';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Đọc giá trị đã mã hóa; fallback plaintext nếu chưa mã hóa (backward compat).
     */
    public static function getEncrypted(string $key, mixed $default = null): mixed
    {
        $raw = static::get($key, null);
        if ($raw === null || $raw === '') {
            return $default;
        }
        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            // Giá trị cũ chưa mã hóa – trả về plaintext, sẽ được ghi đè khi user lưu lại
            return $raw;
        }
    }

    public static function getAuthMethod(): string
    {
        return static::get('auth_method', 'local');
    }

    public static function getSsoConfig(): array
    {
        return [
            'provider_name'      => static::get('sso_provider_name', ''),
            'issuer_url'         => static::get('sso_issuer_url', ''),
            'client_id'          => static::get('sso_client_id', ''),
            'client_secret'      => static::getEncrypted('sso_client_secret', ''),
            'scopes'             => static::get('sso_scopes', 'openid profile email'),
            'role_claim'         => static::get('sso_role_claim', 'roles'),
            'default_role'       => static::get('sso_default_role', 'user'),
            'profile_url'        => static::get('sso_profile_url', static::DEFAULT_SSO_PROFILE_URL),
            'logout_url'         => static::get('sso_logout_url', static::DEFAULT_SSO_LOGOUT_URL),
        ];
    }

    public static function useSsoAccountPages(): bool
    {
        return static::getAuthMethod() === 'sso';
    }

    public static function getSsoProfileUrl(): string
    {
        return (string) static::get('sso_profile_url', static::DEFAULT_SSO_PROFILE_URL);
    }

    public static function getSsoLogoutUrl(): string
    {
        return (string) static::get('sso_logout_url', static::DEFAULT_SSO_LOGOUT_URL);
    }
}
