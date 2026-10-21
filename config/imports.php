<?php
// Colonnes attendues dans les fichiers Excel/CSV. Synonymes comparés sans accents ni ponctuation.
// Les trois examens partagent ici la même structure : ajuster par examen dans 'par_examen' si les listes officielles diffèrent.
return [
    'colonnes' => [
        'nom' => ['nom', 'nom de famille'],
        'prenoms' => ['prenoms', 'prenom'],
        'date_naissance' => ['date de naissance', 'date naissance', 'ne le', 'datenaissance'],
        'lieu_naissance' => ['lieu de naissance', 'lieu naissance', 'a'],
        'sexe' => ['sexe', 'genre'],
        'numero_candidat' => ['numero candidat', 'n candidat', 'no candidat', 'numero de candidat'],
        'numero_inscription' => ['numero inscription', 'n inscription', 'no inscription', 'numero d inscription'],
        'centre_ecrit' => ['centre', 'centre ecrit', 'centre d ecrit'],
        'lycee_siegeant' => ['lycee', 'lycee siegeant', 'siegeant', 'lycee d accueil', 'commission siegeant a'],
        'mention' => ['mention'],
    ],
    'requis' => ['nom', 'prenoms', 'date_naissance', 'lieu_naissance', 'numero_inscription', 'centre_ecrit', 'lycee_siegeant'],
    'par_examen' => [
        // 'CAP.EP' => ['requis' => [...]],  // à renseigner si la structure officielle diffère
    ],
];
