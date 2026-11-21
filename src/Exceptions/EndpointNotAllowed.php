<?php

declare(strict_types=1);

namespace Pushery\SovereignAi\Exceptions;

use InvalidArgumentException;
use Pushery\SovereignAi\Cloud;

/**
 * A provider of this package was configured in a way that could send a request somewhere other
 * than its cloud's endpoints.
 */
final class EndpointNotAllowed extends InvalidArgumentException
{
    /**
     * @param  mixed  $url  the URL as the configuration holds it, which a host may write as any type
     */
    public static function url(string $provider, Cloud $cloud, mixed $url): self
    {
        return new self(sprintf(
            'The AI provider [%s] points to [%s], which is not an endpoint of %s this package allows. Allowed: %s.',
            $provider,
            is_string($url) ? $url : get_debug_type($url),
            $cloud->label(),
            implode(', ', $cloud->endpoints()),
        ));
    }

    /**
     * @param  mixed  $driver  the driver as the configuration holds it, which a host may write as any type
     */
    public static function driver(Cloud $cloud, mixed $driver): self
    {
        return new self(sprintf(
            'The AI provider [%s] uses the driver [%s], and with this package installed the name belongs to %s. Set its driver to [%s], or leave the name to this package by removing the entry from config/ai.php.',
            $cloud->value,
            is_string($driver) ? $driver : get_debug_type($driver),
            $cloud->label(),
            $cloud->value,
        ));
    }

    /**
     * @param  mixed  $name  the entry of the register list as the configuration holds it, which a host may write as any type
     */
    public static function unknownCloud(mixed $name): self
    {
        return new self(sprintf(
            'The setting [sovereign-ai.providers.register] names [%s], which is not a cloud this package knows. Known: %s.',
            is_string($name) ? $name : get_debug_type($name),
            implode(', ', array_map(static fn (Cloud $cloud): string => $cloud->value, Cloud::cases())),
        ));
    }
}
