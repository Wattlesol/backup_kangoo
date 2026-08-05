<?php

return [
    'terminology' => [
        'booking' => 'request',
        'bookings' => 'requests',
        'handyman' => 'employee',
        'handymen' => 'employees',
        'provider' => 'partner',
        'providers' => 'partners',
    ],

    'roles' => [
        'admin' => [
            'admin',
            'demo_admin',
        ],
        'partner' => [
            'provider',
        ],
        'employee' => [
            'handyman',
        ],
        'customer' => [
            'user',
        ],
    ],

    'request_lifecycle' => [
        'draft',
        'submitted',
        'pending_review',
        'assigned_to_partner',
        'assigned_to_employee',
        'in_progress',
        'awaiting_customer_action',
        'awaiting_quality_review',
        'completed',
        'rejected',
        'cancelled',
        'escalated',
    ],

    'document_visibility' => [
        'admin',
        'provider',
        'handyman',
        'user',
    ],

    'ai' => [
        'enabled' => env('SANAD_AI_ENABLED', true),
        'requires_escalation_when_confidence_below' => env('SANAD_AI_ESCALATION_THRESHOLD', 0.65),
    ],
];
