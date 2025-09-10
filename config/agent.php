<?php

return [
    'api_token' => env('AGENT_API_TOKEN'),
    'allowed_ips' => array_filter(explode(',', env('AGENT_ALLOWED_IPS', ''))),
    'user_id' => env('AGENT_USER_ID'),
];

