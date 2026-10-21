<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class NotificationInterne extends Model
{
    protected $table = 'notifications_internes';
    public $timestamps = false;
    protected $fillable = ['user_id', 'titre', 'message', 'lien', 'lu_at'];
    protected $casts = ['created_at' => 'datetime', 'lu_at' => 'datetime'];
}
