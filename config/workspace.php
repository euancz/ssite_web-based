<?php

// Keep sidebar copy and destinations together; User capabilities still authorize every route.
return [
    'brand' => 'SSITE Workspace',
    'roles' => [
        'adviser' => 'Adviser',
        'superadmin' => 'Superadmin',
    ],
    'default_modules' => [
        'President' => 'articles',
        'Secretary' => 'documents',
        'Auditor' => 'liquidations',
        'Multimedia' => 'articles',
    ],
    'sections' => [
        'overview' => 'Overview',
        'create' => 'Create',
        'library' => 'Post library',
        'administration' => 'Administration',
    ],
    'navigation' => [
        'dashboard' => ['capability' => 'workspace'],
        'review' => ['gate' => 'review-posts'],
        'officers' => ['gate' => 'manage-officers'],
        'users' => ['gate' => 'manage-users'],
    ],
    'modules' => [
        'articles' => [
            'label' => 'Articles',
            'singular' => 'Article',
            'icon' => 'file',
            'index' => 'articles.index',
            'editorial' => 'editorial.articles.index',
            'create' => 'articles.create',
            'capability' => 'canPostIn:articles',
        ],
        'activities' => [
            'label' => 'Activities',
            'singular' => 'Activity',
            'icon' => 'calendar',
            'index' => 'activities.index',
            'editorial' => 'editorial.activities.index',
            'create' => 'activities.create',
            'capability' => 'canPostIn:activities',
        ],
        'achievements' => [
            'label' => 'Achievements',
            'singular' => 'Achievement',
            'icon' => 'award',
            'index' => 'achievements.index',
            'editorial' => 'editorial.achievements.index',
            'create' => 'achievements.create',
            'capability' => 'canPostIn:achievements',
        ],
        'documents' => [
            'label' => 'Documents',
            'singular' => 'Document',
            'icon' => 'file',
            'index' => 'documents.index',
            'editorial' => 'editorial.documents.index',
            'create' => 'documents.create',
            'capability' => 'canPostIn:documents',
        ],
        'liquidations' => [
            'label' => 'Liquidation',
            'singular' => 'Liquidation report',
            'icon' => 'chart',
            'index' => 'liquidations.index',
            'editorial' => 'editorial.liquidations.index',
            'create' => 'liquidations.create',
            'capability' => 'canPostIn:liquidations',
        ],
    ],
];
