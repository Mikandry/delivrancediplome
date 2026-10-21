<?php
return [
    'roles' => [
        'super_admin' => 'Super administrateur',
        'administrateur' => 'Administrateur',
        'saisisseur' => 'Saisisseur',
        'agent_validation' => 'Agent de validation',
        'secretaire' => 'Secrétaire',
        'agent_impression' => "Agent d'impression",
        'client' => 'Client / demandeur',
    ],
    'map' => [
        'super_admin' => ['*'],
        'administrateur' => ['candidats.view', 'candidats.edit', 'candidats.edit_locked', 'sessions.manage', 'sessions.verrouiller', 'sessions.deverrouiller',
            'imports.create', 'feries.manage', 'attestations.view', 'attestations.emettre', 'attestations.revoquer', 'registres.gerer', 'diplomes.view', 'diplomes.create', 'demandes.view', 'demandes.valider_liste', 'demandes.archiver', 'stats.view'],
        'saisisseur' => ['candidats.view', 'candidats.create', 'candidats.edit', 'registres.gerer', 'imports.create', 'diplomes.view', 'diplomes.create', 'demandes.view', 'demandes.corriger'],
        'agent_validation' => ['candidats.view', 'diplomes.view', 'demandes.view', 'demandes.verifier'],
        'secretaire' => ['attestations.view', 'attestations.emettre', 'candidats.view', 'diplomes.view', 'diplomes.signer', 'demandes.view', 'demandes.signer'],
        'agent_impression' => ['diplomes.view', 'diplomes.imprimer', 'demandes.view', 'demandes.remettre'],
        'client' => ['demandes.own'],
    ],
];
