# Correspondances entre référentiels

Le module `/crosswalk` gère des relations documentaires génériques entre deux contrôles. Il ne contient aucune règle propre à ISO, NIS2 ou à un autre référentiel.

## Modèle de données

La table `frameworks` complète les codes déjà présents dans `Domain.framework` avec des métadonnées. Elle ne remplace pas ce champ et ne modifie pas sa sémantique. La migration initialise une ligne de métadonnées pour chaque code existant. La sauvegarde ultérieure d'un domaine initialise également les métadonnées minimales de son code.

Une ligne de `control_mappings` est enregistrée une seule fois de `source_control_id` vers `target_control_id`. L'application lit aussi cette relation dans le sens inverse. Dans ce cas, `covers` devient `covered_by`, et réciproquement. Aucun enregistrement miroir n'est créé.

Les types autorisés sont `equivalent`, `covers`, `covered_by`, `partial`, `supports` et `related`. Les couvertures autorisées sont `full`, `high`, `medium`, `low` et `none`.

## Droits et écrans

Tous les utilisateurs authentifiés peuvent consulter la liste, le détail, la matrice et les exports. Seuls les administrateurs (`role=1`) peuvent créer, modifier, valider ou supprimer une relation.

La matrice présente des métriques documentaires : nombre de contrôles source, nombre de contrôles ayant une relation, nombre de contrôles sans relation et nombre de relations. Ces valeurs ne sont jamais des scores de conformité.

## Import XLSX

Commande :

```console
php artisan deming:import-mappings fichier.xlsx
```

Options :

- `--dry-run` valide tout le fichier sans écriture ;
- `--update` autorise la mise à jour d'une paire source/cible existante.

Les colonnes obligatoires sont :

```text
source_framework, source_clause, target_framework, target_clause,
mapping_type, coverage, rationale, source_reference, source_url
```

Toutes les lignes sont validées avant la première écriture. Les écritures sont ensuite exécutées dans une transaction unique. Une erreur empêche donc tout import partiel. Une relation modifiée par import doit être validée à nouveau.

## Premier jeu ReCyF 2.5 vers ISO 2700X

`storage/app/repository/ReCyF-2.5-ISO27001-2022.mappings.xlsx` contient 281 relations issues du comparateur officiel ANSSI. Elles couvrent 118 contrôles ReCyF et 66 contrôles ISO présents dans `ISO27001-2022.fr.xlsx`.

Le jeu est volontairement conservateur :

- seules les références publiées par l'ANSSI et retrouvées exactement dans le classeur ISO de Deming sont présentes ;
- chaque relation est de type `related`, car une correspondance documentaire ne permet pas de conclure à une équivalence ou à une implication de conformité ;
- les niveaux ANSSI `ÉLEVÉE`, `MOYENNE` et `FAIBLE/NULLE` sont convertis respectivement en `high`, `medium` et `low`, tandis que le libellé original reste dans la justification ;
- l'observation ANSSI, la référence ISO d'origine, la clause Deming normalisée et l'URL de la source sont conservées ;
- toutes les relations importées restent non validées jusqu'à une revue humaine dans Deming.

Sources : [comparateur ANSSI](https://messervices.cyber.gouv.fr/nis2#exigences) et [présentation du ReCyF et du comparateur](https://lab.cyber.gouv.fr/les-actualit%C3%A9s-du-lab-anssi/recyf--publication-du-r%C3%A9f%C3%A9rentiel-dexigences-et-du-comparateur/).
