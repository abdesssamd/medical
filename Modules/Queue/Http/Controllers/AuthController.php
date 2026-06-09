<?php

namespace Modules\Queue\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => __('queue.invalid_credentials'),
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user?->role === 'super_admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user?->role === 'agent' && $user->agent && $user->agent->is_active) {
            return redirect()->route('agent.dashboard');
        }

        if ($user?->role === 'professional') {
            return redirect()->route('appointment.pro.dashboard');
        }

        if ($user?->role === 'secretary') {
            $professionalId = User::where('role', 'professional')->value('id') ?? $user->id;

            return redirect()->route('appointment.sec.dashboard', ['professional_id' => $professionalId]);
        }

        Auth::logout();

        return redirect()->route('login')->withErrors([
            'email' => __('queue.account_not_allowed'),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

