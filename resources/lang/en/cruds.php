<?php
return [
    'welcome' => [
         'dashboard' => 'Dashboard',
         'domains' => 'Domains',
         'measures' => 'Controls',
         'controls' => 'Measurements',
         'action_plans' => "Action Plans",
         'next_controls' => 'Measures scheduled for the next 30 days',
         'control_status' => 'Measurements status',
         'control_planning' => 'Scheduled measures',
    ],
     'action' => [
         'index' => 'Action plans',
         'show' => 'Action plan',
         'create' => 'Create an action plan',
         'edit' => 'Edit action plan',
         'title' => 'Actions',
         'close' => 'Close an action',
         'fields' => [
            'clauses' => 'Clauses',
            'name' => 'Name',
            'scope' => 'Scope',
            'action' => 'Action plan',
            'plan_date' => 'Planning date',
            'next_date' => 'Review date',
            'note' => 'Score',
            'objective' => 'Objective',
            'observation' => 'Observation',
            'justification' => 'Justification',
            'action_plan' => 'Action Plan',
            'reference' => 'Ref',
            'type' => 'Type',
            'due_date' => 'Due date',
            'choose_type' => 'Choose type',
            'choose_scope' => 'Choose scope',
            'remediation' => 'Remediation',
            'criticity' => 'Criticity',
            'cause' => 'Reason',
            'owners' => 'Owners',
            'status' => 'Status',
            'status_open' => 'Open',
            'status_closed' => 'Closed',
            'status_rejected' => 'Rejected',
            'status_all' => 'All',
            'close_date' => 'Close date',
            'progress' => 'Progress',
         ],
        'types' => [
            'major' => 'Major',
            'minor' => 'Minor',
            'opportunity' => 'Opportunité',
            'observation' => 'Observation'
        ],
        'types_short' => [
            'major' => 'Maj',
            'minor' => 'Min',
            'opportunity' => 'Opp',
            'observation' => 'Obs'
        ],
     ],
    'attribute' => [
        'fields' => [
            'name' => 'Name',
            'values' => 'Values',
        ],
        'add' => 'Add attribute',
        'edit' => 'Edit attribute',
        'show' => 'Attribute',
        'index' => 'List of attributes',
        'choose' => 'Choose an attribute',
        'title' => 'Attribute',
        'replace' => [
            'title' => 'Manage attributes',
            'old_value' => 'Existing value',
            'new_value' => 'New value',
            'success' => 'Value ":old" has been replaced by ":new" in all measures, controls and attributes.',
        ],
    ],
     'measure' => [
         'description' => '',
         'fields' => [
            'action_plan' => 'Action Plan',
            'attributes' => 'Attributes',
            'choose_domain' => 'Choose domain',
            'choose_scope' => 'Choose scope',
            'choose_period' => 'Choose period',
            'choose_attribute' => 'Choose attribute',
            'choose_clause' => 'Choose clause',
            'domain' => 'Domain',
            'indicator' => 'Function',
            'measure' => 'Measure',
            'model' => 'Model',
            'name' => 'Name',
            'next' => 'Next',
            'note' => 'Note',
            'objective' => 'Objective',
            'observations' => 'Observations',
            'plan_date' => 'Planning date',
            'period' => 'Period',
            'periodicity' => 'Periodicity',
            'planned' => 'Planned',
            'realisation_date' => 'Realization date',
            'realized' => 'Made',
            'evidence' => 'Evidence',
            'score' => 'Score',
            'status' => 'State',
            'status_done' => 'Done',
            'status_todo' => 'To do',
            'status_all' => 'All',
            'scope' => 'Scope',
            'clause' => 'Clause',
            'clauses' => 'Clauses',
            'input' => 'Input',
            'owners' => 'Responsibles',
            'groups' => 'Groupes'
         ],
        'error' => [
            'made' => 'This measurement has already been made.',
            'duplicate' => 'This measurement already exists.',
        ],
        'checklist' => 'Measure sheet',
        'create' => 'Create a measure',
        'list' => 'Measurement list',
        'edit' => 'Edit measurement',
        'history' => 'Schedule',
        'make' => 'Make a measurement',
        'plan' => 'Schedule a measurement',
        'radar' => 'Security measurement status',
        'status_date' => 'Status of controls at',
        'title' => 'Measurements',
        'title_singular' => 'Measurement',
        'groupBy' => 'Group by',
        'create_action' => 'Create an action plan',
        'calendar' => 'Calendar',
        'confirm_delete' => 'Are you sure you want to delete measurements?'
    ],
     'notification' => [
         'subject' => 'Measurement list to carry out',
     ],
     'control' => [
         'title' => 'Control',
         'fields' => [
             'domain' => 'Domain',
             'clause' => 'Clause',
             'name' => 'Name',
             'objective' => 'Description',
             'attributes' => 'Attributes',
             'model' => 'Model',
             'indicator' => 'Indicator',
             'action_plan' => 'Action Plan',
             'periodicity' => 'Periodicity',
             'input' => 'Input',
         ],
         'show' => 'Control',
         'index' => 'Control list',
         'create' => 'Add a control',
         'edit' => 'Edit a control',
         'plan' => 'Measurement planning'
     ],
     'domain' => [
         'fields' => [
             'framework' => 'Framework',
             'name' => 'Name',
             'description' => 'Description',
             'measures' => '# Controls',
         ],
         'add' => 'Add Domain',
         'edit' => 'Edit Domain',
         'show' => 'Domain',
         'index' => 'List of domains',
         'choose' => 'Choose domain',
         'title' => 'Domain',
         'radar' => 'Measurements by domain',
         'measure_date' => 'Status of measures to',
      ],
     'document' => [
        'title' => [
            'storage' => 'Storage',
            'templates' => 'Templates',
            'cleanup' => 'Retention period',
            'cleanup_detail' => 'Number of months before automatic deletion of controls and documents',
        ],
        'month' => 'Months',
        'never' => 'Never',
        'description' => 'Description',
        'list' => 'List of documents',
        'index' => 'Documents',
        'fields' => [
             'name' => 'Name',
             'control' => 'Control',
             'size' => 'Size',
             'hash' => 'Hash',
             'links' => 'Links',
             'status' => 'Check status',
         ],
         'model' => [
            'control' => 'Control sheet template',
            'report' => 'Steering report template',
            'custom' => 'Custom model',
         ],
         'count' => 'Number of documents',
         'total_size' => 'Total Size',
     ],
    'exports' => [
         'index' => 'Export data',
         'start' => 'Start',
         'end' => 'End',
         'report_title' => 'Report',
         'steering' => 'ISMS steering report',
         'data_export_title' => 'Data Export',
         'users_export' => 'Export users',
         'domains_export'=> 'Export domains ',
         'attributes_export' => 'Export attributes',
         'measures_export' => 'Export security measures',
         'controls_export' => 'Export controls',
         'import' => 'Import',
         'actions_export' => 'Export action plans',
         'risks_export' => 'Export risk register',
    ],
    'group' => [
         'index' => 'List of groups',
         'add' => 'Add a user group',
         'edit' => 'Edit a user group',
         'show' => 'Display a user group',
         'fields' => [
             'name' => 'Name',
             'description' => 'Description',
             'users' => 'Users',
             'controls' => 'Controls',
            ],
         ],
    'imports' => [
        'index' => 'Import',
         'title' => 'Import security measures',
         'current' => 'Current security Measures',
         'or' => 'or',
         'remove_all' => 'Remove all other measures and controls',
         'fake' => 'Generate fake measurements',
         'format' => 'The import format is an XLSX document with these columns',
         'framework' => 'Framework',
         'framework_helper' => 'The security framework used',
         'domain' => 'Domain name',
         'domain_helper' => 'The domain name, it is created if it does not exist',
         'domain_description' => 'Domain description',
         'domain_description_helper' => 'The description of the domain',
         'clause' => 'Clause',
         'clause_helper' => 'If the clause exists the security measure is updated,<br>if the clause does not exist, a new security measure is created,<br>if all other fields of the line are empty, the measure, related controls and documents are removed.<br>',
         'name' => 'Name',
         'name_helper' => 'The name of the security measure',
         'description' => 'Description',
         'description_helper' => 'The description of the security measure',
         'attributes' => 'Attributes',
         'attributes_helper' => 'List of tags (#... #... #...)',
         'input' => 'Input',
         'input_helper' => 'The input elements',
         'model' => 'Model',
         'model_helper' => 'The computation model',
         'indicator' => 'Indicator',
         'indicator_helper' => 'Green if..., Orange if..., Red if...',
         'action' => 'Action plan',
         'action_helper' => 'The proposed action plan',
         'warning' => 'This action could not be undone, take a backup before!'
    ],
    'log' => [
        'index' => 'List of logs',
        'title' => 'Log',
        'history' => 'Change history',
        'action' => 'Action',
        'subject_type' => 'Type',
        'subject_id' => 'ID',
        'user' => 'User',
        'host' => 'Host',
        'timestamp' => 'Timestamp',
        'properties' => 'Data'
    ],
     'login' => [
         'title' => 'Enter a password',
         'identification' => 'Login',
         'connection' => 'Connection',
         'connection_with' => 'Connection with',
         'error' => [
            'user_not_exist' => 'User not exist',
            'account_disabled' => 'This account has been disabled.',
        ],
     ],
    'report' => [
        'action_plan' => [
            'id' => '#',
            'title' => 'Action plan',
            'next' => 'Review date'
        ]
    ],
    'soa' => [
        'title' => 'Statement of Applicability (SoA)',
        'generate' => 'Generate report'
    ],
    'user' => [
         'index' => 'List of users',
         'edit' => 'Edit User',
         'add' => 'Add User',
         'show' => 'Show user',
         'fields' => [
            'login' => 'Login',
             'name' => 'Name',
             'title' => 'Title',
             'role' => 'Role',
             'password' => 'Password',
             'email' => 'email',
             'language' => 'Language',
             'controls' => 'Controls',
             'groups' => 'Groups'
         ],
         'roles' => [
             'admin' => 'Administrator',
             'user' => 'User',
             'auditor' => 'Auditor',
             'api' => 'API',
             'auditee' => 'Auditee',
             'disabled' => 'Disabled',
         ],
    ],
    'config' => [
        'notifications' => [
                 'title' => 'Notifications configuration',
                 'title_short' => 'Notifications',
                 'help' => 'This screen allows you to configure the notifications sent by email to users.',
                 'message_subject' => 'Message subject',
                 'message_content' => 'Message content',
                 'message_default_content' => '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="ISO-8859-1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<p>Here is the list of measurement that will be due soon:</p>
%table%
<p>This is an automatically generated email</p>
<p>Best regards,<br>Deming</p>
</body>
</html>',
                 'sent_from' => 'Sent from',
                 'to' => 'to',
                 'delay' => 'for controls that expire within',
                 'recurrence' => 'Send a notification',
                 'duration' => [
                     'day' => 'day',
                     'days' => 'days',
                     'month' => 'month',
                     'months' => 'months',
                ],
            ],
        ],

    // -------------------------------------------------------------------------
    // Risk register
    // -------------------------------------------------------------------------
    'risk' => [

        // Page titles
        'list'           => 'Risk Register',
        'title_singular' => 'Risk',
        'create'         => 'New Risk',
        'edit'           => 'Edit Risk',
        'matrix'         => 'Risk Matrix',
        'singular'       => 'Risk',
        'plural'         => 'Risks',

        // Risk levels (displayed in badges and counters)
        'levels' => [
            'low'      => 'Low',
            'medium'   => 'Medium',
            'high'     => 'High',
            'critical' => 'Critical',
        ],

        // Form fields and list columns
        'fields' => [
            'name'                => 'Name',
            'description'         => 'Description',
            'owner'               => 'Owner',
            'no_owner'            => 'Unassigned',
            'choose_owner'        => 'Select an owner',
            'choose_status'       => 'Select a status',
            'choose_level'        => 'Select level',

            // Assessment
            'probability'         => 'Probability',
            'threat'              => 'Threat',
            'probability_comment' => 'Probability comment',
            'impact'              => 'Impact',
            'impact_comment'      => 'Impact comment',
            'exposure'            => 'Exposure',
            'vulnerability'       => 'Vulnerability',
            'likelihood'          => 'Likelihood',
            'score'               => 'Score',
            'residual_risk'       => 'Residual risk',

            // Treatment
            'status'              => 'Treatment status',
            'status_comment'      => 'Status comment',
            'controls'            => 'Linked controls',
            'controls_hint'       => 'Required when status = Mitigated',
            'action_plan'         => 'Linked action plans',
            'actions_hint'        => 'Required when status = Not accepted (optional for Reduction in MONARC mode)',

            // Planning
            'review_frequency'    => 'Review frequency',
            'next_review'         => 'Next review',
            'overdue'             => 'Overdue review',
            'overdue_all'         => 'All',
            'overdue_only'        => 'Overdue',

            // Dashboard / matrix
            'total'               => 'Total',
            'by_risks'            => 'Distribution by risk level',
            'by_status'           => 'Distribution by status',
        ],

        // Treatment statuses
        'status' => [
            'not_evaluated'        => 'Not evaluated',
            'not_accepted'         => 'Not accepted',
            'temporarily_accepted' => 'Temporarily accepted',
            'accepted'             => 'Accepted',
            'mitigated'            => 'Mitigated',
            'transferred'          => 'Transferred',
            'avoided'              => 'Avoided',
        ],

        // MONARC treatments (reuse the 'status' keys above)
        'status_monarc' => [
            'not_treated' => 'Not treated',
            'reduction'   => 'Reduction',
            'denied'      => 'Denied',
            'accepted'    => 'Accepted',
            'shared'      => 'Shared',
        ],
    ],

    // -------------------------------------------------------------------------
    // Scoring engine configuration
    // -------------------------------------------------------------------------
    'risk_scoring' => [

        // Page titles
        'list'            => 'Risk Scoring Methods',
        'create'          => 'New Scoring Configuration',
        'edit'            => 'Edit Scoring Configuration',
        'activate'        => 'Activate this configuration',

        // Level / threshold actions
        'add_level'       => 'Add level',
        'add_threshold'   => 'Add threshold',

        // Contextual hints
        'levels_hint'     => 'Minimum 2 levels. Value must be a unique integer.',
        'thresholds_hint' => 'The last threshold has no upper bound (catch-all). Sort from lowest to highest score.',

        // Form fields
        'fields' => [
            'name'        => 'Configuration name',
            'formula'     => 'Calculation formula',
            'levels'      => 'Levels',
            'thresholds'  => 'Classification thresholds',
            'value'       => 'Value',
            'label'       => 'Label',
            'description' => 'Description',
            'level_key'   => 'Internal key',
            'score_max'   => 'Max score (∞ = last)',
            'color'       => 'Badge color',
        ],

        // Available badge colors
        'colors' => [
            'success'   => 'Green',
            'warning'   => 'Orange',
            'danger'    => 'Red',
            'alert'     => 'Dark red',
            'info'      => 'Blue',
            'secondary' => 'Grey',
        ],

        // Available formulas (labels)
        'formulas' => [
            'probability_x_impact' => 'Probability × Impact',
            'likelihood_x_impact'  => 'Likelihood × Impact (BSI 200-3)',
            'additive'             => 'Probability + Impact',
            'max_pi'               => 'max(Probability, Impact)',
            'monarc'               => 'MONARC (Impact × Threat × Vulnerability)',
        ],


        // Default values suggested when creating a configuration
        'defaults' => [
            'probability_levels' => [
                'rare' => 'Rare',
                'unlikely' => 'Unlikely',
                'possible' => 'Possible',
                'likely' => 'Probable',
                'very_likely' => 'Very Likely',
            ],
            'exposure_levels' => [
                'offline' => 'Offline',
                'internal' => 'Internal',
                'internet' => 'Internet',
            ],
            'vulnerability_levels' => [
                'none' => 'None',
                'known' => 'Known',
                'exploitable_int' => 'Internally exploitable',
                'exploitable_ext' => 'Externally exploitable',
            ],
            'impact_levels' => [
                'negligible' => 'Negligible',
                'low' => 'Low',
                'moderate' => 'Moderate',
                'high' => 'High',
                'critical' => 'Critical',
            ],
            'risk_thresholds' => [
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
                'critical' => 'Critical',
            ],
            'monarc_impact_levels' => [
                0 => ['label' => 'Negligible',  'description' => 'No significant consequence on reputation, operations, legal, financial aspects or individuals'],
                1 => ['label' => 'Low',         'description' => 'Limited consequences, absorbed without noticeable disruption to the business'],
                2 => ['label' => 'Significant', 'description' => 'Noticeable disruption to the business, measurable reputational damage, notable financial or legal consequences'],
                3 => ['label' => 'Critical',    'description' => 'Serious disruption to the business, severe reputational damage, major legal or financial consequences, or harm to individuals'],
                4 => ['label' => 'Vital',       'description' => 'Endangers the organisation, irreversible consequences or serious harm to individuals'],
            ],
            'monarc_threat_levels' => [
                0 => ['label' => 'Not applicable', 'description' => 'Threat impossible in this context'],
                1 => ['label' => 'Unlikely',       'description' => 'Has never occurred, very unlikely'],
                2 => ['label' => 'Possible',       'description' => 'No clear evidence, could occur'],
                3 => ['label' => 'Likely',         'description' => 'Has already occurred in the organisation or its environment'],
                4 => ['label' => 'Very likely',    'description' => 'Has already occurred on several occasions'],
            ],
            'monarc_vulnerability_levels' => [
                0 => ['label' => 'Non-existent', 'description' => 'All security controls are in place, documented and effective'],
                1 => ['label' => 'Very low',     'description' => 'Controls in place and monitored, minor improvements possible'],
                2 => ['label' => 'Low',          'description' => 'Controls broadly in place but not systematically monitored'],
                3 => ['label' => 'Medium',       'description' => 'Controls partially in place, gaps identified'],
                4 => ['label' => 'High',         'description' => 'Controls embryonic or ineffective'],
                5 => ['label' => 'Very high',    'description' => 'Complete absence of security controls'],
            ],
        ],
    ],

    'crosswalk' => [
        'list' => 'Framework crosswalks',
        'create' => 'New mapping',
        'edit' => 'Edit mapping',
        'show' => 'Mapping details',
        'matrix' => 'Crosswalk matrix',
        'source' => 'Source control',
        'target' => 'Target control',
        'source_framework' => 'Source framework',
        'target_framework' => 'Target framework',
        'search_clause' => 'Search for a clause or control',
        'choose_control' => 'Choose a control',
        'all' => 'All',
        'display' => 'Display',
        'reverse' => 'Reverse direction',
        'show_unmapped' => 'Show only unmapped source controls',
        'select_pair' => 'Select two distinct frameworks to display the matrix.',
        'distinct_frameworks' => 'Source and target frameworks must be distinct.',
        'documentary_notice' => 'These numbers are documentary metrics. They are never a compliance score or evidence of compliance.',
        'source_total' => 'Source controls',
        'mapped_source' => 'With at least one mapping',
        'unmapped_source' => 'Without a mapping',
        'relations' => 'Relations',
        'unmapped' => 'No mapping',
        'no_filtered_mapping' => 'No mapping matches the selected filters',
        'no_mappings' => 'No mapping found.',
        'no_controls' => 'No source control found.',
        'control_mappings' => 'Mappings to other frameworks',
        'corresponding_control' => 'Corresponding control',
        'stored_direction' => 'Stored direction',
        'direct' => 'Source → target',
        'reversed' => 'Read in reverse',
        'validated_by' => 'Validated by :name on :date',
        'not_validated' => 'Not validated',
        'mapping_types' => [
            'equivalent' => 'Equivalent',
            'covers' => 'Covers',
            'covered_by' => 'Covered by',
            'partial' => 'Partial',
            'supports' => 'Supports',
            'supported_by' => 'Supported by',
            'related' => 'Related',
        ],
        'coverage_levels' => [
            'full' => 'Full',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            'none' => 'None',
        ],
        'fields' => [
            'mapping_type' => 'Mapping type',
            'coverage' => 'Coverage',
            'confidence' => 'Confidence',
            'rationale' => 'Rationale',
            'source_reference' => 'Source reference',
            'source_url' => 'Source URL',
            'validated' => 'Validation',
        ],
    ],

    'exception' => [
        'list' => 'Exception Handling',
        'create' => 'New Exception',
        'edit' => 'Edit Exception',
        'title_singular' => 'Exception',
        'title_plural' => 'Exceptions',
        'review_section' => 'Validate Decision',
        'expired_hint' => 'Expired date has passed.',
        'confirm_submit' => 'Submit this exception for validation?',
        'confirm_approve' => 'Approve this exception?',
        'confirm_reject' => 'Reject this exception?',
        'actions' => [
            'submit' => 'Submit',
            'approve' => 'Approve',
            'reject' => 'Reject',
        ],

        'status' => [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
        ],

        'fields' => [
            'name' => 'Name',
            'measure' => 'Linked control',
            'no_measure' => 'No control',
            'description' => 'Description',
            'justification' => 'Justification',
            'compensating_controls' => 'Compensating measures',
            'start_date' => 'Start',
            'end_date' => 'End',
            'status' => 'Status',
            'created_by' => 'Created by',
            'submitted_by' => 'Submitted by',
            'approved_by' => 'Approved by',
            'rejected_by' => 'Rejected by',
            'approval_comment' => 'Decision comment',
            'approval_comment_optional' => 'Optional comment',
            'approval_comment_required' => 'Reason for refusal (required)',
            'choose_status' => 'Filter by status',
            'choose_measure' => 'Filter by control',
            'expired_only' => 'Expired only',
        ],
    ],

];
