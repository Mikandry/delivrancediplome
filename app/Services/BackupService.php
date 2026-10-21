<?php
namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class BackupService
{
    public function dossier(): string
    {
        $d = storage_path('app/private/sauvegardes');
        if (! is_dir($d)) mkdir($d, 0750, true);
        return $d;
    }

    private function cfg(): array
    {
        $c = config('database.connections.' . config('database.default'));
        if (! in_array($c['driver'], ['mysql', 'mariadb'])) throw new RuntimeException('Sauvegarde disponible uniquement pour MySQL/MariaDB.');
        return $c;
    }

    public function creer(): string
    {
        $c = $this->cfg();
        $p = new Process([env('DIPLOMES_MYSQLDUMP', 'mysqldump'), '--single-transaction', '--routines', '--triggers', '-h', $c['host'], '-P', (string) $c['port'], '-u', $c['username'], $c['database']],
            null, ['MYSQL_PWD' => (string) $c['password']]);   // mot de passe hors ligne de commande
        $p->setTimeout(600)->run();
        if (! $p->isSuccessful()) throw new RuntimeException('mysqldump a échoué : ' . trim($p->getErrorOutput()));
        $fichier = $this->dossier() . '/diplomes-' . now()->format('Ymd-His') . '.sql.gz';
        file_put_contents($fichier, gzencode($p->getOutput(), 9));
        chmod($fichier, 0640);
        Audit::log('sauvegarde.creee', null, basename($fichier));
        $this->purger();
        return $fichier;
    }

    public function restaurer(string $fichier): void
    {
        $c = $this->cfg();
        $sql = gzdecode((string) file_get_contents($fichier));
        if ($sql === false || $sql === '') throw new RuntimeException('Fichier de sauvegarde illisible.');
        $p = new Process([env('DIPLOMES_MYSQL', 'mysql'), '-h', $c['host'], '-P', (string) $c['port'], '-u', $c['username'], $c['database']], null, ['MYSQL_PWD' => (string) $c['password']]);
        $p->setInput($sql)->setTimeout(900)->run();
        if (! $p->isSuccessful()) throw new RuntimeException('Restauration échouée : ' . trim($p->getErrorOutput()));
    }

    public function liste(): array
    {
        $out = [];
        foreach (glob($this->dossier() . '/diplomes-*.sql.gz') ?: [] as $f) $out[] = ['nom' => basename($f), 'taille' => filesize($f), 'date' => filemtime($f)];
        usort($out, fn ($a, $b) => $b['date'] <=> $a['date']);
        return $out;
    }

    private function purger(int $garder = 30): void
    {
        foreach (array_slice($this->liste(), $garder) as $old) @unlink($this->dossier() . '/' . $old['nom']);
    }
}
