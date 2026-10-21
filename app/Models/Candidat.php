<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Candidat extends Model
{
    use SoftDeletes;

    protected $fillable = ['examen_id', 'session_id', 'nom', 'prenoms', 'date_naissance', 'lieu_naissance', 'sexe', 'nationalite',
        'numero_candidat', 'numero_inscription', 'centre_ecrit', 'lycee_siegeant', 'mention'];
    protected $casts = ['date_naissance' => 'date'];

    protected static function booted(): void
    {
        static::saving(function (Candidat $c) {
            $c->nom_normalise = self::normaliser($c->nom . ' ' . $c->prenoms);
        });
    }

    public static function normaliser(string $s): string
    {
        return preg_replace('/\s+/', ' ', trim(Str::lower(Str::ascii($s))));
    }

    public function examen() { return $this->belongsTo(Examen::class); }
    public function session() { return $this->belongsTo(SessionExamen::class, 'session_id'); }
    public function diplome() { return $this->hasOne(Diplome::class); }

    /** Données figées : session verrouillée ou diplôme déjà signé. */
    public function estVerrouille(): bool
    {
        return $this->session->verrouillee || ($this->diplome && $this->diplome->statut !== 'brouillon');
    }

    public function nomComplet(): string { return mb_strtoupper($this->nom) . ' ' . $this->prenoms; }
}
