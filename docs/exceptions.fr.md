# Exceptions

La gestion des exceptions permet de formaliser et de tracer les dérogations accordées lorsqu'un contrôle de sécurité ne peut pas être mis en œuvre conformément aux exigences. Une exception est liée à un contrôle non conforme et suit un workflow de validation par un administrateur.

## Liste des exceptions {#list}

Cet écran affiche la liste des exceptions et permet de les filtrer par :

* statut (Brouillon, Soumise, Approuvée, Refusée, Expirée) ;

* contrôle lié ;

* exceptions expirées uniquement.

[![Screenshot](images/ex1.fr.png)](images/ex1.fr.png)

Les dates de fin dépassées sont affichées en rouge.

En cliquant sur une ligne, vous arrivez à l'[écran d'affichage de l'exception](#show).

Le bouton « Nouveau » est accessible aux utilisateurs disposant du rôle **Administrateur** ou **Utilisateur**.

## Afficher une exception {#show}

Cet écran contient les informations d'une exception :

* Le nom de l'exception ;

* Le statut courant ;

* Le contrôle lié (avec un lien vers la [mesure de sécurité](measures.fr.md#show) correspondante) ;

* La description de l'exception ;

* La justification métier ;

* Les mesures compensatoires éventuelles ;

* La période de validité (date de début et date de fin) ;

* L'auteur de la création et la date de création ;

* L'auteur de la soumission et la date de soumission ;

* La décision (approbateur, date et commentaire).

[![Screenshot](images/ex2.fr.png)](images/ex2.fr.png)

Les boutons disponibles dépendent du statut de l'exception et du rôle de l'utilisateur :

| Bouton | Condition |
|---|---|
| « Soumettre » | Statut Brouillon — Administrateur ou Utilisateur |
| « Modifier » | Statut Brouillon ou Refusée — Administrateur ou Utilisateur |
| « Supprimer » | Administrateur uniquement |
| « Approuver » | Statut Soumise — Administrateur uniquement |
| « Refuser » | Statut Soumise — Administrateur uniquement |

Lorsque vous cliquez sur :

* « Soumettre », l'exception passe en statut *Soumise* et est envoyée à la validation d'un administrateur ;

* « Modifier », vous êtes renvoyé vers l'[écran de modification de l'exception](#edit) ;

* « Supprimer », l'exception est supprimée et vous êtes renvoyé vers la [liste des exceptions](#list) ;

* « Approuver », l'exception est acceptée et passe en statut *Approuvée* ;

* « Refuser », l'exception est rejetée (un motif est obligatoire) et passe en statut *Refusée* ;

* « Annuler », vous êtes renvoyé vers la [liste des exceptions](#list).

## Créer une exception {#create}

Depuis la [liste des exceptions](#list), le bouton « Nouveau » ouvre le formulaire de création.

[![Screenshot](images/ex3.fr.png)](images/ex3.fr.png)

Cet écran contient les champs suivants :

* Le nom de l'exception ;

* Le contrôle lié (mesure de sécurité non conforme à l'origine de la dérogation) ;

* La description de l'exception ;

* La justification métier (raison pour laquelle le contrôle ne peut pas être mis en œuvre) ;

* Les mesures compensatoires (contrôles alternatifs mis en place pour réduire le risque résiduel) ;

* La date de début et la date de fin de validité.

Une exception est créée en statut **Brouillon**. Elle doit être soumise explicitement pour être examinée par un administrateur.

Lorsque vous cliquez sur :

* « Sauver », l'exception est enregistrée et vous êtes renvoyé vers l'[écran d'affichage de l'exception](#show) ;

* « Annuler », vous êtes renvoyé vers la [liste des exceptions](#list).

## Modifier une exception {#edit}

Cet écran permet de modifier une exception en statut **Brouillon** ou **Refusée**. Un administrateur peut modifier une exception quel que soit son statut.

[![Screenshot](images/ex4.fr.png)](images/ex4.fr.png)

Une exception refusée et ré-éditée repasse automatiquement en statut *Brouillon* afin de pouvoir être soumise à nouveau après correction.

Cet écran contient les mêmes champs que l'[écran de création](#create). Un administrateur voit également le statut de l'exception et peut le modifier librement.

Lorsque vous cliquez sur :

* « Sauver », les modifications sont enregistrées et vous êtes renvoyé vers l'[écran d'affichage de l'exception](#show) ;

* « Annuler », vous êtes renvoyé vers l'[écran d'affichage de l'exception](#show).

## Workflow de validation {#workflow}

Le cycle de vie d'une exception suit les étapes suivantes :

```
Brouillon → Soumise → Approuvée
                  ↘ Refusée → (ré-édition) → Brouillon
Approuvée → Expirée  (automatique, si la date de fin est dépassée)
```

* **Brouillon** : l'exception est en cours de rédaction. Elle peut être modifiée ou soumise.

* **Soumise** : l'exception est en attente de décision. Elle ne peut plus être modifiée.

* **Approuvée** : la dérogation est accordée pour la période de validité définie.

* **Refusée** : la dérogation est rejetée. Le motif de refus est obligatoire et visible par le demandeur. L'exception peut être corrigée et soumise à nouveau.

* **Expirée** : une exception approuvée dont la date de fin est dépassée passe automatiquement en statut *Expirée*.

!!! note "Traçabilité"
    Chaque transition est horodatée et associée à l'utilisateur qui l'a effectuée. Ces informations sont conservées et visibles dans l'[écran d'affichage de l'exception](#show), ce qui permet de justifier la dérogation lors d'un audit ISO 27001.
