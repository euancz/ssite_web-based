<?php

return [
    'require_profile_for_all_roles' => (bool) env('SCHOOL_REQUIRE_PROFILE_FOR_ALL_ROLES', true),
    'microsoft_email_domain' => env('SCHOOL_MICROSOFT_EMAIL_DOMAIN', 'mcc.edu.ph'),
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

    'year_levels' => [
        '1st Year',
        '2nd Year',
        '3rd Year',
        '4th Year',
        '5th Year',
    ],

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
