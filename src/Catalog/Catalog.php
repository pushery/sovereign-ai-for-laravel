<?php

declare(strict_types=1);

namespace Pushery\SovereignAi\Catalog;

use Illuminate\Contracts\Config\Repository;
use Pushery\SovereignAi\Cloud;
use Pushery\SovereignAi\Exceptions\InvalidCatalog;
use Pushery\SovereignAi\Exceptions\UnsupportedCapability;

/**
 * The models of one cloud with what each can do: the ones the package ships, and the ones the
 * application names under `sovereign-ai.<cloud>.catalog`, which replace a shipped entry of the
 * same id.
 */
final readonly class Catalog
{
    /**
     * @param  array<string, Model>  $models
     */
    private function __construct(
        public Cloud $cloud,
        private array $models,
        private bool $refusesUnknownModels,
    ) {}

    /**
     * The catalog of the cloud, with the models the application adds under
     * `sovereign-ai.<cloud>.catalog`.
     *
     * An entry is read whole or refused: a key that is no model id, a value that is no list and a
     * capability the package does not know each throw. Skipped, a misspelled capability would leave
     * its model without it, over the shipped entry of the same id, and the refusal of the next
     * request that needs it would send its reader back to this setting. A setting that is missing
     * adds no model, as a cached configuration without the key has none.
     *
     * @throws InvalidCatalog
     */
    public static function for(Cloud $cloud, Repository $config): self
    {
        $models = [];

        foreach ($cloud->models() as $model) {
            $models[$model->id] = $model;
        }

        $added = $config->get("sovereign-ai.{$cloud->value}.catalog");

        if ($added !== null && ! is_array($added)) {
            throw InvalidCatalog::setting($cloud, $added);
        }

        foreach ($added ?? [] as $id => $names) {
            if (! is_string($id) || $id === '') {
                throw InvalidCatalog::modelId($cloud, $id, $names);
            }

            if (! is_array($names)) {
                throw InvalidCatalog::capabilityList($cloud, $id, $names);
            }

            $capabilities = [];

            foreach ($names as $name) {
                $capability = is_string($name) ? Capability::tryFrom($name) : null;

                if (! $capability instanceof Capability) {
                    throw InvalidCatalog::unknownCapability($cloud, $id, $name);
                }

                if (! in_array($capability, $capabilities, true)) {
                    $capabilities[] = $capability;
                }
            }

            $models[$id] = new Model($id, $capabilities, 'config');
        }

        return new self($cloud, $models, $config->get('sovereign-ai.providers.refuse_unknown_models') !== false);
    }

    public function model(string $id): ?Model
    {
        return $this->models[$id] ?? null;
    }

    /**
     * Refuse a request that needs a capability the model does not have, before it is sent.
     *
     * A model the catalog does not name is refused as well, unless the application switched that
     * off with `sovereign-ai.providers.refuse_unknown_models`: whether it can do what the request
     * needs is then not known.
     *
     * @throws UnsupportedCapability
     */
    public function assertSupports(string $model, Capability $capability): void
    {
        $entry = $this->model($model);

        if (! $entry instanceof Model) {
            if ($this->refusesUnknownModels) {
                throw UnsupportedCapability::unknownModel($this->cloud, $model, $capability);
            }

            return;
        }

        if (! $entry->supports($capability)) {
            throw UnsupportedCapability::missing($this->cloud, $entry, $capability);
        }
    }
}
