<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Attestation extends Model
{
    protected $fillable = ['numero', 'type', 'candidat_id', 'diplome_id', 'statut', 'reference', 'token_verification', 'valide_jusqu', 'emise_par', 'emise_at', 'motif_revocation'];
    protected $casts = ['valide_jusqu' => 'date', 'emise_at' => 'datetime'];
    public function candidat() { return $this->belongsTo(Candidat::class); }
    public function emetteur() { return $this->belongsTo(User::class, 'emise_par'); }
    public function libelleType(): string { return config('attestations.types')[$this->type] ?? $this->type; }
}
