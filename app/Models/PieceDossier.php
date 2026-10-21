<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PieceDossier extends Model
{
    protected $table = 'pieces_dossiers';
    protected $fillable = ['demande_id', 'type', 'nom_original', 'chemin', 'mime', 'taille', 'user_id'];
    public function demande() { return $this->belongsTo(Demande::class, 'demande_id'); }
    public function libelle(): string { return config('workflow.pieces')[$this->type]['label'] ?? $this->type; }
}
