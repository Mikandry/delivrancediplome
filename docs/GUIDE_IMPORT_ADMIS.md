# Importation des listes des admis

1. Menu **Sessions** : créez la session (examen + année) et renseignez la date du PV de délivrance.
2. Menu **Import des admis** : téléchargez le modèle (Excel ou CSV). Colonnes : Nom, Prénoms, Date de naissance (JJ/MM/AAAA), Lieu de naissance, Sexe, N° candidat, N° inscription, Centre d'écrit, Lycée siégeant, Mention.
3. Choisissez la session, envoyez le fichier : le système **analyse sans rien enregistrer**.
4. Aperçu : lignes vertes OK ; rouges = erreurs (non importées) ; jaunes = doublons. Doublon exact (même n° d'inscription) ou répété dans le fichier : ignoré. Ressemblance (même nom + date de naissance) : cochez « importer quand même » seulement après vérification humaine.
5. **Confirmer l'import**. Un import ne peut être confirmé qu'une fois.
6. Quand la liste est contrôlée, l'administrateur clique **Valider et verrouiller** dans Sessions.

Limites : 10 Mo ; encodage CSV détecté automatiquement ; séparateur `;` ou `,`. Les cases « importer quand même » ne valent que pour la page affichée (50 lignes).
