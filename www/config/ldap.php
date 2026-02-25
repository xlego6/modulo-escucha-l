<?php

return [
    'enabled' => env('LDAP_ENABLED', false),
    'host' => env('LDAP_HOST', ''),
    'port' => (int) env('LDAP_PORT', 389),
    'base_dn' => env('LDAP_BASE_DN', ''),
    'bind_format' => env('LDAP_BIND_FORMAT', 'UPN'), // UPN | DOMAIN_SAM
    'domain' => env('LDAP_DOMAIN', ''),
    'netbios' => env('LDAP_NETBIOS', ''),
    'use_ssl' => env('LDAP_USE_SSL', false),
    'use_starttls' => env('LDAP_USE_STARTTLS', false),
    'timeout' => (int) env('LDAP_TIMEOUT', 5),
];
