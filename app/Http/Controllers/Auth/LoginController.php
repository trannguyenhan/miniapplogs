<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers {
        login as protected traitLogin;
        logout as protected traitLogout;
    }

    protected $redirectTo = '/logs';

    public function showLoginForm()
    {
        $authMethod = SystemSetting::getAuthMethod();

        $ssoConfig = SystemSetting::getSsoConfig();

        return view('auth.login', [
            'authMethod' => $authMethod,
            'ssoEnabled' => in_array($authMethod, ['sso', 'both']),
            'ssoProviderName' => $ssoConfig['provider_name'] ?: 'SSO',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        return $this->traitLogin($request);
    }

    protected function authenticated(Request $request, $user)
    {
        return redirect()->intended($this->redirectPath());
    }

    public function logout(Request $request): RedirectResponse
    {
        if (SystemSetting::useSsoAccountPages()) {
            $ssoLogoutUrl = SystemSetting::getSsoLogoutUrl();
            if (!empty($ssoLogoutUrl)) {
                $this->guard()->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->away($ssoLogoutUrl);
            }
        }

        return $this->traitLogout($request);
    }
}
