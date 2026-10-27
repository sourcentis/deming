# Contrôles

## Liste des contrôles {#list}

Cet écran permet d'afficher la liste des contrôles et de les filtrer par :

* domaine,

* clause,

* périmètre,

* période de planification,

* état du contrôle : tous, fait et à faire.

Le bouton « Colonnes » permet de choisir les colonnes affichées, et les boutons « Copier » et « Exporter »
permettent de copier la liste affichée ou de l'exporter vers Excel.

[![Screenshot](images/c1.fr.png)](images/c1.fr.png)

Lorsque vous cliquez sur :

* Le domaine, vous arrivez à l'écran d'[affichage du domaine de sécurité](config.fr.md/#domains)

* La clause, vous arrivez à l'écran d'[affichage de la mesure de sécurité](measures.fr.md/#show).

* La date de réalisation, de planification ou la date du contrôle suivant, vous arrivez l'écran d'[affichage du contrôle de sécurité](controls.fr.md/#show).

## Afficher un contrôle {#show}

Cet écran contient les informations d'un contrôle :

* Le nom du contrôle ;

* L'objectif du contrôle ;

* Les attributs ;

* Les données ;

* Le modèle ;

* La dates de planification, de réalisation et la date du contrôle suivant ;

* La note attribuée au contrôle ; et

* Le score attribué au contrôle (vert, orange ou rouge).

Les boutons « Faire » et « Planifier » sont présents si ce contrôle n'a pas encore été réalisé.

Les boutons « Modifier », « Copier », « Supprimer » et « Historique » sont présents si l'utilisateur est administrateur.

[![Screenshot](images/c2.fr.png)](images/c2.fr.png)

Lorsque vous cliquez sur :

* « Faire », vous êtes envoyé vers l'[écran de réalisation d'un contrôle](#make)

* « Planifier », vous êtes envoyé vers l'[écran de planification d'un contrôle](#plan)

* « Modifier », vous êtes envoyé vers l'[écran de modification du contrôle](#edit)

* « Copier », vous êtes envoyé vers l'[écran de création d'un contrôle](#create), pré-rempli avec les données de ce contrôle

* « Supprimer », le contrôle est supprimé et vous êtes envoyé vers la [liste des contrôles](#list)

* « Historique », vous êtes envoyé vers l'[historique des modifications](config.fr.md#logs) de ce contrôle

* « Annuler », vous êtes envoyé vers la [liste des contrôles](#list)

Lorsque l'[agent IA](ai.fr.md) est activé, l'icône ✦ « Analyse IA », en haut à droite de l'écran sous le bouton
d'aide, ouvre un panneau d'analyse du contrôle par l'IA. Voir [Utiliser l'analyse](ai.fr.md#use).

## Créer un contrôle {#create}

Un contrôle peut être créé de trois façons :

* Depuis une mesure de sécurité : le bouton « Planifier » de l'[écran d'affichage de la mesure](measures.fr.md#plan)
  crée un nouveau contrôle pré-rempli avec les données de la mesure.

* En copiant un contrôle : le bouton « Copier » de l'[écran d'affichage du contrôle](#show) ouvre le formulaire de
  création pré-rempli avec les données de ce contrôle.

* Manuellement : depuis la [liste des contrôles](#list), le bouton « Nouveau » ouvre le formulaire de création.

[![Screenshot](images/c4.fr.png)](images/c4.fr.png)

Cet écran contient les champs suivants :

* La ou les clauses associées ;

* Le nom du contrôle ;

* Le périmètre ;

* L'objectif du contrôle ;

* Les attributs ;

* Les données à collecter ;

* Le modèle de calcul du score ;

* Le plan d'action à appliquer si le contrôle est en échec ;

* La date de planification et la périodicité et

* Les responsables de la réalisation du contrôle.

Lorsque vous cliquez sur :

* « Sauver », le contrôle est enregistré et vous êtes renvoyé vers la [liste des contrôles](#list)

* « Annuler », vous êtes renvoyé vers la [liste des contrôles](#list)

## Modifier un contrôle {#edit}

Cet écran permet de modifier les informations d'un contrôle existant.

Il est accessible uniquement aux utilisateurs disposant du rôle **Administrateur**.

En plus des champs de l'[écran de création](#create), il contient le résultat du contrôle : date de réalisation,
observations, preuves, note, score et indicateur.

Lorsque vous cliquez sur :

* « Sauver », les modifications sont enregistrées et vous êtes renvoyé vers l'[écran d'affichage du contrôle](#show)

* « Annuler », vous êtes renvoyé vers l'[écran d'affichage du contrôle](#show)

## Planifier un contrôle {#plan}

Cet écran permet de planifier un contrôle.

Cet écran contient les informations d'un contrôle :

* Le nom du contrôle ;

* L'objectif du contrôle ;

* La date de planification ;

* La périodicité ; et

* Les responsables de la réalisation du contrôle.

[![Screenshot](images/c5.fr.png)](images/c5.fr.png)

Lorsque vous cliquez sur :

* « Plan », la date de planification, la récurrence et les responsables sont mis à jour et vous êtes renvoyés vers l'[écran d'affichage du contrôle](controls.fr.md/#show)

* « Annuler », vous êtes renvoyés vers l'[écran d'affichage du contrôle](controls.fr.md/#show)


## Réaliser un contrôle {#make}

Cet écran permet de réaliser un contrôle de sécurité.

Cet écran contient :

* Le ou les clauses,

* Le nom du contrôle,

* L'objectif,

* Les données,

* La date de réalisation, la date de planification,

* Les observations du contrôle,

* Une zone pour sauvegarder les preuves (**CTRL+V** permet de coller un fichier ou une capture d'écran),

* Un lien permettant de télécharger la fiche de contrôles,

* Le modèle de calcul appliqué,

* La note,

* Le score,

* Le plan d'action,

* La date du prochaine contrôle

[![Screenshot](images/c3.fr.png)](images/c3.fr.png)

Lorsque vous cliquez sur :

* « Faire », le contrôle est sauvé et un nouveau contrôle est créé à la date planifiée

* « Sauver », le contrôle est sauvé

et vous revenez vers la [liste des contrôles](#list).


## Fiche de contrôle {#sheet}

La fiche de contrôle est un document Word généré par l'application sur base des données du contrôle.

Cette fiche permet de décrire les observations réalisées, d'ajouter des captures d'écran ou des tableaux et de __signer électroniquement__ les observations.

[![Screenshot](images/report3.png)](images/report3.png)

La fiche de contrôle peut être [adaptée](config.fr.md/#documents) afin de respecter le modèle de document de votre organisation.
