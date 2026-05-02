<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        if (SystemSetting::useSsoAccountPages()) {
            $ssoProfileUrl = SystemSetting::getSsoProfileUrl();
            if (!empty($ssoProfileUrl)) {
                return redirect()->away($ssoProfileUrl);
            }
        }

        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:users,email,' . $user->id,
            'current_password'      => 'nullable|string',
            'password'              => ['nullable', 'confirmed', Password::min(8)],
        ]);

        if (!empty($validated['password'])) {
            if (empty($request->current_password) || !Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => __('app.current_password_invalid')])->withInput();
            }
            $user->password = Hash::make($validated['password']);
        }

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return back()->with('success', __('app.profile_updated'));
    }
}
