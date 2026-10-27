# Configuration

Le menu « Configuration » donne accès aux attributs, aux domaines, aux utilisateurs, aux groupes, à la
[classification des risques](risks.fr.md#scoring), à l'importation, aux [correspondances entre référentiels](crosswalk.fr.md),
aux documents, aux notifications et aux logs.

## Attributs {#tags}

Cet écran permet de gérer les attributs associés aux mesures de sécurité.
Il contient la liste des attributs et permet de créer, supprimer ou modifier des listes d’attributs.

[![Screenshot](images/tags.fr.png)](images/tags.fr.png)

Les administrateurs disposent d'un bouton « Gérer » qui ouvre un formulaire permettant de remplacer globalement une valeur d'attribut existante par une nouvelle valeur. Le remplacement s'applique partout où la valeur est utilisée : dans les listes d'attributs, dans les mesures de sécurité, ainsi que dans les contrôles qui n'ont pas encore été réalisés. Les contrôles déjà réalisés ne sont pas modifiés, car ils constituent un historique.

## Domaines {#domains}

Cet écran permet de créer, modifier ou supprimer des listes de domaines de sécurité.

[![Screenshot](images/domains.fr.png)](images/domains.fr.png)

L’application est fournie avec une base de mesures de sécurité inspirée de la norme ISO 27001:2022, mais il est possible de définir de nouveaux domaines de sécurité inspirés d’autres normes comme PCI DSS, HDS...

## Utilisateurs {#users}

Les utilisateurs sont définis dans l’application.

[![Screenshot](images/users.fr.png)](images/users.fr.png)

Un utilisateur est composé d'un identifiant, d'un nom, d'un titre, d'un rôle, d'une adresse mail, d'une langue,
d'un mot de passe et de la liste des contrôles qui lui sont assignés. La langue est utilisée pour l'interface, les
notifications et les documents générés.

Il existe six rôles différents :

* Administrateurs : L'administrateur de l’application peut créer de nouveaux utilisateur, des mesures, de nouveaux attributs, modifier des contrôles déjà réalisés.

* Utilisateurs : les utilisateurs peuvent utiliser l’application sans pouvoir modifier les mesures, les attributs et les contrôles déjà réalisés.

* Audité : les audités ne peuvent réaliser et voir que les contrôles qui leur sont assignés ou qu'ils ont réalisés
  précédemment (voir [Délégation](delegation.fr.md)).

* Auditeur : l’auditeur a un accès en lecture à l’ensemble des informations de l’application. L'auditeur ne peut pas utiliser l'[agent IA](ai.fr.md#access).

* API : compte utilisé pour accéder à l'[API](api.fr.md).

* Désactivé : le compte est conservé, afin de garder son historique, mais ne peut plus se connecter.

Le bouton « Historique » de l'écran d'affichage d'un utilisateur affiche l'[historique des modifications](#logs) de cet utilisateur.

## Groupes {#groups}

Cet écran permet de définir des groupes d'utilisateurs.
Un groupe permet de rassembler un ensemble d'utilisateurs et de contrôles.

[![Screenshot](images/groups.fr.png)](images/groups.fr.png)

Un groupe est composé :

* d'un nom de groupe

* d'un type : *Business* ou *Sécurité* (modifiable uniquement par un administrateur)

* d'une description du groupe

* d'une liste d'utilisateurs

* d'une liste de contrôles

Le type du groupe permet de distinguer les contrôles portés par l'équipe Sécurité de ceux portés par les autres
fonctions métier dans l'écran de [pilotage](dashboards.fr.md#steering).

Seuls les contrôles non encore réalisés sont proposés dans le formulaire ; les contrôles réalisés ou proposés
restent liés au groupe lors de sa modification.

## Rapports {#reports}

Cet écran est accessible aux administrateurs et aux utilisateurs. Il permet de générer le rapport de pilotage du
SMSI, la déclaration d'applicabilité et les exports de données.

[![Screenshot](images/reports.fr.png)](images/reports.fr.png)

**Rapport de pilotage du SMSI** : choisissez un référentiel (facultatif) et une période, puis cliquez sur « Créer ».
Un document Word est généré à partir du [modèle de rapport de pilotage](#documents).

Voici le rapport de pilotage du SMSI :

[![Screenshot](images/report1.png){: style="width:600px"}](images/report1.png)

[![Screenshot](images/report2.png){: style="width:600px"}](images/report2.png)

**Déclaration d'applicabilité** : génère un fichier Excel listant, pour chaque domaine et chaque clause, les mesures
de sécurité et la date de planification de leurs contrôles pour chaque périmètre.

**Exporter des données** : exporte dans des fichiers Excel les utilisateurs, les domaines, les attributs, les mesures
de sécurité, les contrôles, les plans d'action et le registre des risques.

## Importation {#import}

Il est possible d'importer des mesures de sécurité depuis un fichier .XLSX ou depuis la base de données de modèles.

Lors de l'importation, il est possible de supprimer tous les autres contrôles et mesures et de générer des données de test.

[![Screenshot](images/import.fr.png)](images/import.fr.png)

## Documents {#documents}

Cet écran permet de modifier la configuration de la gestion des documents utilisés dans Deming.

[![Screenshot](images/documents.fr.png)](images/documents.fr.png)

### Modèles

Cette partie de l'écran permet de modifier les modèles de document utilisés pour les fiches de contrôle et le rapport de pilotage du SMSI et permet d’avoir une vue sur l’ensemble des documents utilisés comme preuve lors de la réalisation des contrôles de sécurité.

Le modèle du rapport de pilotage est un document Word (.docx) dont les balises sont remplacées lors de la génération
du rapport. Le panneau « Balises du modèle », affiché à côté des modèles, liste les balises disponibles ; un clic sur
une balise la copie dans le presse-papiers.

* Variables : `${today}`, `${start_date}`, `${end_date}`, `${framework}`, ainsi que les tableaux prédéfinis
  `${control_table}`, `${kpi_table}`, `${made_control_table}` et `${action_plans_table}`.

* Tableaux personnalisés : `${table:<table>:<colonne>[=Entête],...[|option]}`, où `<table>` est l'une des tables
  `domains`, `controls`, `measures`, `actions`, `risks`, `exceptions`, `attributes`, `users` ou `user_groups`.
  La balise doit être seule dans son paragraphe. Les options disponibles sont `period:<colonne>` (lignes comprises
  dans la période du rapport), `where:<colonne>=<valeur>` (filtre, `!=` et `null` sont acceptés),
  `order:[-]<colonne>` (tri, `-` pour un tri décroissant) et `limit:<n>`.

Exemple :

    ${table:actions:reference,name,owners,due_date|where:status=0|order:due_date}

### Stockage

Cette partie de l'écran permet de vérifier l'intégrité des documents conservés dans l'application.

En cliquant sur « Vérifier », Deming lance le contrôle d'intégrité des documents conservés dans l'application en utilisant un CRC32.

### Conservation

Cette partie de l'écran permet de configurer la durée de conservation (en mois) des contrôles réalisés dans l'application.

Tous les contrôles réalisés passé cette date sont supprimés ainsi que les documents et plans d'actions associés.

Cette suppression est effectuée en exécutant la commande

    php artisan deming:cleanup

au travers d'un cron.

## IA {#ai}

Cet écran, réservé aux administrateurs, permet d'activer et de configurer l'[agent IA](ai.fr.md) qui analyse les
mesures de sécurité, les contrôles, les plans d'action et les risques : activation, URL de l'agent, modèle, clé API,
prompts, test de connexion et suppression de toutes les analyses. L'IA est désactivée par défaut.

Voir [Activer et configurer](ai.fr.md#config) et, avant toute activation, [Protection des données](ai.fr.md#privacy).

## Notifications {#notifications}

Cet écran permet de configurer les notifications envoyées aux utilisateurs lorsqu'ils doivent réaliser des contrôles.

L'écran contient :

* Le sujet du mail envoyé à l'utilisateur ;

* L'émetteur du mail ;

* La périodicité de l'envoi des notifications ;

* Les délais de notification.

[![Screenshot](images/config.fr.png)](images/config.fr.png)

Lorsque vous cliquez sur :

* « Sauver » - la configuration est sauvée ;

* « Test » - un mail de test est envoyé à l'utilisateur courant ;

* « Annuler » - vous revenez à la page principale.

## Logs {#logs}

Cet écran, accessible depuis le menu « Configuration », liste, du plus récent au plus ancien, toutes les
modifications apportées aux mesures de sécurité, aux contrôles, aux plans d'action, aux risques et aux utilisateurs :
action (création, modification, suppression), type et identifiant de l'objet, utilisateur, adresse IP et date.

En cliquant sur :

* l'identifiant d'une entrée, ou le bouton « Voir », vous affichez le détail de la modification, avec l'ensemble des
  données de l'objet à ce moment ;

* l'identifiant d'un objet, vous affichez cet objet ;

* le bouton « Historique », vous affichez l'historique des modifications de cet objet.

L'historique des modifications d'un objet est aussi accessible par le bouton « Historique » des écrans d'affichage
d'un [contrôle](controls.fr.md#show), d'un [risque](risks.fr.md#show) ou d'un [utilisateur](#users).

Les logs plus anciens que la [durée de conservation](#documents) sont supprimés par la commande `deming:cleanup`.
