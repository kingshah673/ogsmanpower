<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeker (candidate) settings sections — keys match anchor ids on /candidate/settings
    |--------------------------------------------------------------------------
    */
    'seeker_sections' => [
        'basic-info' => 'Basic Information',
        'job-requirements' => 'Job Requirements',
        'pro-details' => 'Summary / Professional Details',
        'skills' => 'Skills',
        'languages' => 'Languages',
        'work-exp' => 'Experience',
        'social' => 'Social Settings',
        'contact' => 'Contact Settings',
        'attachment' => 'Attachments',
        'privacy' => 'Profile Privacy',
    ],

    'seeker_builtin_fields' => [
        'basic-info' => [
            ['label' => 'Profile Photo', 'type' => 'file'],
            ['label' => 'First Name', 'type' => 'text', 'required' => true],
            ['label' => 'Last Name', 'type' => 'text', 'required' => true],
            ['label' => 'Professional Title / Tagline', 'type' => 'text'],
            ['label' => 'Experience Level', 'type' => 'dropdown', 'required' => true],
            ['label' => 'Education Level', 'type' => 'dropdown', 'required' => true],
            ['label' => 'Date of Birth', 'type' => 'date', 'required' => true],
            ['label' => 'Country', 'type' => 'dropdown'],
            ['label' => 'State / Region', 'type' => 'dropdown'],
            ['label' => 'City / District', 'type' => 'dropdown'],
            ['label' => 'Gender', 'type' => 'dropdown'],
            ['label' => 'Marital Status', 'type' => 'dropdown'],
            ['label' => 'WhatsApp Number', 'type' => 'text'],
            ['label' => 'Passport Number', 'type' => 'text'],
            ['label' => 'Passport Issue / Expiry Date', 'type' => 'date'],
            ['label' => 'National ID / CNIC', 'type' => 'text'],
        ],
        'job-requirements' => [
            ['label' => 'Desired Jobs / Professions', 'type' => 'dropdown', 'required' => true],
            ['label' => 'Desired Industries', 'type' => 'dropdown', 'required' => true],
            ['label' => 'Preferred Region', 'type' => 'text', 'required' => true],
            ['label' => 'Expected Salary', 'type' => 'number', 'required' => true],
            ['label' => 'Currency', 'type' => 'dropdown', 'required' => true],
            ['label' => 'Preferred Country', 'type' => 'dropdown', 'required' => true],
            ['label' => 'Preferred State', 'type' => 'dropdown'],
            ['label' => 'Preferred City', 'type' => 'dropdown'],
        ],
        'pro-details' => [
            ['label' => 'Summary / Bio', 'type' => 'textarea'],
        ],
        'skills' => [
            ['label' => 'Skills', 'type' => 'dropdown', 'required' => true],
        ],
        'languages' => [
            ['label' => 'Languages', 'type' => 'dropdown'],
        ],
        'work-exp' => [
            ['label' => 'Work Experience (company, role, dates)', 'type' => 'textarea'],
            ['label' => 'Education (school, degree, dates)', 'type' => 'textarea'],
        ],
        'social' => [
            ['label' => 'Social Platform', 'type' => 'dropdown'],
            ['label' => 'Profile URL', 'type' => 'text'],
        ],
        'contact' => [
            ['label' => 'Phone', 'type' => 'text'],
            ['label' => 'Secondary Phone', 'type' => 'text'],
            ['label' => 'WhatsApp Number', 'type' => 'text'],
            ['label' => 'Email Address', 'type' => 'email'],
        ],
        'attachment' => [
            ['label' => 'Resume / CV', 'type' => 'file'],
            ['label' => 'Passport Image', 'type' => 'file'],
            ['label' => 'License / ID Image', 'type' => 'file'],
            ['label' => 'Supporting Documents', 'type' => 'file'],
        ],
        'privacy' => [
            ['label' => 'Profile Visibility', 'type' => 'dropdown'],
            ['label' => 'CV Visibility', 'type' => 'dropdown'],
            ['label' => 'Job Alert Preferences', 'type' => 'dropdown'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Employer (company) settings sections — keys match ids on company settings page
    |--------------------------------------------------------------------------
    */
    'employer_sections' => [
        'section-branding' => 'Branding',
        'section-info' => 'Company Info',
        'section-social' => 'Social Media',
        'section-contact' => 'Contact & Location',
        'section-account' => 'Account',
        'job_post' => 'Job Post Form (Post/Edit Job)',
    ],

    'employer_builtin_fields' => [
        'section-branding' => [
            ['label' => 'Company Logo', 'type' => 'file'],
            ['label' => 'Banner Image', 'type' => 'file'],
            ['label' => 'Company Name', 'type' => 'text', 'required' => true],
            ['label' => 'About Us', 'type' => 'textarea'],
        ],
        'section-info' => [
            ['label' => 'Organization Type', 'type' => 'dropdown'],
            ['label' => 'Industry Type', 'type' => 'dropdown'],
            ['label' => 'Team Size', 'type' => 'dropdown'],
            ['label' => 'Year of Establishment', 'type' => 'date'],
            ['label' => 'Website', 'type' => 'text'],
            ['label' => 'Company Vision', 'type' => 'textarea'],
        ],
        'section-social' => [
            ['label' => 'Social Platform', 'type' => 'dropdown'],
            ['label' => 'Profile Link URL', 'type' => 'text'],
        ],
        'section-contact' => [
            ['label' => 'Phone', 'type' => 'text'],
            ['label' => 'Email', 'type' => 'email'],
            ['label' => 'Address / Location', 'type' => 'textarea'],
            ['label' => 'Country', 'type' => 'dropdown'],
            ['label' => 'City', 'type' => 'dropdown'],
        ],
        'section-account' => [
            ['label' => 'Account Email', 'type' => 'email'],
            ['label' => 'Username', 'type' => 'text'],
            ['label' => 'Password', 'type' => 'password'],
        ],
        'job_post' => [
            ['label' => 'Job Title', 'type' => 'text', 'required' => true],
            ['label' => 'Job Description', 'type' => 'textarea', 'required' => true],
            ['label' => 'Job Category', 'type' => 'dropdown', 'required' => true],
            ['label' => 'Job Type', 'type' => 'dropdown'],
            ['label' => 'Salary / Compensation', 'type' => 'text'],
            ['label' => 'Skills', 'type' => 'dropdown'],
            ['label' => 'Benefits', 'type' => 'dropdown'],
            ['label' => 'Experience Required', 'type' => 'dropdown'],
            ['label' => 'Education Required', 'type' => 'dropdown'],
            ['label' => 'Job Location', 'type' => 'text'],
        ],
    ],

];
