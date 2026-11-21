<?php

declare(strict_types=1);

namespace Pushery\SovereignAi\Exceptions;

use InvalidArgumentException;
use Pushery\SovereignAi\Catalog\Capability;
use Pushery\SovereignAi\Cloud;

/**
 * The models an application added under `sovereign-ai.<cloud>.catalog` cannot be read as the id of
 * a model with the list of its capabilities.
 */
final class InvalidCatalog extends InvalidArgumentException
{
    /**
     * @param  mixed  $value  the value as the catalog setting holds it, which a host may write as any type
     */
    public static function setting(Cloud $cloud, mixed $value): self
    {
        return new self(sprintf(
            'The setting [sovereign-ai.%s.catalog] of %s is [%s], where it takes the ids of models, each with the list of its capabilities. Known capabilities: %s.',
            $cloud->value,
            $cloud->label(),
            self::describe($value),
            self::known(),
        ));
    }

    /**
     * @param  mixed  $value  the value as the catalog setting holds it, which a host may write as any type
     */
    public static function modelId(Cloud $cloud, int|string $key, mixed $value): self
    {
        return new self(sprintf(
            'The setting [sovereign-ai.%s.catalog] of %s holds [%s] under %s, where it takes the id of a model with the list of its capabilities. Known capabilities: %s.',
            $cloud->value,
            $cloud->label(),
            self::describe($value),
            $key === '' ? 'an empty key' : "the key [{$key}]",
            self::known(),
        ));
    }

    /**
     * @param  mixed  $value  the value as the catalog setting holds it, which a host may write as any type
     */
    public static function capabilityList(Cloud $cloud, string $model, mixed $value): self
    {
        return new self(sprintf(
            'The setting [sovereign-ai.%s.catalog] of %s gives the model [%s] the value [%s], which is no list of capabilities. Known capabilities: %s.',
            $cloud->value,
            $cloud->label(),
            $model,
            self::describe($value),
            self::known(),
        ));
    }

    /**
     * @param  mixed  $name  the capability as the catalog setting names it, which a host may write as any type
     */
    public static function unknownCapability(Cloud $cloud, string $model, mixed $name): self
    {
        return new self(sprintf(
            'The setting [sovereign-ai.%s.catalog] of %s names [%s] for the model [%s], which is not a capability this package knows. Known: %s.',
            $cloud->value,
            $cloud->label(),
            self::describe($name),
            $model,
            self::known(),
        ));
    }

    /**
     * A value the way a message shows it: a string as it is written, anything else by its type.
     *
     * @param  mixed  $value  any value a message about the catalog has to show
     */
    private static function describe(mixed $value): string
    {
        return is_string($value) ? $value : get_debug_type($value);
    }

    /**
     * The values a capability is written as, in the order Capability declares them.
     */
    private static function known(): string
    {
        return implode(', ', array_map(static fn (Capability $capability): string => $capability->value, Capability::cases()));
    }
}
