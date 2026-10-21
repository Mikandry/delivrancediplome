<?php
namespace App\Http\Controllers;

use App\Models\JournalAudit;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $r)
    {
        $q = JournalAudit::with('user')->latest('id');
        if ($r->filled('action')) $q->where('action', 'like', $r->action . '%');
        if ($r->filled('user_id')) $q->where('user_id', $r->user_id);
        if ($r->filled('du')) $q->whereDate('created_at', '>=', $r->du);
        if ($r->filled('au')) $q->whereDate('created_at', '<=', $r->au);
        return view('audit.index', ['logs' => $q->paginate(40)->withQueryString()]);
    }
}
