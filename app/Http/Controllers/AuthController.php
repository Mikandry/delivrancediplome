<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $key = 'login:' . strtolower($data['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $min = ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages(['email' => "Trop de tentatives. Réessayez dans $min minute(s)."]);
        }
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'actif' => 1])) {
            RateLimiter::hit($key, 900);
            Audit::log('auth.echec', null, $data['email']);
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects ou compte désactivé.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        Audit::log('auth.connexion', $request->user());
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Audit::log('auth.deconnexion', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function editPassword() { return view('auth.password'); }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ], ['password.confirmed' => 'La confirmation ne correspond pas.', 'current_password.current_password' => 'Mot de passe actuel incorrect.']);

        if (strtolower($request->password) === 'motdepasse') {
            throw ValidationException::withMessages(['password' => 'Ce mot de passe est interdit.']);
        }
        $u = $request->user();
        $u->update(['password' => $request->password, 'must_change_password' => false]);
        Audit::log('auth.mot_de_passe_change', $u);
        return redirect()->route('dashboard')->with('success', 'Mot de passe mis à jour.');
    }
}
