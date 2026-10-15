<?php

return [
    // Engagement features require their externally managed columns/table; runtime checks keep missing schema inert.
    'enable_view_counts' => (bool) env('SCHOOL_ENABLE_VIEW_COUNTS', true),
    'enable_likes' => (bool) env('SCHOOL_ENABLE_LIKES', true),
    'view_dedupe_hours' => (int) env('SCHOOL_VIEW_DEDUPE_HOURS', 12),
    'view_bot_user_agents' => ['bot', 'crawler', 'spider', 'slurp', 'facebookexternalhit', 'preview'],

    // The A.Y. changes automatically in this month; no scheduled rollover is needed.
    'academic_year_start_month' => 6,

    // Display order includes historical titles; only keys in positions are assignable capabilities.
    'officer_positions' => [
        'Adviser', 'Student Adviser', 'President', 'Vice President (Internal)',
        'Vice President (External)', 'Secretary', 'Treasurer', 'Auditor',
        'Public Information Officer', 'Business Manager', 'Social Media Manager',
        'Multimedia (Creative)', 'Multimedia (Documentation)',
        'IT Representative I', 'IT Representative II',
    ],
    // SECURITY: Officer permissions come from canonical config keys, never free-text positions.
    'positions' => [
        'President' => [
            'modules' => ['articles', 'activities', 'achievements', 'documents', 'liquidations'],
            'can_review' => true,
            'auto_approved' => true,
            'single_holder' => true,
        ],
        'Secretary' => [
            'modules' => ['documents'],
            'can_review' => false,
            'auto_approved' => false,
            'single_holder' => false,
        ],
        'Auditor' => [
            'modules' => ['liquidations'],
            'can_review' => false,
            'auto_approved' => false,
            'single_holder' => false,
        ],
        'Multimedia' => [
            'modules' => ['articles', 'achievements', 'activities'],
            'can_review' => false,
            'auto_approved' => false,
            'single_holder' => false,
        ],
    ],
    // Enforced during adviser role assignments; config can allow multiple holders later.
    'single_holder_positions' => ['President'],
    // Advisers may be exempted; other signed-in roles must complete their profile by default.
    'require_profile_for_all_roles' => (bool) env('SCHOOL_REQUIRE_PROFILE_FOR_ALL_ROLES', true),

    // President posts publish immediately by default; set false to send them through review.
    'president_posts_auto_approved' => (bool) env('SCHOOL_PRESIDENT_POSTS_AUTO_APPROVED', true),

    // Editing approved officer content sends the changed content back for President review.
    'reset_approval_on_edit' => (bool) env('SCHOOL_RESET_APPROVAL_ON_EDIT', true),

    // PDF lists may be public; stored files remain private and are delivered through authorized routes.
    'documents_list_public' => (bool) env('SCHOOL_DOCUMENTS_LIST_PUBLIC', true),
    'liquidation_list_public' => (bool) env('SCHOOL_LIQUIDATION_LIST_PUBLIC', false),
    // SECURITY: Guest search follows the existing list visibility flags unless explicitly overridden.
    'search_include_documents_for_guests' => (bool) env('SCHOOL_SEARCH_INCLUDE_DOCUMENTS_FOR_GUESTS', env('SCHOOL_DOCUMENTS_LIST_PUBLIC', true)),
    'search_include_liquidation_for_guests' => (bool) env('SCHOOL_SEARCH_INCLUDE_LIQUIDATION_FOR_GUESTS', env('SCHOOL_LIQUIDATION_LIST_PUBLIC', false)),
    'max_pdf_size_kb' => (int) env('SCHOOL_MAX_PDF_SIZE_KB', 10240),

    // Set to an empty string to allow any valid Microsoft email domain.
    'microsoft_email_domain' => env('SCHOOL_MICROSOFT_EMAIL_DOMAIN', 'mcc.edu.ph'),

    // Used for new Microsoft accounts until a profile image upload flow is added.
    'default_profile_picture' => 'images/Wolf.png',

    // TODO: Replace the empty options with the official institute names for this school.
    'institutes' => [
        'ICS',
        'IHTM',
        'IBE',
        'ITE',
        'IAS',
    ],

    // TODO: Add official programs here when they are not filtered by institute.
    'programs' => [
        'BSIT',
    ],

    // TODO: If programs differ by institute, map each institute name to its program labels here.
    'programs_by_institute' => [],

    // Stored values remain the same display labels shown on the form.
    'year_levels' => [
        '1st Year',
        '2nd Year',
        '3rd Year',
        '4th Year',
        '5th Year',
    ],

    // Limit profile submissions to the school's supported options.
    'genders' => [
        'Male',
        'Female',
        'Prefer not to say',
    ],

    // TODO: Replace this default if student numbers follow a stricter school-specific format.
    'student_number_regex' => env(
        'SCHOOL_STUDENT_NUMBER_REGEX',
        '/^[A-Za-z0-9][A-Za-z0-9-]{0,19}$/'
    ),
];
