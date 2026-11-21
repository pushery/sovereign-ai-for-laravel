<?php

declare(strict_types=1);

namespace Pushery\SovereignAi;

use Illuminate\Contracts\Config\Repository;
use Pushery\SovereignAi\Catalog\Catalog;
use Pushery\SovereignAi\Exceptions\EndpointNotAllowed;
use Pushery\SovereignAi\Exceptions\InvalidCatalog;

/**
 * Writes the providers of this package into `ai.providers`, and checks every entry that uses one of
 * its drivers and the models the application adds to the catalog of each cloud.
 */
final readonly class Registration
{
    public function __construct(private Repository $config) {}

    /**
     * Register each cloud the application keeps in `sovereign-ai.providers.register` under its name,
     * with its driver, its endpoint, its key, its models, the timeout of a request `laravel/ai`
     * sends without one, and the stream options that make a streamed answer report its tokens:
     * `laravel/ai` sends `stream_options` to an OpenAI-compatible provider only where its
     * configuration names them. An entry the application wrote into config/ai.php under the same
     * name keeps every key it sets.
     */
    public function write(): void
    {
        $timeout = $this->config->get('sovereign-ai.providers.timeout');

        foreach ($this->clouds() as $cloud) {
            $settings = $this->config->get("sovereign-ai.{$cloud->value}");
            $settings = is_array($settings) ? $settings : [];

            $shipped = array_filter([
                'driver' => $cloud->value,
                'url' => $settings['url'] ?? $cloud->endpoints()[0],
                'key' => $settings['key'] ?? null,
                'models' => $settings['models'] ?? null,
                'timeout' => is_int($timeout) && $timeout >= 1 ? $timeout : null,
                'stream_options' => ['include_usage' => true],
            ], $this->isGiven(...));

            $existing = $this->config->get("ai.providers.{$cloud->value}");

            $this->config->set("ai.providers.{$cloud->value}", array_replace($shipped, is_array($existing) ? $existing : []));
        }
    }

    /**
     * Whether a setting is given, so the entry this package writes leaves out what is not.
     *
     * @param  mixed  $value  a setting as the configuration holds it, which a host may write as any type
     */
    private function isGiven(mixed $value): bool
    {
        return $value !== null;
    }

    /**
     * Refuse a configuration under which a request could leave the clouds' endpoints: a cloud
     * this package does not know in the register list, an entry of `ai.providers` with one of this
     * package's drivers and a URL outside its cloud's list, and a registered name whose entry uses
     * another driver.
     *
     * It also refuses an entry under `sovereign-ai.<cloud>.catalog` it cannot read, for every cloud,
     * registered or not: an entry of `ai.providers` under another name can use the driver of a cloud
     * the register list leaves out, and its requests are checked against that cloud's catalog.
     *
     * @throws EndpointNotAllowed
     * @throws InvalidCatalog
     */
    public function verify(): void
    {
        $register = $this->config->get('sovereign-ai.providers.register');

        foreach (is_array($register) ? $register : [] as $name) {
            if (! is_string($name) || Cloud::tryFrom($name) === null) {
                throw EndpointNotAllowed::unknownCloud($name);
            }
        }

        $providers = $this->config->get('ai.providers');

        foreach (is_array($providers) ? $providers : [] as $name => $entry) {
            $driver = is_array($entry) ? ($entry['driver'] ?? null) : null;
            $cloud = is_string($driver) ? Cloud::tryFrom($driver) : null;

            if ($cloud !== null && ! $cloud->allows($entry['url'] ?? null)) {
                throw EndpointNotAllowed::url((string) $name, $cloud, $entry['url'] ?? null);
            }
        }

        foreach ($this->clouds() as $cloud) {
            $driver = $this->config->get("ai.providers.{$cloud->value}.driver");

            if ($driver !== $cloud->value) {
                throw EndpointNotAllowed::driver($cloud, $driver);
            }
        }

        foreach (Cloud::cases() as $cloud) {
            Catalog::for($cloud, $this->config);
        }
    }

    /**
     * The clouds the application keeps registered. A name this package does not know is left to
     * verify(), which refuses it.
     *
     * @return list<Cloud>
     */
    private function clouds(): array
    {
        $register = $this->config->get('sovereign-ai.providers.register');
        $clouds = [];

        foreach (is_array($register) ? $register : [] as $name) {
            $cloud = is_string($name) ? Cloud::tryFrom($name) : null;

            if ($cloud !== null && ! in_array($cloud, $clouds, true)) {
                $clouds[] = $cloud;
            }
        }

        return $clouds;
    }
}
