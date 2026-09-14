<?php

return [
    'autoload' => false,
    'hooks' => [
        'sms_send' => [
            'alisms',
        ],
        'sms_notice' => [
            'alisms',
        ],
        'sms_check' => [
            'alisms',
        ],
        'epay_config_init' => [
            'epay',
        ],
        'addon_action_begin' => [
            'epay',
        ],
        'action_begin' => [
            'epay',
        ],
        'config_init' => [
            'umeditor',
        ],
        'user_sidenav_after' => [
            'withdraw',
        ],
    ],
    'route' => [],
    'priority' => [],
    'domain' => '',
];
