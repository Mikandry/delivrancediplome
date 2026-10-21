# Guide d'utilisation par rôle

| Rôle | Menu principal | Actions clés |
|---|---|---|
| **Super administrateur** | tout | Comptes (`Utilisateurs`), organismes habilités, sauvegardes, journal d'audit, **autorisation de réimpression** (page du diplôme), déverrouillage exceptionnel d'une session, tous les droits de l'administrateur |
| **Administrateur** | Sessions, Import, Candidats, Demandes, Statistiques, Attestations, Registres | Créer une session et renseigner la date du PV ; **valider et verrouiller** la liste ; déverrouiller avec motif ; valider la liste dans un dossier (« Valider la liste et reverrouiller ») ; jours fériés ; archiver ; révoquer une attestation |
| **Saisisseur** | Import, Candidats, Demandes, Registres | Importer les admis (aperçu puis confirmation) ; saisir/corriger un candidat **avec motif** ; prendre en charge un dossier en correction puis le soumettre à l'administrateur ; saisir un registre de notes. Ne peut rien modifier sur une liste verrouillée |
| **Agent de validation** | Demandes | Commencer la vérification (dossier complet requis) ; associer la demande au candidat de la liste ; retourner au saisisseur (motif obligatoire) ; valider et transmettre au secrétaire (liste verrouillée requise) ; rejeter |
| **Secrétaire** | Demandes, Diplômes, Attestations | Contrôle final ; **valider définitivement** (date de signature automatique) ou retourner au saisisseur ; émettre les attestations |
| **Agent d'impression** | Diplômes, Demandes | Imprimer **une seule fois** un diplôme validé ; marquer disponible pour retrait ; enregistrer la remise. Aucune modification de données |
| **Client** | Mes demandes | Créer un compte, déposer une demande et ses pièces, suivre l'état, ajouter des pièces manquantes, télécharger la quittance |

## Circuit d'un dossier
Demande reçue → (pièces complètes) → vérification par l'agent → [retour correction → saisisseur → administrateur → agent] → attente de signature → validation du secrétaire → impression → disponible → délivré → archivé.

## Règles à retenir
- Un diplôme **ne s'imprime qu'une fois**. Une réimpression exige l'autorisation du super administrateur (motif, usage unique).
- « Non retrouvé dans la base » ne signifie **pas** « non admis ».
- Les attestations de réussite sont désactivées tant que le règlement ne les autorise pas (`DIPLOMES_ATTESTATION_REUSSITE=true`).
