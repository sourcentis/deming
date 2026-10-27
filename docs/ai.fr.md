# Agent IA

Deming peut faire appel à un agent d'intelligence artificielle pour analyser une
[mesure de sécurité](measures.fr.md#show), un [contrôle](controls.fr.md#show), un
[plan d'action](actions.fr.md#show) ou un [risque](risks.fr.md#show). L'agent renvoie un avis structuré :
synthèse, statut évalué, niveau de risque, constats, recommandations, questions ouvertes et quatre points de vue
(CEO, RSSI, DPO et auditeur).

L'agent est une **aide à la décision** : il ne modifie jamais les données de Deming. Ses résultats doivent être
validés par un humain.

L'IA est **désactivée par défaut**. Tant qu'elle n'est pas activée par un administrateur, aucune donnée n'est
envoyée et l'icône d'analyse n'apparaît pas.

L'agent est un service compatible avec l'API OpenAI (`/v1/chat/completions`). Par défaut, Deming est configuré pour
[OVHcloud AI Endpoints](https://www.ovhcloud.com/fr/public-cloud/ai-endpoints/) avec le modèle `gpt-oss-120b`, mais
il est possible d'utiliser un autre fournisseur compatible ou un modèle local (Ollama, vLLM, LM Studio…).

## Activer et configurer {#config}

La configuration se trouve dans l'onglet « IA » de la page [Paramètres](config.fr.md#parameters) (menu « Configuration →
Paramètres »). Elle est réservée aux administrateurs.

<!-- TODO capture : images/ai-config.fr.png -->

L'écran contient un encart « Protection des données » qui rappelle ce qui est envoyé à l'agent ; lisez la section
[Protection des données](#privacy) avant d'activer l'IA.

Les champs sont les suivants :

* **Activer l'IA** : active ou désactive l'agent. Désactivée, aucune requête ne part vers l'agent, mais les analyses
  déjà enregistrées sont conservées (voir [Supprimer toutes les analyses](#purge)) ;

* **URL de l'agent IA** : l'adresse de base de l'API compatible OpenAI, par exemple
  `https://oai.endpoints.kepler.ai.cloud.ovh.net/v1`. Le protocole `https` est obligatoire, sauf pour un modèle local
  sur `http://localhost` ou `http://127.0.0.1` ;

* **Modèle** : le nom du modèle tel que l'attend le fournisseur, par exemple `gpt-oss-120b` ;

* **Clé API** : la clé d'accès au fournisseur. Elle n'est jamais réaffichée : l'écran indique seulement « Clé
  enregistrée » ou « Aucune clé ». Laisser le champ vide conserve la clé actuelle ; la case « Supprimer la clé » la
  retire. La clé est chiffrée en base de données avec la clé de l'application (`APP_KEY`) : si l'`APP_KEY` change,
  il faut ressaisir la clé ;

* **Prompts** : les instructions données à l'agent, une par type d'objet (mesure de sécurité, contrôle, plan
  d'action et risque). `{{langue}}` est remplacé par la langue de l'utilisateur. Le bouton « Restaurer le prompt par
  défaut » rétablit le prompt fourni avec Deming ;

* **Format de réponse : fixe** : le schéma JSON que l'agent doit respecter, commun aux quatre types. Il est ajouté
  automatiquement à chaque prompt et sert à valider la réponse. Il ne peut pas être modifié.

Le bouton « Tester la connexion » envoie une requête minimale à l'agent, avec la configuration enregistrée, et
affiche le statut HTTP, la latence et le modèle. La clé n'est jamais affichée.

### Créer une clé OVHcloud AI Endpoints

1. Disposer d'un projet Public Cloud OVHcloud avec un moyen de paiement. Les projets en mode « Discovery » ne
   fonctionnent pas.
2. Dans l'espace client, ouvrir « AI & Machine Learning → AI Endpoints », puis « Créer une nouvelle clé API ».
3. Copier la clé et la coller dans le champ « Clé API » de Deming. La clé n'est affichée qu'une seule fois.

Pour un simple essai, AI Endpoints accepte aussi un accès anonyme, sans clé, limité à 2 requêtes par minute.

### Utiliser un modèle local

Avec un serveur local comme Ollama, vLLM ou LM Studio, renseignez une URL du type `http://localhost:11434/v1` et le
nom du modèle installé. Le serveur doit exposer l'API `/chat/completions` compatible OpenAI, et le modèle doit savoir
produire du JSON. Avec un modèle local, aucune donnée ne quitte votre infrastructure.

### Supprimer toutes les analyses {#purge}

Le bouton « Supprimer toutes les analyses IA », en bas de l'onglet, efface la dernière analyse enregistrée sur
chaque mesure de sécurité, contrôle, plan d'action et risque, après confirmation. L'opération est tracée dans les
[logs](config.fr.md#logs) (utilisateur et date, sans contenu).

## Utiliser l'analyse {#use}

Lorsque l'IA est activée, une icône robot « Analyse IA » apparaît en haut à droite des écrans d'affichage d'une
[mesure de sécurité](measures.fr.md#show), d'un [contrôle](controls.fr.md#show), d'un
[plan d'action](actions.fr.md#show) et d'un [risque](risks.fr.md#show), sous le bouton d'aide. Un point vert sur
l'icône indique qu'une analyse est déjà enregistrée.

Un clic sur l'icône ouvre un panneau à droite de l'écran. Si l'objet et ses objets liés n'ont pas changé depuis la
dernière analyse, celle-ci est affichée immédiatement, sans appel à l'agent, avec la mention « Analyse du … —
inchangée depuis ». Sinon, une nouvelle analyse est lancée ; elle peut prendre jusqu'à une minute.

<!-- TODO capture : images/ai-panel.fr.png -->

Le panneau contient :

* le **statut évalué**, le **niveau de risque** et la **confiance** de l'agent dans sa propre analyse ;

* la **synthèse** ;

* les **constats** : forces, faiblesses, manques de preuve et incohérences ;

* les **recommandations**, avec leur priorité, leur justification, une échéance suggérée et une référence
  normative ;

* les **questions ouvertes**, s'il y en a ;

* quatre **points de vue**, volontairement contrastés : le CEO (enjeux business et coûts), le RSSI (lecture du risque
  et priorités), le DPO (données personnelles et RGPD) et l'auditeur (exigences normatives et preuves).

La signification du statut dépend du type d'objet :

| Statut | Mesure de sécurité / Contrôle | Plan d'action | Risque |
|---|---|---|---|
| `conforme` | Conforme | Pertinent et en bonne voie | Traitement adéquat |
| `partiellement_conforme` | Partiellement conforme | À renforcer | Traitement partiel |
| `non_conforme` | Non conforme | Inadapté ou à l'arrêt | Traitement inadéquat |
| `non_evaluable` | Non évaluable | Non évaluable | Non évaluable |

Le bouton « Relancer l'analyse » force une nouvelle analyse, même si rien n'a changé. Le pied du panneau rappelle que
l'analyse est générée par une IA et doit être validée par un humain, et indique le modèle et l'hôte de l'agent
auquel les données ont été envoyées. Le lien « Données envoyées » affiche le [contexte transmis](#preview).

Seule la dernière analyse de chaque objet est conservée. Une nouvelle analyse est lancée automatiquement lorsque
l'objet, ses objets liés, le prompt, le modèle ou la langue de l'utilisateur changent.

## Qui peut lancer une analyse {#access}

On peut analyser un objet si l'on peut l'afficher, sauf avec le rôle auditeur ou API. Les droits sont vérifiés par
le serveur à chaque demande.

| Rôle | Mesure de sécurité | Contrôle | Plan d'action | Risque |
|---|---|---|---|---|
| Administrateur | ✓ | ✓ | ✓ | ✓ |
| Utilisateur | ✓ (selon affectation et vue groupe) | ✓ (idem) | ✓ | ✓ |
| Audité | ✓ si assigné | ✓ si assigné | — | ✓ (tous les risques) |
| Auditeur | — | — | — | — |
| API | — | — | — | — |

Précisions :

* en vue groupe, une mesure de sécurité n'est accessible que si l'utilisateur est assigné directement à l'un de ses
  contrôles ; un contrôle est accessible si l'utilisateur y est assigné directement ou par l'un de ses
  [groupes](config.fr.md#groups) ;

* l'audité n'a pas accès aux plans d'action, ni à leur analyse ;

* l'audité peut afficher, et donc analyser, tous les risques ;

* l'auditeur n'a accès ni à l'icône, ni au panneau, ni aux analyses enregistrées, même pour un objet qu'il peut
  afficher.

## Protection des données {#privacy}

Cette section décrit précisément ce qui sort de Deming lors d'une analyse. Elle s'adresse en particulier au RSSI et
au DPO.

### Données envoyées à l'agent

Chaque analyse envoie à l'agent :

* le **prompt** du type d'objet et le **schéma de réponse** ;

* la **date du jour** ;

* le **contexte** de l'objet analysé, décrit ci-dessous, après [masquage](#redaction).

Les champs texte longs de l'objet analysé sont limités à 4 000 caractères ; ceux des objets liés (observations,
justifications…) à 300 caractères.

**Mesure de sécurité** : clause, nom, domaine (référentiel et titre), objectif, attributs, données d'entrée, modèle,
indicateur, plan d'action, périodicité, les 5 derniers contrôles réalisés (date de réalisation, score, observations
et plan d'action résumés), les 5 prochains contrôles planifiés (date planifiée, statut, en retard ou non), les plans
d'action ouverts liés (référence, nom, type, criticité, statut, avancement, échéance, en retard ou non), les risques
liés (nom, niveau, statut de traitement), les exceptions (nom, statut, dates de début et de fin, expirée ou non,
justification et mesures compensatoires résumées) et jusqu'à 10 correspondances avec d'autres référentiels
(référentiel, clause, nom, type et couverture).

**Contrôle** : nom, périmètre, mesures de sécurité couvertes (clause et nom), objectif, attributs, données d'entrée,
modèle, indicateur, périodicité, statut, date planifiée, date de réalisation, en retard ou non, score, note,
observations, plan d'action, documents (**nombre et noms de fichiers uniquement**), plans d'action issus du contrôle
(référence, nom, type, criticité, statut, avancement, échéance, en retard ou non) et les 5 derniers contrôles réalisés
de la même mesure de sécurité (date de réalisation, score, observations et plan d'action résumés).

**Plan d'action** : référence, nom, type, criticité, statut, périmètre, cause, remédiation, dates de création,
d'échéance et de clôture, avancement, justification, en retard ou non, **nombre** de responsables, mesures de
sécurité liées (clause, nom, date et score du dernier contrôle), risques liés (nom, niveau, statut de traitement) et
les 5 dernières étapes de progression (date et pourcentage, sans leur auteur).

**Risque** : nom, description, probabilité et impact (valeur, libellé et commentaire), exposition et vulnérabilité
(valeur et libellé), risque résiduel, méthode de scoring active (nom, formule et seuils), score et niveau calculés,
statut de traitement et son commentaire, fréquence de revue, date de prochaine revue, revue en retard ou non,
**présence** d'un propriétaire (oui ou non), besoin de mesures ou d'actions selon le statut, mesures de sécurité liées
(clause, nom, date et score du dernier contrôle) et plans d'action liés (référence, nom, criticité, avancement,
échéance, clôturé ou non, en retard ou non).

### Données jamais envoyées

* les noms, logins et adresses e-mail des utilisateurs de Deming ;

* l'identité des responsables d'un plan d'action et du propriétaire d'un risque ;

* le contenu des documents (seuls leurs noms sont envoyés) ;

* les journaux d'audit ;

* la configuration de Deming et la clé API ;

* les objets qui ne sont pas liés à l'objet analysé.

### Masquage automatique {#redaction}

Avant l'envoi, tous les textes du contexte sont filtrés. Sont remplacés :

* les adresses e-mail par `[email]` ;

* les numéros de téléphone (formats internationaux, belges, luxembourgeois, français et allemands) par
  `[téléphone]` ;

* les IBAN par `[iban]` ;

* les adresses IP (IPv4 et IPv6) par `[ip]` ;

* le nom, le login et l'adresse e-mail de chaque utilisateur de Deming, en mots entiers et sans tenir compte de la
  casse, par `[utilisateur]`. Les valeurs de moins de trois caractères sont ignorées pour éviter les faux positifs.

!!! warning "Limite importante — champs texte libres"
    Le masquage n'est **pas** une anonymisation. Il ne détecte pas tout : un nom de patient, de client ou de
    collaborateur externe saisi dans des observations, une description ou une justification **sera transmis** à
    l'agent. Ne saisissez pas de données personnelles dans les champs texte de Deming, et rappelez cette consigne aux
    utilisateurs.

### Aperçu des données envoyées {#preview}

Le lien « Données envoyées », en bas du panneau d'analyse, affiche le contexte exactement tel qu'il est envoyé à
l'agent, après minimisation et masquage (sans le prompt). Cet aperçu n'appelle pas l'agent : il peut être consulté
avant ou après une analyse pour vérifier ce qui part réellement. Il est soumis aux mêmes droits que l'analyse.

<!-- TODO capture : images/ai-preview.fr.png -->

### Où vont les données

Les données sont envoyées uniquement à l'hôte configuré dans l'URL de l'agent, affiché en bas du panneau. Deming ne
suit aucune redirection vers un autre hôte, et le protocole `https` est obligatoire, avec vérification du certificat,
sauf pour un modèle local.

Avec OVHcloud AI Endpoints, les données sont traitées par OVHcloud dans ses centres de données européens. Pour la
conservation et la réutilisation éventuelle des requêtes, reportez-vous aux
[conditions contractuelles d'OVHcloud](https://www.ovhcloud.com/fr/terms-and-conditions/contracts/), en particulier
aux conditions particulières d'AI Endpoints et au contrat de sous-traitance (DPA). Pour un autre fournisseur,
reportez-vous à ses propres conditions.

Avec un modèle local, **aucune donnée ne quitte votre infrastructure**.

### Ce que Deming conserve

* Uniquement la **dernière analyse** de chaque objet, enregistrée dans l'objet lui-même. Elle est supprimée avec
  l'objet ou par le bouton « [Supprimer toutes les analyses IA](#purge) ».

* Les analyses n'apparaissent ni dans l'[API REST](api.fr.md), ni dans les exports, ni dans les rapports, et ne sont
  pas enregistrées dans les [logs](config.fr.md#logs) : une analyse n'est pas une modification de l'objet.

* Les logs applicatifs ne contiennent que des métadonnées : type et identifiant de l'objet, modèle, durée, nombre de
  tokens et statut. Ni le prompt, ni le contexte, ni la réponse, ni la clé n'y figurent.

* La clé API est chiffrée avec l'`APP_KEY` de l'application ; changer l'`APP_KEY` impose de ressaisir la clé.

### Sécurité de la fonction

* L'analyse est soumise aux mêmes droits que l'affichage de l'objet ; l'auditeur et le rôle API en sont exclus
  (voir [Qui peut lancer une analyse](#access)).

* Les requêtes sont protégées contre le CSRF et limitées à 10 par minute.

* Les réponses de l'IA sont affichées comme du texte, sans interprétation HTML.

* Les données de l'objet sont présentées à l'agent comme des données, jamais comme des instructions : une tentative
  d'injection dans un champ texte est signalée comme une incohérence.

* L'IA ne peut rien modifier dans Deming.

### Avant d'activer l'IA

Points à vérifier par le responsable de traitement. Ces recommandations ne constituent pas un avis juridique.

- [ ] Consulter le DPO.
- [ ] Mettre à jour le registre des activités de traitement (RGPD, article 30) si des données personnelles peuvent
      être transmises.
- [ ] Disposer d'un contrat de sous-traitance (RGPD, article 28) avec le fournisseur de l'agent, ou utiliser un
      modèle local.
- [ ] Pour un autre fournisseur qu'OVHcloud, vérifier la localisation des traitements et l'absence de transfert hors
      de l'Union européenne.
- [ ] Évaluer le besoin d'une analyse d'impact (AIPD), en particulier dans les secteurs traitant des données
      sensibles comme la santé.
- [ ] Informer les utilisateurs de Deming de l'existence de la fonction et de la consigne sur les textes libres.
- [ ] Garder à l'esprit que les résultats sont une aide à la décision validée par un humain : aucune décision n'est
      prise automatiquement, et l'agent ne traite pas de décisions concernant des personnes.

## Limites {#limits}

* L'IA peut se tromper, mal interpréter une donnée ou omettre un point important.

* Les références normatives citées doivent être vérifiées.

* Les résultats ne sont pas strictement reproductibles : deux analyses des mêmes données peuvent différer.

* Le coût et le temps de réponse dépendent du fournisseur et du modèle.

* La qualité de l'analyse dépend de la qualité des données saisies dans Deming.
