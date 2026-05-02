<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SsoRoleMapping;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /**
     * Redirect to SSO provider's authorization endpoint.
     */
    public function redirect(Request $request)
    {
        $config = SystemSetting::getSsoConfig();

        if (empty($config['issuer_url']) || empty($config['client_id'])) {
            return redirect()->route('login')->with('error', __('app.sso_not_configured'));
        }

        $endpoints = $this->discoverEndpoints($config['issuer_url']);
        if (!$endpoints) {
            return redirect()->route('login')->with('error', __('app.sso_discovery_failed'));
        }

        // Generate state + PKCE code_verifier
        $state = Str::random(40);
        $codeVerifier = Str::random(128);

        $request->session()->put('sso_state', $state);
        $request->session()->put('sso_endpoints', $endpoints);
        $request->session()->put('sso_code_verifier', $codeVerifier);

        // S256 code challenge
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $params = http_build_query([
            'client_id'             => $config['client_id'],
            'redirect_uri'          => route('sso.callback'),
            'response_type'         => 'code',
            'scope'                 => $config['scopes'],
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return redirect($endpoints['authorization_endpoint'] . '?' . $params);
    }

    /**
     * Handle callback from SSO provider.
     */
    public function callback(Request $request)
    {
        // Validate state
        $sessionState = $request->session()->pull('sso_state');
        if (!$sessionState || $request->input('state') !== $sessionState) {
            return redirect()->route('login')->with('error', __('app.sso_invalid_state'));
        }

        if ($request->has('error')) {
            return redirect()->route('login')->with('error', $request->input('error_description', $request->input('error')));
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('login')->with('error', __('app.sso_no_code'));
        }

        $config = SystemSetting::getSsoConfig();
        $endpoints = $request->session()->pull('sso_endpoints');
        $codeVerifier = $request->session()->pull('sso_code_verifier');

        if (!$endpoints) {
            $endpoints = $this->discoverEndpoints($config['issuer_url']);
        }

        if (!$endpoints || empty($endpoints['token_endpoint'])) {
            Log::warning('SSO callback missing token endpoint after discovery.', [
                'issuer_url' => $config['issuer_url'] ?? null,
            ]);

            return redirect()->route('login')->with('error', __('app.sso_discovery_failed'));
        }

        // Exchange code for tokens (with PKCE code_verifier)
        $tokenParams = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => route('sso.callback'),
            'client_id'     => $config['client_id'],
        ];

        if ($codeVerifier) {
            $tokenParams['code_verifier'] = $codeVerifier;
        }

        if (!empty($config['client_secret'])) {
            $tokenParams['client_secret'] = $config['client_secret'];
        }

        $tokenResult = $this->exchangeToken($endpoints['token_endpoint'], $tokenParams, $config);
        if (!$tokenResult['success']) {
            return redirect()->route('login')->with('error', $tokenResult['error']);
        }

        $tokens = $tokenResult['tokens'];

        // Get user info from userinfo endpoint or decode id_token
        $claims = $this->getUserClaims($tokens, $endpoints);
        if (!$claims) {
            return redirect()->route('login')->with('error', __('app.sso_userinfo_error'));
        }

        // Find or create local user
        $user = $this->findOrCreateUser($claims, $config);
        if (!$user) {
            return redirect()->route('login')->with('error', __('app.sso_userinfo_error'));
        }

        Auth::login($user, true);

        return redirect()->intended('/logs');
    }

    /**
     * Exchange authorization code for tokens.
     * Try client_secret_post first, then fallback to client_secret_basic when provider/client expects it.
     */
    private function exchangeToken(string $tokenEndpoint, array $tokenParams, array $config): array
    {
        $attempts = [];

        // Attempt 1: client_secret_post (current behavior)
        try {
            $response = Http::asForm()->post($tokenEndpoint, $tokenParams);
            $attempts[] = ['method' => 'client_secret_post', 'status' => $response->status()];

            if ($response->successful()) {
                return [
                    'success' => true,
                    'tokens' => $response->json(),
                ];
            }

            $error = (string) ($response->json('error') ?? '');

            // If invalid_client and secret is configured, retry with client_secret_basic.
            if ($error === 'invalid_client' && !empty($config['client_secret'])) {
                $basicParams = $tokenParams;
                unset($basicParams['client_secret']);

                $basicResponse = Http::asForm()
                    ->withBasicAuth($config['client_id'], $config['client_secret'])
                    ->post($tokenEndpoint, $basicParams);

                $attempts[] = ['method' => 'client_secret_basic', 'status' => $basicResponse->status()];

                if ($basicResponse->successful()) {
                    Log::info('SSO token exchange succeeded using client_secret_basic fallback.', [
                        'token_endpoint' => $tokenEndpoint,
                    ]);

                    return [
                        'success' => true,
                        'tokens' => $basicResponse->json(),
                    ];
                }

                Log::warning('SSO token exchange failed after auth-method fallback.', [
                    'token_endpoint' => $tokenEndpoint,
                    'attempts' => $attempts,
                    'body' => $basicResponse->body(),
                ]);

                return [
                    'success' => false,
                    'error' => $basicResponse->json('error_description')
                        ?: $basicResponse->json('error')
                        ?: __('app.sso_token_error'),
                ];
            }

            Log::warning('SSO token exchange failed.', [
                'token_endpoint' => $tokenEndpoint,
                'attempts' => $attempts,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('error_description')
                    ?: $response->json('error')
                    ?: __('app.sso_token_error'),
            ];
        } catch (ConnectionException $e) {
            Log::warning('SSO token exchange connection failed.', [
                'token_endpoint' => $tokenEndpoint,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => __('app.sso_token_error'),
            ];
        }
    }

    /**
     * Discover OIDC endpoints from issuer's well-known configuration.
     */
    private function discoverEndpoints(string $issuerUrl): ?array
    {
        $url = rtrim($issuerUrl, '/') . '/.well-known/openid-configuration';

        try {
            $response = Http::timeout(10)->get($url);
            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('SSO discovery returned non-success status.', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::warning('SSO discovery request failed.', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get user claims from userinfo endpoint or id_token.
     */
    private function getUserClaims(array $tokens, array $endpoints): ?array
    {
        $userinfoClaims = null;

        // Try userinfo endpoint first
        if (!empty($endpoints['userinfo_endpoint']) && !empty($tokens['access_token'])) {
            try {
                $response = Http::withToken($tokens['access_token'])->get($endpoints['userinfo_endpoint']);
                if ($response->successful()) {
                    $userinfoClaims = $response->json();
                }

                Log::warning('SSO userinfo request returned non-success status.', [
                    'userinfo_endpoint' => $endpoints['userinfo_endpoint'],
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (\Exception $e) {
                Log::warning('SSO userinfo request failed.', [
                    'userinfo_endpoint' => $endpoints['userinfo_endpoint'],
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $idTokenClaims = $this->decodeJwtPayload($tokens['id_token'] ?? null);
        $accessTokenClaims = $this->decodeJwtPayload($tokens['access_token'] ?? null);

        // Merge claims from multiple sources so role-related claims are not lost.
        // Priority: userinfo > id_token > access_token.
        $mergedClaims = array_merge(
            $accessTokenClaims ?? [],
            $idTokenClaims ?? [],
            $userinfoClaims ?? []
        );

        if (!empty($mergedClaims)) {
            Log::debug('SSO claims merged from available sources.', [
                'userinfo_keys' => array_keys($userinfoClaims ?? []),
                'id_token_keys' => array_keys($idTokenClaims ?? []),
                'access_token_keys' => array_keys($accessTokenClaims ?? []),
                'merged_keys' => array_keys($mergedClaims),
            ]);

            return $mergedClaims;
        }

        return null;
    }

    /**
     * Decode JWT payload without signature verification for claim extraction.
     */
    private function decodeJwtPayload(?string $jwt): ?array
    {
        if (empty($jwt)) {
            return null;
        }

        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        return is_array($payload) ? $payload : null;
    }

    /**
     * Find existing user by email or create a new one from SSO claims.
     */
    private function findOrCreateUser(array $claims, array $config): ?User
    {
        $email = $claims['email'] ?? ($claims['preferred_username'] ?? ($claims['sub'] ?? null));
        if (empty($email)) {
            Log::warning('SSO claims missing email identifier.', [
                'claim_keys' => array_keys($claims),
            ]);

            return null;
        }

        $name = $claims['name'] ?? ($claims['preferred_username'] ?? ($claims['sub'] ?? 'SSO User'));

        // Resolve role from SSO claims using dynamic mapping
        $role = SsoRoleMapping::resolveRole($claims, $config['default_role'] ?: 'user');

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update(['name' => $name, 'role' => $role]);
        } else {
            $user = User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => Str::random(64),
                'role'     => $role,
            ]);
        }

        return $user;
    }
}
