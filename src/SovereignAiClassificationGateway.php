<?php

declare(strict_types=1);

namespace Pushery\SovereignAi;

use Illuminate\Http\UploadedFile;
use Laravel\Ai\Contracts\Gateway\ClassificationGateway;
use Laravel\Ai\Contracts\Providers\ClassificationProvider;
use Laravel\Ai\Contracts\Question;
use Laravel\Ai\Files\File;
use Laravel\Ai\Responses\ClassificationResponse;
use Override;
use Pushery\SovereignAi\Exceptions\UnsupportedCapability;

/**
 * The classification gateway of both clouds. Neither offers a classification, so every one is
 * refused before anything is sent, with a model as without one.
 */
final readonly class SovereignAiClassificationGateway implements ClassificationGateway
{
    public function __construct(private Cloud $cloud) {}

    /**
     * @param  string|array<string, mixed>  $state
     * @param  array<string, Question>  $questions
     * @param  array<string, mixed>  $providerOptions
     * @param  array<int|string, File|UploadedFile>  $attachments
     */
    #[Override]
    public function classify(
        ClassificationProvider $provider,
        string $model,
        string|array $state,
        array $questions,
        int $timeout = 30,
        array $providerOptions = [],
        array $attachments = [],
    ): ClassificationResponse {
        throw UnsupportedCapability::classification($this->cloud);
    }
}
