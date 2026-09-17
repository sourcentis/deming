<?php
return [
    'welcome' => [
        'dashboard' => 'Tableau de bord',
        'domains' => 'Domaines',
        'measures' => 'Mesures de sécurité',
        'controls' => 'Contrôles',
        'action_plans' => 'Plans d\'action',
        'next_controls' => 'Contrôles planifiés les 30 prochains jours',
        'control_status' => 'État des contrôles',
        'control_planning' => 'Planning des contrôles',
    ],
    'action' => [
        'index' => 'Liste des plans d\'action',
        'show' => 'Plan d\'action',
        'create' => 'Créer un plan d\'action',
        'edit' => 'Modifier un plan d\'action',
        'close' => 'Clôturer une action',
        'title' => 'Actions',
        'fields' => [
            'clause' => 'Clause',
            'name' => 'Nom',
            'scope' => 'Périmètre',
            'action' => 'Plan d\'action',
            'plan_date' => 'Date de planification',
            'next_date' => 'Date de revue',
            'note' => 'Score',
            'objective' => 'Objectif',
            'observation' => 'Observation',
            'action_plan' => 'Plan d\'actions',
            'reference' => 'Ref',
            'type' => 'Type',
            'due_date' => 'Date d\'échéance',
            'choose_type' => 'Choisir un type',
            'choose_scope' => 'Choisir un périmètre',
            'remediation' => 'Remédiation',
            'justification' => 'Justification',
            'criticity' => 'Criticité',
            'cause' => 'Cause',
            'clauses' => 'Clauses',
            'owners' => 'Responsables',
            'status_open' => 'Ouvert',
            'status_closed' => 'Fermé',
            'status_rejected' => 'Rejeté',
            'status_all' => 'Tous',
            'close_date' => 'Date de clôture',
            'progress' => 'État d\'avancement',
        ],
        'types' => [
            'major' => 'Majeure',
            'minor' => 'Mineure',
            'opportunity' => 'Opportunité',
            'observation' => 'Observation'
        ],
    ],
    'attribute' => [
        'fields' => [
            'name' => 'Nom',
            'values' => 'Valeurs',
        ],
        'add' => 'Ajouter un attribut',
        'edit' => 'Modifier un attribut',
        'show' => 'Attribut',
        'index' => 'Liste des attributs',
        'choose' => 'Choisir un attribut',
        'title' => 'Attribut',
        'replace' => [
            'title' => 'Gérer les attributs',
            'old_value' => 'Valeur existante',
            'new_value' => 'Nouvelle valeur',
            'success' => 'La valeur ":old" a été remplacée par ":new" dans toutes les mesures, contrôles et attributs.',
        ],
    ],
    'measure' => [
        'description' => '',
        'fields' => [
            'action_plan' => 'Plan d\'actions',
            'attributes' => 'Attributs',
            'input' => 'Données',
            'choose_domain' => 'Choisir un domaine',
            'choose_scope' => 'Choisir un périmètre',
            'choose_period' => 'Choisir une periode',
            'choose_attribute' => 'Choisir un attribut',
            'choose_clause' => 'Choisir une clause',
            'clause' => 'Clause',
            'clauses' => 'Clauses',
            'domain' => 'Domaine',
            'indicator' => 'Fonction',
            'measure' => 'Mesure',
            'model' => 'Modèle',
            'name' => 'Nom',
            'next' => 'Suivant',
            'note' => 'Note',
            'objective' => 'Objectif',
            'observations' => 'Observations',
            'plan_date' => 'Date de planification',
            'period' => 'Période',
            'periodicity' => 'Périodicité',
            'planned' => 'Planifié',
            'realisation_date' => 'Date de réalisation',
            'realized' => 'Réalisé',
            'evidence' => 'Preuves',
            'scope' => 'Périmètre',
            'score' => 'Score',
            'status' => 'Etat',
            'status_done' => 'Fait',
            'status_todo' => 'A faire',
            'status_all' => 'Tous',
            'owners' => 'Responsables',
            'groups' => 'Groupes'
        ],
        'error' => [
            'made' => 'Ce contrôle a déjà été réalisé.',
            'duplicate' => 'Ce contrôle existe déjà.',
        ],
        'checklist' => 'Fiche de contrôle',
        'create' => 'Créer un contrôle',
        'list' => 'Liste des contrôles',
        'edit' => 'Modifier un contrôle de sécurité',
        'history' => 'Planning',
        'make' => 'Réaliser un contrôle',
        'plan' => 'Planifier un contrôle',
        'radar' => 'État des contrôles de sécurité',
        'status_date' => 'État des contrôles au',
        'title' => 'Contrôles',
        'title_singular' => 'Contrôle',
        'groupBy' => 'Groupé par',
        'create_action' => 'Créer un plan d\'action',
        'calendar' => 'Calendrier',
        'confirm_delete' => 'Etes vous sur de vouloir supprimer des contrôles ?'
    ],
    'control' => [
        'title' => 'Mesure',
        'fields' => [
            'domain' => 'Domaine',
            'clause' => 'Clause',
            'name' => 'Nom',
            'objective' => 'Objectif',
            'input' => 'Données',
            'attributes' => 'Attributs',
            'model' => 'Modèle',
            'indicator' => 'Indicateur',
            'action_plan' => 'Plan d\'action',
            'periodicity' => 'Périodicité',
        ],
        'show' => 'Mesure de sécurité',
        'index' => 'Liste des mesures de sécurité',
        'create' => 'Ajouter une mesure de sécurité',
        'edit' => 'Modifier une mesure de sécurité',
        'plan' => 'Planifier un contrôle'
    ],
    'notification' => [
        'subject' => 'Liste des contrôles à réaliser',
    ],
    'domain' => [
        'fields' => [
            'framework' => 'Référentiel',
            'name' => 'Nom',
            'description' => 'Description',
            'measures' => '# Mesures',
        ],
        'add' => 'Ajouter un domaine',
        'edit' => 'Modifier un domaine',
        'show' => 'Domaine',
        'index' => 'Liste des domaines',
        'choose' => 'Choisir un domaine',
        'title' => 'Domaine',
        'radar' => 'Résultat des contrôles par domaine',
        'measure_date' => 'État des contrôles au',
    ],
    'document' => [
        'title' => [
            'storage' => 'Entrepôt de documents',
            'templates' => 'Modèles de documents',
            'cleanup' => 'Durée de conservation',
            'cleanup_detail' => 'Nombre de mois avant suppression automatique des contrôles et des documents',
        ],
        'month' => 'Mois',
        'never' => 'jamais',
        'description' => 'Description',
        'list' => 'Liste des documents',
        'index' => 'Documents',
        'fields' => [
            'name' => 'Nom',
            'control' => 'Contrôle',
            'size' => 'Taille',
            'hash' => 'Hash',
            'links' => 'Liens',
            'status' => 'Vérification',
        ],
        'model' => [
            'control' => 'Modèle de fiche de contrôle',
            'report' => 'Modèle du rapport de pilotage',
            'custom' => 'Modèle modifié',
        ],
        'count' => 'Nombre de documents',
        'total_size' => 'Taille totale',
    ],
    'exports' => [
        'index' => 'Exporter des données',
        'start' => 'Début',
        'end' => 'Fin',
        'report_title' => 'Rapport',
        'steering' => 'Rapport de pilotage du SMSI',
        'users_export' => 'Exportation des utilisateurs',
        'data_export_title' => 'Exportation des données',
        'domains_export'=> 'Exportation des domaines',
        'attributes_export' => 'Exportation des attributs',
        'measures_export' => 'Exportation des contrôles',
        'controls_export' => 'Exportation des mesures de sécurité',
        'actions_export' => 'Exporter les plans d\'action',
        'risks_export' => 'Exporter le registre des risques',
    ],
    'group' => [
        'index' => 'Liste des groupes',
        'add' => 'Ajouter un groupe d\'utilisateurs',
        'edit' => 'Modifier un groupe d\'utilisateurs',
        'show' => 'Afficher un groupe d\'utilisateurs',
        'fields' => [
            'name' => 'Nom',
            'description' => 'Description',
            'users' => 'Utilisateurs',
            'controls' => 'Contrôles',
        ],
    ],
    'imports' => [
        'index' => 'Importer',
        'title' => 'Importer des mesures de sécurité',
        'current' => 'Mesures de sécurité actuelles',
        'or' => 'ou',
        'remove_all' => 'Supprimer toutes les autres mesures et contrôles',
        'fake' => 'Générer de faux contrôles',
        'format' => 'Le format d\'importation est un document XLSX avec les colonnes',
        'framework' => 'Framework',
        'framework_helper' => 'Cadre de sécurité utilisé',
        'domain' => 'Nom du domaine',
        'domain_helper' => 'Le nom de domaine, il est créé s\'il n\'existe pas',
        'domain_description' => 'Description',
        'domain_description_helper' => 'Description du domaine',
        'clause' => 'Clause',
        'clause_helper' => 'Si la clause existe, la mesure de sécurité est mise à jour. <br>Si la clause n\'existe pas, une nouvelle mesure de sécurité est créée. <br>Si tous les autres champs de la ligne sont vides, la mesure, les contrôles et les documents associés sont supprimés.',
        'name' => 'Nom',
        'name_helper' => 'Nom de la mesure de sécurité',
        'description' => 'Description',
        'description_helper' => 'Description de la mesure de sécurité',
        'attributes' => 'Attributs',
        'attributes_helper' => 'Liste des attributs (#... #... #...)',
        'input' => 'Entrée',
        'input_helper' => 'Éléments d\'entrée',
        'model' => 'Modèle',
        'model_helper' => 'Modèle de calcul',
        'indicator' => 'Indicateur',
        'indicator_helper' => 'Vert si ..., Orange si ..., Rouge si ...',
        'action' => 'Plan d\'action',
        'action_helper' => 'Le plan d\'action proposé',
        'warning' => 'Cette action est irréversible. Veuillez effectuer une sauvegarde avant !'
    ],
    'log' => [
        'index' => 'Liste des logs',
        'title' => 'Log',
        'history' => 'Historique des changements',
        'action' => 'Action',
        'subject_type' => 'Type',
        'subject_id' => 'ID',
        'user' => 'Utilisateur',
        'host' => 'Hôte',
        'timestamp' => 'Horodatage',
        'properties' => 'Données'
    ],
    'login' => [
        'title' => 'Entrez un mot de passe',
        'identification' => 'Identification',
        'connection' => 'Connexion',
        'connection_with' => 'Connexion avec',
        'error' => [
            'user_not_exist' => 'L\'utilisateur n\'existe pas',
            'account_disabled' => 'Ce compte a été désactivé.',
        ],
    ],
    'report' => [
        'action_plan' => [
            'id' => '#',
            'title' => 'Plan d\'action',
            'next' => 'Date de revue'
        ]
    ],
    'soa' => [
        'title' => 'Déclaration d\'applicabilité',
        'generate' => 'Générer le rapport'
    ],
    'user' => [
        'show' => 'Afficher un utilisateur',
        'index' => 'Liste des utilisateurs',
        'edit' => 'Modifier un utilisateur',
        'add' => 'Ajouter un utilisateur',
        'fields' => [
            'login' => 'Login',
            'name' => 'Nom',
            'title' => 'Titre',
            'role' => 'Rôle',
            'password' => 'Mot de passe',
            'email' => 'eMail',
            'groups' => 'Groupes',
            'language' => 'Langue',
            'controls' => 'Contrôles de l\'utilisateur'
        ],
        'roles' => [
            'admin' => 'Administrateur',
            'user' => 'Utilisateur',
            'auditor' => 'Auditeur',
            'api' => 'API',
            'auditee' => 'Audité',
            'disabled' => 'Désactivé',
        ],
    ],
    'config' => [
        'notifications' => [
            'title' => 'Configuration de l\'envoi de notifications',
            'title_short' => 'Notifications',
            'help' => 'Cet écran permet de configurer les notifications envoyées par mail aux utilisateurs.',
            'message_subject' => 'Sujet du message',
            'message_content' => 'Contenu du message',
            'message_default_content' => '<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<p>Voici la liste des contr&ocirc;les d&ucirc; prochainement :</p>
%table%
<p>Ceci est un mail g&eacute;n&eacute;r&eacute; automatiquement.</p>
<p>Cordialement,<br>Deming</p>
</body>
</html>',
            'sent_from' => 'Envoyé depuis',
            'to' => 'à',
            'delay' => 'Pour les contrôles qui arrivent à échéance dans',
            'recurrence' => 'Envoyer une notification',
            'duration' => [
                'day' => 'jour',
                'days' => 'jours',
                'month' => 'mois',
                'months' => 'mois',
            ],
        ]
    ],

    // -------------------------------------------------------------------------
    // Registre des risques
    // -------------------------------------------------------------------------
    'risk' => [

        // Titres de page
        'list'            => 'Liste des risques',
        'create'          => 'Nouveau risque',
        'edit'            => 'Modifier le risque',
        'matrix'          => 'Matrice des risques',
        'singular'        => 'Risque',
        'plural'          => 'Risques',
        'export'          => 'Risques',

        // Niveaux de risque (affichés dans les badges et compteurs)
        'levels' => [
            'low'      => 'Faible',
            'medium'   => 'Moyen',
            'high'     => 'Élevé',
            'critical' => 'Critique',
        ],

        // Champs du formulaire et de la liste
        'fields' => [
            'name'                => 'Nom',
            'description'         => 'Description',
            'owner'               => 'Propriétaire',
            'no_owner'            => 'Non assigné',
            'choose_owner'        => 'Choisir un propriétaire',
            'choose_status'       => 'Choisir un statut',
            'choose_level'        => 'Choisir un niveau',

            // Évaluation
            'probability'         => 'Probabilité',
            'threat'              => 'Menace',
            'probability_comment' => 'Commentaire probabilité',
            'impact'              => 'Impact',
            'impact_comment'      => 'Commentaire impact',
            'exposure'            => 'Exposition',
            'vulnerability'       => 'Vulnérabilité',
            'likelihood'          => 'Vraisemblance',
            'score'               => 'Score',
            'residual_risk'       => 'Risque résiduel',

            // Traitement
            'status'              => 'Statut de traitement',
            'status_comment'      => 'Commentaire statut',
            'controls'            => 'Mesures liées',
            'controls_hint'       => 'Mesure nécessaire si statut = Mitigé',
            'action_plan'         => 'Plans d\'action liés',
            'actions_hint'        => 'Requis si statut = Non accepté (facultatif pour Réduction en mode MONARC)',

            // Planification
            'review_frequency'    => 'Fréquence de revue',
            'next_review'         => 'Prochaine revue',
            'overdue'             => 'En retard de revue',
            'overdue_all'         => 'Tous',
            'overdue_only'        => 'En retard',

            // Dashboard / matrice
            'total'               => 'Total',
            'by_status'           => 'Répartition par statut',
            'by_risks'            => 'Répartition par risques',
        ],

        // Statuts de traitement
        'status' => [
            'not_evaluated'        => 'Non évalué',
            'not_accepted'         => 'Non accepté',
            'temporarily_accepted' => 'Accepté temporairement',
            'accepted'             => 'Accepté',
            'mitigated'            => 'Mitigé',
            'transferred'          => 'Transféré',
            'avoided'              => 'Évité',
        ],

        // Traitements MONARC (réutilisent les clés de 'status' ci-dessus)
        'status_monarc' => [
            'not_treated' => 'Non traité',
            'reduction'   => 'Réduction',
            'denied'      => 'Refusé',
            'accepted'    => 'Accepté',
            'shared'      => 'Partagé',
        ],
    ],

    // -------------------------------------------------------------------------
    // Configuration du moteur de scoring
    // -------------------------------------------------------------------------
    'risk_scoring' => [

        // Titres de page
        'list'             => 'Méthodes de classification des risques',
        'create'           => 'Nouvelle classification',
        'edit'             => 'Modifier la classification',
        'activate'         => 'Activer cette configuration',

        // Actions sur les niveaux / seuils
        'add_level'        => 'Ajouter un niveau',
        'add_threshold'    => 'Ajouter un seuil',

        // Aides contextuelles
        'levels_hint'      => 'Minimum 2 niveaux. La valeur doit être un entier unique.',
        'thresholds_hint'  => 'Le dernier seuil n\'a pas de borne supérieure (attrape-tout). Trier du score le plus bas au plus élevé.',

        // Champs du formulaire
        'fields' => [
            'name'        => 'Nom de la configuration',
            'formula'     => 'Formule de calcul',
            'levels'      => 'Niveaux',
            'thresholds'  => 'Seuils de classification',
            'value'       => 'Valeur',
            'label'       => 'Libellé',
            'description' => 'Description',
            'level_key'   => 'Clé interne',
            'score_max'   => 'Score max',
            'color'       => 'Couleur',
        ],

        // Couleurs disponibles pour les seuils
        'colors' => [
            'success'   => 'Vert',
            'warning'   => 'Orange',
            'danger'    => 'Rouge',
            'alert'     => 'Rouge foncé',
            'info'      => 'Bleu',
            'secondary' => 'Gris',
        ],

        // Formules disponibles (libellés)
        'formulas' => [
            'probability_x_impact' => 'Probabilité × Impact',
            'likelihood_x_impact'  => 'Vraisemblance × Impact (BSI 200-3)',
            'additive'             => 'Probabilité + Impact',
            'max_pi'               => 'max(Probabilité, Impact)',
            'monarc'               => 'MONARC (Impact × Menace × Vulnérabilité)',
        ],

        // Valeurs par défaut proposées à la création d'une configuration
        'defaults' => [
            'probability_levels' => [
                'rare'        => 'Rare',
                'unlikely'    => 'Peu probable',
                'possible'    => 'Possible',
                'likely'      => 'Probable',
                'very_likely' => 'Très probable',
            ],
            'exposure_levels' => [
                'offline'  => 'Hors réseau',
                'internal' => 'Interne',
                'internet' => 'Internet',
            ],
            'vulnerability_levels' => [
                'none'             => 'Aucune',
                'known'            => 'Connue',
                'exploitable_int'  => 'Exploitable interne',
                'exploitable_ext'  => 'Exploitable externe',
            ],
            'impact_levels' => [
                'negligible' => 'Négligeable',
                'low'        => 'Faible',
                'moderate'   => 'Modéré',
                'high'       => 'Élevé',
                'critical'   => 'Critique',
            ],
            'risk_thresholds' => [
                'low'      => 'Faible',
                'medium'   => 'Moyen',
                'high'     => 'Élevé',
                'critical' => 'Critique',
            ],
            'monarc_impact_levels' => [
                0 => ['label' => 'Négligeable', 'description' => 'Aucune conséquence significative sur la réputation, les opérations, le plan légal, financier ou les personnes'],
                1 => ['label' => 'Faible',       'description' => 'Conséquences limitées, absorbables sans perturbation notable de l\'activité'],
                2 => ['label' => 'Important',    'description' => 'Perturbation sensible de l\'activité, atteinte mesurable à la réputation, conséquences financières ou légales notables'],
                3 => ['label' => 'Critique',     'description' => 'Perturbation grave de l\'activité, atteinte sérieuse à la réputation, conséquences légales ou financières majeures, ou atteinte aux personnes'],
                4 => ['label' => 'Vital',        'description' => 'Mise en péril de l\'organisation, conséquences irréversibles ou atteinte grave aux personnes'],
            ],
            'monarc_threat_levels' => [
                0 => ['label' => 'Non applicable', 'description' => 'Menace impossible dans le contexte'],
                1 => ['label' => 'Improbable',     'description' => 'Ne s\'est jamais produit, très peu vraisemblable'],
                2 => ['label' => 'Possible',       'description' => 'Pas de position claire, pourrait se produire'],
                3 => ['label' => 'Probable',       'description' => 'S\'est déjà produit dans l\'organisation ou son environnement'],
                4 => ['label' => 'Très probable',  'description' => 'S\'est déjà produit à plusieurs reprises'],
            ],
            'monarc_vulnerability_levels' => [
                0 => ['label' => 'Inexistante',  'description' => 'Toutes les mesures de sécurité sont appliquées, documentées et efficaces'],
                1 => ['label' => 'Très faible',  'description' => 'Mesures appliquées et contrôlées, améliorations mineures possibles'],
                2 => ['label' => 'Faible',       'description' => 'Mesures globalement appliquées mais non systématiquement contrôlées'],
                3 => ['label' => 'Moyenne',      'description' => 'Mesures partiellement appliquées, lacunes identifiées'],
                4 => ['label' => 'Élevée',       'description' => 'Mesures embryonnaires ou inefficaces'],
                5 => ['label' => 'Très élevée',  'description' => 'Absence totale de mesures de sécurité'],
            ],
        ],
    ],

    'crosswalk' => [
        'list' => 'Correspondances entre référentiels',
        'create' => 'Nouvelle correspondance',
        'edit' => 'Modifier la correspondance',
        'show' => 'Détail de la correspondance',
        'matrix' => 'Matrice de correspondances',
        'source' => 'Contrôle source',
        'target' => 'Contrôle cible',
        'source_framework' => 'Référentiel source',
        'target_framework' => 'Référentiel cible',
        'search_clause' => 'Rechercher une clause ou un contrôle',
        'choose_control' => 'Choisir un contrôle',
        'all' => 'Tous',
        'display' => 'Afficher',
        'reverse' => 'Inverser le sens',
        'show_unmapped' => 'Afficher uniquement les contrôles source non mappés',
        'select_pair' => 'Sélectionnez deux référentiels distincts pour afficher la matrice.',
        'distinct_frameworks' => 'Les référentiels source et cible doivent être distincts.',
        'documentary_notice' => 'Ces nombres sont des métriques documentaires. Ils ne constituent jamais un score ou une preuve de conformité.',
        'source_total' => 'Contrôles source',
        'mapped_source' => 'Avec au moins une relation',
        'unmapped_source' => 'Sans relation',
        'relations' => 'Relations',
        'unmapped' => 'Aucune correspondance',
        'no_filtered_mapping' => 'Aucune correspondance ne répond aux filtres',
        'no_mappings' => 'Aucune correspondance trouvée.',
        'no_controls' => 'Aucun contrôle source trouvé.',
        'control_mappings' => 'Correspondances avec d’autres référentiels',
        'corresponding_control' => 'Contrôle correspondant',
        'stored_direction' => 'Sens de stockage',
        'direct' => 'Source → cible',
        'reversed' => 'Lu en sens inverse',
        'validated_by' => 'Validée par :name le :date',
        'not_validated' => 'Non validée',
        'mapping_types' => [
            'equivalent' => 'Équivalent',
            'covers' => 'Couvre',
            'covered_by' => 'Couvert par',
            'partial' => 'Partiel',
            'supports' => 'Contribue à',
            'supported_by' => 'Bénéficie du soutien de',
            'related' => 'Lié',
        ],
        'coverage_levels' => [
            'full' => 'Complète',
            'high' => 'Élevée',
            'medium' => 'Moyenne',
            'low' => 'Faible',
            'none' => 'Nulle',
        ],
        'fields' => [
            'mapping_type' => 'Type de correspondance',
            'coverage' => 'Couverture',
            'confidence' => 'Confiance',
            'rationale' => 'Justification',
            'source_reference' => 'Référence de la source',
            'source_url' => 'URL de la source',
            'validated' => 'Validation',
        ],
    ],

    'exception' => [
        'list'             => 'Gestion des exceptions',
        'create'           => 'Nouvelle exception',
        'edit'             => 'Modifier l\'exception',
        'title_singular'   => 'Exception',
        'title_plural'     => 'Exceptions',

        'review_section'   => 'Décision de validation',
        'expired_hint'     => 'La date de validité est dépassée.',

        'confirm_submit'   => 'Soumettre cette exception à validation ?',
        'confirm_approve'  => 'Approuver cette exception ?',
        'confirm_reject'   => 'Refuser cette exception ?',

        'actions' => [
            'submit'  => 'Soumettre',
            'approve' => 'Approuver',
            'reject'  => 'Refuser',
        ],

        'status' => [
            'draft' => 'Brouillon',
            'submitted' => 'Soumise',
            'approved' => 'Approuvée',
            'rejected' => 'Refusée',
            'expired' => 'Expirée',
        ],

        'fields' => [
            'name'                        => 'Nom',
            'measure'                     => 'Contrôle lié',
            'no_measure'                  => 'Aucun contrôle',
            'description'                 => 'Description',
            'justification'               => 'Justification',
            'compensating_controls'       => 'Mesures compensatoires',
            'start_date'                  => 'Début',
            'end_date'                    => 'Fin',
            'status'                      => 'Statut',
            'created_by'                  => 'Créé par',
            'submitted_by'                => 'Soumis par',
            'approved_by'                 => 'Approuvé par',
            'rejected_by'                 => 'Refusé par',
            'approval_comment'            => 'Commentaire de décision',
            'approval_comment_optional'   => 'Commentaire (facultatif)',
            'approval_comment_required'   => 'Motif du refus (obligatoire)',
            'choose_status'               => 'Filtrer par statut',
            'choose_measure'              => 'Filtrer par contrôle',
            'expired_only'                => 'Expirées uniquement',
        ],
    ],

];
