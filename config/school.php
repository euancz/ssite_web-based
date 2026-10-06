<?php

return [
    // The A.Y. changes automatically in this month; no scheduled rollover is needed.
    'academic_year_start_month' => 6,

    // TODO: Confirm and fill this list with the official officer positions in display order.
    'officer_positions' => [
        'Adviser', 'Student Adviser', 'President', 'Vice President (Internal)',
        'Vice President (External)', 'Secretary', 'Treasurer', 'Auditor',
        'Public Information Officer', 'Business Manager', 'Social Media Manager',
        'Multimedia (Creative)', 'Multimedia (Documentation)',
        'IT Representative I', 'IT Representative II',
    ],
    // Advisers may be exempted; other signed-in roles must complete their profile by default.
    'require_profile_for_all_roles' => (bool) env('SCHOOL_REQUIRE_PROFILE_FOR_ALL_ROLES', true),

    // Adviser-created articles are published immediately unless this setting is disabled.
    'adviser_posts_auto_approved' => (bool) env('SCHOOL_ADVISER_POSTS_AUTO_APPROVED', true),

    // Editing an approved officer article sends the changed content back for adviser review.
    'reset_approval_on_edit' => (bool) env('SCHOOL_RESET_APPROVAL_ON_EDIT', true),

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
