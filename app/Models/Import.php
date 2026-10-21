<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Import extends Model
{
    protected $table = 'imports';
    protected $fillable = ['session_id', 'user_id', 'fichier_original', 'chemin', 'statut', 'total', 'importes'];
    public function session() { return $this->belongsTo(SessionExamen::class, 'session_id'); }
    public function lignes() { return $this->hasMany(ImportLigne::class); }
    public function user() { return $this->belongsTo(User::class); }
}
