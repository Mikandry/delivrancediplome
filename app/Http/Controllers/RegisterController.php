<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show() { return view('auth.register'); }

    public function store(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:120', 'email' => 'required|email|unique:users,email', 'telephone' => 'nullable|string|max:30',
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        $u = User::create($d + ['role' => 'client', 'actif' => true, 'must_change_password' => false]);
        Auth::login($u);
        $r->session()->regenerate();
        Audit::log('client.inscription', $u);
        return redirect()->route('demandes.create');
    }
}
