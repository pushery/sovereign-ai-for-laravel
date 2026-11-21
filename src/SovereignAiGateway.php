<?php

declare(strict_types=1);

namespace Pushery\SovereignAi;

use Laravel\Ai\Gateway\OpenAiCompatible\OpenAiCompatibleGateway;
use Pushery\SovereignAi\Concerns\HoldsRequests;

/**
 * `laravel/ai`'s OpenAI-compatible gateway for text, streamed text, embeddings and transcription,
 * with the checks of HoldsRequests around every request.
 */
final class SovereignAiGateway extends OpenAiCompatibleGateway
{
    use HoldsRequests;
}
