<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JournalAudit extends Model
{
    protected $table = 'journaux_audit';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['anciennes' => 'array', 'nouvelles' => 'array', 'created_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}
