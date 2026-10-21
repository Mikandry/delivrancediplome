<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JourFerie extends Model
{
    protected $table = 'jours_feries';
    protected $fillable = ['jour', 'libelle'];
    protected $casts = ['jour' => 'date'];
}
