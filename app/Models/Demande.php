<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Demande extends Model
{
    use SoftDeletes;
    protected $table = 'demandes_diplomes';
    protected $fillable = ['numero', 'code_suivi', 'user_id', 'examen_id', 'session_id', 'candidat_id', 'diplome_id', 'nom', 'prenoms', 'date_naissance',
        'lieu_naissance', 'numero_inscription', 'telephone', 'statut', 'recu_at', 'complet_at', 'date_prevue', 'delivre_at', 'service_recepteur'];
    protected $casts = ['date_naissance' => 'date', 'recu_at' => 'datetime', 'complet_at' => 'datetime', 'date_prevue' => 'date', 'delivre_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function examen() { return $this->belongsTo(Examen::class); }
    public function session() { return $this->belongsTo(SessionExamen::class, 'session_id'); }
    public function candidat() { return $this->belongsTo(Candidat::class); }
    public function diplome() { return $this->belongsTo(Diplome::class); }
    public function pieces() { return $this->hasMany(PieceDossier::class, 'demande_id'); }
    public function historique() { return $this->hasMany(DemandeHistorique::class, 'demande_id')->orderBy('id'); }

    public function libelleStatut(): string { return config('workflow.statuts')[$this->statut] ?? $this->statut; }
    public function codeSuiviClair(): string { return Crypt::decryptString($this->code_suivi); }
    public function nomComplet(): string { return mb_strtoupper($this->nom) . ' ' . $this->prenoms; }
    public function estTerminee(): bool { return in_array($this->statut, config('workflow.termines'), true); }
    public function enRetard(): bool { return $this->date_prevue && ! $this->estTerminee() && $this->date_prevue->lt(today()) && ! in_array($this->statut, ['delivre', 'disponible_retrait', 'imprime']); }

    /** Types de pièces obligatoires qui manquent encore. */
    public function piecesManquantes(): array
    {
        $presentes = $this->pieces()->pluck('type')->unique()->all();
        $manque = [];
        foreach (config('workflow.pieces') as $type => $def) {
            if ($def['obligatoire'] && ! in_array($type, $presentes, true)) $manque[$type] = $def['label'];
        }
        return $manque;
    }
    public function estComplete(): bool { return $this->piecesManquantes() === []; }
}
