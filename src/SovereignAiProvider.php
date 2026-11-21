<?php

declare(strict_types=1);

namespace Pushery\SovereignAi;

use Laravel\Ai\Contracts\Gateway\ClassificationGateway;
use Laravel\Ai\Contracts\Gateway\EmbeddingGateway;
use Laravel\Ai\Contracts\Gateway\StepTextGateway;
use Laravel\Ai\Contracts\Gateway\TranscriptionGateway;
use Laravel\Ai\Providers\OpenAiCompatibleProvider;
use Override;
use Pushery\SovereignAi\Exceptions\UnsupportedCapability;

/**
 * The provider behind the `ionos` and `ovhcloud` drivers: `laravel/ai`'s OpenAI-compatible
 * provider, whose requests go through gateways that hold them to the cloud's endpoints and name the
 * provider in every error.
 */
final class SovereignAiProvider extends OpenAiCompatibleProvider
{
    /**
     * The cloud this provider sends to, read from its driver.
     */
    public function cloud(): Cloud
    {
        return Cloud::from($this->driver());
    }

    #[Override]
    public function textGateway(): StepTextGateway
    {
        return $this->textGateway ??= new SovereignAiGateway($this->events);
    }

    #[Override]
    public function embeddingGateway(): EmbeddingGateway
    {
        return $this->embeddingGateway ??= new SovereignAiGateway($this->events);
    }

    #[Override]
    public function transcriptionGateway(): TranscriptionGateway
    {
        return $this->transcriptionGateway ??= new SovereignAiGateway($this->events);
    }

    #[Override]
    public function classificationGateway(): ClassificationGateway
    {
        return $this->classificationGateway ??= new SovereignAiClassificationGateway($this->cloud());
    }

    /**
     * A classification without a model asks for this one before it reaches the gateway, and neither
     * cloud has a model to name for it, so it is refused here, before anything is sent.
     */
    #[Override]
    public function defaultClassificationModel(): string
    {
        throw UnsupportedCapability::classification($this->cloud());
    }
}
