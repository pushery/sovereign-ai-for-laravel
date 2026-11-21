<?php

declare(strict_types=1);

namespace Pushery\SovereignAi\Exceptions;

use Laravel\Ai\Exceptions\AiException;
use Pushery\SovereignAi\Catalog\Capability;
use Pushery\SovereignAi\Catalog\Model;
use Pushery\SovereignAi\Cloud;

/**
 * A request needed something its model cannot do, and was not sent.
 */
final class UnsupportedCapability extends AiException
{
    public static function missing(Cloud $cloud, Model $model, Capability $capability): self
    {
        return new self(sprintf(
            'The model [%s] of %s does not support %s, so the request was not sent. Pick a model that does, or name what this one can do under [sovereign-ai.%s.catalog] if the catalog is out of date.',
            $model->id,
            $cloud->label(),
            $capability->label(),
            $cloud->value,
        ));
    }

    /**
     * Neither cloud offers a classification: `laravel/ai` sends one to the `decisions` endpoint of
     * OpenAI, which IONOS does not serve and OVHcloud serves for none of its models.
     */
    public static function classification(Cloud $cloud): self
    {
        return new self(sprintf(
            '%s offers no classification, so the request was not sent. Ask one of its text models for structured output instead.',
            $cloud->label(),
        ));
    }

    public static function unknownModel(Cloud $cloud, string $model, Capability $capability): self
    {
        return new self(sprintf(
            'The model [%s] is not in the catalog of %s, and the request needs %s, so it was not sent. Name what the model can do under [sovereign-ai.%s.catalog], or pick a model from the catalog.',
            $model,
            $cloud->label(),
            $capability->label(),
            $cloud->value,
        ));
    }
}
