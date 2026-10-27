<?php
// Liens vers la documentation utilisateur, par vue (documentation française)
return [
    'button' => 'Aide',
    'url' => 'https://sourcentis.github.io/deming/fr/',

    'pages' => [
        'about' => '',
        'welcome' => 'home/',
        'search' => 'home/#search',

        'controls' => [
            'index' => 'measures/#list',
            'show' => 'measures/#show',
            'create' => 'measures/#create',
            'edit' => 'measures/#edit',
            'plan' => 'measures/#plan',
            'import' => 'config/#import',
        ],

        'measures' => [
            'index' => 'controls/#list',
            'show' => 'controls/#show',
            'create' => 'controls/#create',
            'edit' => 'controls/#edit',
            'plan' => 'controls/#plan',
            'make' => 'controls/#make',
            'history' => 'plan/',
        ],

        'actions' => [
            'index' => 'actions/#list',
            'show' => 'actions/#show',
            'create' => 'actions/#create',
            'edit' => 'actions/#edit',
            'close' => 'actions/#close',
        ],

        'radar' => [
            'monitoring' => 'dashboards/#steering',
            'domains' => 'dashboards/#domains',
            'controls' => 'dashboards/#measures',
            'measures' => 'dashboards/#controls',
            'attributes' => 'dashboards/#attributes',
            'actions' => 'dashboards/#actions',
        ],

        'risks' => [
            'index' => 'risks/#list',
            'show' => 'risks/#show',
            'create' => 'risks/#create',
            'edit' => 'risks/#edit',
            'matrix' => 'risks/#matrix',
            'scoring_index' => 'risks/#scoring-list',
            'scoring_form' => 'risks/#scoring-edit',
        ],

        'exceptions' => [
            'index' => 'exceptions/#list',
            'show' => 'exceptions/#show',
            'create' => 'exceptions/#create',
            'edit' => 'exceptions/#edit',
        ],

        'crosswalk' => 'crosswalk/',
        'reports' => 'config/#reports',
        'attributes' => 'config/#tags',
        'domains' => 'config/#domains',
        'users' => 'config/#users',
        'groups' => 'config/#groups',
        'documents' => 'config/#documents',
        'notifications' => 'config/#notifications',
        'logs' => 'config/#logs',
        'ai_config' => 'ai/#config',
        'ai_use' => 'ai/#use',
    ],
];
