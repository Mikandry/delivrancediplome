<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LigneRegistreNotes extends Model
{
    protected $table = 'lignes_registres_notes';
    public $timestamps = false;
    protected $fillable = ['registre_id', 'ordre', 'matiere', 'coefficient', 'note'];
}
