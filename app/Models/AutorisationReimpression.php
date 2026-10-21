<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AutorisationReimpression extends Model
{
    protected $table = 'autorisations_reimpression';
    protected $fillable = ['diplome_id', 'motif', 'autorise_par', 'utilisee_at'];
    protected $casts = ['utilisee_at' => 'datetime'];
    public function autorisateur() { return $this->belongsTo(User::class, 'autorise_par'); }
}
