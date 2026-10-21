<?php
// Circuit de traitement des demandes. Chaque transition : [permission requise, motif obligatoire, libellé du bouton].
return [
    'delai_jours_ouvres' => 5,
    // 'recalcul' : 5 nouveaux jours ouvrés quand le dossier revient de correction ; 'ignorer' : la date initiale est conservée
    'retour_correction' => 'recalcul',
    'service_recepteur' => env('DIPLOMES_SERVICE_RECEPTEUR', 'Guichet de la Direction des Examens et de la Certification'),

    'pieces' => [
        'cni' => ['label' => "Pièce d'identité (CIN, passeport…)", 'obligatoire' => true],
        'demande' => ['label' => 'Demande écrite de délivrance', 'obligatoire' => true],
        'autre' => ['label' => 'Autre justificatif', 'obligatoire' => false],
    ],

    'statuts' => [
        'recue' => 'Demande reçue',
        'attente_pieces' => 'En attente de pièces',
        'en_verification' => 'En cours de vérification',
        'attente_correction' => 'En attente de correction',
        'en_correction' => 'En cours de correction',
        'attente_validation_admin' => 'En attente de validation administrative',
        'attente_signature' => 'En attente de signature',
        'valide_impression' => 'Validé pour impression',
        'imprime' => 'Imprimé',
        'disponible_retrait' => 'Disponible pour retrait',
        'delivre' => 'Délivré',
        'rejete' => 'Rejeté',
        'archive' => 'Archivé',
    ],

    // Étapes affichées au client (ordre d'avancement)
    'etapes_client' => ['recue', 'en_verification', 'attente_signature', 'valide_impression', 'imprime', 'disponible_retrait', 'delivre'],
    // Les statuts de correction sont internes : le client les voit comme « en cours de vérification »
    'regroupement_client' => ['attente_correction' => 'en_verification', 'en_correction' => 'en_verification', 'attente_validation_admin' => 'en_verification', 'attente_pieces' => 'recue'],
    'termines' => ['delivre', 'rejete', 'archive'],

    'transitions' => [
        'recue' => [
            'en_verification' => ['demandes.verifier', false, 'Commencer la vérification'],
            'attente_pieces' => ['demandes.verifier', true, 'Demander des pièces'],
            'rejete' => ['demandes.verifier', true, 'Rejeter la demande'],
        ],
        'attente_pieces' => ['rejete' => ['demandes.verifier', true, 'Rejeter la demande']],
        'en_verification' => [
            'attente_correction' => ['demandes.verifier', true, 'Retourner au saisisseur'],
            'attente_signature' => ['demandes.verifier', false, 'Valider et transmettre au secrétaire'],
            'rejete' => ['demandes.verifier', true, 'Rejeter la demande'],
        ],
        'attente_correction' => ['en_correction' => ['demandes.corriger', false, 'Prendre en charge la correction']],
        'en_correction' => ['attente_validation_admin' => ['demandes.corriger', false, 'Soumettre pour validation administrative']],
        'attente_validation_admin' => [
            'en_verification' => ['demandes.valider_liste', false, 'Valider la liste et reverrouiller'],
            'attente_correction' => ['demandes.valider_liste', true, 'Renvoyer en correction'],
        ],
        'attente_signature' => [
            'valide_impression' => ['demandes.signer', false, 'Valider définitivement (signature)'],
            'attente_correction' => ['demandes.signer', true, 'Retourner au saisisseur'],
        ],
        'imprime' => ['disponible_retrait' => ['demandes.remettre', false, 'Marquer disponible pour retrait']],
        'disponible_retrait' => ['delivre' => ['demandes.remettre', false, 'Enregistrer la remise au demandeur']],
        'delivre' => ['archive' => ['demandes.archiver', false, 'Archiver le dossier']],
        'rejete' => ['archive' => ['demandes.archiver', false, 'Archiver le dossier']],
        // valide_impression -> imprime : automatique, déclenché par la première impression du diplôme
    ],
];
