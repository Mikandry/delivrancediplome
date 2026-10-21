<?php
namespace App\Services;

use App\Models\JournalAudit;

class Audit
{
    public static function log(string $action, ?object $entite = null, ?string $motif = null, ?array $anciennes = null, ?array $nouvelles = null): void
    {
        JournalAudit::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entite' => $entite ? class_basename($entite) : null,
            'entite_id' => $entite->id ?? null,
            'motif' => $motif,
            'anciennes' => $anciennes,
            'nouvelles' => $nouvelles,
            'ip' => request()?->ip(),
        ]);
    }
}
