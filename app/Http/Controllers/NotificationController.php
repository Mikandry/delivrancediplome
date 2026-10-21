<?php
namespace App\Http\Controllers;

use App\Models\NotificationInterne;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $r)
    {
        $n = NotificationInterne::where('user_id', $r->user()->id)->latest('id')->paginate(20);
        NotificationInterne::where('user_id', $r->user()->id)->whereNull('lu_at')->update(['lu_at' => now()]);
        return view('notifications.index', ['notifs' => $n]);
    }
}
