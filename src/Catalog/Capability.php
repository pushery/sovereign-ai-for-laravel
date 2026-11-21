<?php

declare(strict_types=1);

namespace Pushery\SovereignAi\Catalog;

/**
 * What a request needs from a model, beyond plain text.
 */
enum Capability: string
{
    case StructuredOutput = 'structured_output';
    case Tools = 'tools';
    case Embeddings = 'embeddings';

    /**
     * The words a message uses for the capability.
     */
    public function label(): string
    {
        return match ($this) {
            self::StructuredOutput => 'structured output against a JSON schema',
            self::Tools => 'tool calls',
            self::Embeddings => 'embeddings',
        };
    }
}
