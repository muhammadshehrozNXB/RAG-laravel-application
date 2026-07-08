<?php

return [
    'api_key'         => env('OPENAI_API_KEY', ''),
    'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'chat_model'      => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
];
