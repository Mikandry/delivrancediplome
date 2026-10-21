<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RegistreNotes extends Model
{
    protected $table = 'registres_notes';
    protected $fillable = ['candidat_id', 'reference_demande', 'resultat_officiel', 'total_saisi', 'moyenne_saisie', 'observations', 'token_verification', 'saisi_par', 'date_etablissement'];
    protected $casts = ['date_etablissement' => 'date'];
    public function candidat() { return $this->belongsTo(Candidat::class); }
    public function lignes() { return $this->hasMany(LigneRegistreNotes::class, 'registre_id')->orderBy('ordre'); }
    public function saisisseur() { return $this->belongsTo(User::class, 'saisi_par'); }
}
