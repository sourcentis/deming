# Plans d'action

Chaque fois qu’un contrôle est en échec (orange ou rouge), un plan d’action doit être mis en place et suivi afin de remédier à la situation.

## Liste des plans d’action <a name="list"></a>

Cet écran permet de suivre les plans d’action :

[![Screenshot](images/a1.fr.png)](images/a1.fr.png)

La liste peut être filtrée par type (majeur, mineur, observation, opportunité), par statut (ouvert, fermé, tous) et par périmètre.
Elle affiche pour chaque plan d’action sa référence, son type, les risques associés, son statut, son avancement,
son nom, son périmètre, ses responsables et sa date d’échéance. Un clic sur un risque permet de l’afficher. Les dates d’échéance dépassées sont affichées en rouge.

Le bouton « Colonnes » permet d’afficher des colonnes supplémentaires (clauses associées, remédiation),
et les boutons « Copier » et « Exporter » permettent de copier la liste affichée ou de l’exporter vers Excel.

En cliquant sur la référence d’un plan d’action, vous arrivez sur [ce plan d’action](#show). Le bouton « Nouveau » permet de [créer un nouveau plan d’action](#create).

Un plan d’action peut aussi être créé lors de la [réalisation d’un contrôle](controls.fr.md#make), en cochant
« Créer un plan d’action ».

## Créer un plan d’action {#create}

Cet écran permet de créer manuellement un plan d’action.

[![Screenshot](images/a3.fr.png)](images/a3.fr.png)

L’écran contient :

* la référence du plan d’action ;

* son type : majeur, mineur, observation ou opportunité ;

* son nom ;

* le périmètre et les clauses associées ;

* les risques associés ;

* la cause ;

* la remédiation à mettre en œuvre ;

* la date d’échéance et

* les responsables du plan d’action.

Lorsque vous cliquez sur :

* « Sauver », le plan d’action est créé et vous revenez à la [liste des plans d’action](#list)

* « Annuler » vous revenez à la [liste des plans d’action](#list)

## Afficher un plan d’action {#show}

Cet écran affiche un plan d’action : référence, type, statut, périmètre, clauses, risques, nom, cause, date
d’échéance, responsables et, pour un plan d’action clôturé, sa date de clôture et sa justification. Les clauses et
les risques ne sont affichés que si le plan d’action y est lié. Les clauses, les risques et le contrôle à l’origine
du plan d’action sont accessibles depuis cet écran.

[![Screenshot](images/a4.fr.png)](images/a4.fr.png)

Tant que le plan d’action est ouvert, la remédiation et l’avancement (curseur) peuvent être mis à jour directement.

Lorsque vous cliquez sur :

* « Sauver », la remédiation et l’avancement sont sauvés et vous revenez à la [liste des plans d’action](#list)

* « Fermer », vous arrivez sur l’écran de [clôture du plan d’action](#close) (plans d’action ouverts uniquement)

* « Modifier », vous arrivez sur l’écran de [modification du plan d’action](#edit)

* « Supprimer », le plan d’action est supprimé après confirmation

* « Annuler » vous revenez à la [liste des plans d’action](#list)

Lorsque l'[agent IA](ai.fr.md) est activé, l'icône ✦ « Analyse IA », en haut à droite de l'écran sous le bouton
d'aide, ouvre un panneau d'analyse du plan d'action par l'IA. Voir [Utiliser l'analyse](ai.fr.md#use).

## Clôturer un plan d’action {#close}

Cet écran permet de clôturer un plan d’action une fois la remédiation réalisée, ou de le rejeter.

L’écran rappelle les informations du plan d’action et contient :

* le nouveau statut : ouvert, fermé ou rejeté ;

* la date de clôture et

* la justification de la clôture.

Lorsque vous cliquez sur :

* « Sauver », le statut, la date de clôture et la justification sont sauvés et vous revenez à la [liste des plans d’action](#list)

* « Annuler » vous revenez au [plan d’action](#show)

## Modifier un plan d’action <a name="edit"></a>

Cet écran permet de modifier l’ensemble des informations d’un plan d’action : référence, type, nom, périmètre,
clauses, risques, cause, remédiation, date d’échéance, responsables, avancement, statut, date de clôture et justification.

[![Screenshot](images/a2.fr.png)](images/a2.fr.png)

Lorsque vous cliquez sur :

* « Sauver », vous sauvez le plan d’action et revenez à la [liste des plans d’action](#list)

* « Annuler » vous revenez à la [liste des plans d’action](#list)
