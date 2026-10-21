<?php
// Paramètres métier. Les libellés BEPC sont ceux du diplôme officiel fourni.
// CAE.EP et CAP.EP : intitulés à CONFIRMER avec la Direction des examens avant production.
return [
    'service' => 'Direction des Examens et de la Certification',
    'ministre_titre' => "Le Ministre de l'Éducation Nationale",
    'lieu_signature' => 'Antananarivo',
    'directeur_nom' => env('DIPLOMES_DIRECTEUR_NOM', ''), // nom imprimé sous la signature
    'masquer_prenoms_verification' => false,
    'examens' => [
        'BEPC' => [
            'intitule' => "BREVET D'ÉTUDES DU PREMIER CYCLE DE L'ENSEIGNEMENT SECONDAIRE",
            'abrev_texte' => 'B.E.P.C.',
            'cycle' => "Commission d'Examen du Premier Cycle de l'Enseignement Secondaire",
            'delivre' => "le présent Brevet",
            'diplome_court' => "Brevet d'Études du Premier Cycle de l'Enseignement Secondaire",
            'arrete' => "n° 15 823-MINESEB du 26 décembre 2001",
            'prefixe' => 'T',
        ],
        'CAE.EP' => [
            'intitule' => 'CAE.EP — INTITULÉ OFFICIEL À CONFIRMER',
            'abrev_texte' => 'C.A.E.E.P.',
            'cycle' => "Commission d'Examen",
            'delivre' => 'le présent diplôme',
            'diplome_court' => 'CAE.EP',
            'arrete' => 'n° À RENSEIGNER',
            'prefixe' => 'E',
        ],
        'CAP.EP' => [
            'intitule' => 'CAP.EP — INTITULÉ OFFICIEL À CONFIRMER',
            'abrev_texte' => 'C.A.P.E.P.',
            'cycle' => "Commission d'Examen",
            'delivre' => 'le présent diplôme',
            'diplome_court' => 'CAP.EP',
            'arrete' => 'n° À RENSEIGNER',
            'prefixe' => 'P',
        ],
    ],
];
