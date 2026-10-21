<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Organisme extends Model
{
    protected $table = 'organismes_verification';
    protected $fillable = ['nom', 'email', 'cle_hash', 'cle_prefixe', 'actif', 'limite_heure', 'dernier_usage_at'];
    protected $casts = ['actif' => 'boolean', 'dernier_usage_at' => 'datetime'];
}
