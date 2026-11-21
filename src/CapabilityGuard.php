<?php

declare(strict_types=1);

namespace Pushery\SovereignAi;

use Illuminate\Contracts\Config\Repository;
use Laravel\Ai\Contracts\HasSkills;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Events\GeneratingEmbeddings;
use Laravel\Ai\Events\PromptingAgent;
use Pushery\SovereignAi\Catalog\Capability;
use Pushery\SovereignAi\Catalog\Catalog;
use Pushery\SovereignAi\Exceptions\UnsupportedCapability;

/**
 * Refuses a request to one of the two clouds whose model lacks what the request needs, before the
 * request is sent.
 *
 * It listens to the events `laravel/ai` dispatches right before it builds a request: an agent
 * prompted or streamed, and embeddings generated. An exception thrown here ends the call there,
 * with nothing sent. A provider of another driver is left alone.
 */
final readonly class CapabilityGuard
{
    public function __construct(private Repository $config) {}

    /**
     * @throws UnsupportedCapability
     */
    public function prompting(PromptingAgent $event): void
    {
        $provider = $event->prompt->provider;

        if (! $provider instanceof SovereignAiProvider) {
            return;
        }

        $catalog = Catalog::for($provider->cloud(), $this->config);
        $agent = $event->prompt->agent;

        if ($agent instanceof HasStructuredOutput) {
            $catalog->assertSupports($event->prompt->model, Capability::StructuredOutput);
        }

        // The tools given for the run replace the ones the agent declares, as they do in
        // `laravel/ai`. An agent with at least one skill declares one more, the tool that loads a
        // skill.
        $runTools = $event->prompt->tools;

        if ($runTools !== null) {
            $sendsTools = $runTools !== [];
        } else {
            $sendsTools = ($agent instanceof HasTools && [...$agent->tools()] !== [])
                || ($agent instanceof HasSkills && [...$agent->skills()] !== []);
        }

        if ($sendsTools) {
            $catalog->assertSupports($event->prompt->model, Capability::Tools);
        }
    }

    /**
     * @throws UnsupportedCapability
     */
    public function embedding(GeneratingEmbeddings $event): void
    {
        if ($event->provider instanceof SovereignAiProvider) {
            Catalog::for($event->provider->cloud(), $this->config)->assertSupports($event->model, Capability::Embeddings);
        }
    }
}
