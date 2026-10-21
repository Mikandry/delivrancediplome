<?php
return [
    // L'attestation de réussite n'est émise que si le règlement l'autorise : désactivée par défaut.
    'reussite_autorisee' => env('DIPLOMES_ATTESTATION_REUSSITE', false),
    'validite_provisoire_mois' => 3,
    'types' => [
        'perte' => "Attestation de perte de diplôme",
        'provisoire' => "Attestation provisoire",
        'reussite' => "Attestation de réussite",
    ],
];
