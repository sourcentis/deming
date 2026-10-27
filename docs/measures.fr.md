# Mesures de sécurité

Cette partie permet de définir, de modifier et de planifier de nouvelles mesures de sécurité.

## Liste des mesures de sécurité <a name="list"></a>

La liste des mesures de sécurité permet d’afficher la liste des mesures, de les filtrer par domaine ou de rechercher une mesure sur base d’une partie de son nom.
Les boutons « Colonnes », « Copier » et « Exporter » permettent de choisir les colonnes affichées, de copier la liste ou de l'exporter vers Excel.

[![Screenshot](images/m1.fr.png)](images/m1.fr.png)

En cliquant sur :

* le domaine, vous arrivez sur la [définition du domaine choisi](config.fr.md/#domains)

* la clause, vous arrivez sur la [description de la mesure de sécurité](#show)

* le nombre de contrôle, vous arrivez sur la [liste des contrôles](controls.fr.md/#list) de cette mesure de sécurité

* le bouton « Planifier », vous arrivez sur la [planification de la mesure de sécurité](#plan)

Le bouton « Nouveau » permet de [créer une nouvelle mesure de sécurité](#create).

## Afficher une mesure de sécurité <a name="show"></a>

Cet écran permet d’afficher une mesure de sécurité.

[![Screenshot](images/m2.fr.png)](images/m2.fr.png)

Une mesure de sécurité est composée :

* d’un domaine ;

* d'une clause ;

* d’un nom ;

* d’un objectif de sécurité ;

* d’attributs ;

* des données de mesures ;

* du modèle de vérification ;

* d’un indicateur (vert, orange, rouge) ; et

* d’un plan d’action à appliquer si le contrôle de cette mesure de sécurité est en échec.

L’écran liste également les contrôles de cette mesure de sécurité (périmètre, date de planification, date de
réalisation et score), ainsi que les [correspondances](crosswalk.fr.md) de cette mesure avec les mesures d’autres
référentiels.

Lorsque vous cliquez sur :

* « Planifier » : vous arrivez vers l’écran de [planification de la mesure de sécurité](#plan).

* « Modifier » : vous arrivez vers [l’écran de modification de la mesure](#edit)

* « Copier » : vous arrivez vers l’écran de [création d’une mesure de sécurité](#create), pré-rempli avec les données de cette mesure

* « Supprimer »: vous permet de supprimer la mesure et de revenir à [la liste des mesures de sécurité](#list)

* « Annuler » : vous renvoie vers la [liste des mesures de sécurité](#list)

Lorsque l'[agent IA](ai.fr.md) est activé, l'icône robot « Analyse IA », en haut à droite de l'écran sous le bouton
d'aide, ouvre un panneau d'analyse de la mesure de sécurité par l'IA. Voir [Utiliser l'analyse](ai.fr.md#use).

## Créer une mesure de sécurité {#create}

Cet écran permet d’ajouter une mesure de sécurité à un référentiel. Il est accessible par le bouton « Nouveau » de
la [liste des mesures de sécurité](#list) ou par le bouton « Copier » de l’écran d’[affichage d’une mesure](#show).

[![Screenshot](images/m4.fr.png)](images/m4.fr.png)

L’écran contient les mêmes champs que l’[affichage d’une mesure de sécurité](#show) : domaine, clause, nom, objectif
de sécurité, attributs, données de mesure, modèle de vérification, indicateur et plan d’action.

Lorsque vous cliquez sur :

* « Sauver », la mesure de sécurité est créée et vous revenez à la [liste des mesures de sécurité](#list).

* « Annuler », vous revenez à la [liste des mesures de sécurité](#list).

Les mesures de sécurité peuvent aussi être importées depuis un tableur ou depuis un référentiel du dépôt, via
l’écran d’[importation](config.fr.md#import).


## Modifier une mesure de sécurité <a name="edit"></a>

Cet écran permet de modifier une mesure de sécurité.

[![Screenshot](images/m3.fr.png)](images/m3.fr.png)

Lorsque vous cliquez sur :

* « Sauver », les modifications sont sauvées et vous revenez à l’écran d’[affichage de la mesure de sécurité](#show).

* « Annuler », vous revenez à l’écran d’[affichage de la mesure de sécurité](#show).


## Planifier un contrôle <a name="plan"></a>

Cet écran permet de planifier un contrôle de sécurité à partir d’une mesure.

L’écran contient :

* Une ou plusieurs clauses ;

* Le nom de la mesure ;

* L’objectif de sécurité ;

* La date de planification ;

* Le périmètre ;

* La périodicité du contrôle et

* Les responsables de la réalisation du contrôle.

[![Screenshot](images/plan.fr.png)](images/plan.fr.png)

Lorsque vous cliquez sur :

* « Planifier », un nouveau contrôle est créé pour le périmètre indiqué en copiant toutes les données de la mesure,
  et est planifié à la date spécifiée. Vous revenez ensuite à la [liste des mesures de sécurité](#list).

* « Annuler », vous revenez vers la [liste des mesures de sécurité](#list).
