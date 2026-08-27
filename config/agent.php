<?php

return [
    'api_token' => env('AGENT_API_TOKEN'),
    'allowed_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('AGENT_ALLOWED_IPS', ''))
    ))),
    'user_id' => env('AGENT_USER_ID'),
];

