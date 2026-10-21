<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Diplome extends Model
{
    use SoftDeletes;
    protected $fillable = ['candidat_id', 'numero_diplome', 'statut', 'date_signature', 'lieu_signature', 'signe_par', 'token_verification', 'nb_impressions', 'cree_par'];
    protected $casts = ['date_signature' => 'datetime'];

    public function candidat() { return $this->belongsTo(Candidat::class); }
    public function impressions() { return $this->hasMany(Impression::class); }
    public function autorisations() { return $this->hasMany(AutorisationReimpression::class); }

    public function autorisationDisponible(): ?AutorisationReimpression
    {
        return $this->autorisations()->whereNull('utilisee_at')->oldest()->first();
    }

    public function libelleStatut(): string
    {
        return ['brouillon' => 'Brouillon', 'valide_pour_impression' => 'Validé pour impression', 'imprime' => 'Imprimé',
            'delivre' => 'Délivré', 'revoque' => 'Révoqué', 'remplace' => 'Remplacé'][$this->statut] ?? $this->statut;
    }
}
