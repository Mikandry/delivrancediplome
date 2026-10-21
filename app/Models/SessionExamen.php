<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SessionExamen extends Model
{
    protected $table = 'sessions_examens';
    protected $fillable = ['examen_id', 'annee', 'libelle', 'pv_date', 'pv_reference', 'arrete_reference', 'verrouillee', 'verrouillee_at', 'verrouillee_par'];
    protected $casts = ['pv_date' => 'date', 'verrouillee' => 'boolean', 'verrouillee_at' => 'datetime'];
    public function examen() { return $this->belongsTo(Examen::class); }
    public function candidats() { return $this->hasMany(Candidat::class, 'session_id'); }
}
