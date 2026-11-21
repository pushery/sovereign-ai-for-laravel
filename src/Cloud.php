<?php

declare(strict_types=1);

namespace Pushery\SovereignAi;

use Pushery\SovereignAi\Catalog\Model;
use Pushery\SovereignAi\Catalog\Shipped;

/**
 * The two clouds this package sends requests to, each held to the endpoints listed here.
 *
 * The value of a case is the name its provider is registered under in `ai.providers`, and the name
 * of the `laravel/ai` driver that builds it.
 */
enum Cloud: string
{
    case Ionos = 'ionos';
    case OvhCloud = 'ovhcloud';

    /**
     * The name the cloud goes by in a message.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ionos => 'IONOS AI Model Hub',
            self::OvhCloud => 'OVHcloud AI Endpoints',
        };
    }

    /**
     * The OpenAI-compatible base URLs a request to this cloud may go to. A configured URL outside
     * this list is refused when the application boots and again before every request.
     *
     * @return non-empty-list<string>
     */
    public function endpoints(): array
    {
        return match ($this) {
            self::Ionos => ['https://openai.inference.de-txl.ionos.com/v1'],
            self::OvhCloud => ['https://oai.endpoints.kepler.ai.cloud.ovh.net/v1'],
        };
    }

    /**
     * Whether a configured URL is one of this cloud's endpoints. A trailing slash makes no
     * difference, as `laravel/ai` strips it before it builds a request.
     *
     * @param  mixed  $url  the URL as the configuration holds it, which a host may write as any type
     */
    public function allows(mixed $url): bool
    {
        return is_string($url) && in_array(rtrim($url, '/'), $this->endpoints(), true);
    }

    /**
     * The models of this cloud the package knows, with what each can do.
     *
     * @return list<Model>
     */
    public function models(): array
    {
        return Shipped::models($this);
    }
}
