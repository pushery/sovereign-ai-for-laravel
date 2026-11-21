<?php

declare(strict_types=1);

namespace Pushery\SovereignAi;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\AiManager;
use Laravel\Ai\Events\GeneratingEmbeddings;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StreamingAgent;
use Override;

final class SovereignAiServiceProvider extends ServiceProvider
{
    /**
     * The sections of the config that are catalogs, as dotted paths from the config key: maps whose
     * keys the host chooses, one entry per plan, per document or per signing key. A key that a
     * published catalog leaves out was removed by the host rather than added by a later release, so
     * the merge takes a published catalog as it stands. A catalog the host never published arrives
     * whole from the default. A package lists such a section here, for example
     * 'sovereign-ai.plans'.
     *
     * @var list<string>
     */
    private const array CATALOG_SECTIONS = [];

    #[Override]
    public function register(): void
    {
        $this->mergeConfigRecursivelyFrom(__DIR__.'/../config/sovereign-ai.php', 'sovereign-ai');
    }

    public function boot(): void
    {
        // One driver per cloud, each building `laravel/ai`'s OpenAI-compatible provider with the
        // gateways that hold its requests. The manager binds a driver's closure to itself, so the
        // closure is not static.
        $this->callAfterResolving(AiManager::class, static function (AiManager $manager): void {
            foreach (Cloud::cases() as $cloud) {
                $manager->extend($cloud->value, fn (Container $app, array $config): SovereignAiProvider => new SovereignAiProvider($config, $app->make(Dispatcher::class)));
            }
        });

        // Every provider has registered by now, so `laravel/ai` has merged its own config/ai.php:
        // written in register(), the `providers` block written here could stand in for the one it
        // ships, and its own providers would be gone.
        $registration = new Registration($this->app->make(Repository::class));
        $registration->write();
        $registration->verify();

        $events = $this->app->make(Dispatcher::class);
        $events->listen(PromptingAgent::class, [CapabilityGuard::class, 'prompting']);
        $events->listen(StreamingAgent::class, [CapabilityGuard::class, 'prompting']);
        $events->listen(GeneratingEmbeddings::class, [CapabilityGuard::class, 'embedding']);

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    /**
     * Lay the shipped defaults under a published config file, all the way down.
     *
     * Laravel's own `mergeConfigFrom()` is a single `array_merge` at the top level, so it asks
     * one question per top-level key: is it there? A host that published the config file has
     * every top-level key, so a key that a later release adds inside one of those blocks would
     * never arrive, and the block the host published would win whole.
     *
     * Nothing would error or log: the new setting would read as `null` (or as `[]`). A feature
     * added in a minor release would be off for exactly the hosts that customized that area, and
     * a corrected security default would not take effect, for as long as the host keeps the file
     * it published.
     *
     * Recursion stops at a list on either side, because a list is a value, not a structure.
     * `array_merge_recursive()` concatenates lists and `array_replace_recursive()` merges them by
     * index: a host narrowing a shipped `['web', 'api']` to `['admin']` gets back
     * `['web', 'api', 'admin']` from the first and `['admin', 'api']` from the second. Both hand
     * back an entry the host removed, which on an allowlist is a security regression. Here the
     * published list stands as written.
     *
     * `array_is_list([])` is true, which is the behavior wanted here: an empty array is a host
     * saying "none", and descending into it could only re-introduce what it emptied.
     *
     * Recursion stops at a catalog as well, a section named in CATALOG_SECTIONS. Its keys are the
     * host's, so a key missing from the published catalog is one the host removed, and laying the
     * default under it would hand that entry back.
     *
     * A host with a cached config is not merged at runtime: the cache already holds the merge
     * `config:cache` did when it ran. A key a later release adds is missing from it until the host
     * caches again, so a package that reads a nested key needs its read-site fallback to agree with
     * the shipped default.
     */
    private function mergeConfigRecursivelyFrom(string $path, string $key): void
    {
        if ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached()) {
            return;
        }

        $shipped = require $path;

        $repository = $this->app->make(Repository::class);
        $existing = $repository->get($key);

        // Both sides come off disk or out of the container, so neither is provably
        // string-keyed here — a config array is just an array. The recursion below is
        // written for exactly that: it asks whether a value is a list, never whether a key
        // is a string.
        $repository->set($key, $this->mergeConfigSections(
            is_array($shipped) ? $shipped : [],
            is_array($existing) ? $existing : [],
            $key.'.',
            self::CATALOG_SECTIONS,
        ));
    }

    /**
     * @param  array<array-key, mixed>  $shipped
     * @param  array<array-key, mixed>  $published
     * @param  string  $path  the dotted path of the two maps, so a catalog is recognized at any depth
     * @param  list<string>  $catalogs  the dotted paths of the catalogs, which the merge takes as published
     * @return array<array-key, mixed>
     */
    private function mergeConfigSections(array $shipped, array $published, string $path = '', array $catalogs = []): array
    {
        foreach ($shipped as $key => $value) {
            if (! array_key_exists($key, $published)) {
                $published[$key] = $value;

                continue;
            }

            // Recurse only where both sides are maps and the section is no catalog. If either is a
            // list, or the published value is a scalar or an explicit null, what the host wrote
            // stands. An explicit null is a value like any other here, which keeps "write => null
            // to switch this off" working exactly as a shipped config file describes it.
            if (is_array($value) && is_array($published[$key]) && ! array_is_list($value) && ! array_is_list($published[$key]) && ! in_array($path.$key, $catalogs, true)) {
                $published[$key] = $this->mergeConfigSections($value, $published[$key], $path.$key.'.', $catalogs);
            }
        }

        return $published;
    }

    private function registerPublishing(): void
    {
        // Resolve the publish target through the Application contract's path method
        // (available via illuminate/contracts), not the config_path() global helper. That is a
        // Foundation helper, shipped only with laravel/framework — which this lean package does
        // not require — so the helper form would freeze a wrong dependency contract and fatal
        // in a non-Foundation host. The method form is behavior-identical.
        //
        // The group carries the bare 'sovereign-ai' umbrella tag on top of its specific one,
        // so `vendor:publish --tag="sovereign-ai"` publishes every resource at once — the tag
        // convention Laravel's official package skeleton establishes. The configuration is the
        // one resource a host adapts by editing a file: the package writes no text to translate,
        // renders no view and ships no migration.
        $this->publishes([
            __DIR__.'/../config/sovereign-ai.php' => $this->app->configPath('sovereign-ai.php'),
        ], ['sovereign-ai', 'sovereign-ai-config']);
    }
}
