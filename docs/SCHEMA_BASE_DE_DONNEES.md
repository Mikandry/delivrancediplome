# Schéma de la base (relations principales)

- `users` (role, actif, must_change_password)
- `examens` 1—N `sessions_examens` (verrouillee, pv_date, pv_reference)
- `sessions_examens` 1—N `candidats` (nom_normalise indexé ; unique session+n° d'inscription)
- `candidats` 1—1 `diplomes` (numero_diplome unique, token_verification, date_signature, nb_impressions)
- `diplomes` 1—N `impressions` (unique diplome+rang) ; 1—N `autorisations_reimpression`
- `candidats` 1—N `attestations` (type, statut valide/revoque/remplace, token) ; 1—N `registres_notes` 1—N `lignes_registres_notes`
- `demandes_diplomes` (user client, examen, session, candidat?, diplome?) 1—N `pieces_dossiers`, 1—N `demande_historique`
- `imports` 1—N `import_lignes` ; `jours_feries` ; `notifications_internes` ; `organismes_verification` (cle_hash)
- `journaux_audit` : insertion seule (triggers anti-UPDATE/DELETE)

Garde-fous en base : triggers `impressions_garde` (pas de réimpression sans autorisation), `diplomes_fige` (date de signature et données officielles figées), `audit_no_update/delete`. Clés étrangères en RESTRICT, suppressions logiques sur candidats, diplômes et demandes.

Écart avec le cahier des charges : pas de tables `roles`, `permissions`, `user_roles` (droits dans `config/permissions.php`) ; `validations`/`corrections` sont couvertes par `demande_historique` et `journaux_audit` ; `quittances` sont générées à la volée depuis la demande.
