# Délégation

Deming permet de déléguer la réalisation des contrôles de sécurité à des utilisateurs qui ont le rôle *audité*.
L'assignation du rôle audité à un utilisateur se fait via l'écran de [gestion des utilisateurs](config.fr.md#users).

Cette délégation respecte les règles suivantes :

- Les audités sont informés régulièrement par mail des contrôles à réaliser ;
- Les audités ne voient que les contrôles qui leur sont assignés et les contrôles qu'ils ont réalisés précédemment ;
- Les utilisateurs peuvent accepter ou refuser un contrôle réalisé par un audité ;
- Lorsqu'un contrôle est refusé, il retourne dans la liste des contrôles à réaliser de l'audité.


## Liste des contrôles à réaliser

Du point de vue de l'audité, le tableau de bord de la page d'accueil ne tient compte que des contrôles qui lui sont
assignés, et le menu « Contrôles » ouvre la liste de ces contrôles.

[![Screenshot](images/d1.fr.png)](images/d1.fr.png)

L'audité peut :

- Effectuer des recherches dans la liste des contrôles
- Filtrer les contrôles par domaine, clause, périmètre ou période
- Trier la liste par chacune des colonnes
- Sélectionner un contrôle à réaliser


## Réaliser un contrôle

Lorsqu'un audité réalise un contrôle, il peut :

- Sauver le contrôle

- Faire le contrôle

[![Screenshot](images/d2.fr.png)](images/d2.fr.png)

Lorsqu'il clique sur :

- "Faire", les modifications qu'il a faite sont sauvées et le contrôle passe dans l'état à valider. Du point de vue de l'audité, le contrôle se trouve dans la liste des contrôles réalisés.

- "Sauver", les modifications qu'il a faites sont sauvées, le contrôle reste dans la liste des contrôles à réaliser

- "Annuler", les modifications ne sont pas sauvées, l'utilisateur retourne vers la vue du contrôle.


## Accepter / Refuser un contrôle

Une fois qu'un contrôle a été réalisé par un audité, il passe dans l'état "à valider".

Cela se matérialise pour les autres utilisateurs par un sablier à côté de la date de planification dans la liste des
contrôles planifiés dans les 30 prochains jours de la page d'accueil, et à côté de la date de réalisation dans la
[liste des contrôles](controls.fr.md#list) :

[![Screenshot](images/d3.fr.png)](images/d3.fr.png)

Lorsque l'utilisateur clique sur le sablier, il arrive sur le contrôle réalisé par l'audité.

Il peut alors accepter ou refuser le contrôle en ajoutant une note dans les observations du contrôle et, selon les résultats du contrôle, proposer un plan d'action et une date de revue du contrôle.

[![Screenshot](images/d4.fr.png)](images/d4.fr.png)

Si l'utilisateur clique sur :

- "Accepter" : les données modifiées sont sauvées et un nouveau contrôle est créé à la date de planification introduite.

- "Rejeter" : les données modifiées sont sauvées et le contrôle est renvoyé dans la liste des contrôles à réaliser de l'audité.

- "Annuler" : l'utilisateur revient à la vue du contrôle.
