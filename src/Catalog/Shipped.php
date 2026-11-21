<?php

declare(strict_types=1);

namespace Pushery\SovereignAi\Catalog;

use Pushery\SovereignAi\Cloud;

/**
 * The models the package knows, as the clouds' own documentation described them on the day in
 * RETRIEVED. A capability is listed only where that documentation states it for the model.
 *
 * IONOS states tool calls on each model page and structured output for no model in general ("Which
 * formats a model supports is model-specific"); its only example of a JSON schema is the one on the
 * page of Llama 3.3 70B. OVHcloud states both for every model in its catalog API, in
 * `response_format` and `function_calling`, and the list here holds the models that API marked as
 * available. IONOS removes `sentence-transformers/paraphrase-multilingual-mpnet-base-v2` on
 * 2026-11-30, with `BAAI/bge-m3` as its successor, so it is left out.
 */
final class Shipped
{
    /**
     * The day the documentation behind this list was read.
     */
    public const string RETRIEVED = '2026-11-11';

    private const string IONOS = 'https://docs.ionos.com/cloud/ai/ai-model-hub/models/';

    private const string OVHCLOUD = 'https://catalog.endpoints.ai.ovh.net/rest/v1/models_v2';

    /**
     * @return list<Model>
     */
    public static function models(Cloud $cloud): array
    {
        return match ($cloud) {
            Cloud::Ionos => self::ionos(),
            Cloud::OvhCloud => self::ovhCloud(),
        };
    }

    /**
     * @return list<Model>
     */
    private static function ionos(): array
    {
        $tools = [Capability::Tools];

        return [
            new Model('meta-llama/Llama-3.3-70B-Instruct', [Capability::StructuredOutput, Capability::Tools], self::IONOS.'llms/meta-llama-3-3-70b'),
            new Model('mistralai/Mistral-Small-24B-Instruct', $tools, self::IONOS.'llms/mistral-small-24b'),
            new Model('openai/gpt-oss-120b', $tools, self::IONOS.'llms/openai-gpt-oss-120b'),
            new Model('Qwen/Qwen3.5-9B', $tools, self::IONOS.'llms/qwen3-5-9b'),
            new Model('Qwen/Qwen3.5-397B-A17B', $tools, self::IONOS.'llms/qwen3-5-397b'),
            new Model('Qwen/Qwen3.8-27B', $tools, self::IONOS.'llms/qwen3-8-27b'),
            new Model('Qwen/Qwen3-Coder-Next', $tools, self::IONOS.'coding-models/qwen3-coder-next-80b'),
            new Model('lightonai/LightOnOCR-2-1B', [], self::IONOS.'ocr-models/lightonocr-2-1b'),
            new Model('BAAI/bge-m3', [Capability::Embeddings], self::IONOS.'embedding-models/bge-m3'),
            new Model('Qwen/Qwen3-VL-Embedding-8B', [Capability::Embeddings], self::IONOS.'embedding-models/qwen3-vl-embedding-8b'),
        ];
    }

    /**
     * @return list<Model>
     */
    private static function ovhCloud(): array
    {
        $both = [Capability::StructuredOutput, Capability::Tools];

        return [
            new Model('gpt-oss-120b', $both, self::OVHCLOUD),
            new Model('gpt-oss-20b', $both, self::OVHCLOUD),
            new Model('Mistral-Small-3.2-24B-Instruct-2506', $both, self::OVHCLOUD),
            new Model('Qwen3.5-397B-A17B', $both, self::OVHCLOUD),
            new Model('Qwen3.5-9B', $both, self::OVHCLOUD),
            new Model('Qwen3.8-27B', $both, self::OVHCLOUD),
            new Model('Qwen3Guard-Gen-0.6B', [], self::OVHCLOUD),
            new Model('Qwen3Guard-Gen-8B', [], self::OVHCLOUD),
            new Model('bge-m3', [Capability::Embeddings], self::OVHCLOUD),
            new Model('Qwen3-Embedding-8B', [Capability::Embeddings], self::OVHCLOUD),
        ];
    }
}
