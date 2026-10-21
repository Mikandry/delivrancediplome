<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DemandeHistorique extends Model
{
    protected $table = 'demande_historique';
    public $timestamps = false;
    protected $fillable = ['demande_id', 'user_id', 'de', 'vers', 'motif', 'visible_client'];
    protected $casts = ['created_at' => 'datetime', 'visible_client' => 'boolean'];
    public function user() { return $this->belongsTo(User::class); }
}
