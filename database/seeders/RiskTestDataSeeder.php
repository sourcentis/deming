<?php

namespace Database\Seeders;

use App\Models\Action;
use App\Models\Exception;
use App\Models\Risk;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Jeu de données de test (français ou anglais, plausible) pour les risques,
 * les plans d'action, les exceptions, les groupes d'utilisateurs et la
 * délégation de contrôles à un audité.
 *
 * Supprime intégralement les données existantes de ces domaines
 * (et leurs tables pivot) avant de recréer un jeu cohérent.
 */
class RiskTestDataSeeder extends Seeder
{
    /** @var string 'fr' ou 'en' */
    private string $locale = 'fr';

    /** @var array<int, int> id des mesures de sécurité présentes en base */
    private array $controlIds = [];

    public function run(string $locale = 'fr'): void
    {
        $this->locale = in_array($locale, ['fr', 'en'], true) ? $locale : 'fr';

        $this->ensureUsers();

        // Les liens ci-dessous référencent des mesures de sécurité par id fixe :
        // on ignore celles qui n'existent pas (référentiel absent ou différent).
        $this->controlIds = DB::table('controls')->pluck('id')->all();

        $this->wipe();

        $risks = $this->seedRisks();
        $actions = $this->seedActions();
        $this->seedExceptions();
        $this->seedDelegation();
        $this->seedGroups();

        // Liens risques <-> contrôles
        $risks['fuite_cloud']->controls()->sync($this->controls([26, 78]));
        $risks['datacenter']->controls()->sync($this->controls([32, 33, 80]));
        $risks['comptes_privileges']->controls()->sync($this->controls([68, 71]));
        $risks['detection_intrusion']->controls()->sync($this->controls([81, 82]));
        $risks['prestataire']->controls()->sync($this->controls([23]));
        $risks['rancongiciel']->controls()->sync($this->controls([73, 79]));

        // Liens risques <-> plans d'action
        $risks['comptes_privileges']->actions()->sync([$actions['mfa']->id]);
        $risks['rancongiciel']->actions()->sync([$actions['edr']->id, $actions['segmentation']->id]);
        $risks['fuite_cloud']->actions()->sync([$actions['cloud']->id]);
        $risks['rgpd_rh']->actions()->sync([$actions['rgpd']->id]);
        $risks['datacenter']->actions()->sync([$actions['pra']->id]);

        // Liens plans d'action <-> contrôles
        $actions['mfa']->controls()->sync($this->controls([68, 71]));
        $actions['cloud']->controls()->sync($this->controls([26]));

        // Liens plans d'action <-> responsables
        $actions['mfa']->owners()->sync([1]);
        $actions['edr']->owners()->sync([6]);
        $actions['segmentation']->owners()->sync([1]);
        $actions['cloud']->owners()->sync([2]);
        $actions['rgpd']->owners()->sync([5]);
        $actions['pra']->owners()->sync([1, 6]);
    }

    /**
     * Les jeux de données ci-dessous référencent des utilisateurs par id fixe
     * (owner_id, created_by, approved_by, ...). On crée ici ceux qui manquent
     * avec ces mêmes id, y compris l'administrateur (id 1), absent d'une base
     * qui n'a pas été initialisée par db:seed.
     */
    private function ensureUsers(): void
    {
        $demoUsers = [
            1 => ['login' => 'admin@admin.localhost', 'name' => 'Administrator', 'title' => 'Pirate Captain', 'role' => User::ROLE_ADMIN, 'password' => 'admin'],
            2 =>['login' => 'alice.martin@example.local', 'name' => 'Alice Martin', 'title' => 'Risk Owner'],
            5 => ['login' => 'claire.dubois@example.local', 'name' => 'Claire Dubois', 'title' => 'Compliance Officer'],
            6 => ['login' => 'benoit.leroy@example.local', 'name' => 'Benoit Leroy', 'title' => 'IT Operations Manager'],
            7 => ['login' => 'julien.moreau@example.local', 'name' => 'Julien Moreau', 'title' => 'Application Owner', 'role' => User::ROLE_AUDITEE],
        ];

        foreach ($demoUsers as $id => $attributes) {
            if (DB::table('users')->where('id', $id)->exists()) {
                continue;
            }

            DB::table('users')->insert([
                'id' => $id,
                'login' => $attributes['login'],
                'name' => $attributes['name'],
                'title' => $attributes['title'],
                'role' => $attributes['role'] ?? User::ROLE_USER,
                'language' => $this->locale,
                'email' => $attributes['login'],
                'password' => bcrypt($attributes['password'] ?? 'password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function wipe(): void
    {
        DB::table('action_risk')->delete();
        DB::table('control_risk')->delete();
        DB::table('action_control')->delete();
        DB::table('action_user')->delete();
        DB::table('measure_user_group')->delete();
        DB::table('user_user_group')->delete();
        UserGroup::query()->delete();
        Exception::query()->delete();
        Action::query()->delete();
        Risk::withTrashed()->forceDelete();
    }

    /**
     * Ne conserve que les id de mesures de sécurité présentes en base.
     *
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    private function controls(array $ids): array
    {
        return array_values(array_intersect($ids, $this->controlIds));
    }

    /** Ne conserve que les champs communs (retire les blocs de traduction fr/en). */
    private function base(array $def): array
    {
        unset($def['fr'], $def['en']);

        return $def;
    }

    /** Fusionne les champs communs avec le bloc de traduction de la locale active. */
    private function localized(array $def): array
    {
        return array_merge($this->base($def), $def[$this->locale]);
    }

    /** @return array<string, Risk> */
    private function seedRisks(): array
    {
        $defs = [
            'fuite_cloud' => [
                'owner_id' => 2,
                'probability' => 4,
                'impact' => 5,
                'status' => Risk::STATUS_NOT_ACCEPTED,
                'review_frequency' => 6,
                'next_review_at' => now()->addMonths(3)->format('Y-m-d'),
                'fr' => [
                    'name' => "Fuite de données clients via une mauvaise configuration du cloud",
                    'description' => "Un espace de stockage cloud mal configuré pourrait exposer publiquement des données à caractère personnel de clients, entraînant une violation de données au sens du RGPD.",
                    'status_comment' => "Traitement en cours via le plan d'action de durcissement des configurations cloud.",
                ],
                'en' => [
                    'name' => "Customer data leak due to cloud misconfiguration",
                    'description' => "A misconfigured cloud storage bucket could publicly expose customers' personal data, resulting in a data breach under GDPR.",
                    'status_comment' => "Remediation in progress via the cloud configuration hardening action plan.",
                ],
            ],
            'datacenter' => [
                'owner_id' => 1,
                'probability' => 2,
                'impact' => 5,
                'status' => Risk::STATUS_MITIGATED,
                'review_frequency' => 12,
                'next_review_at' => now()->addMonths(8)->format('Y-m-d'),
                'fr' => [
                    'name' => "Indisponibilité du centre de données principal suite à un sinistre",
                    'description' => "Un incendie, une inondation ou une coupure électrique prolongée sur le site principal pourrait interrompre les services critiques de l'entreprise pendant plusieurs jours.",
                ],
                'en' => [
                    'name' => "Unavailability of the primary data center following a disaster",
                    'description' => "A fire, flood, or prolonged power outage at the primary site could interrupt critical company services for several days.",
                ],
            ],
            'comptes_privileges' => [
                'owner_id' => 1,
                'probability' => 3,
                'impact' => 5,
                'status' => Risk::STATUS_NOT_ACCEPTED,
                'review_frequency' => 6,
                'next_review_at' => now()->addMonths(2)->format('Y-m-d'),
                'fr' => [
                    'name' => "Compromission des comptes à privilèges d'administration",
                    'description' => "Un attaquant ayant obtenu les identifiants d'un compte administrateur pourrait prendre le contrôle total du système d'information.",
                    'status_comment' => "Déploiement du MFA sur les comptes à privilèges en cours.",
                ],
                'en' => [
                    'name' => "Compromise of privileged administrator accounts",
                    'description' => "An attacker who obtains administrator credentials could gain full control of the information system.",
                    'status_comment' => "MFA rollout for privileged accounts in progress.",
                ],
            ],
            'detection_intrusion' => [
                'owner_id' => 6,
                'probability' => 3,
                'impact' => 4,
                'status' => Risk::STATUS_MITIGATED,
                'review_frequency' => 12,
                'next_review_at' => now()->addMonths(6)->format('Y-m-d'),
                'fr' => [
                    'name' => "Non-détection d'une intrusion sur le réseau interne",
                    'description' => "L'absence de supervision de sécurité en temps réel pourrait retarder la détection d'une compromission et en aggraver l'impact.",
                ],
                'en' => [
                    'name' => "Failure to detect an intrusion on the internal network",
                    'description' => "The lack of real-time security monitoring could delay detection of a compromise and worsen its impact.",
                ],
            ],
            'sauvegarde' => [
                'owner_id' => 6,
                'probability' => 2,
                'impact' => 3,
                'status' => Risk::STATUS_ACCEPTED,
                'review_frequency' => 12,
                'next_review_at' => now()->addMonths(10)->format('Y-m-d'),
                'fr' => [
                    'name' => "Perte de données suite à une erreur humaine lors d'une opération de sauvegarde",
                    'description' => "Une mauvaise manipulation lors d'une opération de sauvegarde ou de restauration pourrait entraîner une perte de données irrécupérable.",
                    'status_comment' => "Risque jugé acceptable au vu des contrôles de sauvegarde déjà en place.",
                ],
                'en' => [
                    'name' => "Data loss due to human error during a backup operation",
                    'description' => "A mishandling during a backup or restore operation could result in unrecoverable data loss.",
                    'status_comment' => "Risk deemed acceptable given the backup controls already in place.",
                ],
            ],
            'prestataire' => [
                'owner_id' => 2,
                'probability' => 2,
                'impact' => 4,
                'status' => Risk::STATUS_TRANSFERRED,
                'review_frequency' => 12,
                'next_review_at' => now()->addMonths(9)->format('Y-m-d'),
                'fr' => [
                    'name' => "Divulgation d'informations confidentielles par un prestataire externe",
                    'description' => "Un sous-traitant ayant accès à des informations sensibles pourrait les divulguer ou les utiliser à des fins non autorisées en cas de défaillance contractuelle.",
                    'status_comment' => "Clause de responsabilité et assurance cyber souscrite par le prestataire.",
                ],
                'en' => [
                    'name' => "Disclosure of confidential information by an external provider",
                    'description' => "A subcontractor with access to sensitive information could disclose it or use it for unauthorized purposes in the event of a contractual failure.",
                    'status_comment' => "Liability clause in place and cyber insurance subscribed by the provider.",
                ],
            ],
            'rancongiciel' => [
                'owner_id' => 6,
                'probability' => 3,
                'impact' => 5,
                'status' => Risk::STATUS_NOT_ACCEPTED,
                'review_frequency' => 3,
                'next_review_at' => now()->addMonth()->format('Y-m-d'),
                'fr' => [
                    'name' => "Attaque par rançongiciel sur le parc de postes de travail",
                    'description' => "Une campagne de hameçonnage ciblée pourrait permettre le déploiement d'un rançongiciel chiffrant les données de l'entreprise et paralysant l'activité.",
                    'status_comment' => "Déploiement d'un EDR et segmentation réseau en cours.",
                ],
                'en' => [
                    'name' => "Ransomware attack on the workstation fleet",
                    'description' => "A targeted phishing campaign could enable the deployment of ransomware encrypting company data and paralyzing operations.",
                    'status_comment' => "EDR rollout and network segmentation in progress.",
                ],
            ],
            'rgpd_rh' => [
                'owner_id' => 5,
                'probability' => 3,
                'impact' => 3,
                'status' => Risk::STATUS_TEMPORARILY_ACCEPTED,
                'review_frequency' => 6,
                'next_review_at' => now()->addMonths(4)->format('Y-m-d'),
                'fr' => [
                    'name' => "Non-conformité RGPD dans le traitement des données RH",
                    'description' => "Les durées de conservation des dossiers du personnel ne sont pas alignées avec les obligations légales, exposant l'entreprise à un risque de sanction de la CNIL.",
                    'status_comment' => "Révision du référentiel de conservation planifiée avec le DPO.",
                ],
                'en' => [
                    'name' => "GDPR non-compliance in HR data processing",
                    'description' => "Personnel file retention periods are not aligned with legal obligations, exposing the company to a risk of sanction from the data protection authority.",
                    'status_comment' => "Review of the retention schedule planned with the DPO.",
                ],
            ],
            'depart_collaborateur' => [
                'owner_id' => 1,
                'probability' => 2,
                'impact' => 3,
                'status' => Risk::STATUS_AVOIDED,
                'review_frequency' => 12,
                'next_review_at' => now()->addYear()->format('Y-m-d'),
                'fr' => [
                    'name' => "Départ d'un collaborateur clé sans transfert de compétences",
                    'description' => "Le départ soudain d'un collaborateur détenant une expertise critique pourrait interrompre la maintenance de systèmes sensibles.",
                    'status_comment' => "Plan de doublure et documentation des procédures mis en place.",
                ],
                'en' => [
                    'name' => "Departure of a key employee without knowledge transfer",
                    'description' => "The sudden departure of an employee holding critical expertise could interrupt the maintenance of sensitive systems.",
                    'status_comment' => "Backup staffing plan and procedure documentation put in place.",
                ],
            ],
            'vuln_portail' => [
                'owner_id' => 2,
                'probability' => 4,
                'impact' => 4,
                'status' => Risk::STATUS_NOT_EVALUATED,
                'review_frequency' => 12,
                'next_review_at' => now()->addMonths(5)->format('Y-m-d'),
                'fr' => [
                    'name' => "Vulnérabilité applicative non corrigée sur le portail client",
                    'description' => "Une faille de sécurité identifiée lors d'un test d'intrusion sur le portail client n'a pas encore fait l'objet d'un correctif, exposant l'application à une exploitation.",
                ],
                'en' => [
                    'name' => "Unpatched application vulnerability on the customer portal",
                    'description' => "A security flaw identified during a penetration test on the customer portal has not yet been patched, leaving the application exposed to exploitation.",
                ],
            ],
        ];

        $risks = [];
        foreach ($defs as $key => $def) {
            $risks[$key] = Risk::create($this->localized($def));
        }

        return $risks;
    }

    /** @return array<string, Action> */
    private function seedActions(): array
    {
        $defs = [
            'mfa' => [
                'reference' => 'ACT-2026-001',
                'type' => 1,
                'progress' => 60,
                'criticity' => 3,
                'status' => 0,
                'creation_date' => '2026-04-01',
                'due_date' => '2026-09-30',
                'fr' => [
                    'name' => "Déploiement de l'authentification multifacteur sur les comptes à privilèges",
                    'scope' => "Systèmes d'information",
                    'cause' => "Les comptes administrateur ne sont protégés que par un mot de passe, sans second facteur d'authentification.",
                    'remediation' => "Déployer une solution MFA (TOTP ou clé physique) sur l'ensemble des comptes à privilèges et interdire les mécanismes de secours non sécurisés.",
                ],
                'en' => [
                    'name' => "Rollout of multi-factor authentication on privileged accounts",
                    'scope' => "Information Systems Department",
                    'cause' => "Administrator accounts are only protected by a password, with no second authentication factor.",
                    'remediation' => "Deploy an MFA solution (TOTP or hardware key) on all privileged accounts and prohibit insecure fallback mechanisms.",
                ],
            ],
            'edr' => [
                'reference' => 'ACT-2026-002',
                'type' => 1,
                'progress' => 30,
                'criticity' => 3,
                'status' => 0,
                'creation_date' => '2026-05-15',
                'due_date' => '2026-12-15',
                'fr' => [
                    'name' => "Déploiement d'une solution EDR sur l'ensemble du parc informatique",
                    'scope' => "Systèmes d'information",
                    'cause' => "Les postes de travail ne disposent que d'un antivirus traditionnel, insuffisant face aux techniques d'évasion des rançongiciels récents.",
                    'remediation' => "Déployer un EDR (Endpoint Detection & Response) avec supervision centralisée sur l'ensemble du parc, y compris les postes nomades.",
                ],
                'en' => [
                    'name' => "Rollout of an EDR solution across the entire IT fleet",
                    'scope' => "Information Systems Department",
                    'cause' => "Workstations only have traditional antivirus software, which is insufficient against the evasion techniques used by recent ransomware.",
                    'remediation' => "Deploy an EDR (Endpoint Detection & Response) solution with centralized monitoring across the whole fleet, including remote laptops.",
                ],
            ],
            'segmentation' => [
                'reference' => 'ACT-2026-003',
                'type' => 2,
                'progress' => 10,
                'criticity' => 2,
                'status' => 0,
                'creation_date' => '2026-06-01',
                'due_date' => '2027-03-31',
                'fr' => [
                    'name' => "Segmentation réseau et cloisonnement des environnements sensibles",
                    'scope' => "Infrastructure réseau",
                    'cause' => "Le réseau interne est peu segmenté, ce qui permet une propagation latérale rapide en cas de compromission d'un poste.",
                    'remediation' => "Mettre en place un cloisonnement par VLAN et des règles de filtrage entre les zones utilisateurs, serveurs et administration.",
                ],
                'en' => [
                    'name' => "Network segmentation and isolation of sensitive environments",
                    'scope' => "Network infrastructure",
                    'cause' => "The internal network is poorly segmented, allowing rapid lateral movement in the event of a workstation compromise.",
                    'remediation' => "Implement VLAN-based segmentation and filtering rules between user, server, and administration zones.",
                ],
            ],
            'cloud' => [
                'reference' => 'ACT-2026-004',
                'type' => 1,
                'progress' => 75,
                'criticity' => 3,
                'status' => 0,
                'creation_date' => '2026-03-10',
                'due_date' => '2026-08-31',
                'fr' => [
                    'name' => "Revue et durcissement des configurations de stockage cloud",
                    'scope' => "Cloud & Hébergement",
                    'cause' => "Un audit interne a révélé plusieurs espaces de stockage cloud accessibles publiquement sans authentification.",
                    'remediation' => "Réaliser un audit exhaustif des configurations de stockage cloud, corriger les accès publics non justifiés et mettre en place une revue périodique automatisée.",
                ],
                'en' => [
                    'name' => "Review and hardening of cloud storage configurations",
                    'scope' => "Cloud & Hosting",
                    'cause' => "An internal audit revealed several cloud storage locations publicly accessible without authentication.",
                    'remediation' => "Conduct a full audit of cloud storage configurations, remediate unjustified public access, and set up an automated periodic review.",
                ],
            ],
            'rgpd' => [
                'reference' => 'ACT-2026-005',
                'type' => 3,
                'progress' => 45,
                'criticity' => 1,
                'status' => 0,
                'creation_date' => '2026-02-01',
                'due_date' => '2026-10-31',
                'fr' => [
                    'name' => "Mise à jour de la politique de conservation des données RH",
                    'scope' => "Ressources humaines",
                    'cause' => "Les durées de conservation des dossiers du personnel n'ont pas été révisées depuis l'entrée en application du RGPD.",
                    'remediation' => "Réviser le référentiel de durées de conservation avec le DPO et former les équipes RH à son application.",
                ],
                'en' => [
                    'name' => "Update of the HR data retention policy",
                    'scope' => "Human Resources",
                    'cause' => "Personnel file retention periods have not been reviewed since GDPR came into force.",
                    'remediation' => "Revise the retention schedule with the DPO and train HR staff on its application.",
                ],
            ],
            'pra' => [
                'reference' => 'ACT-2026-006',
                'type' => 2,
                'progress' => 100,
                'criticity' => 2,
                'status' => 1,
                'creation_date' => '2025-11-01',
                'due_date' => '2026-02-28',
                'close_date' => '2026-02-20',
                'fr' => [
                    'name' => "Test annuel du plan de reprise d'activité du site secondaire",
                    'scope' => "Continuité d'activité",
                    'cause' => "Le plan de reprise d'activité n'avait pas été testé depuis plus de 18 mois.",
                    'remediation' => "Organiser un exercice de bascule complet vers le site secondaire et documenter les écarts constatés.",
                ],
                'en' => [
                    'name' => "Annual test of the secondary site disaster recovery plan",
                    'scope' => "Business continuity",
                    'cause' => "The disaster recovery plan had not been tested for more than 18 months.",
                    'remediation' => "Organize a full failover exercise to the secondary site and document any gaps identified.",
                ],
            ],
        ];

        $actions = [];
        foreach ($defs as $key => $def) {
            // The name is not mass assignable
            $actions[$key] = Action::forceCreate($this->localized($def));
        }

        return $actions;
    }

    private function seedExceptions(): void
    {
        $defs = [
            [
                'control_id' => 71,
                'start_date' => '2026-01-15',
                'end_date' => '2027-01-15',
                'status' => Exception::STATUS_APPROVED,
                'created_by' => 1,
                'submitted_by' => 1,
                'submitted_at' => '2026-01-10 09:00:00',
                'approved_by' => 5,
                'approved_at' => '2026-01-14 16:30:00',
                'fr' => [
                    'name' => "Dérogation MFA pour le compte de service de l'ERP historique",
                    'description' => "L'ERP historique ne prend pas en charge l'authentification multifacteur. Une dérogation est demandée en attendant la migration vers la version compatible, prévue en 2027.",
                    'justification' => "Contrainte technique de l'éditeur : aucune mise à jour compatible MFA n'est disponible avant la prochaine version majeure.",
                    'compensating_controls' => "Restriction de l'accès au compte de service à une liste blanche d'adresses IP et rotation du mot de passe tous les 30 jours.",
                    'approval_comment' => "Approuvé sous réserve du respect strict des mesures compensatoires.",
                ],
                'en' => [
                    'name' => "MFA exception for the legacy ERP service account",
                    'description' => "The legacy ERP does not support multi-factor authentication. An exception is requested pending migration to the compatible version, planned for 2027.",
                    'justification' => "Vendor technical constraint: no MFA-compatible update is available before the next major release.",
                    'compensating_controls' => "Access to the service account restricted to an IP allowlist and password rotation every 30 days.",
                    'approval_comment' => "Approved subject to strict compliance with the compensating controls.",
                ],
            ],
            [
                'control_id' => 89,
                'start_date' => '2026-06-01',
                'end_date' => '2026-12-01',
                'status' => Exception::STATUS_SUBMITTED,
                'created_by' => 6,
                'submitted_by' => 6,
                'submitted_at' => '2026-06-01 11:15:00',
                'fr' => [
                    'name' => "Exception de cloisonnement réseau pour l'automate industriel",
                    'description' => "L'automate de production ne peut pas être isolé sur son propre VLAN sans interrompre la chaîne de production durant plusieurs jours.",
                    'justification' => "Interruption de production non planifiable avant le prochain arrêt technique programmé.",
                    'compensating_controls' => "Filtrage strict au niveau du commutateur et surveillance renforcée du trafic sortant de l'automate.",
                ],
                'en' => [
                    'name' => "Network segmentation exception for the industrial controller",
                    'description' => "The production controller cannot be isolated on its own VLAN without interrupting the production line for several days.",
                    'justification' => "Production downtime cannot be scheduled before the next planned technical shutdown.",
                    'compensating_controls' => "Strict switch-level filtering and enhanced monitoring of outbound traffic from the controller.",
                ],
            ],
            [
                'control_id' => 90,
                'start_date' => '2026-07-01',
                'end_date' => '2026-09-30',
                'status' => Exception::STATUS_DRAFT,
                'created_by' => 2,
                'fr' => [
                    'name' => "Report du chiffrement des postes portables du service commercial",
                    'description' => "Le déploiement du chiffrement de disque sur les postes portables du service commercial est reporté faute de licences disponibles.",
                    'justification' => "Rupture de stock chez le fournisseur de la solution de chiffrement ; livraison attendue sous 60 jours.",
                ],
                'en' => [
                    'name' => "Postponement of disk encryption on the sales team's laptops",
                    'description' => "The rollout of disk encryption on the sales team's laptops is postponed due to a lack of available licenses.",
                    'justification' => "Stock shortage at the encryption solution vendor; delivery expected within 60 days.",
                ],
            ],
            [
                'control_id' => 68,
                'start_date' => '2026-03-01',
                'end_date' => '2026-06-01',
                'status' => Exception::STATUS_REJECTED,
                'created_by' => 1,
                'submitted_by' => 1,
                'submitted_at' => '2026-02-25 10:00:00',
                'approved_by' => 5,
                'approved_at' => '2026-02-27 14:00:00',
                'fr' => [
                    'name' => "Maintien temporaire d'un accès VPN partagé pour un prestataire",
                    'description' => "Un compte VPN partagé continue d'être utilisé par le prestataire de maintenance applicative, en attente du déploiement d'accès nominatifs individuels.",
                    'justification' => "Le projet de bascule vers des accès nominatifs a pris du retard côté prestataire.",
                    'compensating_controls' => "Journalisation renforcée des connexions et restriction des horaires d'accès.",
                    'approval_comment' => "Refusé : le partage d'un compte VPN entre plusieurs intervenants ne permet pas d'assurer l'imputabilité des actions. Un accès nominatif doit être mis en place sous 30 jours.",
                ],
                'en' => [
                    'name' => "Temporary continuation of a shared VPN access for a provider",
                    'description' => "A shared VPN account continues to be used by the application maintenance provider, pending the rollout of individual named accounts.",
                    'justification' => "The project to switch to named accounts has been delayed on the provider's side.",
                    'compensating_controls' => "Enhanced connection logging and restricted access hours.",
                    'approval_comment' => "Rejected: sharing a VPN account among multiple individuals does not ensure accountability for actions. A named access must be implemented within 30 days.",
                ],
            ],
            [
                'control_id' => 71,
                'start_date' => '2025-01-01',
                'end_date' => '2026-06-30',
                'status' => Exception::STATUS_EXPIRED,
                'created_by' => 2,
                'submitted_by' => 2,
                'submitted_at' => '2024-12-20 09:00:00',
                'approved_by' => 1,
                'approved_at' => '2024-12-22 09:00:00',
                'fr' => [
                    'name' => "Dérogation à la politique de mot de passe pour l'application comptable historique",
                    'description' => "L'application de comptabilité historique impose une longueur de mot de passe limitée à 8 caractères, en deçà des exigences de la politique de sécurité.",
                    'justification' => "Application en fin de vie, remplacement prévu par le nouvel ERP financier.",
                    'compensating_controls' => "Accès restreint au réseau interne uniquement et changement de mot de passe tous les 45 jours.",
                    'approval_comment' => "Approuvé jusqu'au remplacement de l'application.",
                ],
                'en' => [
                    'name' => "Password policy exception for the legacy accounting application",
                    'description' => "The legacy accounting application enforces a password length limited to 8 characters, below the security policy requirements.",
                    'justification' => "End-of-life application, to be replaced by the new financial ERP.",
                    'compensating_controls' => "Access restricted to the internal network only and password changed every 45 days.",
                    'approval_comment' => "Approved until the application is replaced.",
                ],
            ],
        ];

        foreach ($defs as $def) {
            $def['control_id'] = $this->controls([$def['control_id']])[0] ?? null;
            Exception::create($this->localized($def));
        }
    }

    private function seedGroups(): void
    {
        $defs = [
            [
                'type' => UserGroup::TYPE_SECURITY,
                'users' => [2, 5],
                'measures' => 3,
                'fr' => [
                    'name' => "Équipe sécurité",
                    'description' => "Responsables de la sécurité et de la conformité, chargés des contrôles techniques et organisationnels.",
                ],
                'en' => [
                    'name' => "Security team",
                    'description' => "Security and compliance officers, in charge of the technical and organisational controls.",
                ],
            ],
            [
                'type' => UserGroup::TYPE_BUSINESS,
                'users' => [6],
                'measures' => 2,
                'fr' => [
                    'name' => "Exploitation informatique",
                    'description' => "Équipe d'exploitation chargée des contrôles sur les sauvegardes et la supervision.",
                ],
                'en' => [
                    'name' => "IT operations",
                    'description' => "Operations team in charge of the controls on backups and monitoring.",
                ],
            ],
        ];

        // Contrôles planifiés (non réalisés et non délégués) répartis entre les groupes
        $measures = DB::table('measures')
            ->whereNull('realisation_date')
            ->whereNotIn('id', DB::table('measure_user')->select('measure_id'))
            ->orderBy('plan_date')
            ->pluck('id')
            ->all();

        foreach ($defs as $def) {
            $group = UserGroup::create($this->localized(array_diff_key($def, ['users' => 0, 'measures' => 0])));
            $group->users()->sync($def['users']);
            $group->measures()->sync(array_splice($measures, 0, $def['measures']));
        }
    }

    /**
     * Délègue les premiers contrôles planifiés à l'audité (id 7) ; l'un d'eux
     * a été réalisé par l'audité et attend d'être validé.
     */
    private function seedDelegation(): void
    {
        DB::table('measure_user')->where('user_id', 7)->delete();

        $measures = DB::table('measures')->where('status', 0)->orderBy('plan_date')->limit(6)->pluck('id')->all();
        DB::table('measure_user')->insert(array_map(fn ($id) => ['measure_id' => $id, 'user_id' => 7], $measures));

        if ($measures !== []) {
            // Observation of the security measure of this control, not of another one
            $clause = DB::table('control_measure')
                ->join('controls', 'controls.id', '=', 'control_measure.control_id')
                ->where('control_measure.measure_id', $measures[0])
                ->value('controls.clause');

            DB::table('measures')->where('id', $measures[0])->update([
                'status' => 1,
                'score' => 3,
                'realisation_date' => now()->toDateString(),
                'observations' => TestObservations::for($clause, 3, $this->locale),
            ]);
        }
    }
}
