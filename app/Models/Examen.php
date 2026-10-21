<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Examen extends Model
{
    protected $fillable = ['code', 'intitule', 'actif'];
    public function sessions() { return $this->hasMany(SessionExamen::class, 'examen_id'); }
}
