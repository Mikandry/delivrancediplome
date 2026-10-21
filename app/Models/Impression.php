<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Impression extends Model
{
    public $timestamps = false;
    protected $fillable = ['diplome_id', 'rang', 'user_id', 'autorisation_id', 'ip', 'imprime_at'];
    protected $casts = ['imprime_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}
