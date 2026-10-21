<?php
namespace App\Http\Controllers;

use App\Models\{Attestation, Diplome, RegistreNotes};

class VerificationController extends Controller
{
    /** Page publique : seules les informations d'authentification sont affichées, jamais les notes ni les pièces. */
    public function show(string $token)
    {
        $type = null; $doc = null;
        if (strlen($token) === 48 && ctype_xdigit($token)) {
            if ($d = Diplome::with('candidat.examen', 'candidat.session')->where('token_verification', $token)->first()) {
                if ($d->statut !== 'brouillon') { $type = 'diplome'; $doc = $d; }   // un brouillon n'est pas un document officiel
            } elseif ($a = Attestation::with('candidat.examen', 'candidat.session')->where('token_verification', $token)->first()) {
                $type = 'attestation'; $doc = $a;
            } elseif ($rg = RegistreNotes::with('candidat.examen', 'candidat.session')->where('token_verification', $token)->first()) {
                $type = 'registre'; $doc = $rg;
            }
        }
        return view('public.verification', ['type' => $type, 'doc' => $doc]);
    }
}
