<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index() { return view('users.index', ['users' => User::orderBy('name')->paginate(20)]); }

    public function store(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'role' => ['required', Rule::in(array_keys(config('permissions.roles')))],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        $u = User::create($d + ['actif' => true, 'must_change_password' => true]);
        Audit::log('user.cree', $u, null, null, ['role' => $u->role, 'email' => $u->email]);
        return back()->with('success', "Compte créé. Mot de passe temporaire à communiquer de façon sécurisée.");
    }

    public function update(Request $r, User $user)
    {
        $d = $r->validate(['role' => ['required', Rule::in(array_keys(config('permissions.roles')))]]);
        abort_if($user->id === $r->user()->id, 422, 'Vous ne pouvez pas modifier votre propre rôle.');
        $old = $user->role;
        $user->update($d);
        Audit::log('user.role_change', $user, null, ['role' => $old], ['role' => $user->role]);
        return back()->with('success', 'Rôle mis à jour.');
    }

    public function toggle(Request $r, User $user)
    {
        abort_if($user->id === $r->user()->id, 422, 'Vous ne pouvez pas désactiver votre propre compte.');
        $user->update(['actif' => ! $user->actif]);
        Audit::log($user->actif ? 'user.reactive' : 'user.desactive', $user);
        return back()->with('success', $user->actif ? 'Compte réactivé.' : 'Compte désactivé.');
    }
}
