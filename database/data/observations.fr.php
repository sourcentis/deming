<?php

/*
 * Observations de test utilisées par la commande deming:generate-tests.
 *
 * Pour chaque clause du référentiel ISO 27001:2022, une observation
 * cohérente avec chaque note : 3 = conforme, 2 = partiellement conforme,
 * 1 = non conforme.
 */

return [
    '27001:2022-4.x' => [
        3 => "Le contexte de l'organisation, les parties intéressées et le périmètre du SMSI sont documentés et revus annuellement. La dernière revue date de moins de douze mois et a été validée par la direction.",
        2 => "Le périmètre du SMSI est défini, mais l'analyse des parties intéressées n'a pas été mise à jour depuis la réorganisation. Les nouvelles exigences des clients ne sont pas encore prises en compte.",
        1 => "Aucun document ne formalise le contexte de l'organisation ni le périmètre du SMSI. Les parties intéressées et leurs attentes n'ont pas été identifiées.",
    ],
    '27001:2022-5.x' => [
        3 => 'La direction a approuvé la politique de sécurité et participe aux comités sécurité trimestriels. Les rôles et responsabilités sont formellement attribués et communiqués.',
        2 => 'La politique de sécurité est approuvée par la direction, mais les comités sécurité ne se sont pas tenus depuis six mois. Certaines responsabilités restent attribuées de manière informelle.',
        1 => "Aucune preuve d'engagement de la direction n'a pu être fournie. Les rôles et responsabilités en matière de sécurité de l'information ne sont pas définis.",
    ],
    '27001:2022-6.x' => [
        3 => 'Les risques et opportunités sont appréciés selon une méthodologie documentée et un plan de traitement est suivi. Les objectifs de sécurité sont mesurables et revus chaque trimestre.',
        2 => "L'appréciation des risques a été réalisée, mais le plan de traitement n'est pas suivi de manière régulière. Plusieurs objectifs de sécurité ne disposent pas d'indicateurs mesurables.",
        1 => "Aucune appréciation des risques n'a été réalisée sur le périmètre du SMSI. Les objectifs de sécurité de l'information ne sont pas définis.",
    ],
    '27001:2022-7.x' => [
        3 => 'Les ressources nécessaires au SMSI sont allouées et la communication interne est planifiée. Les informations documentées sont maîtrisées, versionnées et accessibles aux personnes concernées.',
        2 => "Les ressources du SMSI sont identifiées, mais le plan de communication n'est pas formalisé. Plusieurs documents du SMSI ne sont pas versionnés ni approuvés.",
        1 => "Aucune ressource n'est dédiée au fonctionnement du SMSI. Les informations documentées ne sont ni maîtrisées ni tenues à jour.",
    ],
    '27001:2022-7.2' => [
        3 => "Les compétences requises pour chaque rôle sécurité sont définies et les preuves de formation sont conservées. Les écarts de compétence identifiés font l'objet d'un plan de formation suivi.",
        2 => "Les compétences requises sont définies pour les rôles clés, mais les preuves de formation sont incomplètes. Deux administrateurs n'ont pas suivi la formation prévue cette année.",
        1 => "Les compétences nécessaires aux rôles liés à la sécurité ne sont pas définies. Aucune preuve de formation ou de qualification n'a pu être présentée.",
    ],
    '27001:2022-8.x' => [
        3 => 'Les processus du SMSI sont planifiés et maîtrisés conformément au plan de traitement des risques. Les changements planifiés sont évalués et les processus externalisés sont contrôlés.',
        2 => 'Le plan de traitement des risques est mis en œuvre, mais plusieurs actions ont pris du retard sans réévaluation. Les processus externalisés ne sont que partiellement contrôlés.',
        1 => "Les actions du plan de traitement des risques ne sont pas mises en œuvre. Aucune maîtrise opérationnelle des processus du SMSI n'a pu être démontrée.",
    ],
    '27001:2022-9.2' => [
        3 => "Le programme d'audit interne couvre l'ensemble du périmètre sur trois ans et a été respecté. Les rapports d'audit sont communiqués à la direction et les constats sont suivis.",
        2 => "Un programme d'audit interne existe, mais deux audits planifiés cette année n'ont pas été réalisés. L'indépendance des auditeurs n'est pas garantie pour certains processus.",
        1 => "Aucun audit interne du SMSI n'a été réalisé au cours des dix-huit derniers mois. Il n'existe pas de programme d'audit.",
    ],
    '27001:2022-9.3' => [
        3 => "La revue de direction s'est tenue cette année et couvre toutes les entrées requises par la norme. Les décisions et actions qui en découlent sont tracées dans le compte rendu et suivies.",
        2 => "La revue de direction a eu lieu, mais le compte rendu ne traite pas des résultats d'audit ni de l'évolution des risques. Les actions décidées ne disposent pas d'échéances.",
        1 => "Aucune revue de direction n'a été organisée depuis plus de deux ans. La direction n'examine pas la performance du SMSI.",
    ],
    '27001:2022-10.1' => [
        3 => "Les opportunités d'amélioration sont collectées et suivies dans un registre dédié. Les améliorations réalisées sont présentées en revue de direction.",
        2 => "Des améliorations sont réalisées ponctuellement, mais elles ne sont pas tracées dans un registre. Leur efficacité n'est pas évaluée.",
        1 => "Aucune démarche d'amélioration continue du SMSI n'a pu être démontrée. Les mêmes faiblesses sont relevées d'une année sur l'autre.",
    ],
    '27001:2022-10.2' => [
        3 => "Les non-conformités sont enregistrées, leurs causes analysées et les actions correctives suivies jusqu'à leur clôture. L'efficacité des actions est vérifiée.",
        2 => "Les non-conformités sont enregistrées, mais l'analyse des causes est rarement réalisée. Plusieurs actions correctives sont en retard sans justification.",
        1 => "Les non-conformités ne sont pas enregistrées et aucune action corrective n'est suivie. Les écarts identifiés lors du dernier audit sont toujours ouverts.",
    ],
    '27001:2022-5.01' => [
        3 => "La politique de sécurité de l'information est approuvée par la direction, publiée sur l'intranet et revue annuellement. Les politiques thématiques sont à jour et communiquées au personnel.",
        2 => 'La politique de sécurité est approuvée, mais sa dernière revue date de plus de deux ans. Certaines politiques thématiques, comme celle sur le télétravail, sont absentes.',
        1 => "Il n'existe pas de politique de sécurité de l'information approuvée. Le personnel n'a pas connaissance des règles de sécurité applicables.",
    ],
    '27001:2022-5.02' => [
        3 => "Les fonctions et responsabilités en sécurité sont définies dans les fiches de poste et un RSSI est nommé. L'organigramme sécurité est à jour et diffusé.",
        2 => "Un RSSI est nommé, mais les responsabilités des propriétaires d'actifs ne sont pas formalisées. Les fiches de poste ne mentionnent pas les obligations de sécurité.",
        1 => "Aucune fonction ni responsabilité en matière de sécurité de l'information n'est attribuée. Personne n'est désigné pour piloter la sécurité.",
    ],
    '27001:2022-5.03' => [
        3 => "Une matrice de séparation des tâches est définie pour les processus sensibles et vérifiée lors des revues de droits. Aucun conflit n'a été relevé sur l'échantillon contrôlé.",
        2 => "La séparation des tâches est appliquée pour les paiements, mais pas pour l'administration des systèmes. Deux administrateurs peuvent à la fois créer et valider des comptes.",
        1 => "Aucune séparation des tâches n'est mise en œuvre. Une même personne peut initier, approuver et exécuter des opérations sensibles.",
    ],
    '27001:2022-5.04' => [
        3 => "La direction exige du personnel le respect des règles de sécurité et s'assure de leur application. Les engagements sont rappelés lors des entretiens annuels.",
        2 => "La direction a communiqué les règles de sécurité, mais leur respect n'est pas vérifié par les managers. Les prestataires ne sont pas informés de leurs obligations.",
        1 => "La direction ne s'assure pas que le personnel applique les règles de sécurité. Aucune communication en ce sens n'a pu être présentée.",
    ],
    '27001:2022-5.05' => [
        3 => 'La liste des autorités à contacter (ANSSI, CNIL, police) est à jour et intégrée aux procédures de gestion des incidents. Les responsables des contacts sont identifiés.',
        2 => "Une liste des autorités existe, mais elle n'a pas été mise à jour depuis trois ans. Les personnes habilitées à contacter les autorités ne sont pas désignées.",
        1 => "Aucune liste des autorités à contacter n'est disponible. L'organisation ne sait pas qui notifier en cas d'incident majeur.",
    ],
    '27001:2022-5.06' => [
        3 => "L'équipe sécurité est membre d'un CERT sectoriel et participe aux groupes de travail du CLUSIF. Les informations reçues sont partagées en interne.",
        2 => "L'équipe sécurité suit quelques listes de diffusion, mais ne participe à aucun groupe spécialisé. Les informations ne sont pas diffusées de manière structurée.",
        1 => "Aucun contact n'est entretenu avec des groupes ou forums spécialisés en sécurité. L'équipe n'a pas accès aux retours d'expérience de la profession.",
    ],
    '27001:2022-5.07' => [
        3 => 'Un flux de renseignement sur les menaces est collecté, analysé et intégré aux règles de détection du SOC. Un bulletin mensuel est transmis aux équipes concernées.',
        2 => 'Des informations sur les menaces sont reçues, mais elles ne sont pas analysées ni exploitées. Les règles de détection ne sont pas mises à jour en conséquence.',
        1 => "Aucune collecte d'information sur les menaces n'est réalisée. L'organisation n'a pas de visibilité sur les menaces qui la ciblent.",
    ],
    '27001:2022-5.08' => [
        3 => "La sécurité est intégrée à la méthodologie projet avec une analyse de risques à chaque jalon. Les trois projets échantillonnés disposent d'un dossier sécurité validé.",
        2 => "La méthodologie projet prévoit une analyse de risques, mais un des trois projets échantillonnés n'en a pas réalisé. Les exigences de sécurité ne sont pas toujours tracées.",
        1 => "La sécurité de l'information n'est pas prise en compte dans la gestion de projet. Aucun des projets échantillonnés n'a fait l'objet d'une analyse de risques.",
    ],
    '27001:2022-5.09' => [
        3 => "L'inventaire des actifs est tenu dans la CMDB, chaque actif a un propriétaire et l'inventaire est revu semestriellement. L'échantillon de vingt serveurs est intégralement présent.",
        2 => "Un inventaire des actifs existe, mais une partie des équipements réseau et des applications SaaS n'y figure pas. Plusieurs actifs n'ont pas de propriétaire désigné.",
        1 => "Il n'existe pas d'inventaire des actifs informationnels. L'organisation ne peut pas identifier les systèmes qu'elle exploite.",
    ],
    '27001:2022-5.10' => [
        3 => "Une charte d'utilisation des ressources informatiques est signée par l'ensemble du personnel. Elle est revue annuellement et intégrée au parcours d'accueil.",
        2 => "Une charte d'utilisation existe, mais environ 30 % des collaborateurs ne l'ont pas signée. Elle ne couvre pas l'usage des outils d'IA générative.",
        1 => "Aucune règle d'utilisation acceptable des informations et des actifs n'est définie. Le personnel n'a jamais été informé des usages autorisés.",
    ],
    '27001:2022-5.11' => [
        3 => "La restitution des actifs est vérifiée lors de chaque départ à l'aide d'une check-list signée. Les dix départs échantillonnés ont tous restitué leur matériel.",
        2 => "Une procédure de restitution existe, mais deux des dix départs échantillonnés n'ont pas restitué leur ordinateur portable. Le suivi n'est pas systématique.",
        1 => "Aucune procédure de restitution des actifs n'est en place. Plusieurs anciens collaborateurs disposent encore de matériel de l'entreprise.",
    ],
    '27001:2022-5.12' => [
        3 => 'Une politique de classification à quatre niveaux est définie et appliquée aux informations. Les propriétaires classent leurs actifs et la classification est revue annuellement.',
        2 => "Une politique de classification existe, mais elle n'est appliquée qu'aux documents RH. Les données métier ne sont pas classifiées.",
        1 => "Aucun schéma de classification de l'information n'est défini. Les informations sensibles ne sont pas identifiées.",
    ],
    '27001:2022-5.13' => [
        3 => 'Les documents sont marqués selon leur niveau de classification grâce aux modèles bureautiques. Les supports amovibles et courriels sensibles portent également un marquage.',
        2 => 'Les modèles de documents intègrent le marquage, mais celui-ci est rarement utilisé. Les courriels et exports de données ne sont pas marqués.',
        1 => "Aucun marquage des informations n'est réalisé. Il est impossible de distinguer un document confidentiel d'un document public.",
    ],
    '27001:2022-5.14' => [
        3 => "Les règles de transfert d'information sont définies selon la classification et les échanges externes utilisent une plateforme chiffrée. Les accords de transfert sont signés avec les partenaires.",
        2 => "Une plateforme d'échange sécurisée est disponible, mais des fichiers confidentiels sont encore envoyés par courriel non chiffré. Les règles ne sont pas formalisées.",
        1 => "Aucune règle ni moyen sécurisé de transfert d'information n'est en place. Des données sensibles sont échangées via des services de partage grand public.",
    ],
    '27001:2022-5.15' => [
        3 => "La politique de contrôle d'accès est fondée sur le besoin d'en connaître et le moindre privilège. Elle est appliquée à toutes les applications du périmètre et revue annuellement.",
        2 => "Une politique de contrôle d'accès existe, mais elle n'est pas appliquée aux applications SaaS. Certains accès sont accordés sans validation du propriétaire.",
        1 => "Aucune politique de contrôle d'accès n'est définie. Les accès sont attribués sans règle ni validation.",
    ],
    '27001:2022-5.16' => [
        3 => "Le cycle de vie des identités est automatisé depuis le SIRH avec création et suppression des comptes. Aucun compte orphelin n'a été trouvé sur l'échantillon contrôlé.",
        2 => "La création des comptes est encadrée, mais la suppression dépend d'une demande manuelle. Douze comptes de personnes parties sont encore actifs.",
        1 => "Il n'existe aucun processus de gestion des identités. Des comptes partagés et des comptes d'anciens collaborateurs sont utilisés.",
    ],
    '27001:2022-5.17' => [
        3 => "La politique de mots de passe impose longueur et complexité, et l'authentification multifacteur est activée pour tous. Les mots de passe initiaux sont transmis de manière sécurisée.",
        2 => "La politique de mots de passe est appliquée sur l'annuaire, mais pas sur les applications métier historiques. L'authentification multifacteur n'est pas généralisée.",
        1 => "Aucune règle n'encadre les informations d'authentification. Des mots de passe sont partagés entre collègues et stockés en clair dans des fichiers.",
    ],
    '27001:2022-5.18' => [
        3 => "Les droits d'accès sont attribués après validation du responsable et revus semestriellement. La dernière revue a conduit à la suppression de droits inutiles, tracée.",
        2 => "Les droits sont attribués après validation, mais la revue périodique n'a pas été réalisée cette année. Des droits excessifs ont été relevés lors des mutations internes.",
        1 => "Les droits d'accès ne sont jamais revus. Les collaborateurs conservent les droits de leurs postes précédents.",
    ],
    '27001:2022-5.19' => [
        3 => "Les fournisseurs critiques sont identifiés et évalués sur la sécurité avant contractualisation. Un questionnaire sécurité est renseigné et analysé pour chacun d'eux.",
        2 => "Les fournisseurs critiques sont identifiés, mais seule la moitié a fait l'objet d'une évaluation sécurité. Les nouveaux fournisseurs ne sont pas systématiquement évalués.",
        1 => "Aucune évaluation de la sécurité des fournisseurs n'est réalisée. L'organisation ne connaît pas les fournisseurs ayant accès à ses données.",
    ],
    '27001:2022-5.20' => [
        3 => "Les contrats avec les fournisseurs intègrent une annexe sécurité couvrant la confidentialité, la notification des incidents et le droit d'audit. Les contrats échantillonnés sont conformes.",
        2 => "Les nouveaux contrats comportent des clauses de sécurité, mais les contrats anciens n'ont pas été mis à jour. Le droit d'audit est absent de plusieurs contrats critiques.",
        1 => "Les contrats fournisseurs ne contiennent aucune exigence de sécurité de l'information. Aucune obligation de notification des incidents n'est prévue.",
    ],
    '27001:2022-5.21' => [
        3 => "Les exigences de sécurité de la chaîne d'approvisionnement TIC sont répercutées aux sous-traitants des fournisseurs. Les composants critiques font l'objet d'une vérification de provenance.",
        2 => "Les fournisseurs directs sont encadrés, mais la sous-traitance de rang deux n'est pas maîtrisée. La provenance des composants logiciels n'est pas vérifiée.",
        1 => "Aucune maîtrise de la chaîne d'approvisionnement TIC n'est en place. Les risques liés aux sous-traitants ne sont pas identifiés.",
    ],
    '27001:2022-5.22' => [
        3 => "Les services des fournisseurs critiques sont suivis via des comités trimestriels et des indicateurs de niveau de service. Les changements sont analysés avant d'être acceptés.",
        2 => 'Des comités de suivi existent pour certains fournisseurs, mais les rapports de sécurité ne sont pas demandés. Les changements de service ne sont pas évalués.',
        1 => "Aucun suivi ni revue des services fournis par les fournisseurs n'est réalisé. Les changements sont subis sans analyse.",
    ],
    '27001:2022-5.23' => [
        3 => "Une politique d'usage du cloud définit les exigences d'acquisition, d'utilisation et de sortie des services. Les services utilisés sont inventoriés et leurs certifications vérifiées.",
        2 => "Les principaux services cloud sont encadrés, mais plusieurs services SaaS sont utilisés sans validation. La réversibilité n'est pas prévue dans les contrats.",
        1 => "L'utilisation des services en nuage n'est pas encadrée. De nombreux services sont souscrits directement par les métiers sans analyse de sécurité.",
    ],
    '27001:2022-5.24' => [
        3 => 'La procédure de gestion des incidents est documentée, les rôles sont définis et un exercice a été réalisé cette année. Les contacts et outils nécessaires sont disponibles.',
        2 => "Une procédure de gestion des incidents existe, mais elle n'a jamais été testée. Les rôles de l'équipe de réponse ne sont pas tous attribués.",
        1 => "Aucune procédure de gestion des incidents de sécurité n'est définie. L'organisation n'est pas préparée à réagir à un incident.",
    ],
    '27001:2022-5.25' => [
        3 => "Les événements de sécurité sont qualifiés selon une grille de criticité documentée. Les décisions de classement en incident sont tracées dans l'outil de ticketing.",
        2 => "Les événements sont analysés, mais aucune grille de qualification n'est formalisée. Les décisions de classement dépendent de l'appréciation de l'analyste.",
        1 => "Les événements de sécurité ne sont ni évalués ni qualifiés. Aucune trace de décision n'a pu être présentée.",
    ],
    '27001:2022-5.26' => [
        3 => "Les incidents sont traités selon la procédure, avec confinement, éradication et communication tracés. Les trois incidents de l'année ont été clôturés dans les délais.",
        2 => "Les incidents sont traités, mais les actions de réponse ne sont pas toutes tracées. Un incident n'a pas été notifié à la direction dans les délais prévus.",
        1 => "Les incidents sont traités au cas par cas sans procédure. Aucun historique des incidents passés n'est conservé.",
    ],
    '27001:2022-5.27' => [
        3 => "Chaque incident significatif donne lieu à un retour d'expérience documenté. Les actions d'amélioration identifiées sont suivies jusqu'à leur clôture.",
        2 => "Des retours d'expérience sont réalisés pour les incidents majeurs, mais les actions identifiées ne sont pas suivies. Les enseignements ne sont pas partagés.",
        1 => "Aucun retour d'expérience n'est réalisé après les incidents. Les mêmes incidents se reproduisent sans amélioration.",
    ],
    '27001:2022-5.28' => [
        3 => "Une procédure de recueil de preuves garantit la chaîne de custody et l'intégrité des éléments collectés. Des outils forensiques sont disponibles et le personnel est formé.",
        2 => "Les preuves sont collectées lors des incidents, mais sans garantie d'intégrité ni chaîne de custody. Aucune procédure n'est formalisée.",
        1 => "Aucun recueil de preuves n'est prévu lors des incidents. Les journaux sont écrasés avant toute analyse.",
    ],
    '27001:2022-5.29' => [
        3 => "Les plans de continuité intègrent le maintien des mesures de sécurité en mode dégradé. L'exercice de cette année a confirmé leur efficacité.",
        2 => "Les plans de continuité existent, mais ne prévoient pas le maintien de la sécurité en mode dégradé. Les accès d'urgence ne sont pas encadrés.",
        1 => "La sécurité de l'information n'est pas prise en compte en cas de perturbation. Aucun plan ne décrit les mesures à maintenir.",
    ],
    '27001:2022-5.30' => [
        3 => 'Le plan de reprise informatique est défini à partir du BIA et testé annuellement. Les objectifs RTO et RPO ont été atteints lors du dernier test.',
        2 => "Un plan de reprise informatique existe, mais il n'a pas été testé depuis deux ans. Les RTO des applications critiques ne sont pas validés par les métiers.",
        1 => "Aucun plan de reprise informatique n'est défini. L'organisation ne connaît pas ses délais de reprise en cas de sinistre.",
    ],
    '27001:2022-5.31' => [
        3 => 'Un registre des exigences légales, réglementaires et contractuelles est tenu à jour par le service juridique. Il est revu semestriellement avec le RSSI.',
        2 => "Les principales exigences réglementaires sont identifiées, mais le registre n'intègre pas NIS 2. Les exigences contractuelles des clients ne sont pas recensées.",
        1 => "Les exigences légales, réglementaires et contractuelles applicables ne sont pas identifiées. Aucun registre n'existe.",
    ],
    '27001:2022-5.32' => [
        3 => "Les licences logicielles sont inventoriées et rapprochées des installations chaque trimestre. Aucun écart n'a été relevé lors du dernier contrôle.",
        2 => "Un inventaire des licences existe, mais il n'est pas rapproché des installations. Quelques logiciels installés ne disposent pas de licence.",
        1 => "Aucun suivi des licences n'est réalisé. Des logiciels sans licence sont utilisés sur de nombreux postes.",
    ],
    '27001:2022-5.33' => [
        3 => "Les durées de conservation des enregistrements sont définies et appliquées. Les enregistrements sont protégés contre la perte, la falsification et l'accès non autorisé.",
        2 => 'Les durées de conservation sont définies pour les données comptables, mais pas pour les journaux techniques. Certains enregistrements sont stockés sans protection.',
        1 => "Aucune règle de conservation ni de protection des enregistrements n'est définie. Des enregistrements obligatoires ont été perdus.",
    ],
    '27001:2022-5.34' => [
        3 => "Le registre des traitements est à jour et les analyses d'impact ont été réalisées pour les traitements à risque. Le DPO est associé aux projets.",
        2 => "Le registre des traitements existe, mais il n'est pas à jour pour les nouveaux traitements. Aucune analyse d'impact n'a été réalisée pour la vidéosurveillance.",
        1 => "Aucun registre des traitements de données personnelles n'est tenu. Les obligations du RGPD ne sont pas prises en compte.",
    ],
    '27001:2022-5.35' => [
        3 => "Une revue indépendante de la sécurité a été réalisée cette année par un cabinet externe. Les recommandations sont intégrées au plan d'actions et suivies.",
        2 => "Une revue indépendante a été réalisée il y a trois ans, mais elle n'a pas été renouvelée. La moitié des recommandations n'est pas traitée.",
        1 => "Aucune revue indépendante de la sécurité de l'information n'a jamais été réalisée.",
    ],
    '27001:2022-5.36' => [
        3 => 'Les managers vérifient régulièrement le respect des politiques de sécurité dans leur périmètre. Des contrôles techniques de conformité sont automatisés.',
        2 => 'Des contrôles de conformité sont réalisés ponctuellement, mais sans planification ni traçabilité. Les écarts relevés ne sont pas suivis.',
        1 => "Le respect des politiques et normes de sécurité n'est jamais vérifié. Plusieurs écarts manifestes ont été constatés pendant l'audit.",
    ],
    '27001:2022-5.37' => [
        3 => "Les procédures d'exploitation sont documentées, versionnées et accessibles aux équipes. Elles sont revues à chaque changement significatif.",
        2 => "Les procédures d'exploitation existent pour les serveurs, mais pas pour les équipements réseau. Plusieurs procédures sont obsolètes.",
        1 => "Aucune procédure d'exploitation n'est documentée. Le savoir-faire repose sur quelques personnes clés.",
    ],
    '27001:2022-6.01' => [
        3 => 'Les vérifications des antécédents sont réalisées avant chaque embauche, proportionnellement aux risques du poste. Les dix dossiers échantillonnés sont complets.',
        2 => 'Les vérifications sont réalisées pour les cadres, mais pas pour les prestataires ni les intérimaires. Deux dossiers échantillonnés sont incomplets.',
        1 => "Aucune vérification des antécédents n'est réalisée avant l'embauche, y compris pour les postes sensibles.",
    ],
    '27001:2022-6.02' => [
        3 => "Les contrats de travail intègrent les responsabilités en matière de sécurité de l'information. Les collaborateurs signent la charte informatique à leur arrivée.",
        2 => "Les nouveaux contrats intègrent une clause de sécurité, mais les contrats antérieurs à 2022 n'ont pas été mis à jour. La charte n'est pas annexée.",
        1 => "Les contrats de travail ne mentionnent aucune obligation en matière de sécurité de l'information.",
    ],
    '27001:2022-6.03' => [
        3 => "Un programme annuel de sensibilisation est déployé avec un taux de complétion de 95 %. Des campagnes d'hameçonnage simulé sont réalisées chaque trimestre.",
        2 => "Une sensibilisation est réalisée à l'arrivée, mais aucun rappel périodique n'est organisé. Le taux de complétion de la formation en ligne est de 55 %.",
        1 => "Aucune action de sensibilisation à la sécurité de l'information n'est réalisée. Le personnel ne connaît pas les risques ni les bons réflexes.",
    ],
    '27001:2022-6.04' => [
        3 => 'Le processus disciplinaire prévoit explicitement les manquements aux règles de sécurité. Il est communiqué au personnel via le règlement intérieur.',
        2 => "Le règlement intérieur mentionne les sanctions, mais pas les violations de la politique de sécurité. Le processus n'a jamais été appliqué.",
        1 => "Aucun processus disciplinaire ne couvre les violations de la sécurité de l'information.",
    ],
    '27001:2022-6.05' => [
        3 => "Les obligations de sécurité qui perdurent après le départ sont rappelées lors de l'entretien de sortie. Les accès sont révoqués le jour du départ.",
        2 => 'Les accès sont révoqués au départ, mais les obligations de confidentialité ne sont pas rappelées. Les mutations internes ne sont pas prises en compte.',
        1 => "Aucune mesure n'est prise lors des départs ou changements de poste. Les accès des anciens collaborateurs restent actifs.",
    ],
    '27001:2022-6.06' => [
        3 => "Des engagements de confidentialité sont signés par l'ensemble du personnel et des prestataires. Ils sont revus annuellement par le service juridique.",
        2 => "Les salariés signent un engagement de confidentialité, mais pas les prestataires. Le modèle n'a pas été revu depuis cinq ans.",
        1 => "Aucun engagement de confidentialité n'est signé par le personnel ou les tiers.",
    ],
    '27001:2022-6.07' => [
        3 => 'Une politique de télétravail définit les règles de sécurité et les accès distants passent par un VPN avec MFA. Les postes nomades sont chiffrés et gérés.',
        2 => "Les accès distants sont sécurisés par VPN, mais l'authentification multifacteur n'est pas activée. La politique de télétravail n'aborde pas la sécurité.",
        1 => "Le travail à distance n'est pas encadré. Des collaborateurs accèdent au SI depuis des équipements personnels non maîtrisés.",
    ],
    '27001:2022-6.08' => [
        3 => 'Un canal unique de signalement des événements de sécurité est connu du personnel. Les signalements sont traités et un retour est fait aux déclarants.',
        2 => 'Un canal de signalement existe, mais il est peu connu du personnel. Les signalements reçus ne sont pas tous traités.',
        1 => "Aucun moyen de signalement des événements de sécurité n'est mis à disposition du personnel.",
    ],
    '27001:2022-7.01' => [
        3 => 'Le périmètre de sécurité physique est défini et matérialisé par des clôtures et des accès contrôlés. Les zones sensibles sont clairement identifiées.',
        2 => 'Le périmètre est défini, mais une issue de secours du bâtiment reste régulièrement ouverte. Les zones sensibles ne sont pas toutes identifiées.',
        1 => "Aucun périmètre de sécurité physique n'est défini. Les locaux sont librement accessibles depuis l'extérieur.",
    ],
    '27001:2022-7.02' => [
        3 => "Les accès aux locaux sont contrôlés par badge et les visiteurs sont enregistrés et accompagnés. Les droits d'accès physiques sont revus semestriellement.",
        2 => "Les accès sont contrôlés par badge, mais le registre des visiteurs n'est pas tenu systématiquement. Des badges de personnes parties sont encore actifs.",
        1 => "Aucun contrôle des accès physiques n'est réalisé. Les visiteurs circulent librement dans les locaux.",
    ],
    '27001:2022-7.03' => [
        3 => 'Les bureaux et salles sensibles sont fermés à clé et leur accès est restreint. Les informations sur leur localisation ne sont pas divulguées.',
        2 => 'La salle serveur est sécurisée, mais les bureaux de la direction et des RH restent ouverts en dehors des heures de travail.',
        1 => "Aucune mesure de sécurisation des bureaux et des salles n'est en place. La salle serveur n'est pas fermée à clé.",
    ],
    '27001:2022-7.04' => [
        3 => "Les locaux sont couverts par une vidéosurveillance et une détection d'intrusion reliées à un télésurveilleur. Les alarmes sont testées mensuellement.",
        2 => "Une vidéosurveillance est installée, mais les enregistrements ne sont pas exploités. Le système d'alarme n'a pas été testé depuis un an.",
        1 => "Aucun dispositif de surveillance de la sécurité physique n'est installé.",
    ],
    '27001:2022-7.05' => [
        3 => "Les menaces physiques et environnementales sont analysées et la salle serveur dispose de détection incendie, climatisation redondante et sonde d'inondation.",
        2 => "La salle serveur dispose d'une détection incendie, mais la climatisation n'est pas redondante. Aucune sonde d'inondation n'est installée.",
        1 => "Aucune protection contre les menaces physiques et environnementales n'est en place dans la salle serveur.",
    ],
    '27001:2022-7.06' => [
        3 => 'Des règles de travail dans les zones sécurisées sont définies et affichées. Les interventions de tiers y sont systématiquement accompagnées.',
        2 => 'Les règles de travail en zone sécurisée existent, mais les prestataires y interviennent parfois sans accompagnement.',
        1 => "Aucune règle ne s'applique au travail dans les zones sécurisées.",
    ],
    '27001:2022-7.07' => [
        3 => "La politique du bureau propre et de l'écran vide est appliquée et le verrouillage automatique est configuré à cinq minutes. La ronde de contrôle n'a relevé aucun écart.",
        2 => 'Le verrouillage automatique des écrans est configuré, mais la ronde a relevé des documents confidentiels laissés sur plusieurs bureaux.',
        1 => "Aucune règle de bureau propre ni d'écran vide n'est définie. De nombreux postes restent déverrouillés en l'absence de leur utilisateur.",
    ],
    '27001:2022-7.08' => [
        3 => "Les équipements sont installés dans des emplacements protégés, à l'abri des regards et des risques environnementaux. Les écrans exposés disposent de filtres de confidentialité.",
        2 => 'Les serveurs sont correctement installés, mais des équipements réseau se trouvent dans des locaux techniques non fermés.',
        1 => 'Le matériel est installé sans prise en compte des risques. Des équipements sensibles sont accessibles dans les couloirs.',
    ],
    '27001:2022-7.09' => [
        3 => "Les actifs utilisés hors des locaux sont chiffrés, inventoriés et couverts par des règles d'usage. Le personnel nomade est sensibilisé.",
        2 => "Les ordinateurs portables sont chiffrés, mais les smartphones professionnels ne sont pas gérés. Les règles d'usage hors des locaux ne sont pas formalisées.",
        1 => "Aucune mesure de protection des actifs hors des locaux n'est en place. Les ordinateurs portables ne sont pas chiffrés.",
    ],
    '27001:2022-7.10' => [
        3 => "L'usage des supports amovibles est restreint par une solution de contrôle des périphériques. Les supports autorisés sont chiffrés et inventoriés.",
        2 => 'Une politique encadre les supports amovibles, mais aucune mesure technique ne bloque les clés USB non autorisées.',
        1 => "L'utilisation des supports de stockage amovibles n'est pas encadrée. Des données sensibles circulent sur des clés USB non chiffrées.",
    ],
    '27001:2022-7.11' => [
        3 => 'La salle serveur est alimentée par un onduleur et un groupe électrogène testés trimestriellement. La redondance des services généraux est assurée.',
        2 => "Un onduleur protège la salle serveur, mais le groupe électrogène n'a pas été testé depuis dix-huit mois.",
        1 => "Aucune protection contre les défaillances des services généraux n'est en place. Une coupure électrique entraîne l'arrêt immédiat des serveurs.",
    ],
    '27001:2022-7.12' => [
        3 => 'Le câblage est protégé dans des chemins de câbles fermés et documenté. Les baies de brassage sont verrouillées.',
        2 => 'Le câblage est documenté, mais plusieurs baies de brassage ne sont pas verrouillées.',
        1 => "Le câblage n'est ni protégé ni documenté. Des prises réseau actives sont accessibles dans les espaces publics.",
    ],
    '27001:2022-7.13' => [
        3 => "Le matériel fait l'objet d'une maintenance préventive selon les recommandations des fabricants. Les interventions sont tracées et les contrats de maintenance sont à jour.",
        2 => 'Des contrats de maintenance existent, mais plusieurs équipements sont hors support. Les interventions ne sont pas tracées.',
        1 => "Aucune maintenance du matériel n'est planifiée. Les équipements sont réparés uniquement en cas de panne.",
    ],
    '27001:2022-7.14' => [
        3 => 'Les supports sont effacés de manière sécurisée ou détruits par un prestataire certifié avant mise au rebut. Les certificats de destruction sont conservés.',
        2 => "Les disques des serveurs sont détruits, mais les postes de travail recyclés ne font pas l'objet d'un effacement sécurisé.",
        1 => "Aucune procédure de mise au rebut sécurisée n'est appliquée. Des ordinateurs sont donnés sans effacement des données.",
    ],
    '27001:2022-8.01' => [
        3 => "Les terminaux utilisateurs sont gérés par une solution MDM, chiffrés et maintenus à jour. La conformité des postes est vérifiée avant l'accès au SI.",
        2 => "Les postes de travail sont gérés, mais les terminaux mobiles ne le sont pas. Le chiffrement n'est pas activé sur 15 % des postes.",
        1 => 'Les terminaux utilisateurs ne sont ni gérés ni sécurisés. Les utilisateurs sont administrateurs de leur poste.',
    ],
    '27001:2022-8.02' => [
        3 => "Les privilèges d'accès sont attribués nominativement, via un bastion, et revus trimestriellement. Les comptes à privilèges sont distincts des comptes bureautiques.",
        2 => "Les comptes à privilèges sont identifiés, mais certains administrateurs utilisent leur compte d'administration pour la bureautique. La revue n'est pas réalisée.",
        1 => "Les privilèges d'accès ne sont pas maîtrisés. Des comptes administrateurs génériques sont partagés entre plusieurs personnes.",
    ],
    '27001:2022-8.03' => [
        3 => "L'accès aux informations est restreint selon la politique de contrôle d'accès dans toutes les applications. Les partages réseau sont nominatifs.",
        2 => 'Les applications métier restreignent les accès, mais plusieurs partages réseau sont accessibles à tous les utilisateurs.',
        1 => "L'accès à l'information n'est pas restreint. Tous les utilisateurs peuvent consulter les données sensibles.",
    ],
    '27001:2022-8.04' => [
        3 => 'Le code source est stocké dans un gestionnaire de versions avec des droits restreints et revus. Les modifications nécessitent une revue par un pair.',
        2 => "Le code source est versionné, mais l'ensemble des développeurs a accès à tous les dépôts, y compris les plus sensibles.",
        1 => "L'accès au code source n'est pas contrôlé. Le code est partagé sur des répertoires réseau ouverts.",
    ],
    '27001:2022-8.05' => [
        3 => "L'authentification multifacteur est imposée pour tous les accès distants et les comptes à privilèges. Les mécanismes d'authentification résistent au rejeu.",
        2 => "L'authentification multifacteur est activée pour la messagerie, mais pas pour le VPN ni pour les consoles d'administration.",
        1 => "Aucune authentification forte n'est mise en œuvre. Les accès distants reposent uniquement sur un mot de passe.",
    ],
    '27001:2022-8.06' => [
        3 => "La capacité des systèmes est surveillée avec des seuils d'alerte et un plan de capacité est revu annuellement. Aucun incident de saturation n'a été constaté.",
        2 => "Des alertes de capacité sont configurées, mais aucun plan de capacité n'est établi. Deux saturations de stockage ont eu lieu cette année.",
        1 => "La capacité des systèmes n'est pas surveillée. Les saturations sont découvertes par les utilisateurs.",
    ],
    '27001:2022-8.07' => [
        3 => 'Une solution EDR est déployée sur 100 % des postes et serveurs, avec des signatures à jour. Les alertes sont traitées par le SOC.',
        2 => 'Un antivirus est déployé sur les postes, mais pas sur les serveurs Linux. Les alertes ne sont pas surveillées.',
        1 => "Aucune protection contre les programmes malveillants n'est déployée sur une partie significative du parc.",
    ],
    '27001:2022-8.08' => [
        3 => "Les vulnérabilités sont détectées par des scans hebdomadaires et corrigées selon des délais définis par criticité. Aucune vulnérabilité critique n'est ouverte depuis plus de quinze jours.",
        2 => 'Des scans de vulnérabilités sont réalisés, mais les correctifs critiques sont appliqués avec plusieurs mois de retard.',
        1 => "Aucune gestion des vulnérabilités techniques n'est en place. Des systèmes exposés sur Internet présentent des vulnérabilités critiques connues.",
    ],
    '27001:2022-8.09' => [
        3 => 'Les configurations de référence sont définies et déployées automatiquement. Les écarts de configuration sont détectés et corrigés.',
        2 => 'Des configurations de référence existent pour les postes, mais pas pour les serveurs. Les écarts ne sont pas contrôlés.',
        1 => "Aucune configuration de référence n'est définie. Les systèmes sont installés avec leurs paramètres par défaut.",
    ],
    '27001:2022-8.10' => [
        3 => "Les informations sont supprimées à l'issue de leur durée de conservation selon une procédure documentée. Les suppressions sont tracées.",
        2 => 'Les durées de conservation sont définies, mais les données ne sont pas supprimées à leur échéance. Des données clients de plus de dix ans sont conservées.',
        1 => "Aucune suppression des informations n'est réalisée. Les données sont conservées indéfiniment.",
    ],
    '27001:2022-8.11' => [
        3 => 'Les données personnelles sont masquées ou pseudonymisées dans les environnements hors production. Le masquage est automatisé lors des copies de base.',
        2 => "Le masquage est appliqué sur l'application principale, mais d'autres environnements de test contiennent des données réelles.",
        1 => "Aucun masquage des données n'est réalisé. Les environnements de test contiennent des copies intégrales de la production.",
    ],
    '27001:2022-8.12' => [
        3 => 'Une solution DLP surveille les flux de messagerie et de transfert de fichiers. Les alertes sont analysées et les fuites potentielles bloquées.',
        2 => 'Des règles de DLP sont configurées sur la messagerie, mais les transferts vers le cloud personnel ne sont pas surveillés.',
        1 => "Aucune mesure de prévention de la fuite de données n'est en place.",
    ],
    '27001:2022-8.13' => [
        3 => 'Les sauvegardes sont réalisées quotidiennement, dont une copie hors ligne. Les tests de restauration trimestriels sont concluants.',
        2 => "Les sauvegardes sont réalisées quotidiennement, mais aucun test de restauration n'a été effectué cette année. Aucune copie n'est hors ligne.",
        1 => 'Les sauvegardes ne sont pas réalisées régulièrement. Une restauration récente a échoué faute de sauvegarde exploitable.',
    ],
    '27001:2022-8.14' => [
        3 => 'Les systèmes critiques sont redondés sur deux sites et le basculement est testé annuellement. Le dernier test a respecté les objectifs de disponibilité.',
        2 => 'Les serveurs critiques sont redondés, mais les équipements réseau constituent un point de défaillance unique.',
        1 => "Aucune redondance des moyens de traitement n'est en place. La panne d'un serveur entraîne l'arrêt des services critiques.",
    ],
    '27001:2022-8.15' => [
        3 => "Les journaux des systèmes critiques sont centralisés dans un SIEM, protégés et conservés un an. Les journaux d'administration sont inclus.",
        2 => 'Les journaux sont collectés pour les serveurs Windows, mais pas pour les applications métier. La durée de conservation est de trente jours.',
        1 => "Aucune journalisation centralisée n'est en place. Les journaux locaux sont écrasés après quelques jours.",
    ],
    '27001:2022-8.16' => [
        3 => 'Le SOC surveille les réseaux et systèmes 24h/24 et traite les alertes selon des procédures définies. Les règles de détection sont revues mensuellement.',
        2 => "Des alertes sont générées par le SIEM, mais elles ne sont consultées qu'en heures ouvrées. De nombreuses alertes ne sont pas traitées.",
        1 => "Aucune activité de surveillance des réseaux et des systèmes n'est réalisée.",
    ],
    '27001:2022-8.17' => [
        3 => 'Tous les systèmes sont synchronisés sur des sources de temps NTP de référence. La dérive est surveillée.',
        2 => 'Les serveurs sont synchronisés, mais plusieurs équipements réseau présentent un décalage de plusieurs minutes.',
        1 => 'Les horloges des systèmes ne sont pas synchronisées, ce qui empêche la corrélation des journaux.',
    ],
    '27001:2022-8.18' => [
        3 => "L'utilisation des programmes utilitaires à privilèges est restreinte aux administrateurs et journalisée. Leur liste est revue annuellement.",
        2 => 'Les utilitaires à privilèges sont restreints sur les serveurs, mais disponibles sur certains postes utilisateurs.',
        1 => "L'utilisation des programmes utilitaires à privilèges n'est pas contrôlée.",
    ],
    '27001:2022-8.19' => [
        3 => "L'installation de logiciels est réservée aux administrateurs via un catalogue validé. Les installations sur les systèmes de production suivent le processus de changement.",
        2 => 'Les utilisateurs ne peuvent pas installer de logiciels, mais des installations en production sont réalisées hors processus de changement.',
        1 => 'Les utilisateurs installent librement des logiciels, y compris sur les serveurs.',
    ],
    '27001:2022-8.20' => [
        3 => 'Les réseaux sont sécurisés et administrés selon des règles documentées. Les équipements sont durcis et leurs configurations sauvegardées.',
        2 => 'Les équipements réseau sont administrés, mais certains utilisent encore des protocoles non chiffrés comme Telnet.',
        1 => "Aucune mesure de sécurité n'est appliquée aux réseaux. Les équipements conservent leurs mots de passe par défaut.",
    ],
    '27001:2022-8.21' => [
        3 => 'Les exigences de sécurité des services réseau sont définies dans les contrats des opérateurs. Les niveaux de service sont suivis.',
        2 => "Les services réseau sont contractualisés, mais aucune exigence de sécurité n'y est formalisée.",
        1 => 'Les mécanismes de sécurité des services réseau ne sont ni identifiés ni contrôlés.',
    ],
    '27001:2022-8.22' => [
        3 => "Un proxy filtre l'accès à Internet selon des catégories définies et bloque les sites malveillants. Les journaux de navigation sont conservés.",
        2 => "Un filtrage Internet est en place sur site, mais les postes en télétravail n'en bénéficient pas.",
        1 => "Aucun filtrage de l'accès à Internet n'est mis en œuvre.",
    ],
    '27001:2022-8.23' => [
        3 => 'Le réseau est segmenté en zones de sécurité séparées par des pare-feux. Les flux entre zones sont limités au strict nécessaire et revus.',
        2 => 'Le réseau est segmenté, mais les règles de filtrage entre zones sont trop permissives. Plusieurs règles « any-any » ont été relevées.',
        1 => "Le réseau n'est pas cloisonné. Les postes utilisateurs peuvent joindre directement les serveurs de production.",
    ],
    '27001:2022-8.24' => [
        3 => 'Une politique cryptographique définit les algorithmes autorisés et la gestion des clés. Les données sensibles sont chiffrées au repos et en transit.',
        2 => "Les flux externes sont chiffrés, mais certaines bases de données sensibles ne sont pas chiffrées au repos. Aucune politique de gestion des clés n'est formalisée.",
        1 => "Aucune mesure cryptographique n'est mise en œuvre. Des protocoles obsolètes et des certificats expirés sont utilisés.",
    ],
    '27001:2022-8.25' => [
        3 => 'Le cycle de développement sécurisé est défini et appliqué, avec analyse de code automatisée dans la chaîne CI/CD. Les développeurs sont formés.',
        2 => "Des règles de développement sécurisé existent, mais l'analyse de code n'est pas intégrée à la chaîne CI/CD.",
        1 => "Aucune règle de développement sécurisé n'est définie.",
    ],
    '27001:2022-8.26' => [
        3 => "Les exigences de sécurité sont identifiées et validées lors de la conception de chaque application. Elles sont tracées jusqu'aux tests.",
        2 => 'Les exigences de sécurité sont définies pour les nouvelles applications, mais pas lors des évolutions majeures.',
        1 => 'Les exigences de sécurité des applications ne sont pas identifiées.',
    ],
    '27001:2022-8.27' => [
        3 => "Des principes d'architecture sécurisée sont documentés et appliqués lors des revues d'architecture. Les nouveaux systèmes respectent la défense en profondeur.",
        2 => "Des principes d'architecture existent, mais aucune revue d'architecture n'est réalisée systématiquement.",
        1 => "Aucun principe d'ingénierie sécurisée n'est défini pour la conception des systèmes.",
    ],
    '27001:2022-8.28' => [
        3 => 'Des règles de codage sécurisé sont appliquées et vérifiées par revue de code et analyse statique. Les dépendances vulnérables sont détectées automatiquement.',
        2 => 'Des règles de codage sécurisé existent, mais les revues de code ne sont pas systématiques. Les dépendances ne sont pas analysées.',
        1 => "Aucune pratique de codage sécurisé n'est appliquée. Des injections SQL ont été identifiées dans le code.",
    ],
    '27001:2022-8.29' => [
        3 => "Des tests de sécurité et un test d'intrusion sont réalisés avant chaque mise en production majeure. Les vulnérabilités sont corrigées avant la recette.",
        2 => "Des tests d'intrusion sont réalisés annuellement, mais pas avant les mises en production. Certaines vulnérabilités sont acceptées sans analyse.",
        1 => "Aucun test de sécurité n'est réalisé dans le cycle de développement.",
    ],
    '27001:2022-8.30' => [
        3 => 'Le développement externalisé est encadré par des exigences de sécurité contractuelles et des revues de code. Les livrables sont testés avant acceptation.',
        2 => 'Les contrats de développement externalisé intègrent des exigences de sécurité, mais les livrables ne sont pas contrôlés.',
        1 => "Le développement externalisé n'est soumis à aucune exigence ni contrôle de sécurité.",
    ],
    '27001:2022-8.31' => [
        3 => "Les environnements de développement, de test et de production sont séparés logiquement et les accès sont distincts. Les développeurs n'ont pas accès à la production.",
        2 => "Les environnements sont séparés, mais plusieurs développeurs disposent encore d'un accès à la production.",
        1 => 'Les environnements de développement, de test et de production ne sont pas séparés.',
    ],
    '27001:2022-8.32' => [
        3 => "Les changements sont gérés via un processus formalisé avec analyse d'impact, validation et retour arrière. Les changements échantillonnés sont tous tracés.",
        2 => 'Un processus de gestion des changements existe, mais plusieurs changements urgents ont été réalisés sans validation a posteriori.',
        1 => "Aucun processus de gestion des changements n'est en place. Les modifications sont réalisées directement en production.",
    ],
    '27001:2022-8.33' => [
        3 => 'Les données de test sont sélectionnées et protégées, les données de production étant anonymisées avant usage. Les jeux de test sont supprimés après utilisation.',
        2 => "Les données de test sont anonymisées pour la plupart des applications, mais pas pour l'application de facturation.",
        1 => 'Les données de production sont utilisées telles quelles pour les tests, sans aucune protection.',
    ],
    '27001:2022-8.34' => [
        3 => "Les audits et tests sur les systèmes en exploitation sont planifiés et validés pour limiter l'impact. Les accès des auditeurs sont limités en lecture et révoqués ensuite.",
        2 => 'Les tests sont planifiés, mais les accès accordés aux auditeurs ne sont pas révoqués à la fin de la mission.',
        1 => 'Les audits et tests sont réalisés sur les systèmes en exploitation sans planification ni encadrement.',
    ],
];
