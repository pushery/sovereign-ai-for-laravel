<?php

declare(strict_types=1);

namespace Pushery\SovereignAi\Catalog;

/**
 * A model one of the clouds serves, with the capabilities it has and where that is written down.
 */
final readonly class Model
{
    /**
     * @param  string  $id  The id a request names in its `model` field.
     * @param  list<Capability>  $capabilities
     * @param  string  $source  The page the capabilities were read from, or `config` for a model the application added.
     */
    public function __construct(
        public string $id,
        public array $capabilities,
        public string $source,
    ) {}

    public function supports(Capability $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }
}
