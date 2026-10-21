<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'actif', 'must_change_password', 'telephone'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'actif' => 'boolean', 'must_change_password' => 'boolean', 'email_verified_at' => 'datetime'];
    }

    public function hasPermission(string $permission): bool
    {
        $perms = config('permissions.map')[$this->role] ?? [];
        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }

    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }

    public function roleLabel(): string { return config('permissions.roles')[$this->role] ?? $this->role; }
}
