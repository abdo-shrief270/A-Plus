<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI (ChatGPT) — AI explanations
    |--------------------------------------------------------------------------
    | The feature is enabled only when an API key is present. Leave the key
    | empty to keep AI explanations off; the endpoint returns a graceful
    | "unavailable" response and the UI hides the button.
    */
    'openai_key' => env('OPENAI_API_KEY', ''),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'request_timeout' => (int) env('OPENAI_TIMEOUT', 30),

    // Flat wallet cost the first time a student requests an AI explanation for
    // a given question (pay-once). Subscribers are free. 0 = free for everyone.
    'explanation_cost' => (int) env('AI_EXPLANATION_COST', 5),
];
