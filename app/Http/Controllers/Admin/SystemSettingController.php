<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SsoRoleMapping;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SystemSettingController extends Controller
{
    public function index()
    {
        $authMethod = SystemSetting::getAuthMethod();
        $ssoConfig  = SystemSetting::getSsoConfig();
        $mappings   = collect();
        try {
            $mappings = SsoRoleMapping::orderBy('created_at')->get();
        } catch (\Exception $e) {
            // Table may not exist yet
        }

        return view('admin.system-settings.index', compact('authMethod', 'ssoConfig', 'mappings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'auth_method' => 'required|in:local,sso,both',
        ]);

        SystemSetting::set('auth_method', $request->input('auth_method'));

        // SSO config
        if (in_array($request->input('auth_method'), ['sso', 'both'])) {
            $request->validate([
                'sso_issuer_url' => 'required|url',
                'sso_client_id'  => 'required|string',
                'sso_profile_url' => 'nullable|url',
                'sso_logout_url' => 'nullable|url',
            ]);
        }

        if ($request->input('auth_method') === 'sso') {
            $request->validate([
                'sso_profile_url' => 'required|url',
                'sso_logout_url' => 'required|url',
            ]);
        }

        $ssoFields = [
            'sso_provider_name', 'sso_issuer_url', 'sso_client_id',
            'sso_scopes', 'sso_role_claim', 'sso_default_role',
            'sso_profile_url', 'sso_logout_url',
        ];

        if (in_array($request->input('auth_method'), ['sso', 'both'])) {
            foreach ($ssoFields as $field) {
                if ($request->has($field)) {
                    SystemSetting::set($field, $request->input($field));
                }
            }
            // Only update secret if a new value was provided – store encrypted
            if ($request->filled('sso_client_secret')) {
                SystemSetting::set('sso_client_secret', Crypt::encryptString($request->input('sso_client_secret')));
            }
        }

        return redirect()->route('admin.system-settings.index')
            ->with('success', __('app.settings_updated'));
    }

    public function uploadFavicon(Request $request)
    {
        $request->validate([
            'favicon' => 'required|file|mimes:ico,png,jpg,jpeg,gif,svg|max:512',
        ]);

        $file = $request->file('favicon');
        $ext  = strtolower($file->getClientOriginalExtension());

        // Remove old custom favicon if different extension
        $old = SystemSetting::get('app_favicon_file');
        if ($old && file_exists(public_path($old)) && $old !== 'favicon.' . $ext) {
            @unlink(public_path($old));
        }

        $filename = 'favicon.' . $ext;
        $file->move(public_path(), $filename);

        SystemSetting::set('app_favicon_file', $filename);

        return redirect()->route('admin.system-settings.index')
            ->with('success', __('app.favicon_updated'));
    }

    public function storeMapping(Request $request)
    {
        $request->validate([
            'sso_claim_field' => 'required|string|max:100',
            'sso_claim_value' => 'required|string|max:255',
            'local_role'      => 'required|in:admin,user',
        ]);

        SsoRoleMapping::create($request->only('sso_claim_field', 'sso_claim_value', 'local_role'));

        return redirect()->route('admin.system-settings.index')
            ->with('success', __('app.mapping_added'));
    }

    public function destroyMapping(SsoRoleMapping $mapping)
    {
        $mapping->delete();

        return redirect()->route('admin.system-settings.index')
            ->with('success', __('app.mapping_deleted'));
    }
}
