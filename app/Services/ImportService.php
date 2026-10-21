<?php
namespace App\Services;

use App\Models\{Candidat, Import, SessionExamen};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class ImportService
{
    private function cle(string $s): string { return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii(str_replace(['°', 'º', '˚'], '', $s))))); }

    public function colonnesRequises(string $codeExamen): array
    {
        return config("imports.par_examen.$codeExamen.requis", config('imports.requis'));
    }

    /** Lit le fichier, analyse chaque ligne et enregistre le résultat pour l'aperçu. Rien n'est écrit dans `candidats`. */
    public function analyser(Import $imp, string $cheminAbsolu): void
    {
        $session = SessionExamen::with('examen')->findOrFail($imp->session_id);
        $lignes = $this->lire($cheminAbsolu);
        if (count($lignes) < 2) throw new RuntimeException('Le fichier ne contient aucune ligne de données.');

        // Correspondance des en-têtes
        $map = [];
        foreach ($lignes[0] as $i => $titre) {
            $k = $this->cle((string) $titre);
            foreach (config('imports.colonnes') as $champ => $synonymes) {
                if ($k === $champ || $k === str_replace('_', ' ', $champ) || in_array($k, $synonymes, true)) { $map[$champ] ??= $i; }
            }
        }
        $manquantes = array_diff($this->colonnesRequises($session->examen->code), array_keys($map));
        if ($manquantes) throw new RuntimeException('Colonnes obligatoires introuvables : ' . implode(', ', $manquantes) . '. Utilisez le modèle téléchargeable.');

        $existants = Candidat::where('session_id', $session->id)->get(['numero_inscription', 'nom_normalise', 'date_naissance']);
        $numeros = $existants->pluck('numero_inscription')->flip();
        $noms = $existants->mapWithKeys(fn ($c) => [$c->nom_normalise . '|' . $c->date_naissance->format('Y-m-d') => true]);
        $vusFichier = [];
        $lot = []; $total = 0;

        foreach (array_slice($lignes, 1, null, true) as $idx => $row) {
            $donnees = [];
            foreach ($map as $champ => $col) $donnees[$champ] = isset($row[$col]) ? trim((string) $row[$col]) : '';
            if (! array_filter($donnees, fn ($v) => $v !== '')) continue;   // ligne vide

            $erreurs = [];
            $dn = $this->date($row[$map['date_naissance']] ?? null);
            if ($dn === null) { $erreurs[] = 'Date de naissance illisible (attendu JJ/MM/AAAA).'; }
            elseif ($dn >= now()->toDateString()) { $erreurs[] = 'Date de naissance dans le futur.'; }
            $donnees['date_naissance'] = $dn;
            if (isset($donnees['sexe']) && $donnees['sexe'] !== '') {
                $sx = Str::upper(Str::substr($donnees['sexe'], 0, 1));
                if (in_array($sx, ['M', 'F'], true)) { $donnees['sexe'] = $sx; }
                else { $erreurs[] = 'Sexe invalide (M ou F).'; $donnees['sexe'] = null; }
            } else { $donnees['sexe'] = null; }
            $v = Validator::make($donnees, [
                'nom' => 'required|max:120', 'prenoms' => 'required|max:200', 'lieu_naissance' => 'required|max:150',
                'numero_inscription' => 'required|max:40', 'centre_ecrit' => 'required|max:150', 'lycee_siegeant' => 'required|max:150',
                'numero_candidat' => 'nullable|max:40', 'mention' => 'nullable|max:40',
            ], [], ['nom' => 'nom', 'prenoms' => 'prénoms', 'lieu_naissance' => 'lieu de naissance', 'numero_inscription' => "n° d'inscription", 'centre_ecrit' => "centre d'écrit", 'lycee_siegeant' => 'lycée siégeant']);
            foreach ($v->errors()->all() as $m) $erreurs[] = $m;

            $doublon = null;
            $num = $donnees['numero_inscription'] ?? '';
            if ($num !== '' && isset($numeros[$num])) $doublon = 'exact';
            elseif ($num !== '' && isset($vusFichier[$num])) $doublon = 'fichier';
            else {
                $cle = Candidat::normaliser(($donnees['nom'] ?? '') . ' ' . ($donnees['prenoms'] ?? '')) . '|' . ($donnees['date_naissance'] ?? '');
                if (isset($noms[$cle]) || isset($vusFichier['n:' . $cle])) $doublon = 'ressemblance';
                $vusFichier['n:' . $cle] = true;
            }
            if ($num !== '') $vusFichier[$num] = true;

            $lot[] = ['import_id' => $imp->id, 'ligne' => $idx + 1, 'donnees' => json_encode($donnees, JSON_UNESCAPED_UNICODE),
                'erreurs' => $erreurs ? json_encode($erreurs, JSON_UNESCAPED_UNICODE) : null, 'doublon' => $doublon];
            $total++;
            if (count($lot) >= 500) { DB::table('import_lignes')->insert($lot); $lot = []; }
        }
        if ($lot) DB::table('import_lignes')->insert($lot);
        $imp->update(['total' => $total]);
    }

    /** Écrit dans `candidats` les lignes sans erreur ; les ressemblances seulement si cochées explicitement. */
    public function confirmer(Import $imp, array $forcerIds, ?int $userId = null): int
    {
        return DB::transaction(function () use ($imp, $forcerIds) {
            $session = SessionExamen::whereKey($imp->session_id)->lockForUpdate()->firstOrFail();
            if ($session->verrouillee) throw new RuntimeException('La session est verrouillée : importation impossible.');
            if ($imp->fresh()->statut !== 'apercu') throw new RuntimeException('Cet import a déjà été traité.');
            $n = 0;
            $imp->lignes()->whereNull('erreurs')->orderBy('id')->chunkById(500, function ($lignes) use ($session, $forcerIds, &$n) {
                foreach ($lignes as $l) {
                    if ($l->doublon === 'exact' || $l->doublon === 'fichier') continue;
                    if ($l->doublon === 'ressemblance' && ! in_array($l->id, $forcerIds)) continue;
                    $d = $l->donnees;
                    Candidat::create(array_merge(array_intersect_key($d, array_flip(['nom', 'prenoms', 'date_naissance', 'lieu_naissance', 'sexe', 'numero_candidat',
                        'numero_inscription', 'centre_ecrit', 'lycee_siegeant', 'mention'])), ['examen_id' => $session->examen_id, 'session_id' => $session->id]));
                    $n++;
                }
            });
            $imp->update(['statut' => 'termine', 'importes' => $n]);
            Audit::log('import.termine', $imp, null, null, ['importes' => $n, 'total' => $imp->total]);
            return $n;
        });
    }

    private function lire(string $chemin): array
    {
        $ext = strtolower(pathinfo($chemin, PATHINFO_EXTENSION));
        $reader = IOFactory::createReader(in_array($ext, ['csv', 'txt']) ? 'Csv' : ($ext === 'xls' ? 'Xls' : 'Xlsx'));
        if ($reader instanceof Csv) $reader->setInputEncoding(Csv::GUESS_ENCODING);
        $reader->setReadDataOnly(true);
        return $reader->load($chemin)->getActiveSheet()->toArray(null, false, false, false);
    }

    private function date(mixed $v): ?string
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return XlDate::excelToDateTimeObject((float) $v)->format('Y-m-d');
        $v = trim((string) $v);
        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d'] as $f) {
            $d = \DateTime::createFromFormat('!' . $f, $v);
            if ($d && $d->format($f) === $v) return $d->format('Y-m-d');
        }
        return null;
    }

    /** Modèle de référence téléchargeable (une ligne FICTIVE d'exemple). */
    public function modele(string $format): array
    {
        $titres = ['Nom', 'Prénoms', 'Date de naissance', 'Lieu de naissance', 'Sexe', 'N° candidat', 'N° inscription', 'Centre d\'écrit', 'Lycée siégeant', 'Mention'];
        $ex = ['EXEMPLE', 'Fictif Un', '02/12/2010', 'Toamasina', 'M', 'FICTIF-001', 'INS-0001', 'CEG Exemple', 'Lycée Exemple', ''];
        $x = new Spreadsheet();
        $sh = $x->getActiveSheet();
        $sh->fromArray($titres, null, 'A1')->fromArray($ex, null, 'A2');
        $sh->getStyle('A1:J1')->getFont()->setBold(true);
        $sh->getStyle('C2:C1000')->getNumberFormat()->setFormatCode('@');
        foreach (range('A', 'J') as $c) $sh->getColumnDimension($c)->setAutoSize(true);
        $tmp = tempnam(sys_get_temp_dir(), 'mod');
        if ($format === 'csv') { $w = new CsvWriter($x); $w->setDelimiter(';'); $w->setUseBOM(true); }
        else $w = new Xlsx($x);
        $w->save($tmp);
        return [$tmp, 'modele_liste_admis.' . $format];
    }
}
