<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ImportLigne extends Model
{
    protected $table = 'import_lignes';
    public $timestamps = false;
    protected $fillable = ['import_id', 'ligne', 'donnees', 'erreurs', 'doublon'];
    protected $casts = ['donnees' => 'array', 'erreurs' => 'array'];
}
