---
name: sovereign-ai-for-laravel
description: >
  Install, configure, and apply the Sovereign AI for Laravel package in a Laravel
  application.
license: MIT
metadata:
  author: pushery
---

# Sovereign AI for Laravel

Use this skill when a Laravel application installs or integrates the
`pushery/sovereign-ai-for-laravel` package. The full reference is at
<https://docs.pushery.com/sovereign-ai-for-laravel/>.

## Primary Goal

Apply the package's public API in the smallest correct way for the consuming
application.

## Workflow

### 1. Install

```bash
composer require pushery/sovereign-ai-for-laravel
```

The service provider is registered automatically through package discovery. It needs
`laravel/ai` 1.4 or later, which Composer installs with it.

### 2. Configure

Set the key of each cloud the application uses:

```dotenv
SOVEREIGN_AI_IONOS_KEY=
SOVEREIGN_AI_OVHCLOUD_KEY=
```

Publish the configuration file, with the umbrella tag or with its own:

```bash
php artisan vendor:publish --tag="sovereign-ai"
php artisan vendor:publish --tag="sovereign-ai-config"
```

Every option in `config/sovereign-ai.php` is documented inline. The package ships no
migrations, routes, views or translations.

### 3. Apply the package

The package registers two providers of `laravel/ai`: `ionos` for the IONOS AI Model Hub and
`ovhcloud` for OVHcloud AI Endpoints. Pass one of the names as the `provider` of a prompt, or
both as a list to fall over from the first to the second when the first is rate limited, out of
credits or quota, overloaded, or cannot be reached because the connection is refused or times
out. Name a model with `model`, or leave it out to use the default model of the provider.
Embeddings come from `Laravel\Ai\Ai::embeddingProvider('ionos')->embeddings([...])`.

Before a request that needs structured output, tools or embeddings, the package checks its model
against a catalog and throws `Pushery\SovereignAi\Exceptions\UnsupportedCapability` when the
model lacks the capability. Pick a model the documentation lists with the capability, or name the
model with its capabilities under `sovereign-ai.ionos.catalog` or `sovereign-ai.ovhcloud.catalog`
when the application knows it can do more than the catalog says. An entry there is the model id
with a list of `structured_output`, `tools` and `embeddings`; an entry the package cannot read
stops the boot with `Pushery\SovereignAi\Exceptions\InvalidCatalog`.

## Examples

```php
use function Laravel\Ai\agent;

$answer = agent('You answer in one sentence.')
    ->prompt('What is the capital of France?', provider: ['ionos', 'ovhcloud'])
    ->text;
```

```php
use Laravel\Ai\Ai;

$vectors = Ai::embeddingProvider('ovhcloud')->embeddings(['Invoice 2026-114 is overdue.'])->embeddings;
```

## Anti-Patterns

- Pointing `ai.providers.ionos.url` or `ai.providers.ovhcloud.url` at another address, such as a
  proxy: the package stops the boot with `EndpointNotAllowed`. Only the endpoints listed in the
  documentation are accepted.
- Defining an entry named `ionos` or `ovhcloud` in `config/ai.php` with the `openai-compatible`
  driver: the names belong to the package while it registers them. Use the drivers `ionos` and
  `ovhcloud`, or remove the cloud from `sovereign-ai.providers.register`.
- Sending an agent with a schema to a model whose structured output the catalog does not list,
  and catching the exception to retry elsewhere: choose a model with the capability instead.
- Reading cache or reasoning tokens from the usage to bill a customer: neither cloud reports
  cache tokens, so those counts stay empty.
