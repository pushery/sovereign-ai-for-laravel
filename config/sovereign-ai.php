<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Sovereign AI for Laravel
    |--------------------------------------------------------------------------
    |
    | IONOS AI Model Hub and OVHcloud AI Endpoints as providers of the Laravel AI
    | SDK. Published to config/sovereign-ai.php with
    | `php artisan vendor:publish --tag=sovereign-ai-config`.
    |
    | Read environment values with env() here and nowhere else. Outside a config
    | file env() returns null the moment the host app caches its config, so the
    | value silently collapses to its default in production and nowhere else.
    |
    */

    'providers' => [

        /*
        | The clouds registered in `ai.providers`, each under its own name. An
        | application with a contract at only one of them removes the other, which
        | leaves that name free. A name this package does not know stops the boot.
        */
        'register' => ['ionos', 'ovhcloud'],

        /*
        | A request that needs structured output, tool calls or embeddings from a
        | model the catalog does not name is refused before it is sent, because
        | whether the model can do it is not known. Set this to false to send such
        | a request anyway. A model the catalog names without the capability is
        | refused either way.
        */
        'refuse_unknown_models' => true,

        /*
        | The seconds a request waits for its cloud when `laravel/ai` sends it
        | without a timeout, such as the prompt that titles a remembered
        | conversation; `laravel/ai` waits 60 there. Every other request carries a
        | timeout of its own, which this does not change: an agent waits 60 seconds
        | unless `#[Timeout]`, a `timeout()` method or `timeout:` on `prompt()`
        | names another, and embeddings and a transcription wait 30 unless
        | `timeout:` or `->timeout()` names another. Read typed and held to a
        | floor of 1, so a value no timeout can use lands on 60 rather than on no
        | limit.
        */
        'timeout' => is_numeric($timeout = env('SOVEREIGN_AI_TIMEOUT')) && (int) $timeout >= 1
            ? (int) $timeout
            : 60,

    ],

    /*
    | IONOS AI Model Hub, served from its data center in Berlin. The key is a
    | token from the IONOS Token Manager. The URL must be one of the endpoints
    | the package allows for IONOS; any other value stops the boot.
    */
    'ionos' => [
        'key' => env('SOVEREIGN_AI_IONOS_KEY'),
        'url' => 'https://openai.inference.de-txl.ionos.com/v1',
        'models' => [
            'text' => [
                'default' => 'meta-llama/Llama-3.3-70B-Instruct',
            ],
            'embeddings' => [
                'default' => 'BAAI/bge-m3',
            ],
        ],

        /*
        | Models the catalog does not name, or names with less than they can do
        | today, each with the list of its capabilities: structured_output, tools,
        | embeddings. An entry here replaces the catalog's entry of the same model,
        | and an entry the package cannot read, such as a capability by another
        | name, stops the boot.
        |
        |     'vendor/model' => ['structured_output', 'tools'],
        */
        'catalog' => [],
    ],

    /*
    | OVHcloud AI Endpoints. The key is an access key of a Public Cloud project.
    | The URL must be one of the endpoints the package allows for OVHcloud; any
    | other value stops the boot.
    */
    'ovhcloud' => [
        'key' => env('SOVEREIGN_AI_OVHCLOUD_KEY'),
        'url' => 'https://oai.endpoints.kepler.ai.cloud.ovh.net/v1',
        'models' => [
            'text' => [
                'default' => 'Mistral-Small-3.2-24B-Instruct-2506',
            ],
            'embeddings' => [
                'default' => 'bge-m3',
            ],
        ],

        /*
        | Models the catalog does not name, or names with less than they can do
        | today, in the same form as for IONOS above.
        */
        'catalog' => [],
    ],

];
